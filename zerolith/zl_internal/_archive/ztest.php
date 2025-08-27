<?php
//zerounit; poor man's testing for controllers
//05/16/2023 - v0.1 - Hastily invented

//allows ability to inject GET/POST into a target controller script
//execution facts are added to the target script.
//use a boilerplate ( zl_internal/zltests/_template.php )

class ztest
{
	private static $execFacts = [];     //facts about the execution
	private static $resultsArray = [];
	public static $injectInputData = [];//inject input into the script via zfilter::array(). Associative array format
	public static $injectMethod = "GET";//mode to send during injection
	public static $pageOutput = "";
	
	//judge a regular variable, IE the output of a function
	public static function judge($inVal, $judgementMethod, $compareVal = "") {}
	
	//judge an execution fact and return the verdict to resultsArray for later reporting with compileResults()
	public static function judgeFact($factName, $judgementMethod, $compareVal = "")
	{
		//initialize this result if it hasn't been already.
		if(!isset($resultsArray[$factName]))
		{
			$resultsArray[$factName] = [];
		}
		$result = [];
		
		//all possible judgement methods
		if(in_array($judgementMethod,
		            [
			            // doesn't require input; just comparison of $compareVal
			            "isAssocArray", //is an associative array
			            "isArray",      //is an array of any shape
			            "isNumeric",    //is numeric
			            "isFloat",      //is numeric and a float
			            "isString",     //is string
			            "isBool",       //is boolean
			            "isTrue",       //is boolean and true
			            "isFalse",      //is boolean and false
			            "notAssocArray",//not an associative array
			            "notArray",     //not an array of any shape
			            "notNumeric",   //not numeric
			            "notFloat",     //not numeric and a float
			            "notString",    //not string
			            "notBool",      //not boolean
			            
			            // requires input to check $compareVal against the execution fact
			            "==",           //equals the execution fact
			            "===",          //strict php equals the execution fact
			            "~==",          //equals the execution fact;case insensitive ( for strings only; uses == for arrays )
			            "!=",           //not equals the execution fact
			            ">0",           //( number ) above zero
			            "<0",           //( number ) below zero
			            "~contains",    //contains the input ( string ), case insensitive
			            "contains",     //contains the input ( string )
			            "isAtStart",    //is at the start of the ( string )
			            "isAtEnd",      //is at the end of the ( string )
			            "~notContains", //doesn't contain the input ( string ), case insensitive
			            "notContains",  //doesn't contain the input ( string )
			            "notAtStart",   //not at the start of the ( string )
			            "notAtEnd",     //not at the end of the ( string )
			            
			            "hasKeys",       //an array [,,,] or piped list "|||" includes certain keys
			            "hasKeysVals",   //has keys matching these values [["" => ""]]
			            "notHasKeys",    //an array [,,,] or piped list "|||" doesn't include
			            "notHasKeysVals",//doesn't have keys matching thees values [["" => ""]]
		            ]
		))
		{
			if($factName == "[output]")
			{
				$execFact = self::$pageOutput;
			} //scan through page output if we have this special code
			else if(!isset(self::$execFacts[$factName]))
			{
				$execFact = "[Missing Execution Fact]";
			} //if there isn't a fact, mark it as missing
			else
			{
				$execFact = self::$execFacts[$factName];
			}
			
			//massive rule processing blob
			switch($judgementMethod)
			{
			
			case "":
				echo "yeah";
			break;
			case "":
				echo "woo";
			break;
			}
			
			//result object for later use
			$result['compareVal'] = $compareVal;
			$result['operator'] = $judgementMethod;
			$result['success'] = true;
			
			//write judgement to the results[] table
			$resultsArray[$factName][] = $result;
		}
		else
		{
			zl::fault("An invalid judgement method was passed to ztest. Halting test.");
		}
	}
	
	//example fact object:
	//[
	//  "factName" => "someFunction_output",
	//  "resultVal" => "31337",
	//  "call" => "someFunction('translateToHackerSpeak','elite')",
	//  "callLine" => "/var/www/someFunction.php @ 141"
	//];
	
	//if unit testing is turned on, add this fact of execution from the target script
	public static function addFact($factName = "Unspecified Fact", $resultVal = "")
	{
		if(zl::$unitTesting)
		{
			$callData = zl::formatBacktrace(debug_backtrace(0, 2)[1]); //get the thing that called this
			self::$execFacts[$factName] =
				[
					"factName" => $factName,
					"resultVal" => $resultVal,
					"call" => $callData['call'],
					"callLine" => $callData['callLine']
				];
		}
	}
	
	//output results from the result array we collected
	public static function compileResults()
	{
		$fail = 0;
		$success = 0;
		$textBuf = "";
		$textOutput = "";
		
		//go through the results
		foreach(self::$resultsArray as $factName => $resultArray)
		{
			$compiledResult = "";
			
			//use this to compile
			$callLine = self::$execFacts[$factName]['callLine'];
			$resultVal = self::$execFacts[$factName]['resultVal'];
			$resultVal = self::$execFacts[$factName]['resultVal'];
			
			//go through each individual test performed
			foreach($resultArray as $result)
			{
				//variables we can use here
				$factName = "";
				$result['compareVal'] = $compareVal;
				$result['operator'] = $judgementMethod;
				$result['success'] = true;
				
				if($result['success'])
				{
					$success++;
				}
				else
				{
					$fail++;
				}
			}
			
			//turn result into text representation because we want HTML output
			$textOutput = $compiledResult;
		}
		//output text + fail/pass
		
	}
	
	//this exit handler is used by zl::terminate to output the test results.
	public static function exitHandler()
	{
		//this should be in it's own box
		zui::quip(self::compileResults());
		exit;
	}
}