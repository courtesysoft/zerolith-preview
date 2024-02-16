<?php
//classes\imageTender. Developed for Endless Sphere by Courtesy Software.
//Version 0.9 - 10/2021 - Finished and working
//Version 1.0 Todos: periodically scan folder for new files, file changes, and deleted files.
//Version 1.1 Todos: allow safe multithreading by using multiple lock files. Add ability to specify number of concurrent processes used.
//Version 1.2 Todos: Implement renaming and brute force transcoding using imageConnect ( per platform library )
//Version 1.3 Todos: Make SQLite database the default option but can be switched to MySQL also

//exiftool -r -ext * "-Filename<%f.$FileTypeExtension" <DIR> (add extensions to files that don't have filetypes)
//exiftool -if '$warning or error' -directory=SOME_OTHER_DIRECTORY -r DIR (move all corrupt files to a certain folder.)

class imageTender
{
	var $set = [];
	var $imageMimeTypes = ['image/jpeg', 'image/png', 'image/gif']; //mime types the image processor can handle.
	var $filesProcessed = 0;
	var $filesFailed = 0;
	var $filesShrunk = 0;
	var $invalidFiles = 0;
	var $corruptJPG = 0;
	var $corruptGIF = 0;
	var $corruptPNG = 0;
	var $percentageReduction = [];
	var $tempFiles = [];
	var $logFile;
	
	public function __construct()
	{
		//let's set some defaults. User can change these after instantiating the class.
		
		//sys settings.
		$this->set['debug'] = false;                                    //should i be extra talkative?
		$this->set['onlyShowErrors'] = true;                            //should i only talk about failures?
		$this->set['debugOutputMode'] = "html";                         //"html" for browser or "text" for console output.
		
		//process settings
		$this->set['fullPassMode'] = false;                             //Single pass ( no database tracking / throttling )
		$this->set['itemsToProcess'] = 10000;                           //how many files do you want to process in one run of processBatch()
		$this->set['fixExtensions'] = false;                            //fix/add extensions for files?
		$this->set['copyCorruptTo'] = "/var/www/corruptfiles";          //copy corrupt files? leave blank if unwanted.
		$this->set['renameCorruptFiles'] = false;                       //rename broken extensions when copying corrupt files.
		$this->set['logToFile'] = "imageTender.log";            //log output to file? Leave blank unwanted..
		
		//image settings.
		$this->set['processImages'] = true;                             //process images at all?
		$this->set['shrinkImages'] = true;                              //resize images during processing?
		$this->set['minimumShrinkPercent'] = 7;                         //don't keep the new file unless it's this % smaller.
		$this->set['jpegQuality'] = 83;                                 //as a percentage. higher is less compressed.
		$this->set['gifLossiness'] = 25;                                //as a percentage. higher is more compressed.
		$this->set['gifLossyThreshold'] = 50000;                        //GIF files over x bytes will be subject to lossy compression.
		$this->set['maxWidth'] = 2048;                                  //Maximum width for Png/Jpeg.
		$this->set['maxHeight'] = 1500;                                 //Maximum height for Png/Jpeg.
		$this->set['maxDimensionSlack'] = 1.1;                          //Slack for the decision to resize or not. Ratio of 1.0 is 0 slack.
		
		//generate other values.
		$this->set['maxSlackWidth'] = intval($this->set['maxWidth'] * $this->set['maxDimensionSlack']);
		$this->set['maxSlackHeight'] = intval($this->set['maxHeight'] * $this->set['maxDimensionSlack']);
		
		if($this->set['logToFile'] != "")
		{
			//create the file if it doesn't exist & open the file.
			if(!file_exists($this->set['logToFile']))
			{
				$this->fileCreate($this->set['logToFile']);
			}
			if(!$this->logFile = fopen($this->set['logToFile'], "a+"))
			{
				$this->fault("Can't open log file: " . $this->logFile);
			}
		}
	}
	
	//produce a template for a 'result object'
	private function newResult() { return ['errorMessage' => "", 'processed' => true, 'corruptAtProcessor' => false]; }
	
