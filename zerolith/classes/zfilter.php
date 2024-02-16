<?php
// zerolith Filtering Library - (c)2021 Courtesy Software
class zfilter
{
	private static $validateChecks;             //array of variables for filter to co-process
	public static $validateActive = false;      //whether validation is active for this page or not
	public static $validateUserDinged = false;  //whether we have dinged the user or not
	public static $validatePassMinLength = 8;
	public static $validatePassMaxLength = 25;
	public static $validatePassRegEx = "/[^A-Za-z0-9\s',-.!_]/";
	public static $validatePassSpecialChars = "',-.!_";  //allowable special characters in password validation ( displayed to the user ).
	public static $validateChecksFailed = []; //the aggregated list of all failed checks; ZUI can reference this.
	
	private static $badwords = []; //not used?
	
    //process and filter inputted form variables and return as an associative array.
	//Use extract() to pop the array into separate vars in local scope if needed at the beginning of a script.
	//Or simply take the output into an associative array.
    public static function array($inputVars, $filterFunction)
    {
		//auto detection of mode.
        if($_SERVER['REQUEST_METHOD'] == "GET"){ $getVars = $_GET; }
        else if($_SERVER['REQUEST_METHOD'] == "POST"){ $getVars = $_POST; }
        else { return; } //there is no data to return; we don't know the mode!
        
        $outputVars = [];
        $varArray = zarr::toArray($inputVars, false); //can take a pipe or regular array.
        foreach($varArray as $varName)
        {
			//is it a HTML form name like value[subValue]? if so, let's filter based on that.
            //this is a protection mechanism that ZUI uses
            $subVal = "";
            if(zs::containsCase($varName, "["))
            {
				$x = explode($varName);
				if(count($x) != 2) { zl::fault(); }
				$getVars[$varName] = $x[0];  //the base array.
				$subVal = rtrim($x[1], "]"); //the key in that array.
            }

            if(isset($getVars[$varName])) //does the variable exist?
            {
                $valueType = gettype($getVars[$varName]);

                if($valueType == "array") //handle a form variable that's an array.
	            {
	                $varData = [];
	                foreach($getVars[$varName] as $key => $value)
					{
						if($subVal != "" && $subVal == $key) { $varData[$key] = self::$filterFunction($value); }
						else { $varData[$key] = self::$filterFunction($value); }
					}
	            }
                else if($valueType == "string"){ $varData = self::$filterFunction($getVars[$varName]); }
                else { zl::fault("zl doesn't know how to filter input: " . $varName); }
            }
            else
            {
	            if(isset($_FILES[$varName])) { $varData = $_FILES[$varName]; } //is it a file?
	            else { $varData = ""; } //it's really nothin?
            }
            $outputVars[$varName] = $varData;
        }
        return($outputVars);
    }

    //only allow harmless characters
    public static function stringSafe($inString)
    {
        //filter out all nonalphanumeric characters except - . , .
        $inString = preg_replace("/[^A-Za-z0-9\s',-.&_]/", "", $inString);
        $inString = trim($inString, " _,.'"); //trim characters off the sides
        return $inString;
    }

    //allow more characters, but prevent SQL injection.
    public static function stringExtended($inString)
    {
        //filter out all nonalphanumeric characters except ' - @ .
        $inString = preg_replace("/[^A-Za-z0-9\s+',-.\\_|&~@]/", "", $inString);
        $inString = trim($inString, " ',/");  //trim possibly malicious characters off the sides
        $inString = str_replace("--", "-", $inString);
        $inString = str_replace("  ", " ", $inString);
        $inString = str_replace("@@", "@", $inString);
        $inString = str_replace("''", "'", $inString);
		
		//anti sql injection
        $inString = str_ireplace("drop index", "dro p index", $inString);
        $inString = str_ireplace("drop table", "dro p table", $inString);
        $inString = str_ireplace("drop database", "dro p database", $inString);
        $inString = str_ireplace("update ", "up-date ", $inString);
        $inString = str_ireplace("delete", "erase", $inString);
        $inString = str_ireplace("insert ", "in-sert ", $inString);
        $inString = str_ireplace(" drop", " dro p", $inString);
        $inString = str_ireplace(" update", " up-date", $inString);
        $inString = str_ireplace(" insert", " in-sert", $inString);
        $inString = str_ireplace("where 1", "where one", $inString);
        $inString = str_ireplace("or 1", "or one", $inString);
        return $inString;
    }

    public static function none($inString) { return $inString; } //straight pipe.

    public static function date($inString) //filter out all non-numeric characters except : / - . .
    { return preg_replace("/[^0-9:.\-\s]/", "", $inString); }

    public static function page($inString) //filter out all non-numeric characters except _ -
    { return preg_replace("/[^A-Za-z0-9_-]/", "", $inString); }

    public static function number($inString) //filter out all non-numeric characters.
    { return preg_replace("/[^0-9]/", "", $inString); }
    
    public static function email($inString) //filter out invalid email characters
    { return preg_replace("/[^A-Za-z0-9@!#&*+-=_.]/", "", $inString); }
	
	public static function html($inString) //perform XSS filtering on HTML.
    {
		//and then...
	    zl::fault("html filtering not available yet.");
		
		//which one is better?
		
		require_once(zl_frameworkPath . "/classes/3p/AntiXSS/AntiXSS.php");
		$antiXss = new AntiXSS();
		return $antiXss->xss_clean($inString);
		
		require_once(zl_frameworkPath . "/classes/3p/htmLawed/htmLawed.php");
		$inString = htmLawed($inString, array('safe'=>1));
		return $inString;
	}

