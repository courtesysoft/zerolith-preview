<?php
// zerolith [Beta] Arrays Library - (c)2021 Courtesy Software
class zarr
{
	private static $pipeDe = "|";   //pipe delimiter
	private static $apipeDe = "~";  //apipe delimiter
	
	//-------------- Virtual SQL operations -----------------
	
	//Not working, needs finish
	//Example: zarr::leftJoin($customerArray,$customerEmailArray, 'ID', 'p.*, s.response, s.date AS emailDate, s.succeeded', true);
	public static function leftJoin
	(
		$primaryArray,              //The primary array from DB table 1
		$secondaryArray,            //The secondary array to join from DB table 2
		$sharedKey = "ID",          //The name of the database field which joins the two arrays together.
		$sqlAS = "",                //SQL stmt that determines the fields returned in the joined array. Use 'p' (primary) and 's' (secondary) as table names.
		$arraysAreOrdered = false,  //If you did ORDER BY sharedKey in the actual DB query, this setting provides a large optimization.
		$stopHuntAtPCT = 0.5        //Controls how much further to iterate into the secondaryArray after not finding a shared key match, as a percentage of the secondary array's size
	)
	{
		$outArray = []; //the array we will return
		
		//use manual stepping? ( very fast )
		if($arraysAreOrdered)
		{
			if($stopHuntAtPCT < 0.05) { $stopHuntAtPCT = 0.05; } //sane default
			$secondaryStep = 0; //stepping counter
			$primaryCount = count($primaryArray);
			$secondaryCount = count($secondaryArray);
			
			$stopHuntAfterIter = $secondaryCount * $stopHuntAtPCT;
			if($stopHuntAfterIter < 3) { $stopHuntAfterIter = 3; } //sanity check for very small arrays
			
			for($i = 0; $i < $primaryCount; $i++)
			{
				$foundMatch = false;
				$stepMax = $secondaryStep + $stopHuntAfterIter;
				for($k = $secondaryStep; $k < $stepMax; $k++)
				{
					if($k == $secondaryCount){ break; } //this is higher than the array size by 1, so we stop here.
					else
					{
						if($primaryArray[$i][$sharedKey] == $secondaryArray[$k][$sharedKey])
						{
							$secondaryStep = $k; //update the marker
							$foundMatch = $k;
							break;
						}
					}
				}
				
				if($foundMatch !== false) //just add the $primaryArray part
				{
				
				}
				else //perform the join
				{
				
				}
			}
		}
		else //brute force ( slow )
		{
		
		}
		
		
		function returnAs($sqlAs)
		{
		
		}
		
		//interpret SQL AS statement as if it were mysql
		if($sqlAS == "")
		{
		
		}
		
		return $outArray;
	}
	
	//sort an array by a single key's value.
	public static function orderBy(array &$inArray, $key, $sortDirection = "DESC")
	{
		ztime::startTimer("zl_zarr_orderBy");
		$sortDirection = strtoupper($sortDirection);
		if($sortDirection == "DESC") { array_multisort(array_column($inArray, $key), SORT_DESC, $inArray); }
		elseif($sortDirection == "ASC") { array_multisort(array_column($inArray, $key), SORT_ASC, $inArray); }
		ztime::stopLastTimer();
	}
	
	//Given an 'array of arrays' output from a function like getArray,
	//this function can be used to return a row or rows from that array when WHERE $whereField = $equalsValue
	//this can be used for a virtual left join where the entire contents of two tables are used; this can be faster than SQL on multi-database scenarios.
	public static function where($inputArray, $whereField, $equalsValue, $multiple = false)
	{
		ztime::startTimer("zl_zarr_where");
		if($multiple) //multi row; thanks chatGPT
		{
			$keys = array_keys(array_column($inputArray, $whereField), $equalsValue);
			if(!empty($keys)) //return all matching rows using the found keys if exist
			{
				$results = [];
				foreach($keys as $key) { $results[] = $inputArray[$key]; }
				//$results = array_intersect_key($inputArray, array_flip($keys));
				
				//unfortunately, this line doesn't make the operation faster.
				//foreach($keys as $key) { unset($inputArray[$key]); }
				
				ztime::stopLastTimer();
				return $results;
			}
			else { ztime::stopLastTimer(); return []; }
		}
		else //single row
		{
			$key = array_search($equalsValue, array_column($inputArray, $whereField));
			if(is_numeric($key)) { ztime::stopLastTimer(); return $inputArray[$key]; }
			else { ztime::stopLastTimer(); return false; }
		}
	}
	
	//-------------- All other array functions  -----------------
	