	//handle a batch of images. Main control loop.
	function processBatch($pathInput = "", $pathRecursive = false)
	{
		ztime::stopWatch("processBatch*");
		
		if(zsys::isLocked("imageTender"))
		{
			$this->fault("lock enabled; cannot proceed. Another process running?", true);
		}
		else
		{
			zsys::lockStart("imageTender");
		}
		
		//temporary override of max execution time.
		$maxExecutionTime = ini_get('max_execution_time');
		set_time_limit(999999);
		
		//mutate global settings for the batch ( yes, i know this is bad form.. )
		if($pathInput == "")
		{
			$this->fault("Startup: filePath is blank.");
		}
		else
		{
			$this->set['filePath'] = $pathInput;
		}
		if(!is_bool($pathRecursive))
		{
			$this->fault("Startup: recursive setting not a boolean");
		}
		else
		{
			$this->set['filePathRecursive'] = $pathRecursive;
		}
		
		//check dependencies
		if(!extension_loaded('gd'))
		{
			$this->fault("Startup: GD is not installed in PHP.");
		}
		if($this->set['processImages']) //need to check commandline tools.
		{
			$tools = ["pngquant", "gifsicle", "cjpeg", "exiftool"];
			foreach($tools as $tool)
			{
				if(!zsys::commandExists($tool))
				{
					$this->fault("Startup: $tool not installed.");
				}
			}
		}
		
		//announce operation and begin performance counters.
		if($this->set['fullPassMode'])
		{
			$this->talkStatus("---imageTender started (single pass mode) @ " . ztime::now(), true);
		}
		else
		{
			$this->talkStatus("---imageTender started (database mode) @ " . ztime::now(), true);
		}
		
		//reset batch counters.
		$this->filesProcessed = 0;
		$this->filesFailed = 0;
		$this->filesShrunk = 0;
		$fileArray = $this->fileGetList();
		$this->fileCount = count($fileArray);
		
		if(count($fileArray) == 0)
		{
			zui::quip("No images to process. I want to attempt sync in this condition");
		}
		
		//operate.
		foreach($fileArray as $file)
		{
			if(!$this->processImage($file))
			{
				$this->filesFailed++;
			}
			else
			{
				$this->filesProcessed++;
			}
		}
		
		zsys::lockStop("imageTender"); //remove lock immediately.
		
		//figure out percentage of reduction of this batch.
		if(count($this->percentageReduction) > 0)
		{
			$reductionAgregate = 0;
			foreach($this->percentageReduction as $reduction)
			{
				$reductionAgregate += $reduction;
			}
			$totalReduction = $reductionAgregate / count($this->percentageReduction);
		}
		else
		{
			$totalReduction = 0;
		} //not a successful process..
		
		//produce process report.
		$pReport = "Finished in " . ztime::stopWatch("processBatch*") . "\n";
		$pReport .= "Processed: " . $this->filesProcessed . "/" . $this->fileCount . "\n";
		if($this->filesFailed > 0)
		{
			$pReport .= "Failed: " . $this->filesFailed . "\n";
		}
		$pReport .= "Shrunk: " . $this->filesShrunk . " (" . znum::shortFloat($totalReduction) . "% reduction)\n\n";
		
		$pReport .= "Non-image files: " . $this->invalidFiles . "\n";
		$pReport .= "Corrupt JPG: " . $this->corruptJPG . "\n";
		$pReport .= "Corrupt GIF: " . $this->corruptGIF . "\n";
		$pReport .= "Corrupt PNG: " . $this->corruptPNG . "\n";
		$this->talk($pReport, true);
		
		set_time_limit($maxExecutionTime); //resume regular execution time.
	}
	
