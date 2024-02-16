<?php
define("zl_version", 1.17);
//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// Fast, Low abstraction, non-PSR, non-MVC framework for people who hate frameworks.
//
// "Abstraction trades an increase in real complexity for a decrease in
//  perceived complexity. That isn't always a win." - John Carmack
//
// "Best weapon against complexity spirit demon is magic word: 'no'" - Grug
//
// "An idiot admires complexity, a genius admires simplicity" - Terry Davis
//
// "The problem with object-oriented languages is they've got all this implicit
// environment that they carry around with them. You wanted a banana but what
// you got was a gorilla holding the banana and the entire jungle." - Joe Armstrong
//
// If the implementation is hard to explain, it's a bad idea.
// If the implementation is easy to explain, it may be a good idea. - Tim Peters ( Zen of Python )

// Todo for v1.18:
// Remove $htmxIncludeSelector/$htmxGenerateTRIDField from zPTA
// refactor zui.css and zui:: to refer to zui_ tags instead of zlt_
// add multibyte compatible mode ( warning: giga performance hit )
// Relative paths in CSS.JS includes - possible so ZL can easily run in a subfolder?

// Todo for v1.25:
// Hook up zvalid to ZUI
// Verify accurate debug/timing in zcurl
// Add code/script/injection detection option into zfilter::HTML
// Finish Parameterized database reads

// Todo for v1.3 ( debugger enhancement + prep for the gauntlet ):
// Debugger remembers open/closed state via sessions
// Auto-scroll debug tab to the bottom
// Compact debug tab format as much as possible, wrap individual div tags per library to enable JS Filtering
// Add JS Filtering so user can toggle debugger settings across sessions
// File cache for getCodeSnippet() ( performance )
// Use zstd extension instead of gzip to write debug dumps if available ( faster )

// Todo for v1.35:
// Full Testing
// Full Documentation

// Far future:
// Rapid application development system

// Todo Pre-Public Release:
// File Layout Proposals
//   Why?
//     * Upgrades can become "remove /zerolith/" then "add new /zerolith/"
//     * Stops pollution of /application/ folder.
//
//   application/
//     zerolith/
//       ...
//       zinternal/
//     zdata/
//       zl_config.php
//       zl_theme.css
//       lockFiles
//       cache/
//       tests/

class zl
{
	public static $set = [];                    //various ZL settings.
	public static $page = [];                   //page settings ( for zpage ).
	public static $site = [];                   //site settings ( company names, emails, addresses, etc ).

	public static $initMem = "";                //initial memory used - for later reference.
	public static $alive = false;               //prevent respawn.
	public static $debugAvailable = true;       //set to false to force debugging off even if the mode is something other than 0
	public static $isHTMXrequest = false;       //track HTMX calls incoming at boot
	public static $zlhxLastFunc = "";           //reference for the most recent zlhx function called.
	public static $envInsideApp = "";           //is ZL running in a compatible host application? if so, which?
	public static $envSideApp = "";             //is ZL side loaded on an existing application? if so, which?
	
	private static $user = [];                  //Standardized user object from host application
	private static $extAutoloaders = false;     //marker for hijacking autoloading in other frameworks.
	private static $deBuffer = "";              //rolling debug messages buffer.
	private static $deBufferQuip = "";          //but specifically for quips.
	private static $deBufferZmail = "";         //but specifically for zmail.
	private static $debugErrors = 0;            //track of total errors reported to debugger.
	private static $PHPwarnings = "";           //Internal php warning log for debugger
	private static $shutdownRegistered = false; //Because PHP doesn't have unregister_shutdown_handler :(
	private static $zlhxInit = false;           //flag for zlhx::zlhxInit() so we don't run it twice.
	private static $zlhxRouted = false;         //flag that prevents zl::routeZLHX() from running twice.

	//debug voices
	private static $voiceProgram = ['libraryName' => "program", 'micon' => 'code', 'textClass' => "zl_white", 'bgClass' => "zl_bgGrey9"];
    private static $voiceZL = ['libraryName' => "zl", 'micon' => 'auto_fix_high', 'textClass' => "zl_black", 'bgClass' => "zl_bgGrey4"];

	//bootup time.
	public static function init(array $zl_set, array $zl_page, array $zl_site, $zl_initTimer, $zl_initMem)
	{
		self::$initMem = $zl_initMem;                   //initialization memory tracker
		self::$alive = true;                            //for tracking/preventing accidental double-load.
		
		require(zl_frameworkPath.'classes/ztime.php');  //we always need these so load it faster than the autoloader can get to it.
		require(zl_frameworkPath.'classes/zs.php');

		//screech if mode not explicitly set as a const.
		if(!defined('zl_mode')) { exit("ZL mode undefined.<br><b>Can't initialize.</b>"); }

		//Dev mode force explicit errors in PHP engine
		if(zl_mode == "dev")
		{
			error_reporting(-1);
			ini_set('display_startup_errors',1);
			ini_set('display_errors',1);
		}

		//Straight copy arrays from config file/zl_init.php.
		self::$set = $zl_set; self::$page = $zl_page; self::$site = $zl_site;

		self::$extAutoloaders = boolval(spl_autoload_functions()); //are we hijacking another framework (autoload functions already exist)?
		spl_autoload_register('zl::autoloader'); //register autoloader.

		if(!self::$extAutoloaders) //if we are not in a host framework.
		{
			self::$shutdownRegistered = true;
			
			//zerolith termination handler ( enables debug, zpage, etc )
			register_shutdown_function('zl::terminate', "shutdown");
			//catch PHP warnings/notices - comment out if causing dysfunction
			set_error_handler('zl::phpWarningCollector');
			//turn off error reporting in non-development modes
			//if(zl_mode != 'dev') { error_reporting(0); }
		}
		else //detect various environments
		{
			if(defined('WPINC')) { self::$envInsideApp = "WP"; }
		}
		
		//don't attach exception handler if debugger off.
		if(self::$set['debugger']) { set_exception_handler('zl::terminate'); }

		ztime::injectInitTimer($zl_initTimer); //inject timer from start of zl_init.php; init done.
	}

