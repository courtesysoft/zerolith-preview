<?php
//ZL installer - creates database tables ZL will use to facilitate the DB and configuration of zerolith.
//01/24/2023 - v0.0 ( started )
//01/25/2023 - v1.0 ( finished )

//are these files valid?
if(!file_exists("../../zl_config.php")) { exit ("Fatal error; could not find ../../zl_config.php"); }
else if(!file_exists("../../zl_theme.css")) { exit ("Fatal error; could not find ../../zl_theme.css"); }

$s = true; //short for success

//actually do the load
require "../zl_init.php";
zl::setOutFormat("page");
zl::setDebugLevel(4);
zpage::start("Zerolith " . zl_version . " installer");
zui::notify("ok", "Zerolith successfully loaded.");

//check for the existance of zerolith/zl_internal/cache
if(!file_exists("cache"))
{
	if(!mkdir("cache", 770, true)) { zui::notify("error", "Couldn't create the cache directory in /zerolith/zl_internal ( permissions issue? )"); $s = false; }
	else { zui::notify("warn", "Created the cache directory."); }
}
if(is_writeable("cache")) { zui::notify("error", "Can't write to cache directory in /zerolith/zl_internal ( permissions issue? )"); $s = false; }
else { zui::notify("ok", "Can write to the ZL cache directory."); }

//check for existence of zl database tables.. if not, create them.
if(!zdb::tableExists("zl_mail"))
{
	$err = "failed to create zl_mail table.";
	$SQL = "CREATE TABLE `zl_mail` (
  `ID` mediumint UNSIGNED NOT NULL,
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
  `resultData` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
	
	if(!zdb::writeSQL($SQL)) { zl::fault($err); }
    else
    {
        if(!zdb::writeSQL("ALTER TABLE `zl_mail` ADD PRIMARY KEY (`ID`), ADD KEY `status` (`status`) USING BTREE;")) { zl::fault($err); }
        if(!zdb::writeSQL("ALTER TABLE `zl_mail` MODIFY `ID` mediumint UNSIGNED NOT NULL AUTO_INCREMENT;")) { zl::fault($err); }
    }
	
	if(!zdb::writeSQL($SQL)) { zl::fault($err); }
	else { zui::notify("ok", "zl_mail table created."); }
}
else { zui::notify("ok", "zl_mail database table exists"); }

if(!zdb::tableExists("zl_debug"))
{
	$err = "failed to create zl_debug table.";
	$SQL = "CREATE TABLE `zl_debug` (
    `ID` smallint UNSIGNED NOT NULL,
    `debugReason` enum('supervision','fault','abuse') CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT 'supervision',
    `time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
	`visitURL` char(255) NOT NULL DEFAULT '',
	`visitIP` char(50) NOT NULL DEFAULT '',
	`visitAgent` varchar(255) NOT NULL DEFAULT '',
	`visitOS` char(24) NOT NULL DEFAULT '',
	`visitBrowser` char(32) NOT NULL DEFAULT '',
	`visitInput` text CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL,
	`userID` mediumint UNSIGNED NOT NULL DEFAULT '0',
	`userName` char(32) NOT NULL DEFAULT '',
	`userType` char(16) NOT NULL DEFAULT '',
	`permission` char(1) NOT NULL DEFAULT '',
	`debugDump` mediumblob NOT NULL,
	`faultReason` varchar(255) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT '',
	`faultFunc` varchar(64) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL DEFAULT ''
	) ENGINE=InnoDB DEFAULT CHARSET=latin1;";
	
	if(!zdb::writeSQL($SQL)) { zl::fault($err); }
    else
    {
        if(!zdb::writeSQL("ALTER TABLE `zl_debug` ADD PRIMARY KEY (`ID`), ADD KEY `visitIP` (`visitIP`) USING BTREE, ADD KEY `userID` (`userID`) USING BTREE, ADD KEY `faultFunc` (`faultFunc`), ADD KEY `trackReason` (`debugReason`);")) { zl::fault($err); }
        if(!zdb::writeSQL("ALTER TABLE `zl_debug` MODIFY `ID` smallint UNSIGNED NOT NULL AUTO_INCREMENT;")) { zl::fault($err); }
    }
	
	if(!zdb::writeSQL($SQL)) { zl::fault($err); }
	else { zui::notify("ok", "zl_debug table created."); }
}
else { zui::notify("ok", "zl_debug database table exists"); }

if(!zdb::tableExists("zl_imageTender"))
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

if(!zdb::tableExists("zl_audit"))
{
	$SQL = "CREATE TABLE IF NOT EXISTS `zl_audit` ( `ID` int(11) NOT NULL AUTO_INCREMENT, `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP, `byUser` char(150) NOT NULL, `toUser` char(150) NOT NULL, `category` char(25) NOT NULL, `action` char(25) NOT NULL, `success` char(1) NOT NULL, `eventData` varchar(255) NOT NULL, PRIMARY KEY (`ID`), UNIQUE KEY `byUser` (`byUser`), UNIQUE KEY `category` (`category`), UNIQUE KEY `action` (`action`), KEY `toUser` (`toUser`), KEY `success` (`success`) ) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;";
 
	if(!zdb::writeSQL($SQL)) { zl::fault("failed to create zl_audit table."); }
	else { zui::notify("warn", "zl_audit table created."); }
}
else { zui::notify("ok", "zl_audit database table exists"); }

//passed
echo "<br>";
if($s) { zui::notify("ok", "Zerolith is fully installed."); }
else { zui::notify("error", "Zerolith is partially installed; some functions may not work."); }
?>