	//handle a single image.
	public function processImage($filename)
	{
		$result = $this->newResult();
		$result['processed'] = true; //almost always true.
		$this->msgBuffer = "";
		$mimetype = mime_content_type($filename);
		$this->msgBuffer .= $filename . " (" . $mimetype . ") (" . znum::bytesToUnits(filesize($filename)) . ")";
		
		//process an image.
		if(in_array($mimetype, $this->imageMimeTypes))
		{
			//get image data object
			$imageData = $this->imageGetData($filename);
			
			$this->tempFiles[$imageData['pathTemp']] = $imageData['pathTemp'];
			$this->tempFiles[$imageData['pathTemp2']] = $imageData['pathTemp2'];
			
			//finish message buffer line about this file.
			if($imageData['state'] == "cantOpen")
			{
				$this->msgBuffer .= " - can't open file";
			}
			else if($imageData['state'] == "corrupt")
			{
				$this->msgBuffer .= " - corrupt\n";
			}
			else
			{
				$this->msgBuffer .= " (" . $imageData['width'] . "x" . $imageData['height'] . ")\n";
			}
			
			//formulate the reason for the failure.
			if($imageData['state'] == "corrupt")
			{
				$result['errorMessage'] = "Image was corrupt.";
			}
			else if($imageData['state'] == "invalid")
			{
				$result['errorMessage'] = "Image is invalid. Not processed.";
			}
			else if($imageData['state'] == "cantOpen")
			{
				$result['processed'] = false;
				$result['errorMessage'] = "Cannot open this file.";
			}
			else
			{
				if($this->set['processImages'])
				{
					if($mimetype == "image/jpeg")
					{
						$result = $this->recodeJPG($imageData);
					}
					else if($mimetype == "image/gif")
					{
						$result = $this->recodeGIF($imageData);
					}
					else if($mimetype == "image/png")
					{
						$result = $this->recodePNG($imageData);
					}
					
					//mark an overall corrupt status because the command line processor couldn't handle the times.
					if($result['corruptAtProcessor'])
					{
						$imageData['state'] = "corruptAtProcessor";
						$result['errorMessage'] = "The image processor crashed/failed.";
					}
					else
					{
						//if result is fail, don't delete the original so that the user can try the commandline on the last file.
						if($result['processed'] && $this->fileIsSmaller($imageData['path'], $imageData['pathTemp']))
						{
							if(file_exists($imageData['path'])) //replace the image with smaller file.
							{
								$this->fileDelete($imageData['path']);
								$this->fileRename($imageData['pathTemp'], $imageData['path']);
							}
						}
						else
						{
							$this->fileDelete($imageData['pathTemp']);
						}
						
						//nuke the second temporary file.
						if(file_exists($imageData['pathTemp2']))
						{
							$this->fileDelete($imageData['pathTemp2']);
						}
					}
				}
				else
				{
					$result['errorMessage'] = "The image wasnt processed (settings)";
				}
			}
		}
		else
		{
			//not in the list of types we can process.
			$imageData = ["state" => "invalid"];
			$this->msgBuffer .= "\n";
			$result['errorMessage'] = "Cannot process this file type.";
		}
		
		if($imageData['state'] == "invalid")
		{
			$this->invalidFiles++;
		}
		
		//copy file to corrupt folder if needed.
		if(zs::contains($imageData['state'], "corrupt"))
		{
			if($mimetype == "image/jpeg")
			{
				$this->corruptJPG++;
			}
			else if($mimetype == "image/gif")
			{
				$this->corruptGIF++;
			}
			else if($mimetype == "image/png")
			{
				$this->corruptPNG++;
			}
			
			//copy corrupt files to specified folder if requested.
			if($this->set['copyCorruptTo'])
			{
				//copy to the corrupt files folder.
				$corruptPath = $this->set['copyCorruptTo'] . "/" . $imageData['pathShort'];
				$this->fileCopy($imageData['path'], $corruptPath);
				
				//rename the files in the corrupt folder.
				if($this->set['renameCorruptFiles'])
				{
					$corruptPath = $this->fileRenameFixExtension($corruptPath);
				}
			}
			else
			{
				$this->msgBuffer .= $imageData['path'] . " is corrupt.\n";
			}
		}
		
		//rename the image if requested ( regardless of what happened )
		if($this->set['fixExtensions'])
		{
			$newPath = $this->fileRenameFixExtension($filename);
			
			if($newPath != $filename)
			{
				$this->msgBuffer .= "The file was renamed to " . $newPath . "\n";
				if(!$this->set['fullPassMode']) //notify the database of the change.
				{
					if(!zdb::writeSQL("UPDATE zl_imageTender SET filepath = '" . $newPath . "', extension='" . files::mimeToExtension($mimetype) . "' WHERE filepath ='" . $filename . "'"))
					{
						$this->fault("Could not update database with new filename.");
					}
				}
			}
		}
		else
		{
			$newPath = $filename;
		}
		
		//write result to database.
		if(!$this->set['fullPassMode'])
		{
			$R = $result['processed'] ? "Y" : "N";
			if(!zdb::writeSQL("UPDATE zl_imageTender SET lastError = '" . $result['errorMessage'] . "', processed = '" . $R . "', parseResult = '" . $imageData['state'] . "', extension = '" . files::mimeToExtension($mimetype) . "' WHERE filepath ='" . $newPath . "'"))
			{
				$this->fault("could not update database with new filename.");
			}
		}
		
		//display result
		if($result['errorMessage'] != "")
		{
			$this->msgBuffer .= $result['errorMessage'] . "\n";
		}
		
		if($this->set['onlyShowErrors'])
		{
			if(zs::contains($imageData['state'], "valid"))
			{
				$this->talkStatus($this->msgBuffer, true);
			}
		}
		else
		{
			$this->talkStatus($this->msgBuffer, true);
		}
		
		$this->msgBuffer = ""; //clear message buffer.
		
		return $result['processed'];
	}
	