	//automatic routing of HTMX calls to the magic class zlhx::
	public static function routeZLHX()
	{
		//prevent double calls
		if(!self::$zlhxRouted) { self::$zlhxRouted = true; } else { return; }
		
		//prereqs
		self::$isHTMXrequest = isset($_SERVER['HTTP_HX_REQUEST']); //HTMX sets this header value
		if(self::$isHTMXrequest)
		{
			//disable zpage but keep debugger output
			zl::setOutFormat('html');
			if(zl_mode == "dev") { self::setDebugLevel(3); }
		}
		else { return; } //no work to do
		
		//route htmx?
		if(class_exists('zlhx', false))
		{
			//grab input for this globally
			extract(zfilter::array("hxfunc", "page"));
			
			//valid hxfunc?
			if($hxfunc != "")
			{
				self::$zlhxLastFunc = $hxfunc; //for debug reporting and other fun uses
				
				//does the hxfunc match a call, and it isn't one of the inbuilt calls?
				if(method_exists("zlhx", $hxfunc) && $hxfunc != "zlhxInit" && $hxfunc != "zlhxBefore" && $hxfunc != "zlhxAfter")
				{
					if(zl_mode == "dev") { self::setDebugLevel(3); } //force debugging output on for htmx calls
					
					//run init the first time if it exists in the user typed magic class
					if(!zl::$zlhxInit && method_exists("zlhx", "zlhxInit")) { zlhx::zlhxInit(); zl::$zlhxInit = true; }
					
					//finally, run the actual request(s).
					if(method_exists("zlhx", "zlhxBefore")) { zlhx::zlhxBefore(); } //before each call
					zlhx::$hxfunc(); //execute the passed hxfunc
					if(method_exists("zlhx", "zlhxAfter")) { zlhx::zlhxAfter(); } //after each call
					zl::terminate(); //and then die
				}
				else { zl::faultAbuse("Incorrect HTMX function sent"); } //app vuln probing or dev mistake?
			}
		}
	}

	//Show ZL Version
	public static function getZLVer()
	{
		if(zl::$set['debugLog']) { $dw = " debugLog: on "; } else { $dw = " debugLog: off "; }
		if(zl::$set['debugLogOnFault']) { $dw .= " debugLogOnFault: on "; } else { $dw .= " debugLogOnFault: off "; }
		if(zl::$set['debugLogOnWarn']) { $dw .= " debugLogOnWarn: on "; } else { $dw .= " debugLogOnWarn: off "; }
		
		return "Zerolith v" . zl_version . " (mode: " . zl_mode . " debug lvl: " . zl::$set['debugLevel'] . $dw . ")";
	}

	//setters 'n shortcuts
	public static function setOutFormat($format)
	{
		if($format != 'page' && $format != 'html' && $format != 'api'){ zl::fault("ZL: Invalid outFormat set, cannot proceed."); }
		self::$set['outFormat'] = $format;
	}
	public static function setDebugLevel(int $debugLvl = 0)
	{
		zl::quipDZL("ZL Debug level set to " . $debugLvl);
		self::$set['debugLevel'] = intval($debugLvl);
	}

	//write debug logs for a script regardless of successful / failed execution
	public static function setDebugLog(bool $logging = false) { self::$set['debugLog'] = $logging; }
	
	//--- The following exit functions execute at the end of the zl::terminate() routine and should be set in zl::$set['envExitFunc']
	
	//Use this inside Wordpress, where ZL is most likely stuck in an exec() environment.
	//You must always use zl::terminate() in this case to stop execution, otherwise control will be returned to wordpress while
	//ZL's shutdown handler/autoloader/error trap is present
	public static function exitWP($fault = false, $userMsg = "")
	{
		self::quipDZL("wordpress exiting");
		if(!$fault) { return; } //return gracefully
		else { echo file_get_contents("/var/www/zerolith/zl_internal/cache/wpFoot.html"); exit; } //dump the footer to screen.
	}

	//This mode is designed to draw a fake wordpress page ( header and footer )
	//This output handler outputs a fake wordPress footer ( get it from wpscrape.php ) after zl::terminate or natural end of script.
	//In this mode, you need to draw the top of the page manually
	public static function exitFauxpress($fault = false, $userMsg = "")
	{
		self::quipDZL("fauxpress exiting and outputting WP footer.");
		echo file_get_contents("/var/www/zerolith/zl_internal/cache/wpFoot.html"); exit; //dump the footer to screen.
	}

	//not tested & incomplete:
	public static function exitFauxforo($fault = false, $userMsg = "")
	{
		self::quipDZL("Xenforo exiting and outputting XF footer.");
		echo file_get_contents("/var/www/zerolith/zl_internal/cache/xenFoot.html"); exit; //dump the footer to screen.
	}

	//For API mode: exit or produce a generic API error.
	public static function exitApi($fault = false, $userMsg = "")
	{
		if(!$fault) { exit; } else { echo "[[API Error]] " . $userMsg; exit; }
	}

