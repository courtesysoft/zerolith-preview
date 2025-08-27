<?php
//ZL installer - creates database tables ZL will use to facilitate the DB and configuration of zerolith.
//01/25/2023 - v1.0 ( finished )
//08/13/2025 - v1.5 ( bugfix and mega refinement )

//pre-flight check: are these files valid?
if(!file_exists("../../zerolithData/zl_config.php")) { exit ("Fatal error; could not find ../../zl_config.php"); }
else if(!file_exists("../../zerolithData/zl_theme.css")) { exit ("Fatal error; could not find ../../zl_theme.css"); }

require "../zl_init.php"; //load framework
require "zpanelConfig.php"; //load zpanel settings modification
define('zl_install_tables', ["zl_mail", "zl_debug"]); //list of tables that should be installed
define('zl_paths', ['pathRoot','pathZerolith','pathZerolithData','pathAppClasses', 'pathZLHXclasses']); //zl::$set paths to check

//let's go!


extract(zfilter::array("action", "page"));

zpage::start("Zerolith " . zl_version . " installer");
zui::notify("ok", "Zerolith successfully loaded.<br>If you see a green checkmark, the frontend also loaded correctly.");
if(zl::$set['envChecks']) { zui::notify("ok", "Environment check passed"); } 
else { zui::notify("warn", "Environment check not performed due to config setting"); }

// Check paths in $zl_set for permissions
$failed = [];
foreach(zl_paths as $key) 
{
    if(!empty(zl::$set[$key]) && (!is_readable(zl::$set[$key]) || (!is_dir(zl::$set[$key]) && !is_writable(zl::$set[$key]))))
	{ $failed[] = zl::$set[$key]; }
}
echo "<p>Path permissions:</p>";
if($failed) { zui::notify("warn", "Failed paths: ".implode(", ", $failed)); } 
else { zui::notify("ok", "All paths defined in the config file are read/writable!"); }

//check for existance of database
echo "<p>Database Status:</p>";
checkDBconnection();
zui::notify("ok", "Zerolith can talk to database #1");

if(isset($action)) //handle actions passed to web form part
{
    switch ($action) 
    {
        case 'create': createZLtables(); break;
        case 'delete': deleteZLtables(); break;
        case 'reset': deleteZLtables(); createZLtables(); break;
    }
}

//database table existence check
foreach(zl_install_tables as $table)
{
	if(zdb::tableExists($table)) { zui::notify("ok", "Found $table"); }
	else { zui::notify("warn", "'$table' doesn't exist"); }
}
?>
<br>
Database control: 
<form class = "zl_ib" method="post" style="margin: 10px 0;">
	<input type="submit" name="action" value="create" class="zlt_button">
	<input type="submit" name="action" value="delete" class="zlt_button">
	<input type="submit" name="action" value="reset" class="zlt_button">
</form>

<?php //logic section