	//rename ( altering extension ) and return the new filename.
	private function fileRenameFixExtension($filename, $mimetype = "")
	{
		$newPath = $filename;
		if($mimetype == "")
		{
			$mimetype = mime_content_type($filename);
		}
		$realExtension = files::mimeToExtension($mimetype);
		$pathData = $this->fileGetPathData($filename);
		
		if($pathData['extension'] == "" && $realExtension != "")
		{
			$newPath .= "." . $realExtension;
		} //no extension..?
		else //mislabeled filename?
		{
			$pathData['extension'] = strtolower(str_replace("jpeg", "jpg", $pathData['extension']));
			if($pathData['extension'] != $realExtension)
			{
				$newPath = $pathData['folder'] . "/" . $pathData['filenameNoExt'] . "." . $realExtension;
			}
			$this->talk("Renaming " . $pathData['filename'] . " to " . $pathData['filenameNoExt'] . "." . $realExtension);
		}
		
		$this->talk("Renaming: [" . $filename . "] to [" . $newPath . "]");
		
		//do the rename.
		if(!rename($filename, $newPath))
		{
			$this->talk("Could not rename file.", true);
			return $filename;
		} //return original path.
		else
		{
			return $newPath;
		} //return new path on success.
	}
	
	//answers the ever important question.. ( if the file is smaller, we'll keep the processed file )
	public function fileIsSmaller($oldFile, $newFile, $silent = false)
	{
		$newFileSize = @filesize($newFile);
		$oldFileSize = @filesize($oldFile);
		if($oldFileSize == 0)
		{
			$this->msgBuffer .= "The old file was 0 bytes!";
			return false;
		}
		if($newFileSize == 0)
		{
			$this->msgBuffer .= "The new file was 0 bytes!";
			return false;
		}
		
		$sizeDifference = intval((($newFileSize / $oldFileSize) * 100));
		$this->percentageReduction[] = $sizeDifference;
		
		if($sizeDifference < (100 - $this->set['minimumShrinkPercent']))
		{
			if(!$silent)
			{
				$this->msgBuffer .= "✓ shrunk from " . znum::bytesToUnits($oldFileSize) . " to " . znum::bytesToUnits($newFileSize) . " (" . $sizeDifference . "%)\n";
				$this->filesShrunk++;
			}
			return true;
		}
		else
		{
			if(!$silent)
			{
				$this->msgBuffer .= "old file kept; new file was: " . znum::bytesToUnits($newFileSize) . " (" . $sizeDifference . "%)\n";
			}
			return false;
		}
	}
	
	//return full path information on a file; rearranged and blanked.
	public function fileGetPathData($filename)
	{
		$pathData = [];
		$t = pathinfo($filename);
		if(isset($t['dirname']))
		{
			$pathData['folder'] = $t['dirname'];
		}
		else
		{
			$pathData['folder'] = "";
		}
		if(isset($t['basename']))
		{
			$pathData['filename'] = $t['basename'];
		}
		else
		{
			$pathData['filename'] = "";
		}
		if(isset($t['filename']))
		{
			$pathData['filenameNoExt'] = $t['filename'];
		}
		else
		{
			$pathData['filenameNoExt'] = "";
		}
		if(isset($t['extension']))
		{
			$pathData['extension'] = $t['extension'];
		}
		else
		{
			$pathData['extension'] = "";
		}
		
		$pathData['fullPath'] = $pathData['folder'] . "/" . $pathData['filename'];
		return $pathData;
	}
	