	//class autoloader
	public static function autoloader($class)
	{
		ztime::startTimer("zl_autoload");
		if($class == "PHPMailer") { return; } //exception for internal phpmailer.
		
		//speed hack - try zerolith class first if the first letter is z. This is worth a 50% perf improvement, lol
		//side note: performance-wise, doing file_exists() is 2x faster than failing an include()
		if($class[0] == "z")
		{
			if(file_exists(zl_frameworkPath . 'classes/' . $class . '.php')) //load zerolith class first.
		    { include(zl_frameworkPath . 'classes/' . $class . '.php'); }
			else if(file_exists(self::$site['filerootClasses'] . $class . '.php')) //try application class next.
		    { include( self::$site['filerootClasses'] . $class . '.php'); }
		    else if(file_exists( $class . '.php')) //load file next.
		    { include($class . '.php'); }
		    else //classname/classname.php; replace this with PSR loader.
		    {
		        if(file_exists(self::$site['filerootClasses'] . $class . "/" . $class . ".php"))
		        { include(self::$site['filerootClasses'] . $class . "/" . $class . ".php"); }
		        else { self::fault("Autoloader says: class [" . $class . "] not found"); }
		    }
		}
		else
		{
			if(file_exists(self::$site['filerootClasses'] . $class . '.php')) //try application class first..
		    { include( self::$site['filerootClasses'] . $class . '.php'); }
		    else if(file_exists(zl_frameworkPath . 'classes/' . $class . '.php')) //load zerolith class next.
		    { include(zl_frameworkPath . 'classes/' . $class . '.php'); }
		    else if(file_exists( $class . '.php')) //load file next.
		    { include($class . '.php'); }
		    else //classname/classname.php; replace this with PSR loader.
		    {
		        if(file_exists(self::$site['filerootClasses'] . $class . "/" . $class . ".php"))
		        { include(self::$site['filerootClasses'] . $class . "/" . $class . ".php"); }
		        else { self::fault("Autoloader says: class [" . $class . "] not found"); }
		    }
		}
				
		ztime::stopTimer("zl_autoload");
	}

	// --------------- Talking functions --------------- //

	//Send a message to the debug log from your program.
	public static function quipD($message, $regarding = "?")
	{
		if(!self::$set['debugger']) { return; } //if debugger is absolutely off, forget accumulating this data

		$debugObj = self::$voiceProgram;
		$debugObj['callData'] = debug_backtrace(0,1)[0];
		$debugObj['out'] = $message; //no output for quip
		$debugObj['success'] = "?";
		$debugObj['faultData'] = "";
		$debugObj['time'] = "";
		$debugObj['isQuip'] = true;
		if($regarding != "?") { $debugObj['data'] = "RE: " . $regarding; }
		self::deBuffer($debugObj);
	}

	//Send a message to the debug log from the framework.
	public static function quipDZL($message, $regarding = "?")
	{
		//only present in loud mode or debug logging.
		if(self::$set['debugLevel'] == 4 && self::$set['debugger']) //if debugger is absolutely off, forget accumulating this data)
		{
			$debugObj = self::$voiceZL;
			$debugObj['callData'] = debug_backtrace(0,2)[1];
			$debugObj['out'] = $message; //no output for quip
			$debugObj['success'] = "?";
			$debugObj['faultData'] = "";
			$debugObj['time'] = "";
			$debugObj['isQuip'] = true;
			if($regarding != "?") { $debugObj['data'] = "Regarding: " . $regarding; }
			self::deBuffer($debugObj);
		}
	}

	// --------------- Termination --------------- //

	//Specify a below type of fault so that zl bug log/abuse system knows why.

	//hard fault - logs debug and terminates your program
	public static function fault($userMsg = "(generic fault)") { self::terminate("program", "", $userMsg); }

	//tipoff that the system is being abused badly.
	public static function faultAbuse($userMsg = "(abuse)") { zkarma::bad($userMsg, true); self::terminate("program", "", $userMsg, "abuse"); }

	//actually the user's fault
	public static function faultUser($userMsg = "(user fault)") { self::terminate("program", "", $userMsg); }

	//just note the bug and move on
	public static function faultSoft($userMsg = "(soft fault)") { self::logSoftFault($userMsg); }