	//return a flat recursive array. This can also be modified to print arrays in nice formats.
	//Side effect: overwrites items with same name.
	public static function flatten($array)
	{
		if(!is_array($array)) { return [$array]; }
	    return array_reduce($array, function ($carry, $item) { return array_merge($carry, self::flatten($item)); }, []);
	}
	
	//count the bytes ( in strings ) in an array.
	//this method could be faster.
	public static function countToBytes(array $inArray)
	{
		$total = 0;
		$flatArray = self::flatten($inArray);
		foreach($flatArray as $flatVal) { $total += mb_strlen($flatVal); }
		return $total;
	}
	
	//non-utf compatible word distance
	function wordMatch($wordArray, $input, $sensitivity = 2)
	{
        $shortest = -1;
        foreach ($wordArray as $word)
		{
			if($word == $input) { return $input; } //dead ringer, so..
			$lev = levenshtein($input, $word);
            if ($lev == 0) { $closest = $word; $shortest = 0; break; }
            if ($lev <= $shortest || $shortest < 0) { $closest = $word; $shortest = $lev; }
        }
		
        if($shortest <= $sensitivity){ return $closest; } //return closest match
		else { return false; } //nothing met the criteria
    }
	
	//removes keys from a numbered associative array. $filterKeys is a piped list of values.
	//'include' mode not working
    public static function removeKeysArray(array &$inArray, $filterKeys, $filterDir = "exclude")
	{
		$filterKeys = self::dePipe($filterKeys);
		
		//sanity checks
		if(!is_array($inArray) || !is_array($filterKeys)) { zl::fault("Part of the input was not an array.",__FUNCTION__); }
		if($filterDir != "exclude" && $filterDir != "include") { zl::fault("unknown filterDir: [" . $filterDir . "]", __FUNCTION__); }
		
		$tac = count($inArray);
		for($i = 0; $i < $tac; $i++)
		{
			foreach($filterKeys as $filterKey)
			{
				if($filterKey != "")
				{
					if($filterDir == "exclude" &&  array_key_exists($filterKey, $inArray[$i])) { unset($inArray[$i][$filterKey]); }
					elseif(!array_key_exists($filterKey, $inArray[$i])) { unset($inArray[$i][$filterKey]); }
				}
			}
		}
	}
	
	//removes keys from a single layer associative array. Input piped list of values.
	//'include' mode not working.
    public static function removeKeys(array &$inArray, $filterKeys, $filterDir = "exclude")
	{
		$filterKeys = self::dePipe($filterKeys);
		if(!is_array($inArray) || !is_array($filterKeys)) { zl::fault("Part of the input was not an array.",__FUNCTION__); }
		if($filterDir != "exclude" && $filterDir != "include") { zl::fault("unknown filterDir: [" . $filterDir . "]", __FUNCTION__); }
		
		foreach($filterKeys as $filterKey)
		{
			if($filterKey != "")
			{
				if($filterDir == "exclude" && array_key_exists($filterKey, $inArray)) { unset($inArray[$filterKey]); }
				elseif(!array_key_exists($filterKey, $inArray)) { unset($inArray[$filterKey]); }
			}
		}
	}
	
	//sort an array descending by a key's value.
	public static function sortByKey(array &$inArray, $key)
	{ array_multisort(array_column($inArray, $key), SORT_DESC, $inArray); }
	
	//untested function
	public static function groupByFirst($key, $recordArray)
	{
	    $result = [];
	
	    foreach($recordArray as $record)
	    {
	        if(array_key_exists($key, $record)) { $result[$record[$key]][] = $record; }
	        else{ $result[""][] = $record; }
	    }
	
	    return $result;
	}
	
	//get the last item in a flat array.
	public static function last(array $array) { return end($array); }
	public static function first(array $array) { foreach($array as $arrayItem) { return $arrayItem; } }
	
	//return if an array is associative or not.
	//not 100% reliable; refactor to abuse getArrayInfo for data
	public static function isAssociative($arr)
	{
		if(!is_array($arr)) { return false; }
	    if(array() === $arr) { return false; }
	    return(array_keys($arr) !== range(0, count($arr) - 1));
	}
	
