<?php
//zerolith system library
//v1.0 - 05/16/2023 - Added functions for getting system stats on linux
//v1.1 - 01/23/2024 - Merged zfile into zsys

class zsys
{
	private static $locks = [];
	private static $serialIter = 0;
	
	//return a 12 digit 'serial' number based on iterator + second + microsecond time. ( great for generating random IDs that won't collide in JS/HTML. )
	public static function getTimeSerial()
	{
		self::$serialIter++;
		if(self::$serialIter == 1000) { self::$serialIter = 0; }
		return(self::$serialIter . str_replace(".","",substr(microtime(true), -9)) );
	}
	
	//used internally - could use a less strict filter..
	private static function filter($input){ return zstr::shorten(zfilter::page($input)); }
	
	//check for existance of a command.
	public static function commandExists($command)
	{
		$command = self::filter($command);
		$return = shell_exec(sprintf("which %s", escapeshellarg($command)));
        return !empty($return);
	}
	
	//execute a process in the background, ignoring the return from the output
	//can be used for multithreading in some cases.
	public static function executeBG($cmd) { shell_exec($cmd . " > /dev/null 2>/dev/null &"); return true; }
	
	//execute a process, ignoring the return from the output
	public static function execute($cmd, $advanced = false )
	{
		if(!$advanced) { return shell_exec($cmd); }
		else { $return = []; }
	}
	
	
	//locking functionality - currently untested/unused.
	
	
	//start a lock.
	public static function lockStart($lockName = "default")
	{
		$lockData = self::getLockData($lockName);
		
		//write lock file.
		if(!@touch($lockData['filename'])) { zl::fault("Can't create lock file: [" . $lockData['filename'] . "]"); }
		self::$locks[$lockData['name']] = true;
	}
	
	//stop a lock.
    public static function lockStop($lockName = "default") //stop and return timer.
    {
    	$lockData = self::getLockData($lockName);
    	
    	//delete lock
	    if(!@unlink($lockData['filename'])) { zl::fault("Can't delete lock file: [" . $lockData['filename'] . "]"); }
	    self::$locks[$lockData['name']] = false;
    }
    
    //check status of a lock via filesystem.
    public static function isLocked($lockName = "default")
    {
    	$lockData = self::getLockData($lockName);
    	return file_exists($lockData['filename']);
    }
    
    //flush all known locks.
    public static function flushLocks()
    {
    	foreach(self::$locks as $name => $locked) { if($locked) { self::lockStop($name); } }
    	self::$locks = [];
    }
	
	//internal shortcut
	private static function getLockData($lockname)
	{
		$lockData = [];
		$lockData['name'] = self::filter($lockname);
		$lockData['filename'] = zl_frameworkPath . "zl_internal/lockfiles/" . $lockData['name'] . ".zlock";
		return $lockData;
	}
	
	
	//------------------ system statistics for ( mostly ) linux based systems --------------------
	
	
	// Thanks chatGPT!
	// Returns server load in percent (just number, without percent sign)
	public static function getCpuUsedPct()
	{
	    $load = null;
		
		//decipher processor statistics from /proc/stat
		function decipherLinux()
		{
		    if(is_readable("/proc/stat"))
		    {
		        $stats = file_get_contents("/proc/stat");
		
		        if($stats !== false)
		        {
		            // Remove double spaces to make it easier to extract values with explode()
		            $stats = preg_replace("/[[:blank:]]+/", " ", $stats);
		
		            // Separate lines
		            $stats = str_replace(array("\r\n", "\n\r", "\r"), "\n", $stats);
		            $stats = explode("\n", $stats);
		
		            // Separate values and find line for main CPU load
		            foreach($stats as $statLine)
		            {
		                $statLineData = explode(" ", trim($statLine));
		
		                // Found!
		                if(count($statLineData) >= 5 && $statLineData[0] == "cpu")
		                { return [$statLineData[1], $statLineData[2], $statLineData[3], $statLineData[4]]; }
		            }
		        }
		    }
		    return null;
		}
		
	    if(stristr(PHP_OS, "win"))
	    {
	        $cmd = "wmic cpu get loadpercentage /all";
	        @exec($cmd, $output);
	
	        if($output)
	        { foreach ($output as $line) { if ($line && preg_match("/^[0-9]+\$/", $line)) { $load = $line; break; } } }
	    }
	    else
	    {
	        if(is_readable("/proc/stat"))
	        {
	            // Collect 2 samples - each with 1 second period
	            // See: https://de.wikipedia.org/wiki/Load#Der_Load_Average_auf_Unix-Systemen
	            $statData1 = decipherLinux();
	            sleep(1);
	            $statData2 = decipherLinux();
	
	            if(!is_null($statData1) && !is_null($statData2))
	            {
	                // Get difference
	                $statData2[0] -= $statData1[0];
	                $statData2[1] -= $statData1[1];
	                $statData2[2] -= $statData1[2];
	                $statData2[3] -= $statData1[3];
	
	                // Sum up the 4 values for User, Nice, System and Idle and calculate
	                // the percentage of idle time (which is part of the 4 values!)
	                $cpuTime = $statData2[0] + $statData2[1] + $statData2[2] + $statData2[3];
	
	                // Invert percentage to get CPU time, not idle time
	                $load = 100 - ($statData2[3] * 100 / $cpuTime);
	            }
	        }
	    }
	
	    return round($load, 2);
	}
	