	//Terminate the program ( runs through the debug, performance report, and custom exit handler if applicable ).
	//Terminate types: graceful (intentional), shutdown (script end/exit), program (program error), php (interpreter error)
	public static function terminate($type = "graceful", $extraTabs = "", $userMsg = "", $subType = "")
	{
		//ignore termination and continue (running test system)
		if(defined('zl_terminate_ignore')) return;
		//detect fatal core PHP error ( PHP sends this as a graceful shutdown - bad design )
		if($type == "shutdown")
		{
			$lastError = error_get_last();
			//only trigger this on actual error codes
			if(is_array($lastError) && isset($lastError['message']) && in_array($lastError['type'], [1,4,16,64])) { $type = $lastError; }
		}
		
		//if $type is an object, it's an exceptionObject sent from set_exception_handler.
		if(is_object($type) || is_array($type)) { $exceptionObject = $type; $type = "php"; } else { $exceptionObject = ""; }
		
		//echo "<br>";
		//echo "exceptionobject: " . print_r($exceptionObject, true) . "<br>";
		//echo "envExitFunc: " . zl::$set['envExitFunc'] . "<br>"; //<-- if setting are effed up
		//echo "extAutoloaders: " . print_r(self::$extAutoloaders, true) . "<br>";
		//echo "alive: " . self::$alive . "<br>";
		//echo "envInsideApp: " . self::$envInsideApp . "<br>";
		//echo "termination type: " . $type . "<br>";
		//echo "shutdownregistered: " . self::$shutdownRegistered . "<br>";
		
		//prevent double termination/shutdown
		if($type == "graceful" && !self::$shutdownRegistered && self::$envInsideApp == ""){ exit; }
		if(!self::$alive) { exit(); } else { self::$alive = false; }
		
		//force api exit handler to be used for this mode
		if(zl::$set['outFormat'] == 'api' && zl::$set['envExitFunc'] == "") { zl::$set['envExitFunc'] = "zl::exitApi"; }
		if($type != "graceful" && $type != "shutdown") { $fault = true; zpage::start("System error"); }  //attempt to start page
		else { $fault = false; }

		zsys::flushLocks(); //flush known file locks immediately.
		zl::quipDZL("Terminating [" . self::$set['debugLevel']. "][" . $type . "]" . zs::pr($fault));
		
		//Failed page generation.
		if($fault)
		{
			//zkarma::bad($userMsg); //it's a tiny bit bad that the user is doing this.

			//be expressive to browsers
			if($fault && !headers_sent() && zl_mode != "dev") { @header('HTTP/1.1 500 Internal Server Error'); }

			if(zl::$set['outFormat'] != 'api')
			{
				//Error message shown to user. Make this cuter in the future.
				if($userMsg != ""){ zui::quip($userMsg, "Page error", "pest_control"); }
				else
				{
					if(zl_mode == "dev") //if in dev mode, show the goods
					{ $ex = "\nPHP Exception:\n" . zstr::sanitizeHTML(print_r($exceptionObject, true)); }
					else { $ex = ""; } //show generic message to normies
                    ?>></select></a></label><?php //attempt to close existing tags
					zui::quip("The server had an error generating this page. [" . $type . "]\n" . $ex, "System Error", "pest_control");
				}
			}
		}
		
		//release error handlers at this point so if there's issues generating the debug/writing it, they'll show.
		//not currently working ( zl calls fault and exits instead of intercepting the error and displaying it )
		if(!self::$extAutoloaders) //if we are not in a host framework.
		{
			restore_error_handler();
			self::$shutdownRegistered = false; //virtually turn off the error handler
		}
		if(self::$set['debugger']) { restore_exception_handler(); }
		
		//shall we print debug data, write it, or both?
		if(zl::$set['outFormat'] != 'api' || self::$set['debugLog'] || self::$set['debugLogOnFault'] && $fault || self::$set['debugLevel'] != 0)
		{
			//extra tabs to add to the debugger.
			if(zs::isBlank($extraTabs) || !is_array($extraTabs)) { $extraTabs = []; }
			if(self::$deBufferZmail != "" ) { $extraTabs['ZMail'] = self::$deBufferZmail; }
			$extraTabs['Debug'] = self::$deBuffer;
			if(self::$deBufferQuip != "" ) { $extraTabs['Quip'] = self::$deBufferQuip; }
			
			if(self::$set['debugLevel'] != 0 && self::$set['debugFlushOB']){ @ob_flush(); }
			
			//let debugger() make the decision to log, show, etc.
			if(self::$debugAvailable) { self::debugger($exceptionObject, $extraTabs, $fault, $userMsg, $subType); }
		}
		
		zpage::end(); //attempt to end the page, if started.
		
		//unregister ZL autoloader so that host framework doesn't notice.
		if(self::$extAutoloaders)
		{
			foreach(spl_autoload_functions() as $loader)
			{
				if(is_array($loader))
				{ if($loader[0] == "zl" && $loader[1] == "autoloader") { spl_autoload_unregister([$loader[0], $loader[1]]); } }
			}
		}
		
		//call the custom exit function if we made it this far.
		if(self::$set['envExitFunc'] != "") { call_user_func(self::$set['envExitFunc'], $fault, $userMsg); }
		else { exit(); } //the final gasp.
	}

	
	// --------------- Debug/Error handling --------------- //

	
	//record a soft fault ( different than logging on termination )
	//not currently used
	public static function logSoftFault($message = "", $source = "php")
	{
		if(!self::$set['logSoftFault']) { return; } //don't log if turned off.

		//form the debug report.
		$WA = ['errorText' => $message, 'errorType' => $source];
		$authData = zauth::getUser(true);
		$WA['visitURL'] = $authData['visitURL'];     $WA['visitIP'] = $authData['visitIP'];
		$WA['visitHost'] = $authData['visitHost'];   $WA['visitAgent'] = $authData['visitAgent'];
		$WA['visitInput'] = $authData['visitInput']; $WA['userID'] = $authData['userID'];
		$WA['userName'] = $authData['userName'];     $WA['userType'] = $authData['userType'];
		zdb::writeRow("INSERT", "zl_debug", $writeArray);
	}

	//pass a debug object from a library to the global debug buffer.
	public static function deBuffer(array $debugObj)
	{
		if(!self::$set['debugger']) { return; } //if debugger is absolutely off, forget accumulating this data
		ztime::startTimer("zl_debug_debuffer");

		if(isset($debugObj['isQuip'])) { $callData = self::formatBacktrace($debugObj['callData'], true); }
		else { $callData = self::formatBacktrace($debugObj['callData']); }

		//start the debug printout
		ob_start();
		?>
		<div class="<?=$debugObj['textClass']?> <?=$debugObj['bgClass']?> zl_w100p zl_flow-root">
		<span class="zl_left zl_padLR1"><pre><?=zui::miconR($debugObj['micon'])?> <?=$callData['callLine']?></pre></span>
		<span class="zl_right zl_padLR1"><pre><?=$debugObj['time']?><?php
		if($debugObj['success']) { echo " OK"; }
		else { echo ' <span class= "zl_white zl_bgRed10"><b>ERR</b></span>'; self::$debugErrors++; }
		?></pre></span></div><?php

		//show the input (function call) if exists.
		if(isset($callData['call']) && $callData['call'] != "")
		{ ?><div class="zl_w100p zl_padLR1"><pre><b>&gt;</b> <?=zui::readMore($callData['call'])?></pre></div><?php }

		//show sub-data ( addl. debugging info from the class, etc )
		if(isset($debugObj['data']) && $debugObj['data'] != "")
		{ ?><div class="zl_w100p zl_padLR1 zl_blue9"><pre><b>&lt;</b> <?=zui::readMore(zstr::sanitizeHTML(zs::pr($debugObj['data'])))?></pre></div><?php }

		//show output? not relevant if not set..
		//note: in PHP 7.2, float(0) is considered == "", hence the latter || condition
		if(isset($debugObj['out']) && $debugObj['out'] != "" || isset($debugObj['out']) && is_float($debugObj['out']))
		{
			?><div class="zl_w100p zl_padLR1"><pre><b>&lt;</b> <?=zui::readMore(zstr::sanitizeHTML(zs::pr($debugObj['out'])))?></pre></div><?php }
			else{ ?><div class="zl_w100p zl_padLR1"><pre><b>&lt;</b> <span class="zl_grey6"><?=var_dump($debugObj['out'])?>[blank]</span></pre></div><?php
		}

		//show error?
		if(isset($debugObj['faultData']) && $debugObj['faultData'] != "")
		{ ?><div class="zl_red10 zl_bgRed1 zl_w100p zl_padLR1"><pre><?=zui::miconR("pest_control")?> <b>Error!</b> data:<br><?=zstr::sanitizeHTML(zs::pr($debugObj['faultData']))?></pre></div><?php }

		echo "<pre>\n\n</pre>";
		
		if($debugObj['libraryName'] == 'program' || $debugObj['libraryName'] == 'zl') //quips to it's own buffer
		{
			$buf = ob_get_clean();
			self::$deBuffer .= $buf;
			
			//co-hack with ZUI to make these differentiable since hte IDs duplicate. Slow; optimize some day 02/01/2024 - DS
			$buf = str_replace(zui::$lastIDreadMore, zui::$lastIDreadMore . "_qbuf", $buf);
			self::$deBufferQuip .= $buf;
		}
		else if($debugObj['libraryName'] == 'zmail') //zmail to it's own buffer
		{
			$buf = ob_get_clean();
			self::$deBuffer .= $buf;
			self::$deBufferZmail .= $buf;
		}
		else { self::$deBuffer .= ob_get_clean(); }
		ztime::stopTimer("zl_debug_debuffer");
	}

