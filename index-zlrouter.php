<?php
/*
This router has not been used in a production application yet and can still be considered an alpha. Don't expect it to work properly without modification.
This router is intended to be used with Apache to provide cute URLs for scripts which weren't written with any routing in mind. You must use .htaccess-zlrouter ( rename to .htaccess ) to enable it.

How it works:

[webroot] = /var/www/ or whatever your base path for your project + ZL is.

1) https://yoursite.com/whatever = [webroot]/whatever.php
2) If $allowDirect = true, https://yoursite.com/whatever.php = [webroot]/whatever.php
3) If $subFolders contains 'admin', https://yoursite.com/admin/viewStuff = [webroot]/admin/viewStuff.php
4) Subsequent URL segments after a valid file path are key->value pairs of variable input sent to the script, IE https://yoursite.com/admin/viewStuff/ID/4224/isEnabled/Y = [webroot]/admin/viewStuff.php?ID=4224&isEnabled=Y
*/

//01-2024 - DS - Complete, has initial testing, needs battle testing.

require("zerolith/zl_init.php");  //Load framework. Creates the zl object.
zl::setDebugLevel(3); //Set any zerolith options needed.

//settings for routing
$allowDirect = true; //allow a direct hit to .php ( to support legacy URLs )
$subFolders = ['admin','manager','user']; //subfolders that scripts are in; will not load a script with this name & will attempt to find in that folder
$defaultScript = "home.php"; //default script ( index ) for when / is sent

//begin routing
$params = [];
$origurl = zfilter::URL(parse_url($_SERVER['REQUEST_URI'])['path']);

//sanity checks and filtration
if(strpos($origurl, "..") !== FALSE) { zl::faultAbuse("Path traversal attempted"); } //bad news
if($allowDirect && strpos(strtolower($origurl), ".php")) //direct hit
{ zl_router_cwd($origurl); require(substr($origurl, 1)); exit; }
$origurl = str_replace("//", "/", $origurl); //error?
$origurl = rtrim($origurl,"/");

//process
$url = explode("/", $origurl);
array_shift($url); //first segment is always blank

if($url == [] || $url[0] == "") { $scriptPath = $defaultScript; } //blank
else if(count($url) >= 2 && $url[1] != "" && in_array($url[0], $subFolders)) //script in subfolder
{
	$scriptPath = $url[0] . "/" . $url[1] . ".php";
	$scriptPath = zl_router_cwd($scriptPath);
	array_shift($url); array_shift($url); //remove remaining
}
else //single path starting at root dir
{
	$scriptPath = $url[0] . ".php";
	array_shift($url); //remove remaining
}

//process variables if any
if($url != [])
{
	$segments = count($url);
	if($segments % 2 == 0) //correct varName -> value -> varName -> value order, as expected
	{
		//first seg is variable name, second seg is value
		for($i = 0; $i < $segments; $i++)
		{ if($i % 2 == 0){ $varName = $url[$i]; } else { $params[$varName] = $url[$i]; } }
	}
	else { zl::fault("An odd number of parameters was provided in the URL"); }
}

//echo "original URL: " . $origurl . "<br>";
//echo "script path: " . $scriptPath . "<br>";
//echo "raw params: " . print_r($params, true) . "<br><br>";

if(!file_exists($scriptPath)) { zl::fault("Invalid Address: " . $origurl); }
require($scriptPath);

//change working directory based on path, return script filename
function zl_router_cwd($path)
{
	if(strpos($path, "/") !== FALSE)
	{
		$pathArr = explode("/", $path);
		$scriptName = array_pop($pathArr);
		$newdir = ltrim(implode('/', $pathArr), "/") . "/";
		chdir($newdir);
		return $scriptName;
	}
	else { return ""; }
}