	// use 'free' to get memory used
	public static function getMemUsed()
	{
		// Memory usage
	    $output = shell_exec('free');
	    $lines = explode("\n", trim($output));
		$memInfo = preg_split('/\s+/', trim($lines[1])); // Extract memory values from last line of output
	 
		//Return megabyte values for each
	    $stats =
		[
	        'total' => round($memInfo[1] / 1024, 2),
	        'used' => round($memInfo[2] / 1024, 2),
	        'free' => round($memInfo[3] / 1024, 2),
			'buff/cache' => round($memInfo[5] / 1024, 2),
			'avail' => round($memInfo[6] / 1024, 2)
	    ];
		$stats['usedPct'] = abs(round(($stats['avail'] / ($stats['total'])) * 100, 2) - 100);
		$stats['availPct'] = round(($stats['avail'] / ($stats['total'])) * 100, 2);
		return $stats;
	}
	
	//gets disk space available with DF
	public static function getDiskSpace($fileSystemTypeFilter = "-x squashfs -x tmpfs -x devtmpfs")
	{
		ztime::stopWatch("getDiskSpace");
		//call command
		$lines = explode("\n", exec('df -l -h ' . $fileSystemTypeFilter));
		$disks = [];
		
		foreach ($lines as $line)
		{
			$cols = preg_split('/\s+/', $line); //split linux output lines by any amount of whitespace
		 
			//cols[0] is the filesystem name.
			$disks[$cols[0]] =
			[
				'fs' => $cols[0],
		        'size' => $cols[1],
		        'used' => $cols[2],
		        'avail' => $cols[3],
				'usedPct' => rtrim($cols[4], "%")
		    ];
		}
		return $disks;
		ztime::stopWatch("getDiskSpace");
	}
	
	//return full path information on a file; rearranged and blanked.
	public static function getPathData($filename)
	{
		$pathData = [];
		$t = pathinfo($filename);
		if(isset($t['dirname'])) { $pathData['folder'] = $t['dirname']; } else { $pathData['folder'] = ""; }
		if(isset($t['basename'])) { $pathData['filename'] = $t['basename']; } else { $pathData['filename'] = ""; }
		if(isset($t['filename'])) { $pathData['filenameNoExt'] = $t['filename']; } else { $pathData['filenameNoExt'] = ""; }
		if(isset($t['extension'])) { $pathData['extension'] = $t['extension']; } else { $pathData['extension'] = ""; }
		
		$pathData['fullPath'] = $pathData['folder'] . "/" . $pathData['filename'];
		return $pathData;
	}
	
	//--------------------------------- File system -------------------------------
	