	//Show collected details of the running script.
	//$exceptionObject is passed via zl::terminate(), $extraTabs is passed via zl::terminate()
	private static function debugger($exceptionObject = "", $extraTabs = "", $fault = false, $faultReason = "", $subType = "")
	{
		//because static variables are slow to access, and we're about to access these a ton, we make a non-static copy
		$debugLevel = self::$set['debugLevel'];
		if(self::$set['debugLogOnFault'] && $fault || self::$set['debugLog']) { $debugLog = true; } else { $debugLog = false; }
		
		if($fault) { self::$debugErrors++; } //honorary error in the error count.
		
		//do not compile this data if we're at a 0 debug mode ( used for production w/o supervision )
		if($debugLog || $debugLevel != 0)
	    {
			$zl_debugMem = memory_get_usage();
			ztime::startTimer("zl_debug_report");
			$tabs = []; //debugger tabs to later display.

		    if($fault)
		    {
			    //get trace data first ( pass $exceptionObject if available )
			    $trace = self::getBacktrace($exceptionObject);
				if(!zs::isBlank($trace)) { $tabs['Trace'] = "<pre>" . $trace . "</pre>"; }
				unset($trace);
		    }

			//PHP warning log
		    if(self::$PHPwarnings != "") { $tabs['PHP Warn'] = "<pre>" . self::$PHPwarnings . "</pre>"; }

			//forward extra tabs inputted at start of function.
		    $tabs = array_merge($tabs, $extraTabs);
			
			//fire it up? Y/N
		    if(self::$set['debugger'] || $debugLog)
		    {
				//get a dump of all defined variables.
				$PHPVars = [];
				$userVars = [];

				if(self::$set['debugAllVars'])
				{
					$globalsArray = $GLOBALS;        //this is a huge copy..
					unset($globalsArray['GLOBALS']); //this will cause a recursive memory blowout if not removed.
				}
				else
				{
					$globalsArray = ['zl_debugger_message' => 'User variables aren`t available in this mode.', '_SERVER' => $_SERVER, '_FILES' => $_FILES, '_GET' => $_GET, '_COOKIE' => $_COOKIE, '_POST' => $_POST];
				}

				//this alone is ~33% of the debugger's time so that we can get an alphabetized list.
				ksort($globalsArray);

				//blacklist some PHP variables from being seen according to app settings.
			    $hideVars = ['zdb', 'auth', 'db', 'DATABASE']; //common application goofups
		        $hideVars = array_merge($hideVars, zarr::toArray(zl::$set['debugHideGlobals']));

				//whitelist for $_SERVER; censor all variables except these.
				$SERVERWhitelist = array('HTTP_HOST', 'REQUEST_URI', 'SCRIPT_URI', 'SCRIPT_NAME', 'REDIRECT_SCRIPT_URL', 'REDIRECT_SCRIPT_URI', 'REQUEST_METHOD', 'QUERY_STRING', 'REQUEST_TIME', 'HTTP_REFERER', 'SERVER_ADDR', 'REMOTE_ADDR');
				
	            foreach($globalsArray as $key => $value)
	            {
	                if(!in_array($key, $hideVars))
	                {
	                    $varText = "";
	                    if(zs::isBlank($value)) { $varText .= '<span class="zl_grey6"><b>$' . $key . ': []</b></span>' . "\n"; } //blank entry.
	                    else if($key == "_SERVER") //special _SERVER printout.
	                    {
							if($debugLevel > 1)
							{
		                        $varText .= '<b>$_SERVER</b>' . ":\nArray\n(\n";
		                        foreach($globalsArray['_SERVER'] as $Skey => $Svalue)
		                        {
		                            if(in_array($Skey, $SERVERWhitelist))
		                            {
		                                $varText .= "   " . zstr::shorten(zstr::sanitizeHTML($Skey), self::$set['debugArgVarLimit']) . ": " . zstr::shorten(zstr::sanitizeHTML($Svalue), self::$set['debugArgVarLimit']). "\n";
		                            }
		                        }
		                        $varText .= ")\n";
							}
	                    }
	                    else
	                    {
	                        //default printout
	                        if(is_object($value) || is_array($value))
							{ $varText .= '<b>$' . $key. "</b>:\n" . zstr::shorten(zstr::sanitizeHTML(zs::pr($value), true), self::$set['debugArgVarLimit']) . "\n"; }
							else
							{ $varText .= '<b>$' . $key. ": [</b>" . zstr::shorten(zstr::sanitizeHTML(zs::pr($value), true), self::$set['debugArgVarLimit']) . "<b>]</b>\n"; }
	                    }

	                    //which array do we go into?
	                    if(($debugLog || $debugLevel < 3) && $key == '_SESSION') { $userVars[] = $varText; } //hide _SESSION if = 1
	                    elseif(strpos($key, "_") === 0) { $PHPVars[] = $varText; }
	                    else { $userVars[] = $varText; }
	                }
	            }
				unset($globalsArray);

	            if(($debugLog || $debugLevel > 1))
	            {
	                $tabs['Global var'] = "<pre>";
	                foreach($userVars as $userVariable){ $tabs['Global var'] .= $userVariable; }
					$tabs['Global var'] .= "</pre>";
		        }

	            if(($debugLog || $debugLevel != 0))
		        {
	                $tabs['PHP var'] = "<pre>";
	                foreach($PHPVars as $PHPVariable){ $tabs['PHP var'] .= $PHPVariable; }
					$tabs['PHP var'] .= "</pre>";
					unset($PHPVars);
	            }
				unset($userVars);
		    }

			//get defined stuff.
	        if($debugLog || $debugLevel > 2)
	        {
	            $tabs['Defines'] = "<pre><b>Includes (sequential):</b>\n" . print_r(get_included_files(), true) . "</pre>";
	            $tabs['Defines'] .= "<pre><b>Defined functions:</b>\n" . print_r(get_defined_functions(true)['user'], true) . "</pre>";

				if(!$debugLog && $debugLevel == 4)
				{
					$tabs['Defines'] .= "\n\n<pre><b>Defined constants:</b>\n" . zstr::sanitizeHTML(self::censorPR(get_defined_constants(true)['user'])) . "</pre>";
					//ZL settings
					$tabs['ZL Config'] = '<pre><b>zl::$set:</b><br>' . self::censorPR(self::$set) . '</pre><br><pre><b>zl::$site:</b><br>' . self::censorPR(self::$site) . '</pre><br><pre><b>zl::$page:</b><br>' . zs::pr(self::$page) . "</pre>";
				}
	        }
			
			//Determine icon for debug bar.
			if($fault) { $dIcon = "pest_control"; }
			else
			{
				if(zl::$deBuffer == "") { $dIcon = "chat_bubble_outline"; } else { $dIcon = "chat"; }
			}

			//position hack for htmx requests
			if(self::$isHTMXrequest)
			{
				$positionHack = 'style="left:' . (200 + rand(0,1000)) . 'px"';
				$hxBarClass = " zd_hxDebug"; $dIcon = "cloud";
			}
			else { $positionHack = ""; $hxBarClass = ""; }
			
			//overrides all existing icons because mail is the importantest
			if(self::$deBufferZmail != "") { $dIcon = "email"; }
			
			//compose debugbar output.
		    $serial = "_" . zsys::getTimeSerial();
		    $temp = ztime::reportPerf(memory_get_usage() - $zl_debugMem); //as late as we can do this for an accuracy.
		    $tabs['Perf'] = $temp['report'];
			$zlTime = str_replace(" ","",ztime::secsToUnits($temp['time']));
			
			$script = "";
			//if zlhx, let's print the zlhx function we called
			if(zl::$isHTMXrequest && self::$zlhxLastFunc != "")
			{
				$hxBarClass .= " " . self::$zlhxLastFunc; //tack on class name
				$title = "d<small>" . $debugLevel . " " . self::$zlhxLastFunc . "()</small>";
				
				zui::bufStart();
				?>
				<script>
					{
						//remove all other instances of the same zlhx function's output
						let currentID = "zd_debugBar<?=$serial?>";
						let hxOnScreen = zl.getSelectors(".<?=self::$zlhxLastFunc?>");
						let c = hxOnScreen.length;
						for(let i = 0; i < c; i++)
						{
							if(hxOnScreen[i].id != currentID) { zl_IDdelete( hxOnScreen[i].id); }
						}
					}
				</script>
				<?php
				$script = zui::bufStop();
			}
			else { $title = "d<small>" . $debugLevel . "</small>"; }
			
			$htmlHead =
			"\n<table class='zlt_table zd_debugBar$hxBarClass' $positionHack id='zd_debugBar$serial'><tr><th id ='zd_debugBarHeader$serial' class='zd_debugBarHeader'>" .
			zui::miconR($dIcon) . " " . ucfirst($title) . " &nbsp;<small>" . $zlTime . "</small> &nbsp;<div class='zl_right'>" .
			zui::windowActionR("min", "zd_debugContent$serial") .
			zui::windowActionR("max", "zd_debugContent$serial") .
			zui::windowActionR("close", "zd_debugBar$serial") .
			"</div>" . $script . "</th></tr>";
			
			//error count
			if(self::$debugErrors == 0) { $errText = zui::miconR("check", "", "ok") . " &nbsp;No errors&nbsp;"; }
			else { $errText = '<span class="zl_red10"><b>' . zui::miconR("close") . " " . self::$debugErrors . "&nbsp;Errors&nbsp;</b></span>"; }
			
			//$debugHtml = zui::tabsR($tabs, 500, '', 'zl_w900', $fault, $errText);
			zui::bufStart();
			echo zui::tabsCSS($tabs, 1, "", "", $errText);
		    $debugHtml = zui::bufStop();
		 
			//ONLY print to screen if we're in dev mode!!!
			if(zl_mode == "dev")
			{
				//attempt to shove the necessary ZL includes in if you haven't done zpage::start()
				if(!zpage::$includesDisplayed && zl::$set['outFormat'] == "page" ) { zpage::includes(); }
				
				$html = $htmlHead;
				$html .= '<tr><td id="zd_debugContent' . $serial . '" class="zd_debugContent" style="display:none;">' . $debugHtml . '</td></tr></table>' . "\n";
				$html .= "<script>addDrag('zd_debugBar$serial','zd_debugBarHeader$serial')</script>";

				//force debugger open if relevant status
				if($fault || zl::$PHPwarnings != ""){ $html .= "<script>zl_IDshow('zd_debugContent$serial', 'inline-block')</script>"; }
				
				echo $html;
			}

			//write the debug log version?
			if($debugLog)
			{
				$html = $htmlHead . '<tr><td id="zd_debugContent' . $serial . '" class="zd_debugContent">' . $debugHtml . '</td></tr></table>' . "\n";
				
				//append database dump to visitor data
				$WA = [];
				$WA['debugDump'] = gzcompress($html); //should replace this with zstd eventually; 5-10x faster
				$WA['faultReason'] = $faultReason;
				if($subType == "abuse") { $WA['debugReason'] = "abuse"; }
				elseif($fault) { $WA['debugReason'] = "fault"; }
				
				//add whatever data about the user we can find
				$authData = zauth::getUser(true);
				$WA['visitURL'] = $authData['visitURL'];     $WA['visitIP'] = $authData['visitIP'];
				$WA['visitHost'] = $authData['visitHost'];   $WA['visitAgent'] = $authData['visitAgent'];
				$WA['visitInput'] = $authData['visitInput']; $WA['userID'] = $authData['userID'];
				$WA['userName'] = $authData['userName'];     $WA['userType'] = $authData['userType'];
				zdb::writeRow("INSERT", "zl_debug", $WA);
			}
	    }
	}