	//Iterate through array and figure out what kind it be and how to handle it.
	//Multiple functions will use this to check the type of array you are sending to them.
	//Tested and accurate
	public static function getArrayInfo(array $array, $advancedInfo = false, $faultIfNotType = "") //#TESTED
	{
		$multiConsistentWidth = "";
		//some reasonable defaults
		$arrInfo = ["type" => "", "depth" => 0, "canLoop" => true, "allKeys" => []];
		$stringBytes = 0;
		
		if(count($array) == 0) { $arrInfo['type'] = "blank"; $arrInfo['canLoop'] = false; } //well, that was easy
		else
		{
			//what's beneath the iteration?
			$singleAssocKeys = 0;
			$singleNumKeys = 0;
			$multiLastWidth = -1; //set this now
			
			foreach($array as $key => $value)
			{
				if($arrInfo['depth'] == 0){ $arrInfo['depth'] = 1; }
				
				//identify key type
				if(is_numeric($key)) { $singleNumKeys++; } else { $singleAssocKeys++; }
				
				//traversible?
				$valueType = gettype($value);
				if($valueType == "array")
				{
					if(!$advancedInfo) { $arrInfo['lastKeyWidth'] = count($value); } //our best guess
					
					//consistency tipoff
					if($multiLastWidth == -1) { $multiLastWidth = count($value); $multiConsistentWidth = true; }
					else { if(count($value) != $multiLastWidth) { $multiConsistentWidth = false; } }
					
					$multiAssocKeys = 0;
					$multiNumKeys = 0;
					if($arrInfo['depth'] < 2){ $arrInfo['depth'] = 2; }
					
					if($advancedInfo)
					{
						//start compiling keys
						if(empty($arrInfo['allKeys'])) //initial fill
						{
							$arrInfo['consistentKeys'] = true; //true until..
							$arrInfo['allKeys'] = array_keys($value);
						}
						else
						{
							//do we have a difference in keys?
							$tempKeys = array_keys($value);
							if($tempKeys != $arrInfo['allKeys'])
							{
								$arrInfo['consistentKeys'] = false; //not consistent
								$arrInfo['allKeys'] = array_merge($arrInfo['allKeys'], $tempKeys);
								//$arrInfo['allKeys'] = [...$arrInfo['allKeys'], ...$tempKeys]; //more performant php7.4.x ver
							}
							else { $arrInfo['consistentKeys'] = true; }
						}
					}
					
					foreach($value as $key => $value)
					{
						if(is_numeric($key)) { $multiNumKeys++; } else { $multiAssocKeys++; }
						
						//traversible?
						$valueType = gettype($value);
						if($valueType == "array")
						{
							$arrInfo['depth'] = 3;
							$arrInfo['type'] = "hasObjects";
							$arrInfo['canLoop'] = false;
							$stringBytes = 0;
							break 2;
						}
						elseif($valueType == "object" || $valueType == "resource") //yikes
						{
							$arrInfo['depth'] = 3;
							$arrInfo['type'] = "hasObjects";
							$arrInfo['canLoop'] = false;
							$stringBytes = 0;
							break 2;
						}
						elseif($advancedInfo && $valueType == "string") { $stringBytes += strlen($value); }
					}
				}
				elseif($valueType == "object" || $valueType == "resource") //yikes
				{
					$arrInfo['depth'] = 2;
					$arrInfo['type'] = "hasObjects";
					$arrInfo['canLoop'] = false;
					$stringBytes = 0;
					break;
				}
				elseif($advancedInfo && $valueType == "string") { $stringBytes += strlen($value); }
			}
			
			//figure out key types
			if(!$arrInfo['canLoop']) {} //just broken
			else
			{
				//what type of single keys?
				if($singleAssocKeys != 0 && $singleNumKeys == 0) { $singleKeyType = "assoc"; }
				else if($singleAssocKeys == 0 && $singleNumKeys != 0) { $singleKeyType = "num"; }
				else { $singleKeyType = "mixed"; } //mixed key types? not good!!
				
				//if the keys are not exactly in order starting from 0, this is technically an associative array because PHP thinks so.
				if($singleKeyType == "num" && array_keys($array) !== range(0, count($array) - 1)) { $singleKeyType = "assoc"; }
				
				//what type of multi keys?
				if($arrInfo['depth'] > 1)
				{
					if($multiAssocKeys != 0 && $multiNumKeys == 0) { $multiKeyType = "assoc"; }
					else if($multiAssocKeys == 0 && $multiNumKeys != 0) { $multiKeyType = "num"; }
					else { $multiKeyType = "mixed"; } //mixed key types? not good!!
					
					//if the keys are not exactly in order starting from 0, this is technically an associative array because PHP thinks so.
					if($multiKeyType == "num" && array_keys($array) !== range(0, count($array) - 1)) { $multiKeyType = "assoc"; }
				}
			}
			
			//compile some other useful data
			if(is_bool($multiConsistentWidth)) { $arrInfo['consistentWidth'] = $multiConsistentWidth; }
			
			//decide on the type
			if($arrInfo['type'] == "")
			{
				if($arrInfo['depth'] == 1) { $arrInfo['type'] = "single" . ucfirst($singleKeyType); }
				else if($arrInfo['depth'] == 2) { $arrInfo['type'] = "multi" . ucfirst($multiKeyType); }
			}
		}
		
		//blow up if array not correct type.
		if($faultIfNotType != "")
		{
			if(strtolower($faultIfNotType) != strtolower($arrInfo['type']))
			{ zl::fault("Invalid array type sent: [" . $arrInfo['type'] . "], but expected: [" . $faultIfNotType . "]"); }
		}
		
		$mixedType = zs::contains($arrInfo['type'], "mixed");
		
		//no such thing as consistency checks with mixed keys
		if($mixedType && $arrInfo['depth'] > 1) { $arrInfo['consistentWidth'] = false; $arrInfo['consistentKeys'] = false; }
		
		if($advancedInfo) //advanced info?
		{
			if($stringBytes > 0) { $arrInfo['stringBytes'] = $stringBytes; }
			if(!$mixedType)
			{
				$arrInfo['allKeys'] = array_unique($arrInfo['allKeys']);
				$arrInfo['allKeysWidth'] = count($arrInfo['allKeys']);
			}
			else { unset($arrInfo['allKeys']); }
		}
		else //remove scrap and inaccurate data..
		{
			unset($arrInfo['allKeys']);
			if(isset($arrInfo['consistentWidth']) && !$arrInfo['consistentWidth']) { unset($arrInfo['lastKeyWidth']); }
		}
		
		//remove if blank ( not applicable )
		if(empty($arrInfo['allKeys'])) { unset($arrInfo['allKeys']); }
		if(empty($arrInfo['allKeysWidth'])) { unset($arrInfo['allKeysWidth']); }
		
		//for clarity - these encounters do not have accurate counts because they break early / do not recurse deep enough
		if($arrInfo['type'] == "hasObjects" || $arrInfo['type'] == "tooDeep")
		{
			unset($arrInfo['consistentKeys'], $arrInfo['consistentWidth'], $arrInfo['stringBytes'], $arrInfo['allKeys'], $arrInfo['allKeysWidth'], $arrInfo['lastKeyWidth']);
		}
		
		return $arrInfo;
	}
	