	//upload an image, resize it with gd, and return the binary data as a string
	public static function uploadImageToString($fileData)
	{
		$result = array("result" => true, "data" => "", "reason" => "");
		
		if(isset($fileData['name'])) //must be an image upload.
		{
			if(!isset($fileData['name'] ) || strlen( $fileData['name']) > 150 ) { $result['result'] = false; $result['reason'] .= "Unspecified issue with the filename."; }
			if( $fileData['errors'] != 0 ) { $result['result'] = false; $result['reason'] .= "Try a different format or web browser."; }
			
			//bail early.
			if(!$result['result']) { return $result;}
			
			$extension = pathinfo($fileData['name'], PATHINFO_EXTENSION); //get filename extension
			if($extension == "") { $result['result'] = false; $result['reason'] .= "File did not have an extension."; }
			$e = strtolower($extension);
			
			if($fileData['size'] > 10000000) { $result['result'] = false; $result['reason'] .= "Uploaded file is over 10mb in size.";}
			elseif($e == "jpg" || $e == "jpeg" || $e == "png" || $e == "gif" || $e == "wbmp" || $e == "webp" || $e == "xbm" || $e == "tiff" )
			{
				$srcImg = imagecreatefromstring(file_get_contents($fileData['tmp_name']));
				$origWidth = imagesx($srcImg); $origHeight = imagesy($srcImg);
				$outputWidth = 1200;
				
				if($origWidth < $outputWidth )	//don't resize it
				{
					$outputImg = imagecreatetruecolor($origWidth, $origHeight);
					imagecopy($outputImg, $srcImg, 0, 0, 0, 0, $origWidth, $origHeight);
				}
				else	//resize it
				{
					$ratio = $origWidth / $outputWidth;	//shrink on ratio to 800 pixels wide
					$outputHeight = intval($origHeight / $ratio);
					$outputImg = imagecreatetruecolor($outputWidth, $outputHeight);
					imagecopyresampled($outputImg, $srcImg, 0, 0, 0, 0, $outputWidth, $outputHeight, $origWidth, $origHeight);
				}
				ob_start();
				if(!imagejpeg($outputImg, NULL, 80))
				{ $result['result'] = "false"; $result['reason'] .= "Can't upload image to our server. Please contact us!"; }
				$result['data'] = ob_get_clean();
				imagedestroy($outputImg); imagedestroy($srcImg);	//now, lay waste to these poor temp images!
			}
			else {$result['result'] = false; $result['reason'] .= "Uploaded file is not a recognizable image or PDF."; }
			
			//form final image string.
			if($result['result'])
			{
				$result['data'] = $fileData['name']."|".$fileData['type']."|".base64_encode($result['data']);
				$result['reason'] .= "The file uploaded successfully.";
			}
		}
		else {$result['reason'] .= "Didn't look like a valid file upload."; }
		
		if($result['result']) {$result['reason'] = zui::notifyR("ok", $result['reason']); }
		else {$result['reason'] = zui::notifyR("error", $result['reason']); }
		
		return $result;
	}
	
