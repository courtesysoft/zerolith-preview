<?php
//Zerolith 1.23 configuration file - 05/19/2025
$zl_set = [];  //Piped into zl::$set after init
$zl_page = []; //Piped into zl::$page after init
$zl_site = []; //Piped into zl::$site after init

//Automatically set the dev or prod mode based on server's linux hostname
//Feel free to replace this with your own logic to automatically determine the environment
/*
if(!defined('zl_mode'))
{
	//hostname based detection ( is dev or stage at the end of the name?)
	$serverHostname = strtolower(gethostname());
	if(substr($serverHostname,-3,3) == "dev")      { define("zl_mode", "dev"); }
	elseif(substr($serverHostname,-5,5) == "stage"){ define("zl_mode", "stage"); }
	elseif
	(
		(substr($_SERVER['SERVER_ADDR'], 0,4) == "172." || substr($_SERVER['SERVER_ADDR'], 0,7) == "10.0.2.") //inside virtualbox network?
		&& $_SERVER['REQUEST_SCHEME'] == "http" && isset($_SERVER['HTTP_X_FORWARDED_HOST'])) { define("zl_mode", "dev"); }
	else { define("zl_mode", "prod"); } //default to prod so we don't accidentally spit out debug messages!
}
*/

//change this to prod or insert your own logic to set it when done evaluating.
define('zl_mode', "dev");

//--------------------- ZL behavior and features ---------------------

//behavioral settings.
$zl_set['envChecks'] = true;          //check PHP extensions/settings/paths. Turn off for a tiny speed boost once everything is 100%.
$zl_set['outFormat'] = "page";        //default output format: page (zpage), html (no zpage), api (no zpage, no custom exit func)
$zl_set['envExitFunc'] = "";          //run custom function @ zl::terminate(); if outFormat 'api', forces zl::exitApi
$zl_set['requireBeforeInit'] = "";    //full pathname to file to require_once before ZL god class is loaded
$zl_set['requireAfterInit'] = __DIR__ . "/zl_after.php"; //load this file after ZL class loaded and before auto zl::routeZLHX()
$zl_set['routeZLHXauto'] = false;     //run zl::routeZLHX() after zl::init. If false, zl::routeZLHX() must be called in the script to route HTMX calls to ZLHX.
$zl_set['logSoftFault'] = false;      //log soft faults to DB ( NOT IMPLEMENTED YET )

//debugger settings
$zl_set['debugger'] = true;           //false = minimal debug functions, don't attach exception handler, don't debug.
$zl_set['debugLevel'] = 0;            //0 = no debugger, 4 = crazy verbosity.
$zl_set['debugInStageMode'] = true;   //if enabled, debugging will be shown in staging mode
$zl_set['debugAllVars'] = true;       //false = only output PHP vars - for host frameworks w/huge global vars.
$zl_set['debugHideGlobals'] = "dbip|dbname|dbpass|dbuser|emailBlacklist|hDB|ignoreLog|labelIDArray|mailhost|mailpassword|mailusername|serverHostname|startPTTime|";//piped list of global var names to hide from debug output
$zl_set['debugFlushOB'] = true;       //flush output buffer when ending in debug mode.
$zl_set['debugLog'] = false;          //constantly write debug to db. Useful for tracking behavior in production.
$zl_set['debugLogOnFault'] = false;   //write debug to db on fault. ( NOT TESTED )
$zl_set['debugLogOnWarn'] = false;    //write debug to db on PHP notice/warning. ( NOT TESTED )

$zl_set['debugTextLimit'] = 2048;     //turn text into 'read more' after this amount of chars for sanity.
$zl_set['debugArgVarLimit'] = 30000;  //clip args/var text after this amount for sanity / mem use.
$zl_set['debugCodeLines'] = 14;       //The amount of lines of code previewed in traces.
$zl_set['debugTraceLimit'] = 10;      //limits the amount of traces outputted.

//database settings
$zl_set['dbDefault'] = 1; //use DB 1 by default

//MySQL DB 1 settings.
$zl_set['dbHost'] = '127.0.0.1';
$zl_set['dbUser'] = 'test';
$zl_set['dbPass'] = 'test';
$zl_set['dbName'] = 'test';

//MySQL DB 2 settings, if any. Type zdb::switchDB(2) to change to it.
$zl_set['db2Host'] = '';
$zl_set['db2User'] = '';
$zl_set['db2Pass'] = '';
$zl_set['db2Name'] = '';