//note: all of this is tested on mysql 8.4
function createZLtables() 
{
	$success = true;

    // Create zl_mail table
    if(!zdb::tableExists("zl_mail"))
    {
        $SQL = "CREATE TABLE `zl_mail` (
		`ID` mediumint unsigned NOT NULL AUTO_INCREMENT,
		`timeSent` timestamp NULL DEFAULT NULL,
		`status` enum('frozen','queued','sending','sent','failed') NOT NULL DEFAULT 'queued',
		`category` char(48) NOT NULL DEFAULT '',
		`toUserID` char(32) NOT NULL DEFAULT '',
		`to` char(160) NOT NULL,
		`subject` char(128) NOT NULL,
		`message` text NOT NULL,
		`from` char(48) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
		`replyto` char(48) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
		`attachmentPath` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
		`resultData` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
		PRIMARY KEY (`ID`),
		KEY `status` (`status`) USING BTREE,
		KEY `subject` (`subject`),
		KEY `category` (`category`) USING BTREE,
		KEY `toUserID` (`toUserID`),
		KEY `from` (`from`),
		KEY `to` (`to`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1";

        if(!zdb::writeSQL($SQL)) { $success = false; }
        if(!$success) { zl::fault("failed when creating the zl_mail table"); }
        else { zui::notify("ok", "zl_mail table created."); }
    }
    else { zui::notify("ok", "zl_mail database table already exists"); }
	
	
	// Create zl_debug table
    $success = true;
	if(!zdb::tableExists("zl_debug"))
    {
        $SQL = "CREATE TABLE `zl_debug` (
		`ID` smallint unsigned NOT NULL AUTO_INCREMENT,
		`debugReason` enum('supervision','fault','abuse') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'supervision',
		`time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		`visitURL` char(255) NOT NULL DEFAULT '',
		`visitIP` char(50) NOT NULL DEFAULT '',
		`visitAgent` varchar(255) NOT NULL DEFAULT '',
		`visitOS` char(24) NOT NULL DEFAULT '',
		`visitBrowser` char(32) NOT NULL DEFAULT '',
		`visitInput` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
		`userID` mediumint unsigned NOT NULL DEFAULT '0',
		`userName` char(32) NOT NULL DEFAULT '',
		`userType` char(16) NOT NULL DEFAULT '',
		`permission` char(1) NOT NULL DEFAULT '',
		`debugDump` mediumblob NOT NULL,
		`faultReason` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
		`faultFunc` varchar(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
		PRIMARY KEY (`ID`),
		KEY `visitIP` (`visitIP`) USING BTREE,
		KEY `userID` (`userID`) USING BTREE,
		KEY `faultFunc` (`faultFunc`),
		KEY `trackReason` (`debugReason`)
		) ENGINE=InnoDB DEFAULT CHARSET=latin1";

        if(!zdb::writeSQL($SQL)) { $success = false; }
        if(!$success) { zl::fault("failed when creating the zl_debug table."); }
        else { zui::notify("ok", "zl_debug table created."); }
    }
    else { zui::notify("ok", "zl_debug database table already exists"); }
	
	/*if(!zdb::tableExists("zl_imageTender"))
	{
		$err = "cannot create zl_imageTender database";
		$SQL = "CREATE TABLE `zl_imageTender` (`ID` mediumint UNSIGNED NOT NULL, `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `filepath` char(128) NOT NULL DEFAULT '', `extension` char(18) NOT NULL DEFAULT '', `lastError` char(50) NOT NULL DEFAULT '', `parseResult` char(18) NOT NULL DEFAULT '',`processed` enum('Y','N') NOT NULL DEFAULT 'N' ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
	
	    if(!zdb::writeSQL($SQL)) { zl::fault($err); }
	    else
	    {
	        if(!zdb::writeSQL("ALTER TABLE `zl_imageTender` ADD PRIMARY KEY (`ID`)")) { zl::fault($err); }
	        if(!zdb::writeSQL("ALTER TABLE `zl_imageTender` ADD UNIQUE KEY (`filepath`);")) { zl::fault($err); }
	        if(!zdb::writeSQL("ALTER TABLE `zl_imageTender` MODIFY `ID` mediumint UNSIGNED NOT NULL AUTO_INCREMENT;")) { zl::fault($err); }
	    }
		
		zui::notify("warn", "zl_imageTender table created.");
	}
	else { zui::notify("ok", "zl_imageTender database table exists"); }
	
	if(!zdb::tableExists("zl_accessAudit"))
	{
		$SQL = "CREATE TABLE IF NOT EXISTS `zl_audit` ( `ID` int(11) NOT NULL AUTO_INCREMENT, `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `byUser` char(150) NOT NULL, `toUser` char(150) NOT NULL, `category` char(25) NOT NULL, `action` char(25) NOT NULL, `success` char(1) NOT NULL, `eventData` varchar(255) NOT NULL, PRIMARY KEY (`ID`), UNIQUE KEY `byUser` (`byUser`), UNIQUE KEY `category` (`category`), UNIQUE KEY `action` (`action`), KEY `toUser` (`toUser`), KEY `success` (`success`) ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
	 
		if(!zdb::writeSQL($SQL)) { zl::fault("failed to create zl_audit table."); }
		else { zui::notify("warn", "zl_audit table created."); }
	}
	else { zui::notify("ok", "zl_audit database table exists"); }*/
}

//soft-connect to the database without zdb ( it crashes and won't create a friendly message )
function checkDBconnection()
{	
	//check username/pw
	try{ $cn = mysqli_connect(zl::$set['dbHost'], zl::$set['dbUser'], zl::$set['dbPass']); }
	catch(Exception $e) { zl::fault("Can't connect to mysql with the given username/password.<br>Please review your zl_config.php and make sure mysql is setup correctly.", $e); }

	//does the database table exist?
	try{ $exists = mysqli_num_rows(mysqli_query($cn, "SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '" . zl::$set['dbName'] . "'")); }
	catch(Exception $e) { zl::fault("mysql username/password valid, but can't query schema to find out if database #1 exists.", $e); }
    mysqli_close($cn);
    if(!$exists)  { zl::fault("ZL DB #1 either doesn't exist or cannot be connected to.<br>Please review your zl_config.php and make sure mysql is setup correctly."); }
}

function deleteZLtables() 
{
	$errors = "";

    foreach(zl_install_tables as $table)
	{ if(zdb::tableExists($table) && !zdb::writeSQL("DROP TABLE IF EXISTS `$table`")) { $errors .= "Failed to drop $table table<br>"; } }

    if(empty($errors)) { zui::notify("ok", "Zerolith database tables successfully deleted."); return true; } 
	else { zl::fault($errors); }
}