	//if transcoding is enabled, perform a variety of checks. ( not implemented yet )
	public function imageProposeFormat($filename)
	{
		$idealFormat = "";
		//convert to gif and check.
		//convert to png and check.
		//convert to jpg and check.
		return $idealFormat;
	}
	
	//returns an object containing the file data.
	private function imageGetData($filename)
	{
		//create pseudo-object
		$imageData = ["state" => "valid", "path" => $filename];
		
		//perform initial validity checks
		if(!@$id = file_get_contents($filename))
		{
			$imageData['state'] = "cantOpen";
		}
		else
		{
			if(!@$imageData['data'] = imagecreatefromstring($id))
			{
				$imageData['state'] = "corrupt";
			}
			else
			{
				if(!@$gdData = getimagesize($filename))
				{
					$imageData['state'] = "corrupt";
				}
				else
				{
					if($gdData[0] == 0 || $gdData == 0)
					{
						$imageData['state'] = "corrupt";
					}
				}
			}
		}
		
		//establish path data if needed.
		if($imageData['state'] == "valid" || $this->set['copyCorruptTo'])
		{
			$pathData = $this->fileGetPathData($filename);
			$imageData['pathShort'] = $pathData['filename'];//just the filename
		}
		
		//establish output filenames.
		if($imageData['state'] == "valid")
		{
			if($pathData['extension'] == "") //invent it
			{
				$imageData['extension'] = str_replace("image/", "", $gdData['mime']);
				if(strtolower($imageData['extension']) == "jpeg")
				{
					$imageData['extension'] = "jpg";
				}
			}
			else
			{
				$imageData['extension'] = $pathData['extension'];
			} //straight passthrough.
			
			$imageData['pathTemp'] = $this->set['filePath'] . "/__imageTender__temp";
			
			if($gdData['mime'] == "image/png" || $gdData['mime'] == "image/gif")
			{
				$imageData['pathTemp2'] = $imageData['pathTemp'] . "2." . $imageData['extension'];
			}
			else if($gdData['mime'] == "image/jpeg")
			{
				$imageData['pathTemp2'] = $imageData['pathTemp'] . "2.png";
			}
			else
			{
				$imageData['state'] = "invalid";
				return $imageData;
			} //how did an unhandled image get here? but ok.
			
			$imageData['pathTemp'] .= "." . $imageData['extension'];
			
			//establish whether to resize, and if so, to what size.
			$imageData['width'] = $gdData[0];
			$imageData['height'] = $gdData[1];
			if($this->set['shrinkImages'] && $imageData['width'] > $this->set['maxSlackWidth'] || $this->set['shrinkImages'] && $imageData['height'] > $this->set['maxSlackHeight'])
			{
				$imageData['resize'] = true;
				if($imageData['width'] > $imageData['height'])
				{
					$imageData['resizeToHeight'] = floor(($imageData['height'] / $imageData['width']) * $this->set['maxWidth']);
					$imageData['resizeToWidth'] = $this->set['maxWidth'];
				}
				else
				{
					$imageData['resizeToWidth'] = floor(($imageData['width'] / $imageData['height']) * $this->set['maxHeight']);
					$imageData['resizeToHeight'] = $this->set['maxHeight'];
				}
			}
			else
			{
				$imageData['resize'] = false;
			}
		}
		
		return $imageData; //return completed object.
	}
	
	//generic failure for recoding.
	private function recodeFail($message = "", $corruptAtProcessor = false)
	{
		$result = $this->newResult(); //use template.
		$result['errorMessage'] = zstr::shorten($message, 47);
		$result['processed'] = false;
		$result['corruptAtProcessor'] = $corruptAtProcessor;
		return $result;
	}
	
