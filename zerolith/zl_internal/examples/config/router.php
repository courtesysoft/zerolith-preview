<?php
require("zerolith/zl_init.php");  //Load framework. Creates the zl object.
zl::setDebugDisplay(3); //Set any zerolith options needed.

//subfolders that scripts are in
$subFolders = ['admin','manager','user'];
$defaultScript = "home.php";
$routeOverrides = ['badpage' => "badpage.php"];

//begin routing
$url = explode("/", zfilter::URL(parse_url($_SERVER['REQUEST_URI'])['path']));
array_shift($url);

if(zs::isBlank($url)) { $controller = $defaultScript; }
else {  } //remove initial segment



$controller = zfilter::URL($url[0]);

if($controller == "") { $controller = "home"; }
$controllerPath = "scripts/" . $controller . ".php";
if(!file_exists($controllerPath)) { zl::fault("Invalid Page: " . zfilter::stringSafe($controller)); }

//add automatic path to variable


echo "resulting path: " . $controllerPath;
require($controllerPath); //load controller