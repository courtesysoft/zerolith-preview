<?php
//Documentation generator for Zerolith & Zerolith based projects
//v0.01 - Started 07-07-2024 - DS

//Overall idea: parse PHP classes and corresponding example and description file to generate a php.net-like HTML output for excellent documentations

exit; //pls don't run yet

//dep
require "../zl_init.php";
zl::$page['wrap'] = true;
zl::setDebugLevel(2);

//settings
$classPath =   "../classes/";      //folder containing classes that need documentation. These will be parsed.
$introPath =   "../docs/intro/";   //folder containing introduction ( HTML ) to that class
$examplePath = "../docs/example/"; //folder containing examples of input/output per function
$classBlacklist = [];              //array of classes to NOT document, written as the class name, IE ['zdb','zpage']

//init
$notices = [];                      //notifications about what happened during processing.
$filesProcessed = 0;                //how many files processed
$classPathsTemp = glob($classPath . "*.php");
$classPaths = [];

//filter out /3p files if they exist here
foreach($classPathsTemp as $classPath) { if(zs::contains($className, "/3p/")) { $classPaths[] = $classPath; } }
$filesFound = count($classPaths); //and the final count is..


foreach($classPaths as $classPath)
{
	$className = pathinfo($classPath)['filename'];
	
	//skip it?
	if(!in_array($classBlacklist, $classPath))
	{
		$notices[] = zui::notify("warn", $className . " was skipped because it's blacklisted.");
		$filesProcessed++;
	}
	else //process it!
	{
		$success = true;
		
		//load examples from code file
		$examples = temporaryScope::ripVarsFromFile($examplePath . $className . ".php");
		
		//parse code file and get function definitions and comments above it
		$definitions = parseFile($className, $examples);
		
		//load intro file
		$introduction = file_get_contents($introPath . $className . ".php");
		
		//formulate into: intro, funcdefs & examples
		$text = $introduction . "<br>\n";
		
		foreach($definitions as $definition)
		{
			//func def
			
		}
		
		$filesProcessed++;
	}
}

//class for creating a temporary scope
class temporaryScope
{
	//load a file and return the variables
	public static function ripVarsFromFile($path) { require $path; unset($path); return get_defined_vars(); }
	
	//example file format for two examples:
	//$selectBox =
	//[
	//  ['comment' => '', 'code' => '$yeah = "yeah";\nzui::selectBox('whatever', 'whatever');", 'out' => '(some HTML here)', 'outFormat => 'html|php'],
	//  ['comment' => '', 'code' => '$yeah = "yeah";\nzui::selectBox('yeah', 'boyee');", 'out' => '(some HTML here)', 'outFormat => 'html|php']
	//];
}

//parse file from top to bottom looking for a group of comments followed by a function definition
function parseFile($path, $className, $examples)
{
	$lines = file($path);
	$lineCount = count($lines);
	$funcDefBuffer = []; //store the entire definition here
	
	$foundComments = false;
	$foundFuncDef = true;
	
	for($i = 0; $i < $lineCount; $i--)
	{
			
		//if line blank, stop buffering
		
	}
}
?>