	//does all the necessary recoding of a jpeg and returns a result object.
	private function recodeJPG($imageData)
	{
		$result = $this->newResult();
		
		if($imageData['resize'])
		{
			ztime::stopWatch("GD jpeg");
			$tempImage = imagecreatetruecolor($imageData['resizeToWidth'], $imageData['resizeToHeight']);
			imagecopyresampled($tempImage, $imageData['data'], 0, 0, 0, 0, $imageData['resizeToWidth'], $imageData['resizeToHeight'], $imageData['width'], $imageData['height']);
			
			//write image to disk as png
			imagepng($tempImage, $imageData['pathTemp2']);
			imagedestroy($tempImage);
			imagedestroy($imageData['data']); //remove from memory.
			ztime::stopWatch("GD jpeg");
		}
		else
		{
			//direct copy jpeg for cjpeg input
			$imageData['pathTemp2'] = str_replace(".png", ".jpg", $imageData['pathTemp2']);
			$this->fileCopy($imageData['path'], $imageData['pathTemp2']);
		}
		
		$cmdLine = "cjpeg -quality " . $this->set['jpegQuality'] . " -optimize -outfile " . $imageData['pathTemp'] . " " . $imageData['pathTemp2'];
		
		ztime::stopWatch("cjpeg");
		$cmdResult = shell_exec($cmdLine);
		ztime::stopWatch("cjpeg");
		
		//exiftool is SLOW AF, so it is faster to do checks before running it.
		if($imageData['resize'] && self::fileIsSmaller($imageData['path'], $imageData['pathTemp'], true))
		{
			ztime::stopWatch("exiftool");
			$cmdExif = "exiftool -tagsfromfile " . $imageData['path'] . " -orientation " . $imageData['pathTemp'];
			$cmdExifResult = shell_exec($cmdExif);
			ztime::stopWatch("exiftool");
			
			if(!is_null($cmdResult))
			{
				$this->fault("Exiftool had an error with command:\n" . $cmdLine . "\n" . print_r($cmdResult, true), true);
				return ($this->recodeFail("Exiftool had an error with command:\n" . $cmdLine . "\n" . print_r($cmdResult, true), true));
			}
		}
		
		if(!is_null($cmdResult))
		{
			return ($this->recodeFail("Mozjpeg had an error with command:\n" . $cmdLine . "\n" . print_r($cmdResult, true), true));
		}
		else
		{
			$result['processed'] = true;
		}
		
		if(!file_exists($imageData['pathTemp2']))
		{
			return ($this->recodeFail("Mozjpeg had some other kind of error. File marked as corrupt.", true));
		}
		
		return $result;
	}
	
	//does all the necessary recoding of a jpeg and returns a result object.
	private function recodePNG($imageData)
	{
		$result = $this->newResult();
		
		ztime::stopWatch("GD png");
		if(imageistruecolor($imageData['data'])) //only pre-resize truecolor images.
		{
			if($imageData['resize'])
			{
				$tempImage = imagecreatetruecolor($imageData['resizeToWidth'], $imageData['resizeToHeight']);
				imagecopyresampled($tempImage, $imageData['data'], 0, 0, 0, 0, $imageData['resizeToWidth'], $imageData['resizeToHeight'], $imageData['width'], $imageData['height']);
				
				if(!imagepng($tempImage, $imageData['pathTemp2'])) //write image file to disk
				{
					return ($this->recodeFail("Could not imagePNG the file."));
				}
				imagedestroy($tempImage);
				imagedestroy($imageData['data']); //remove from memory.
			}
			else
			{
				$this->fileCopy($imageData['path'], $imageData['pathTemp2']);
			}
		}
		else
		{
			$this->fileCopy($imageData['path'], $imageData['pathTemp2']);
		}
		ztime::stopWatch("GD png");
		
		$this->fileExists($imageData['pathTemp2']);
		
		$cmdLine = "pngquant --force -s1 --strip -o " . $imageData['pathTemp'] . " " . $imageData['pathTemp2'];
		ztime::stopWatch("pngquant");
		$cmdResult = shell_exec($cmdLine);
		ztime::stopWatch("pngquant");
		
		if(!is_null($cmdResult))
		{
			return ($this->recodeFail("PNGQuant had an error with command:\n" . $cmdLine . "\n" . print_r($cmdResult, true), true));
		}
		else
		{
			$result['processed'] = true;
		}
		
		if(!file_exists($imageData['pathTemp2']))
		{
			return ($this->recodeFail("Pngquant had some other kind of error. File marked corrupt.", true));
		}
		return $result;
	}
	
