<?php
//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// Framework initialization file
//
// Constants that you can send to zerolith ahead of loading zl_init.php to force things:
// zl_mode = dev/prod/stage; if set, will force the mode regardless of logic in zl_config.php
// zl_pathZerolith = example: /var/www/zerolith; if set, will force zerolith to read a different framework path
// zl_pathZerolithData = example: /var/www/zerolithData; if set, will force zerolith to read a different data path 
// zl_pathRoot = example: /var/www/; if set, will zerolith to use a different app root path
// You can also load a custom zl_config.php before running zl_init.php to override it

if(!class_exists("zl", false) || isset(zl::$alive) && !zl::$alive) //check if ZL is running already; otherwise don't do anything
{
	//get initialization statistics early for accuracy.
	$zl_initMem = memory_get_usage();
	$zl_initTimer = microtime(true);
	
	//establish framework paths automatically, unless the user specified one before load
	if(!defined("zl_pathZerolith"))     { define("zl_pathZerolith", __dir__ . "/"); } //Happens to be where we are!
	if(!defined('zl_pathRoot'))         { define('zl_pathRoot', substr(zl_pathZerolith, 0, -9)); } //1 below /zerolith
	if(!defined("zl_pathZerolithData")) { define("zl_pathZerolithData", zl_pathRoot . 'zerolithData/'); }
	
	//load the configuration file, if it's not been loaded ahead of init
	if(!isset($zl_set))                 { require(zl_pathZerolithData . "zl_config.php"); }
	
	//does a file needs to be loaded before ZL is loaded?
	if(isset($zl_set['requireBeforeInit']) && $zl_set['requireBeforeInit'] != "")
	{
		$zl_beforeTimer = microtime(true);
		require_once($zl_set['requireBeforeInit']);
		$zl_beforeTimer = floatval(microtime(true) - $zl_beforeTimer);
	}
	else{ $zl_beforeTimer = ""; }

	require(zl_pathZerolith . "classes/zl.php"); //load ZL god object
	unset($zl_set, $zl_page, $zl_site, $zl_initTimer, $zl_initMem, $zl_beforeTimer); //cleanup so we have a clean global space.
	
	//After ZL load; for example an authentication script, bootloader script, or your application's god object.
	if(isset(zl::$set['requireAfterInit']) && zl::$set['requireAfterInit'] != "")
	{
		$zl_afterTimer = microtime(true);
		require_once(zl::$set['requireAfterInit']);
		ztime::injectAfterTimer(floatval(microtime(true) - $zl_afterTimer));
		unset($zl_afterTimer);
	}
	
	//if auto ZLHX routing turned on, route ZLHX. This may execute before your script.
	if(zl::$set['routeZLHXauto']) { zl::routeZLHX(); }
}

//and at this point, we return control flow to whatever script loaded this file.