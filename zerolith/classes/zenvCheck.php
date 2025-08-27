<?php
class zenvCheck
{
    //check to see if the environment is suitable for running zerolith.
    public static function zlInit()
    {
		ztime::startTimer("zl_envcheck");
        //sanity checks for compatible environment.
	    if(!extension_loaded('mbstring')) { self::fault("we're missing the mbstring extension."); }
        if(!extension_loaded('mysqli')) { self::fault("we're missing the mysqli extension."); }

        //check required paths
        if(!self::canRWpath(zl::$site['pathZerolithData'] . "ztests/")) { self::fault("ztest folder isn't read+writable."); }
        if(!self::canRWpath(zl::$site['pathZerolithData'] . "lockFiles/")) { self::fault("lockFiles folder isn't read+writable."); }
        if(!self::canRWpath(zl::$site['pathZerolithData'] . "cache/")) { self::fault("cache folder isn't read+writable."); }

        //zcache checks are in classes/zcache.php

        //check for valid config file - to implement down the line when mature
	    ztime::stopTimer("zl_envcheck");
    }

    //check external dependencies, like memcached
    public static function external($externalDependencyName)
    {
		
    }

    private static function fault($msg) 
    { 
		$msg = "zerolith environment check error: " . $msg;  
		zl::fault($msg, $msg);  //break
		//zl::terminate(); //just in case
	}
    private static function canRWpath($path) { return (is_readable($path) && is_writable($path)); }
}

//run the initialization
zenvCheck::zlInit();