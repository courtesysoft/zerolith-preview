<?php
//Possibly outdated 02/05/2024 - DS
//Router intended for zPanel.
//Needs testing with heirarchical folders ( probably not working )
//Also add feature: *heirarchy/scriptname/var/value/var/value

//----- Zerolith bootloader begin -----

require("../zl_init.php"); //Load framework. Creates the zl object.
zl::setDebugLevel(2);
zl::$page['wrap'] = true;


ztime::startTimer("zl_router");

//----- Customize the router's behavior. -----

$skipFirstSegs = 2;   //remove the first X segments of the route.
$scriptFolders = 1;   //0 if all your scripts are in one folder.
$scriptRoot = "";     //set a root where your script files start, If any; make sure there's a slash at the end.
$scriptHome = "home"; //where we go when there's no route specified.

//----- Router code -----

$zl_route = [];
$zl_route['origUrl'] = parse_url($_SERVER['REQUEST_URI'])['path']; //need a copy of this for debug.

//malicious string?
if($zl_route['origUrl'] != preg_replace("/[^A-Za-z0-9?=\/-_]/", "", $zl_route['origUrl']))
{
	//mark minor zabuse. This will add up if someone is attempting to probe.
	zkarma::bad(false, "Strange characters sent to router.");
	$zl_route['logic'] = "url error";
	$zl_route['script'] = $zl_route['origUrl'];
	zl_badController("Invalid or corrupt URL.", $zl_route);
}

$url = explode("/", $zl_route['origUrl']);

//strip the unnecessary first segments.
for($i = -1; $i < $skipFirstSegs; $i++) { array_shift($url); }

//determine the script filename.
if(is_array($url) && !zs::isBlank($url[0]))
{
	$nextSeg = array_shift($url);
	
	//for flat heirarchy
	if($scriptFolders == 0)
	{
		$zl_route['script'] = $scriptRoot . $nextSeg . ".php";
		$zl_route['logic'] = "direct relation";
	}
	else //for multidimensional heirarchy
	{
		$zl_route['script'] = $scriptRoot;
		for($i = 0; $i < $scriptFolders; $i++) { $zl_route['script'] .= $nextSeg . "/"; }
		$zl_route['script'] .= '.php';
		$zl_route['logic'] = "folder relation";
	}
}
else
{
	$zl_route['script'] = $scriptRoot . $scriptHome . ".php";
	$zl_route['logic'] = "home route";
}

$zl_route['action'] = $url; //the remnant is 'action' for the script to interpret in it's own fashion.

//responding to a bad route
function zl_badController($url, $routeData = "")
{
	ztime::stopTimer("zl_router");
	zl::faultUser("Sorry, we couldn't find that page.","Invalid Address: " . $url . "<br>" . "Attempted script: " . $routeData['script'] . "<br>Logic: " . $routeData['logic']);
}

if(!file_exists($zl_route['script'])) { zl_badController($zl_route['origUrl'], $zl_route); }

//remove temporary variables and git'r done.
unset($skipFirstSegs, $scriptFolders, $scriptRoot, $scriptHome, $url);
ztime::stopTimer("zl_router");

//go time!
require($zl_route['script']);