	//censors a full path containing the file root to obscure sensitive web hosting details.
	public static function censorPath(&$path)
	{
		if(self::$set['debugLevel'] < 3)
		{
			if(isset($path)) { if($path != "") { $path = str_replace(zl::$site['fileroot'], "/", $path); } }
			else { $path = ""; } //force set it
		}
	}

	//print_r with censorship abilities; for debugger use.
	private static function censorPR($var)
	{
		if(self::$set['debugLevel'] < 3)
		{
			$banStrings =
			[
				self::$site['emailOwner'], self::$site['emailSupport'], self::$site['emailNoReply'], self::$site['emailDebug'],
				self::$site['domain'], $_SERVER['SERVER_ADDR'], $_SERVER['REMOTE_ADDR'], self::$site['fileroot'],
				self::$set['dbHost'], self::$set['dbUser'], self::$set['dbPass'], self::$set['dbName'],
				self::$set['db2Host'], self::$set['db2User'], self::$set['db2Pass'], self::$set['db2Name'],
				self::$set['mailHost'], self::$set['mailUser'], self::$set['mailPass'], self::$set['mailPort']
			];
			return str_replace($banStrings, "[redacted]", print_r($var, true));
		}
		else { return zs::pr($var); }
	}

	//a single backtraces into a readable function call + reference call line.
	public static function formatBacktrace($bkTrace, $forQuip = false)
	{
		//accidentally sent a multidimensional array with a single object?
		if(isset($bkTrace[0])) { $bkTrace = $bkTrace[0]; }

		self::censorPath($bkTrace['file']); //for safety.

        //formulate arg string
        $args = "";
		if(!$forQuip) //don't show args when we're talkin' bout a quip
		{
			if(isset($bkTrace['args']) && is_array($bkTrace['args']))
	        {
	            foreach($bkTrace['args'] as $arg)
	            {
	                if(is_array($arg) || is_object($arg)) //automatic shorten if array.
	                { $arg = "<br>" . zstr::shorten(zs::pr($arg), self::$set['debugArgVarLimit']); }
	                else
	                {
	                    if($arg === true) { $arg = "[true]"; }
						elseif($arg === false) { $arg = "[false]"; }
	                    else { $arg = '"' . zstr::shorten($arg, self::$set['debugArgVarLimit']) . '"'; }
	                }
	                $args .= $arg . ", ";
	            }
	        }
		}

		$args = zstr::sanitizeHTML("(" . trim($args, ", ") . ");"); //remove 'hanging chad' and sanitize

        //format virtual call
        if(!zs::isBlank($bkTrace['class']) || !zs::isBlank($bkTrace['function']))
        {
			$call = "<b>";
            if(!zs::isBlank($bkTrace['class'])) { $call .= $bkTrace['class'] . $bkTrace['type'] . $bkTrace['function'] ."</b>" . $args; }
            else { $call .= $bkTrace['function'] . "</b>" . $args; }
        }
		else { $call = ""; }

        //got a message from PHP?
        if(!zs::isBlank($bkTrace['message'])) { $call .= "<b>PHP Exception:</b> " . $bkTrace['message']; }

		//format virtual line
		zs::ifBlankFill($bkTrace['file'], "?");
		zs::ifBlankFill($bkTrace['line'], "?");
		$callLine = $bkTrace['file'] . " @ " . $bkTrace['line'];

		return array("call" => $call, "callLine" => $callLine);
	}