    public static function URL($inString) //filter out all non-numeric characters except ones used in URLs.
    { return preg_replace("/[^A-Za-z0-9\s+-.|~_&?=:\/@]/", "", $inString); }
	
	
	
	// --------- zvalid section - do not use yet ------------
	
	
	
	//zvalid class concept. Performs batch and single validations and returns list of messages + fail/succeed status
	//01/16/2022 - v0.0 - concept
	
	//concept: user adds validation per var ahead of filterarray, with similar syntax, runs filterarray, then gets results with validResult()
	//zui:: provides an easy way to output validation messages
	
	//This adds pending checks that zfilter will send the values to.
	//This requires that you define your checks before running zfilter, and process checks after zfilter
	//This is a short way to check a lot of variables for gigaforms.
	// $varName = the HTML/PHP input variable for this name.
	// $rules = single or piped list of rules to apply: notblank/isstring/isnumber/isEmail/password/below#/above#
	// $englishName(optional) = the human readable version of the field name.
	static private function validateAdd($varNames, $englishNames, $rules)
	{
		//convert array or piped input into array
		$varNames = zarr::toArray($varNames);
		$englishNames = zarr::toArray($englishNames);
		
		if($englishNames == "") { $englishNames = $varNames; }
		else if(substr_count($englishNames, "|") != substr_count($varNames, "|"))
		{ zl::fault("number of english names doesn't match the number of variable names"); }
		
		//add to the list of variables to check when running filtering
		$count = count($varNames);
		for($i = 0; $i < $count; $i++)
		{ self::$validateChecks['varName'] = ['varValue' => $varNames[$i], 'rules' => $rules, 'englishName' => $englishNames[$i]]; }
	}
	
	//run an individual check - zfilter uses this function internally
	static private function validateCheck($varName, $varValue, $rules, $englishName = "")
	{
		//run individual checks
		$rulesArray = explode("|", $rules);
		foreach($rulesArray as $rule)
		{
			if($rule == "notblank" && zs::isBlank($varValue))
			{ self::$validateChecksFailed[$varName] = "The " . $englishName . " field is blank."; }
			else if($rule == "isstring" && !is_string($varValue))
			{ self::$validateChecksFailed[$varName] = "The " . $englishName . " field doesn't contain letters."; }
			else if($rule == "isnumber" && !is_number($varValue))
			{ self::$validateChecksFailed[$varName] = "The " . $englishName . " field isn't a number."; }
			else if($rule == "isEmail" && !self::isEmail($varValue))
			{ self::$validateChecksFailed[$varName] = "The " . $englishName . " field isn't a valid email address."; }
			else if($rule == "password")
			{
				//password field should be non-empty 100% of the time.
				if(zs::isBlank($varValue)) { self::$validateChecksFailed[$varName] = "The " . $englishName . " field is blank."; }
				else
				{
					if(strlen($varValue) > self::$validatePassMaxLength || strlen($varValue) < self::$validatePassMinLength)
					{ self::$validateChecksFailed[$varName] = "The " . $englishName . " field must be between " . self::$validatePassMinLength . "-" . self::$validatePassMaxLength. " characters."; }
					else
					{
						//does it meet filtration guidelines?
						$validateedPassword = preg_replace(self::$validatePassRegEx, "", $varValue);
						if($validateedPassword != $varValue)
						{ self::$validateChecksFailed[$varName] = "The " . $englishName . " field has invalid characters; only alpha, numeric, and " . self::$validatePassSpecialChars . " characters accepted."; }
					}
				}
			}
			elseif(zs::contains($rule, "below")) //below # of characters; example: below10
			{
				$rule = str_replace("below", "", $rule);
				if(!is_numeric($rule) || $rule == "0") { zl::terminate("invalid below# rule"); }
				if(strlen($varValue) < $rule) { self::$validateChecksFailed[$varName] = "The " . $englishName . " field isn't a valid email."; }
				
			}
			elseif(zs::contains($rule, "above")) //above # of characters; example: above1
			{
				$rule = str_replace("above", "", $rule);
				if(!is_numeric($rule)) { zl::terminate("invalid above# rule"); }
				
			}
			else //hard stop on processing because we can't ensure proper processing
			{ zl::fault("An unrecognized rule attempted to process in zfilter::processChecks()"); }
		}
	}
	
	//compute results from the zfilter phase - sloppy RN
	static private function validateResult()
	{
		if(count(self::$validateChecksFailed) > 0)
		{
			if(!self::$validateUserDinged)
			{
				zkarma::bad("Data didn't pass validation");
				self::$validateUserDinged = true;
			}
			return false;
		}
		else { self::$validateChecks = []; return true; }
	}
	
	//--------- individual validators which just return true/false. -------
	
	static public function isEmail($email) { return(filter_var($email, FILTER_VALIDATE_EMAIL)); }
	
	static public function isHtml($input) //detect the presence of HTML; possibly too strict
	{
		//such optimized, wow
		if(strpos($input, "<") !== FALSE) { return (preg_match("/<([^>]*)>/im", $input) !== 0); }
		else { return false; }
	}
	
	//detect the presence of javascript, iframe, style, tags which are likely malicious in nature
	//DO NOT USE, far from complete!!!!
	static public function hasBadHtml($input, $badTagsPiped = "script|iframe|style|form|meta|embed")
	{
		if(strpos($input, "<") !== FALSE) { return (preg_match("/<\s*\/?\s*" . $badTagsPiped . "([^>]*)?>/im",$input) !== 0); }
		else { return false; }
	}
}
?>
