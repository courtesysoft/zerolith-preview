<?php
//Zerolith shortcuts library
class zs
{
	//check for the existence in a class in such a way that PHP does not attempt to autoload the class.
	public static function classExists($className) { return class_exists($className, false); }
	
	//case-insensitive contains that's compatible with php 7.x
	public static function contains($haystack, $needle)
	{
		if($haystack === null) { return false; }
		return strpos(strtolower($haystack), strtolower($needle)) !== FALSE;
	}
	
	//faster non-case sensitive version; str_contains in php 8.x can do this also
	public static function containsCase($haystack, $needle)
	{ return strpos($haystack, $needle) !== FALSE; }
	
	//if blank, fill the variable ( modifies $entity variable ). Fills non-existent variables.
	public static function ifBlankFill(&$entity, $fillWith = "") { if(self::isBlank($entity)) { $entity = $fillWith; } }
	
	//does a variable of any sort not exist or is blank?
	public static function isBlank(&$entity)
	{
		if(!isset($entity)) { return true; } //that was easy!
		else if(is_array($entity)) { if(count($entity) == 0) { return true; } else { return false; } } //could be a more complete check
		else if(is_string($entity)) { return !strlen(trim($entity)); } //good
		else if(is_numeric($entity)) { return false; } //good
		else if(is_bool($entity)) { return false; } //good
	    else if(is_object($entity)) { if(count(get_object_vars($entity)) == 0) { return true; } else { return false; } } //like arr check
		else if(is_null($entity)) { return true; } //considered blank
		else { return false; } //all other types such as resource etc
	}
	
	//create standardized result array object from template.
	public static function resultObject(bool $success = true)
	{
		return
		[
			"success" => $success,  //true/false
			"data" => "",           //any returned data
			"msg" => "",            //any returned success messages
			"msgErr" => ""          //any returned error messages
		];
	}
	
	//more flexible print_r that returns by default
	public static function pr($entity)
	{
		if(is_bool($entity) === true) { if($entity === true){ return "[bool: true]"; } else { return "[bool: false]"; } }
		else if(is_string($entity) || is_numeric($entity)) { return $entity; }
		else if(is_array($entity)) { return trim(print_r($entity, true), "\n"); }
		else if(is_object($entity)){ zui::bufStart(); var_dump($entity); return zui::bufStop(); } //objects and other items.
	}
}