//sqlite beta path; these files are stored in /zerolithData/sqlites/
$zl_set['dbSQLiteDefaultFilename'] = 'zl_test.sqlite';

//mail server settings
$zl_set['mailOn'] = true;  //if false, only log email.
$zl_set['mailHost'] = 'smtp.nowhere1234.com';
$zl_set['mailUser'] = 'bob';
$zl_set['mailPass'] = 'Bobbin-1234';
$zl_set['mailPort'] = 587;
$zl_set['mailSecurity'] = 'tls';
$zl_set['mailSMTPAuth'] = true;
$zl_set['mailKeepalive'] = true;
$zl_set['mailBlackList'] = [];  //never send emails to these addresses.
$zl_set['mailBatchSize'] = 25;  //how many emails to send in a batch with the mailQueue.php cronjob

//--------------------- Site variables ---------------------

$zl_site['emailDomain'] = 'yeehaw.com';
$zl_site['emailDebug'] = 'bob@' . $zl_site['emailDomain'];                   //Email for site admin.
$zl_site['emailOwner'] = 'jeff@' . $zl_site['emailDomain'];                   //Company owner
$zl_site['emailSystem'] = 'support@' . $zl_site['emailDomain'];                //Email from which to send out automated email.
$zl_site['emailSupport'] = 'support@' . $zl_site['emailDomain'];               //Email from which to send support email.

if(zl_mode == 'dev') //dev mode.
{
	$zl_set['mailOn'] = true;
	$zl_site['URLbase'] = 'http://localhost/'; //base http/s path.
	$zl_site['name'] = 'Your app [dev]';       //site's name.
	$zl_site['namePlural'] = "Your app's [dev]";
	$zl_site['logo'] = "/zerolith/public/logoZerolithDev.png";//site's logo image.
	$zl_site['curlPasswords'] = ['yeehaw.com' => 'wcd40'];//HTTP auth passwords for cURL.
}
elseif(zl_mode == 'stage') //staging mode
{
	$zl_set['mailOn'] = false;
	$zl_site['URLbase'] = 'http://localhost/';//base http/s path.
	$zl_site['name'] = 'Your app [stage]';  //site's name.
	$zl_site['namePlural'] = "Your app's [stage]";
	$zl_site['logo'] = '/zerolith/public/logoZerolithStage.png';  //site's logo image.
}
else //production mode.
{
	$zl_site['URLbase'] = 'http://localhost/'; //base http/s path.
	$zl_site['name'] = 'Your App';             //site's name.
	$zl_site['namePlural'] = "Your App's";
	$zl_site['logo'] = '/zerolith/public/logoZerolith.png';        //site's logo image.
}

$zl_site['domain'] = str_replace(['https://', 'http://', "/"], '', $zl_site['URLbase']); //just the domain name
$zl_site['URLZLpublic'] = $zl_site['URLbase'] . 'zerolith/public/';//Web path of zerolith's assets.

//default setting is to use the autodetected paths fom zl_init.php
$zl_site['pathRoot'] = zl_pathRoot;                    //root folder of your site's files.
$zl_site['pathZerolith'] = zl_pathZerolith;            //zerolith
$zl_site['pathZerolithData'] = zl_pathZerolithData;    //zerolith data folder
$zl_site['pathAppClasses'] = zl_pathRoot . 'classes/'; //path to application's classes to autoload.
$zl_site['pathZLHXclasses'] = zl_pathRoot . 'classesZLHX/'; //for external zlhx objects

//--------------------- Page Control ( zpage ) ---------------------

$zl_page['wrap'] = true;                           //Produce HTML wrapper? ( use below functions )
$zl_page['startFunc'] = "zpage::startCourtesy";    //Page start generation function
$zl_page['navFunc'] = "zpage::navCourtesy";        //Page nav generation function
$zl_page['endFunc'] = "zpage::endCourtesy";        //Page end generation function
$zl_page['navItems'] = [];                         //pre-inject nav items? leave blank
$zl_page['navWidth'] = 185;                        //width of the nav menu.
$zl_page['logoLink'] = "/yourhomepage.php";        //what should the logo link to?
$zl_page['deprecatedCSS'] = false;                 //load deprecated CSS file

//--------------------- End ---------------------
?>