	//generate and format backtrace for the debug 'Trace' tab.
	public static function getBacktrace($exceptionObject = "")
    {
		//patch exception trace on to ordinary backtrace ( we get the most information this way )
		if(is_object($exceptionObject))
		{
			//exception data
			$bkTraces = [];
			$bkTraces[] =
			[
				'file' => $exceptionObject->getFile(), 'line' => $exceptionObject->getLine(),
				'function' => "", 'class' => "", 'type' => "",
		        'message' => $exceptionObject->getMessage(), 'number' => $exceptionObject->getCode()
			];
			
			//regular exception data gets patched on
			$exceptionTrace = $exceptionObject->getTrace();
			$first = true;
			foreach($exceptionTrace as $trace) { if($first) { $first = false; } else { $bkTraces[] = $trace; } }
		}
		elseif(is_array($exceptionObject)) //fatal error catch passthrough from PHP
		{
			$bkTraces = [];
			$bkTraces[] =
			[
				'file' => $exceptionObject['file'], 'line' => $exceptionObject['line'],
				'function' => "", 'class' => "", 'type' => "",
		        'message' => $exceptionObject['message'], 'number' => $exceptionObject['file']
			];
		}
		else //just use straight pipe from PHP
		{ $bkTraces = debug_backtrace(0, self::$set['debugTraceLimit']); }

		//filter out zerolith specific backtraces (sensitive/excessive data!).
	    foreach($bkTraces as $bKey => $bkTrace)
		{
			if(isset($bkTrace['class']) && $bkTrace['class'] == "zl")
			{
				if
				(
					$bkTrace['function'] == "getBacktrace" ||
					$bkTrace['function'] == "debugger" ||
					$bkTrace['function'] == "terminate" ||
					$bkTrace['function'] == "fault" ||
					$bkTrace['function'] == "init"
				)
				{ unset($bkTraces[$bKey]); }
			}
		}

	    //generate the text
		$formattedText = "";
		foreach($bkTraces as $bkTrace)
        {
			$callText = self::formatBacktrace($bkTrace); //insert snippet
            $formattedText .= rtrim(self::getCodeSnippet($bkTrace, $callText['call']), "<br>");
        }

        return $formattedText;
    }
	