	//maps a CSV to array; accepts a piped list of field order
	//tested
	public static function mapCsvToArray($csvFile, $fieldOrder)
	{
		//initialized data
		if(zs::containsCase($fieldOrder, "|")) { $fieldOrder = self::dePipe($fieldOrder); }
		else{ $fieldOrder = explode(",", $fieldOrder); } //CS uses this style
		$parseResult = array("result" => true, "data" => array(), "msg" => "", "count" => 0);
		$resultArray = [];
		
		//load and interpret CSV
		$csvLines = file_get_contents($csvFile);
		$csvLines = explode("\n", preg_replace('/\n$/','',preg_replace('/^\n/','',preg_replace('/[\r\n]+/',"\n",$csvLines))));
		
		foreach($csvLines as $line) //go through each line
		{
			$temp = explode(",", rtrim($line, ","));
			
			//field mismatch?
			if(count($temp) < 2) { $parseResult['msg'] .= "One line was skipped because it was missing fields.<br>"; }
			else
			{
				if(count($temp) != count($fieldOrder))
				{
					$parseResult['msg'] .= "This CSV line had too many/too few fields:<br>" . rtrim($line, ",") . "<br>";
					$parseResult['result'] = false;
				}
				else
				{
					$tempResult = [];
					for($k = 0; $k <= count($fieldOrder) -1; $k++) { $tempResult[$fieldOrder[$k]] = $temp[$k]; }
					$resultArray[] = $tempResult;
				}
			}
		}
		
		//return completed result object.
		$parseResult['count'] = count($resultArray);
		$parseResult['countInput'] = count($csvLines);
		if(count($resultArray) >0) { $parseResult['data'] = $resultArray; }
		
		return $parseResult;
	}
	
	//Possibly busted - do not use
	//see if list of variables are present in an associative array; accepts pipe and flat array.
	public static function varsInArray($variableNames, array $data)
	{
		$variableNames = self::dePipe($variableNames);
		if(self::isAssociative($variableNames)) { self::fault("zarr::varsInArray passed an associative array."); } //nope
		foreach($variableNames as $variable) { if(!isset($data[$variable])) { return false; }}
		return true;
	}
	
