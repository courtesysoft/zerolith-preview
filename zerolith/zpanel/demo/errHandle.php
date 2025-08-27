<?php
//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// Error handling test

require "../../zl_init.php";
zl::routeZLHX();
require "index.nav.php"; //navigation menu
zpage::start("Error handling test");

?>
	<div class="zl_cols">
	<div class="col">
		<?=zui::buttonJS("Create deprecated", "", "", "", 'hx-target="#frame" hx-get="?hxfunc=deprecated"');?><br><br>
		<?=zui::buttonJS("Create notice", "", "", "", 'hx-target="#frame" hx-get="?hxfunc=notice"');?><br><br>
		<?=zui::buttonJS("Create warning", "", "", "", 'hx-target="#frame" hx-get="?hxfunc=warning"');?><br><br>
		<?=zui::buttonJS("Create error", "", "", "", 'hx-target="#frame" hx-get="?hxfunc=error"');?><br><br>
		<?=zui::buttonJS("Create DB error", "", "", "", 'hx-target="#frame" hx-get="?hxfunc=DBerror"');?><br><br>
	</div>
	<div class="col4" ID="frame">Output will go here</div>
	<?php

zpage::end();

class zlhx
{
	public static function zlhxInit() { }
	
	//various returns
	public static function notice()
	{
		$someArray = ['pi', 'ka', 'chu'];
		echo $someArray;
	}
	
	public static function error() { throw new Exception("The robot got confused."); }
	
	public static function DBerror() { zdb::getRow("SELECT * FROM invalidDatabaseName2000", "simulated fault"); }
	
	public static function deprecated() { trigger_error("The robot smells old.", E_USER_DEPRECATED); }
	
	public static function warning() { preg_match('The robot is holding a weapon', 'test'); }
}