	//show preview of the affected code: needs backtrace['line'], backtrace['file'] sent at a minimum.
	private static function getCodeSnippet($bkTrace, $callText = "")
	{
		if(zs::isBlank($bkTrace['file']) || $bkTrace['file'] == "unknown(parser)" ) //insert fake file data with error.
		{
			self::censorPath($bkTrace['file']);
			$fileData = ["[getCodeSnippet: file name " . $bkTrace['file'] . "] was blank."];
		}
		else { @$fileData = file($bkTrace['file'], FILE_IGNORE_NEW_LINES); } //attempt to load.
		
		//In case of a freak event..
		if($fileData === false)
		{
			self::censorPath($bkTrace['file']);
			$fileData = ["[getCodeSnippet: " . $bkTrace['file'] . "] doesn't exist."];
		}
		
		array_unshift($fileData, ""); //account for off by 1 error

	    if($bkTrace['line'] < (self::$set['debugCodeLines'] / 2 )) //show start of file.
	    { $fileData = array_slice($fileData, 0, self::$set['debugCodeLines'], true); }
	    elseif($bkTrace['line'] > count($fileData) - (self::$set['debugCodeLines'] /2)) //show end of file
	    { $fileData = array_slice($fileData, -self::$set['debugCodeLines'], self::$set['debugCodeLines'], true); }
	    else //middle of the file.
	    { $fileData = array_slice($fileData, ($bkTrace['line'] - (intval(self::$set['debugCodeLines'] / 2))), self::$set['debugCodeLines'], true); }

		//print to screen!
		if(!zs::isBlank($callText)){ $callText = "<br>" . $callText; }
	    $fileSnippet = '<b>' . $bkTrace['file'] . ": " . $bkTrace['line'] . "</b>" . $callText . "\n\n";
		$fileSnippet .= "<table class='zd_codeTable zlt_table zl_w100pp'>";

        foreach($fileData as $line => $code)
        {
			//post-process - pretty or safe?
			$code = zstr::sanitizeHTML($code);

        	if($bkTrace['line'] == $line) { $codecolor = ' class="codeError"'; } else { $codecolor = ""; }
        	if($bkTrace['line'] == $line) { $linecolor = ' class="lineError"'; } else { $linecolor = ""; }
        	$fileSnippet .= "<tr><td" . $linecolor . "><b>" . $line . "</b></td><td" . $codecolor . "><pre>" . $code . "</pre></td></tr>\n";
        }

        return $fileSnippet . "</table>\n";
	}

	//collect warnings/errors/info from PHP
	public static function phpWarningCollector($code, $errText, $file, $line)
	{
		$errText = htmlspecialchars($errText);

		//This error code is not included in error_reporting; handover to standard PHP error handler.
	    if (!(error_reporting() & $code)) { return false; }
		//else { print_r(get_defined_vars()); }
		
		//deal with PHP's weird consts
		if(in_array($code, [E_WARNING, E_NOTICE, E_STRICT, E_DEPRECATED, E_RECOVERABLE_ERROR]))
		{
			//mutate code into a real text version
			if($code == E_WARNING) { $code = "E_WARNING"; }
			else if($code == E_NOTICE) { $code = "E_NOTICE"; }
			else if($code == E_STRICT) { $code = "E_STRICT"; }
			else if($code == E_DEPRECATED) { $code = "E_DEPRECATED"; }
			else if($code == E_RECOVERABLE_ERROR) { $code = "E_RECOVERABLE_ERROR"; }

			$ret = "<b>$code</b> $errText $file @ $line\n";
			$retE = "\n$code $errText $file @ $line\n";

			self::$PHPwarnings .= $ret; //send to ZL warning log buffer
			error_log($retE);           //send to web server log as expected
			return true;                //tell PHP we handled it ( don't bomb on a warning! )
		}
		else { return false; } //returning false will tell PHP to bomb.
	}
}

//initialize the static class ( and pass config vars for measuring init time )
zl::init($zl_set, $zl_page, $zl_site, $zl_initTimer, $zl_initMem);

//after this, we're returning control back to zl_init to cleanup, handle hx-