	//convert a single associative array into a 'get' string for submitting to web server; accepts APipe
	public static function toGetRequest($datas)
	{
		$datas = self::toArray($datas);
		$getString = "";
		foreach($datas as $key => $value)
		{
			//(optimization) only spit out if there is an existing string.
			if($value != "") { $getString .= "&" . $key . "=" . $value; }
		}
		return rtrim($getString, "&");
	}
	
	//convert an array to HTML attributes; also accepts APipe
	public static function toHtmlAttributes($datum)
	{
		if(zs::isBlank($datum)) { return ""; } //otherwise weird stuff can happen.
		$datum = self::toArray($datum);
		$attString = "";
		
		foreach($datum as $key => $value) { $attString .= " " . $key . " = '" . $value . "'"; }
		$out = str_replace("  ", " ", $attString);
		return $out;
	}
	
	//array to string format conversions
	public static function toJson(array $array) { return json_encode($array); } //shortcut
	public static function toIG(array $array) { return igbinary_serialize($array); } //shortcut
	public static function toPipe(array $array){ return(implode(self::$pipeDe, $array)); } //simple alias.
	public static function toAPipe(array $array) //explode a flat associative array to a string.
	{
		$returnString = "";
		foreach($array as $key => $value) { $returnString .= $key . self::$pipeDe. $value. self::$apipeDe; }
		return $returnString;
	}
	
	//Magic conversion of various types of string formatted array to real array.
	//$preferAssociative what style array to output in unclear situations with pipes ( IE: 2 items)
	public static function toArray($inputData, $preferAssociative = true)
	{
		if(is_array($inputData)) //don't be silly.
		{
			//zl::quipDZL("is already an array.");
			return $inputData;
		}
		else if(zs::isBlank($inputData)) //return blank data.
		{
			//zl::quipDZL("was blank...");
			return [];
		}
		
		//preprocess data.
		$inputData = trim($inputData);
		$firstChar = substr($inputData, 0, 1);
		$lastChar = substr($inputData, -1);
		
		//decision time.
		if($firstChar == "<" && $lastChar == ">") //smells like XML
		{
			//zl::quipDZL("is xml.");
			return self::deXML($inputData);
		}
		if($firstChar == "{" && $lastChar == "}") //smells like JSON
		{
			//zl::quipDZL("is JSON.");
			return self::deJson($inputData);
		}
		
		//precalculate for speed
		$pipes = substr_count($inputData,self::$pipeDe); if($pipes > 0) { $hasPipe = true; } else { $hasPipe = false; }
		
		if($hasPipe && zs::containsCase($inputData,self::$apipeDe)) //apipe!
		{
			//zl::quipDZL("is apipe.");
			return self::deAPipe($inputData);
		}
		if($hasPipe) //multiple elements, not sure if apipe or pipe
		{
			if($pipes > 2) //regular pipe for sure.
			{
				//zl::quipDZL("is pipe.");
				return self::dePipe($inputData);
			}
			if($pipes == 2) //this is where we need that flag.
			{
				if($preferAssociative)
				{
					//zl::quipDZL("is apipe. (loose guess)");
					return self::deAPipe($inputData);
				}
				else
				{
					//zl::quipDZL("is pipe. (loose guess)");
					return self::dePipe($inputData);
				}
			}
			else //must be pipe.
			{
				//zl::quipDZL("is pipe. (last guess)");
				return self::dePipe($inputData);
			}
		}
		else { return array($inputData); } //what did you send me?
	}
	
	//turn string formats into arrays.
	public static function deJson($textString) //shortcut
	{
		ztime::startTimer("zl_zarr_deJson");
		$out = json_decode($textString, true);
		ztime::stopLastTimer();
		return $out;
	}
	public static function deIG($textString) { return igbinary_unserialize($textString); }
	public static function dePipe($textString){ return(explode(self::$pipeDe, trim($textString, self::$pipeDe))); } //shortcut.
	public static function deAPipe($textString) //explode into an associative array.
	{
		$returnArray = [];
		$temp = explode(self::$apipeDe, trim($textString, self::$apipeDe));
		foreach($temp as $t) { $temp2 = explode(self::$pipeDe, $t); $returnArray[$temp2[0]] = $temp2[1]; }
		return $returnArray;
	}
	
	//dirty hack to get XML into an array.
	public static function deXML($textString) { return array_map('self::objectToArray', (array) simplexml_load_string($textString)); }
	
	//dirty hack to get object into an array.
	private static function objectToArray($object)
	{
        if(!is_object($object) && !is_array($object)) { return $object; }
        return array_map('self::objectToArray', (array) $object);
	}
}
?>