	//no resizing here. Works on animated GIFs. Needs gifsicle fork installed
	private function recodeGIF($imageData)
	{
		$result = $this->newResult();
		
		if(filesize($imageData['path']) > $this->set['gifLossyThreshold'])
		{
			$lossyString = "--lossy=" . $this->set['gifLossiness'] . " ";
		}
		else
		{
			$lossyString = "";
		}
		
		$this->fileCopy($imageData['path'], $imageData['pathTemp']);
		
		$cmdLine = "gifsicle " . $imageData['pathTemp'] . " " . $lossyString . "--optimize -o " . $imageData['pathTemp2'];
		
		ztime::stopWatch("gifsicle");
		$cmdResult = shell_exec($cmdLine);
		ztime::stopWatch("gifsicle");
		
		if(!is_null($cmdResult))
		{
			return ($this->recodeFail("GIFsicle(lossy fork) had an error with command:\n" . $cmdLine . "\n" . print_r($cmdResult, true), true));
		}
		else
		{
			$result['processed'] = true;
		}
		
		if(!file_exists($imageData['pathTemp2']))
		{
			return ($this->recodeFail("gifsicle had some other kind of error. File marked as corrupt.", true));
		}
		
		return $result;
	}
	
	//return list of files as an array, or as a DB array, if not in fullPass mode.
	private function fileGetList($mode = "")
	{
		ztime::stopWatch("fileGetList");
		
		//php7.2 polyfill from v7.3 --v
		if(!function_exists('array_key_first'))
		{
			function array_key_first(array $arr)
			{
				foreach($arr as $key => $unused)
				{
					return $key;
				}
				return null;
			}
		}
		
		if($this->set['fullPassMode'] || $mode == "files") //file only mode.
		{
			//can i read/write the path?
			if(!file_exists($this->set['filePath']))
			{
				$this->fault("Can't read from: " . $this->set['filePath']);
			}
			if(!is_writeable($this->set['filePath']))
			{
				$this->fault("Can't write to: " . $this->set['filePath']);
			}
			
			if($this->set['copyCorruptTo'] != "" && !is_writeable($this->set['copyCorruptTo']))
			{
				$this->fault("Can't write to: " . $this->set['copyCorruptTo']);
			}
			
			//return list of files.
			if($this->set['filePathRecursive'])
			{
				$files = [];
				$rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->set['filePath']));
				foreach($rii as $file)
				{
					if(!$file->isDir())
					{
						$files[] = $this->set['filePath'] . "/" . $file->getPathname();
					}
				}
				unset($rii); //conserve memory.
			}
			else
			{
				$files = array_diff(scandir($this->set['filePath']), ['..', '.']);
				$k = array_key_first($files);
				$fileCount = count($files) + intval($k);
				for($i = $k; $i < $fileCount; $i++)
				{
					$files[$i] = $this->set['filePath'] . "/" . $files[$i];
				}
			}
			
