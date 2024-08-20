<?php
//Zerolith 1.17 configuration file - 02/12/2024
$zl_set = [];  //Piped into zl::$set after init
$zl_page = []; //Piped into zl::$page after init
$zl_site = []; //Piped into zl::$site after init

//Auto-detect mode (environment). Add your own rules here!
if(!defined('zl_mode'))
{
    $host = trim(gethostname());
    if(stripos($host, 'stage') !== false)          define("zl_mode", "stage");
    elseif(getenv('STAGE') || getenv('STAGING'))   define("zl_mode", "stage");
    elseif(stripos($host, 'prod') !== false)       define("zl_mode", "prod");
    elseif(getenv('PROD') || getenv('PRODUCTION')) define("zl_mode", "prod");
    else                                           define("zl_mode", "dev"); //Default
}

//--------------------- ZL behavior and features ---------------------

//behavioral settings.
$zl_set['outFormat'] = "page";        //default output format: page (zpage), html (no zpage), api (no zpage, no custom exit func)
$zl_set['envExitFunc'] = "";          //run custom function @ zl::terminate(); if outFormat 'api', forces zl::exitApi
$zl_set['requireBeforeInit'] = "";    //full pathname to file to require_once before ZL init
$zl_set['requireAfterInit'] = ""; //full path to require_once after ZL init and before auto zl::routeZLHX()
$zl_set['routeZLHXauto'] = true;     //run zl::routeZLHX() after ZL init. If false, zl::routeZLHX() must be called in the script.
$zl_set['logSoftFault'] = false;      //log soft faults to DB ( NOT IMPLEMENTED YET )

//default debugger settings
$zl_set['debugger'] = true;           //false = minimal debug functions, don't attach exception handler, don't debug.
$zl_set['debugLevel'] = 0;            //0 = no debugger, 4 = crazy verbosity.
$zl_set['debugAllVars'] = true;       //false = only output PHP vars - for host frameworks w/huge global vars.
$zl_set['debugHideGlobals'] = "somevariablename|someothervariablename|variable3";     //piped list of global vars to hide from debug output
$zl_set['debugFlushOB'] = true;       //flush output buffer when ending in debug mode.
$zl_set['debugLog'] = false;          //constantly write debug to db. Useful for tracking behavior in production.
$zl_set['debugLogOnFault'] = false;   //write debug to db on fault.
$zl_set['debugLogOnWarn'] = false;    //write debug to db on PHP notice/warning.

$zl_set['debugTextLimit'] = 2048;     //turn text into 'read more' after this amount of chars for sanity.
$zl_set['debugArgVarLimit'] = 30000;  //clip args/var text after this amount for sanity / mem use.
$zl_set['debugCodeLines'] = 14;       //The amount of lines of code previewed in traces.
$zl_set['debugTraceLimit'] = 10;      //limits the amount of traces outputted.

$zl_set['dbDefault'] = 1; //use DB 1 by default

//MySQL DB 1 settings.
$zl_set['dbHost'] = 'localhost';
$zl_set['dbUser'] = 'youruser';
$zl_set['dbPass'] = 'yourpassword';
$zl_set['dbName'] = 'databasename';

//MySQL DB 2 settings, if any. Type zdb::switchDB(2) to change to it.
$zl_set['db2Host'] = '';
$zl_set['db2User'] = '';
$zl_set['db2Pass'] = '';
$zl_set['db2Name'] = '';

//PostgreSQL DB 1 settings.
$zl_set['dbpHost'] = '';
$zl_set['dbpUser'] = '';
$zl_set['dbpPass'] = '';
$zl_set['dbpName'] = '';

//mail server settings
$zl_set['mailOn'] = true;  //if false, only log email.
$zl_set['mailHost'] = 'email.com';
$zl_set['mailUser'] = '1234';
$zl_set['mailPass'] = 'super secure password';
$zl_set['mailPort'] = 587;
$zl_set['mailSecurity'] = 'tls';
$zl_set['mailSMTPAuth'] = true;
$zl_set['mailKeepalive'] = true;
$zl_set['mailBlackList'] = [];  //never send emails to these addresses.


//--------------------- Site variables ---------------------

$zl_site['emailDomain'] = "yeehaw.com";
$zl_site['emailDebug'] = 'bob@' . $zl_site['emailDomain'];                   //Email for site admin.
$zl_site['emailOwner'] = 'joe@' . $zl_site['emailDomain'];                   //Company owner
$zl_site['emailSystem'] = 'will@' . $zl_site['emailDomain'];                //Email from which to send out automated email.
$zl_site['emailSupport'] = 'support@' . $zl_site['emailDomain'];               //Email from which to send support email.

if(zl_mode == 'prod') //production mode.
{
	$zl_site['URLbase'] = 'https://yeehaw.com/';    //base http/s path.
	$zl_site['name'] = 'Yee-Haw Enterprises';                      //site's name.
	$zl_site['namePlural'] = "Yee Haw Enterprises";
	$zl_site['logo'] = '/yeehaw.png';        //site's logo image.
}
elseif(zl_mode == 'stage') //staging mode
{
	$zl_set['mailOn'] = false;
	$zl_site['URLbase'] = 'https://stage.yeehaw.com/';//base http/s path.
	$zl_site['name'] = 'Yee-Haw Enterprises Staging';            //site's name.
	$zl_site['namePlural'] = "YHSTAGE's";
	$zl_site['logo'] = '/yeehaw.png';      //site's logo image.
}
else //dev mode.
{
	$zl_set['mailOn'] = false;
	$zl_site['URLbase'] = 'http://localhost/'; //base http/s path.
	$zl_site['name'] = 'Yee-Haw enterprises Dev';                  //site's name.
	$zl_site['namePlural'] = "YHDEV's";
	$zl_site['logo'] = '/yeehaw.png';     //site's logo image.
	$zl_site['curlPasswords'] = ['dev.yeehaw.com' => 'user:password'];//HTTP auth passwords for cURL.
}

$zl_site['domain'] = str_replace(['https://', 'http://', "/"], '', $zl_site['URLbase']); //just the domain name
$zl_site['fileroot'] = __dir__ . '/';                                           //root folder of your site's files.
$zl_site['filerootClasses'] = $zl_site['fileroot'] . 'classes/';                //classes to autoload from your program.
$zl_site['URLZLpublic'] = $zl_site['URLbase'] . 'zerolith/public/';             //Web path of zerolith's assets.
$zl_site['URLZLcache'] = $zl_site['URLbase'] . 'zerolith/zl_internal/cache/';   //Web path of zerolith's cache files.

//--------------------- Page Control ( zpage ) ---------------------

$zl_page['wrap'] = false;                          //Produce HTML wrapper? ( use below functions )
$zl_page['startFunc'] = "zpage::startCourtesy";    //Page start generation function
$zl_page['navFunc'] = "zpage::navCourtesy";        //Page nav generation function
$zl_page['endFunc'] = "zpage::endCourtesy";        //Page end generation function
$zl_page['navItems'] = [];                         //pre-inject nav items? leave blank
$zl_page['navWidth'] = 185;                        //width of the nav menu.
$zl_page['navPosition'] = "";                      //where should the nav be?
$zl_page['logoLink'] = "/yee-home.php";           //where should the nav be?

//--------------------- End ---------------------
?>
