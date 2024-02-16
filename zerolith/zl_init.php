<?php
//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// Framework initialization file
// Note: pre-initialization constant must be set zl_mode = prod/dev/stage ( determines mode )

//check if ZL is running before initializing; otherwise don't do anything
if(!class_exists("zl", false) || isset(zl::$alive) && !zl::$alive)
{
	//get initialization statistics as early as possible for accuracy.
	$zl_initMem = memory_get_usage();
	$zl_initTimer = array("total" => 0, "events" => 0, "time" => microtime(true));

	//This file is in the root path for zerolith. How convenient!
	define("zl_frameworkPath", __dir__ . "/");
	
	//if we haven't ran a require() on the a custom configuration file before now, load the default one.
	if(!isset($zl_set)) { require(zl_frameworkPath . "../zl_config.php"); }
	
	//if a file needs to be loaded before ZL. Note: this gets counted in the zl_init total time
	if(isset($zl_set['requireBeforeInit']) && $zl_set['requireBeforeInit'] != ""){ require_once($zl_set['requireBeforeInit']); }
	
	require(zl_frameworkPath . "/classes/zl.php"); //load ZL god object
	unset($zl_set, $zl_page, $zl_site, $zl_initTimer, $zl_initMem); //cleanup from init so we have a clean global space.
	
	//After-ZL load; for example an authentication script, bootloader script, or your application's god object.
	if(isset(zl::$set['requireAfterInit']) && zl::$set['requireAfterInit'] != ""){ require_once(zl::$set['requireAfterInit']); }
	
	//if auto turned on, route ZLHX. This effectively will skip the execution of the remainder of the rest of the script.
	if(zl::$set['routeZLHXauto']) { zl::routeZLHX(); }
	//print_r(zl::$set);
}