			if(count($files) < 2)
			{
				$this->fault("Startup: " . $this->set['filePath'] . " Doesn't contain more than 1 file.");
			}
		}
		else //database operation
		{
			zlInstall::ccImageTender(); //check and potentially populate the database.
			$imagesCount = zdb::getCount("zl_imageTender");
			$this->talkStatus("There are " . $imagesCount . " images cataloged.", true);
			
			//populate the database and fault.
			if($imagesCount == 0)
			{
				$this->databasePopulate();
				$this->fault("The database population process completed in: " . ztime::stopWatch("fileGetList") . ".\nThere are " . zdb::getCount('zl_imageTender') . " images recorded in the database.\nPlease re-run the process to initiate the batch conversion.");
			}
			
			$filesTemp = zdb::getArray("SELECT filepath FROM zl_imageTender WHERE lastError = '' AND processed != 'Y' LIMIT " . $this->set['itemsToProcess']);
			
			//turn database array into simpler array
			$files = [];
			foreach($filesTemp as $file)
			{
				$files[] = $file['filepath'];
			}
		}
		ztime::stopWatch("fileGetList");
		
		return $files;
	}
	
	//fills, and optionally creates the database
	private function databasePopulate()
	{
		//reset the log file upon the first population.
		if($this->set['logToFile'] != "")
		{
			if(!file_exists($this->set['logToFile']))
			{
				$this->fileCreate($this->set['logToFile']);
			}
			else
			{
				$this->fileDelete($this->set['logToFile']);
				$this->fileCreate($this->set['logToFile']);
			}
		}
		
		$fileArray = $this->fileGetList("files");
		$this->talk("Populating database.\n" . count($fileArray) . " files were found.", true);
		
		//write the data in chunks of 1000 ( throttle the database hammering! )
		$fileArray = array_chunk($fileArray, 1000);
		foreach($fileArray as $files)
		{
			$sql = "INSERT INTO zl_imageTender (filepath) VALUES "; //warning - SQL injection hole!
			foreach($files as $file)
			{
				$sql .= "('" . $file . "'),";
			}
			$sql = trim($sql, ",") . ";\n";
			
			if(!zdb::writeSQL($sql))
			{
				$this->fault("Could not write to DB table: zl_imageTender", true);
			}
			sleep(1);
		}
	}
	
	//syncs the database with newer files.
	private function databaseAudit()
	{
		//basic for now; CPU intensive to populate the list from scratch
		if(!zdb::writeSQL("TRUNCATE zl_imageTender"))
		{
			$this->fault("Could not truncate database.");
		}
		$this->databasePopulate();
	}
	
	//talk to the user
	private function talk($statement = "", $important = false)
	{
		if($statement == "")
		{
			return;
		}
		
		if($this->set['debug'] || $important)
		{
			if($this->set['debugOutputMode'] == "text")
			{
				echo "\n" . $statement;
			} //text mode
			else
			{
				zui::quip(zstr::rn2br($statement));
			} //html mode
			self::logLine($statement);
		}
	}
	
	//log operations to a file
	private function logLine($statement)
	{
		if($this->set['logToFile'] != "")
		{
			if(fwrite($this->logFile, $statement) === false)
			{
				$this->fault("Can't write to logfile: " . $this->set['logToFile'] . ")");
			}
		}
	}
	
	//talk about the status in plaintext.
	private function talkStatus($statement, $important = false)
	{
		if($statement == "")
		{
			return;
		}
		if($this->set['debug'] || $important)
		{
			echo "<pre>" . $statement . "</pre>";
			self::logLine($statement);
		}
	}
	
	private function fault($reason = "", $becauseLocked = false)
	{
		if(!$becauseLocked)
		{
			zsys::lockStop("imageTender");
		}
		$this->talkStatus($this->msgBuffer, true); //if there were messages..
		
		//attempt to delete temp files
		foreach($this->tempFiles as $tempFile)
		{
			if(file_exists($tempFile))
			{
				@unlink($tempFile);
			}
		}
		
		if($this->set['debugOutputMode'] == "text")
		{
			echo("\nIMAGETENDER ERROR: " . $reason);
		}
		else
		{
			zui::quip($reason, "imageTender error");
		}
		
		zl::terminate("program");
	}
	
	//rename ( altering extension ) and return the new filename.
	private function fileDelete($filename)
	{
		$this->fileExists($filename);
		if(!@unlink($filename))
		{
			$this->fault("Couldn't delete file: [" . $filename . "]");
		}
		else if($this->set['debug'])
		{
			$this->msgBuffer .= "Deleted [" . $filename . "]\n";
		}
	}
	
	private function fileRename($from, $to)
	{
		$this->fileExists($from);
		if(!@rename($from, $to))
		{
			$this->fault("Couldn't rename: [" . $from . "] to [" . $to . "]");
		}
		else if($this->set['debug'])
		{
			$this->msgBuffer .= "Renamed [" . $from . "] to [" . $to . "]\n";
		}
	}
	
	private function fileExists($filename)
	{
		if(!file_exists($filename))
		{
			$this->fault("File doesn't exist: [" . $filename . "]");
		}
	}
	
	private function fileCopy($from, $to)
	{
		$this->fileExists($from);
		if(!@copy($from, $to))
		{
			$this->fault("Couldn't copy: [" . $from . "] to [" . $to . "]");
		}
		else if($this->set['debug'])
		{
			$this->msgBuffer .= "Copied [" . $from . "] to [" . $to . "]\n";
		}
	}
	
	private function fileCreate($filename)
	{
		if(!touch($filename))
		{
			$this->fault("Can't create file: [" . $filename . "]");
		}
	}
}