	//return an extension based on MIME detection
	public static function mimeToExtension($mime)
	{
        $mime_map = [
            'video/3gpp2'                                                               => '3g2',
            'video/3gp'                                                                 => '3gp',
            'video/3gpp'                                                                => '3gp',
            'application/x-compressed'                                                  => '7zip',
            'audio/x-acc'                                                               => 'aac',
            'audio/ac3'                                                                 => 'ac3',
            'application/postscript'                                                    => 'ai',
            'audio/x-aiff'                                                              => 'aif',
            'audio/aiff'                                                                => 'aif',
            'audio/x-au'                                                                => 'au',
            'video/x-msvideo'                                                           => 'avi',
            'video/msvideo'                                                             => 'avi',
            'video/avi'                                                                 => 'avi',
            'application/x-troff-msvideo'                                               => 'avi',
            'application/macbinary'                                                     => 'bin',
            'application/mac-binary'                                                    => 'bin',
            'application/x-binary'                                                      => 'bin',
            'application/x-macbinary'                                                   => 'bin',
            'image/bmp'                                                                 => 'bmp',
            'image/x-bmp'                                                               => 'bmp',
            'image/x-bitmap'                                                            => 'bmp',
            'image/x-xbitmap'                                                           => 'bmp',
            'image/x-win-bitmap'                                                        => 'bmp',
            'image/x-windows-bmp'                                                       => 'bmp',
            'image/ms-bmp'                                                              => 'bmp',
            'image/x-ms-bmp'                                                            => 'bmp',
            'application/bmp'                                                           => 'bmp',
            'application/x-bmp'                                                         => 'bmp',
            'application/x-win-bitmap'                                                  => 'bmp',
            'application/cdr'                                                           => 'cdr',
            'application/coreldraw'                                                     => 'cdr',
            'application/x-cdr'                                                         => 'cdr',
            'application/x-coreldraw'                                                   => 'cdr',
            'image/cdr'                                                                 => 'cdr',
            'image/x-cdr'                                                               => 'cdr',
            'zz-application/zz-winassoc-cdr'                                            => 'cdr',
            'application/mac-compactpro'                                                => 'cpt',
            'application/pkix-crl'                                                      => 'crl',
            'application/pkcs-crl'                                                      => 'crl',
            'application/x-x509-ca-cert'                                                => 'crt',
            'application/pkix-cert'                                                     => 'crt',
            'text/css'                                                                  => 'css',
            'text/x-comma-separated-values'                                             => 'csv',
            'text/comma-separated-values'                                               => 'csv',
            'application/vnd.msexcel'                                                   => 'csv',
            'application/x-director'                                                    => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/x-dvi'                                                         => 'dvi',
            'message/rfc822'                                                            => 'eml',
            'application/x-msdownload'                                                  => 'exe',
            'video/x-f4v'                                                               => 'f4v',
            'audio/x-flac'                                                              => 'flac',
            'video/x-flv'                                                               => 'flv',
            'image/gif'                                                                 => 'gif',
            'application/gpg-keys'                                                      => 'gpg',
            'application/x-gtar'                                                        => 'gtar',
            'application/x-gzip'                                                        => 'gzip',
            'application/mac-binhex40'                                                  => 'hqx',
            'application/mac-binhex'                                                    => 'hqx',
            'application/x-binhex40'                                                    => 'hqx',
            'application/x-mac-binhex40'                                                => 'hqx',
            'text/html'                                                                 => 'html',
            'image/x-icon'                                                              => 'ico',
            'image/x-ico'                                                               => 'ico',
            'image/vnd.microsoft.icon'                                                  => 'ico',
            'text/calendar'                                                             => 'ics',
            'application/java-archive'                                                  => 'jar',
            'application/x-java-application'                                            => 'jar',
            'application/x-jar'                                                         => 'jar',
            'image/jp2'                                                                 => 'jp2',
            'video/mj2'                                                                 => 'jp2',
            'image/jpx'                                                                 => 'jp2',
            'image/jpm'                                                                 => 'jp2',
            'image/jpeg'                                                                => 'jpg',
            'image/pjpeg'                                                               => 'jpg',
            'application/x-javascript'                                                  => 'js',
            'application/json'                                                          => 'json',
            'text/json'                                                                 => 'json',
            'application/vnd.google-earth.kml+xml'                                      => 'kml',
            'application/vnd.google-earth.kmz'                                          => 'kmz',
            'text/x-log'                                                                => 'log',
            'audio/x-m4a'                                                               => 'm4a',
            'application/vnd.mpegurl'                                                   => 'm4u',
            'audio/midi'                                                                => 'mid',
            'application/vnd.mif'                                                       => 'mif',
            'video/quicktime'                                                           => 'mov',
            'video/x-sgi-movie'                                                         => 'movie',
            'audio/mpeg'                                                                => 'mp3',
            'audio/mpg'                                                                 => 'mp3',
            'audio/mpeg3'                                                               => 'mp3',
            'audio/mp3'                                                                 => 'mp3',
            'video/mp4'                                                                 => 'mp4',
            'video/mpeg'                                                                => 'mpeg',
            'application/oda'                                                           => 'oda',
            'audio/ogg'                                                                 => 'ogg',
            'video/ogg'                                                                 => 'ogg',
            'application/ogg'                                                           => 'ogg',
            'application/x-pkcs10'                                                      => 'p10',
            'application/pkcs10'                                                        => 'p10',
            'application/x-pkcs12'                                                      => 'p12',
            'application/x-pkcs7-signature'                                             => 'p7a',
            'application/pkcs7-mime'                                                    => 'p7c',
            'application/x-pkcs7-mime'                                                  => 'p7c',
            'application/x-pkcs7-certreqresp'                                           => 'p7r',
            'application/pkcs7-signature'                                               => 'p7s',
            'application/pdf'                                                           => 'pdf',
            'application/octet-stream'                                                  => 'pdf',
            'application/x-x509-user-cert'                                              => 'pem',
            'application/x-pem-file'                                                    => 'pem',
            'application/pgp'                                                           => 'pgp',
            'application/x-httpd-php'                                                   => 'php',
            'application/php'                                                           => 'php',
            'application/x-php'                                                         => 'php',
            'text/php'                                                                  => 'php',
            'text/x-php'                                                                => 'php',
            'application/x-httpd-php-source'                                            => 'php',
            'image/png'                                                                 => 'png',
            'image/x-png'                                                               => 'png',
            'application/powerpoint'                                                    => 'ppt',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.ms-office'                                                 => 'ppt',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop'                                                   => 'psd',
            'image/vnd.adobe.photoshop'                                                 => 'psd',
            'audio/x-realaudio'                                                         => 'ra',
            'audio/x-pn-realaudio'                                                      => 'ram',
            'application/x-rar'                                                         => 'rar',
            'application/rar'                                                           => 'rar',
            'application/x-rar-compressed'                                              => 'rar',
            'audio/x-pn-realaudio-plugin'                                               => 'rpm',
            'application/x-pkcs7'                                                       => 'rsa',
            'text/rtf'                                                                  => 'rtf',
            'text/richtext'                                                             => 'rtx',
            'video/vnd.rn-realvideo'                                                    => 'rv',
            'application/x-stuffit'                                                     => 'sit',
            'application/smil'                                                          => 'smil',
            'text/srt'                                                                  => 'srt',
            'image/svg+xml'                                                             => 'svg',
            'application/x-shockwave-flash'                                             => 'swf',
            'application/x-tar'                                                         => 'tar',
            'application/x-gzip-compressed'                                             => 'tgz',
            'image/tiff'                                                                => 'tiff',
            'text/plain'                                                                => 'txt',
            'text/x-vcard'                                                              => 'vcf',
            'application/videolan'                                                      => 'vlc',
            'text/vtt'                                                                  => 'vtt',
            'audio/x-wav'                                                               => 'wav',
            'audio/wave'                                                                => 'wav',
            'audio/wav'                                                                 => 'wav',
            'application/wbxml'                                                         => 'wbxml',
            'video/webm'                                                                => 'webm',
            'audio/x-ms-wma'                                                            => 'wma',
            'application/wmlc'                                                          => 'wmlc',
            'video/x-ms-wmv'                                                            => 'wmv',
            'video/x-ms-asf'                                                            => 'wmv',
            'application/xhtml+xml'                                                     => 'xhtml',
            'application/excel'                                                         => 'xl',
            'application/msexcel'                                                       => 'xls',
            'application/x-msexcel'                                                     => 'xls',
            'application/x-ms-excel'                                                    => 'xls',
            'application/x-excel'                                                       => 'xls',
            'application/x-dos_ms_excel'                                                => 'xls',
            'application/xls'                                                           => 'xls',
            'application/x-xls'                                                         => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-excel'                                                  => 'xlsx',
            'application/xml'                                                           => 'xml',
            'text/xml'                                                                  => 'xml',
            'text/xsl'                                                                  => 'xsl',
            'application/xspf+xml'                                                      => 'xspf',
            'application/x-compress'                                                    => 'z',
            'application/x-zip'                                                         => 'zip',
            'application/zip'                                                           => 'zip',
            'application/x-zip-compressed'                                              => 'zip',
            'application/s-compressed'                                                  => 'zip',
            'multipart/x-zip'                                                           => 'zip',
            'text/x-scriptzsh'                                                          => 'zsh',
        ];
		
        if(isset($mime_map[$mime]) === true) { return $mime_map[$mime]; } else { return ""; }
    }
}