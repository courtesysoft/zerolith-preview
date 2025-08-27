<?php
//ZL email queue processor
//02-08-23 - v0.9 created and adapted from pom code
require "../../zl_init.php";
$debugQueue = true;

//determine if we should output HTML or text for CLI mode
if(php_sapi_name() == "cli") { zl::setDebugLevel(0); zl::setOutFormat('api'); } else { zl::setDebugLevel(2); }

//respect if the user turned email off!
if(!zl::$set['mailOn']) { exit("ZL mail turned off - not processing queue."); }

//check lock state first.
if(zsys::isLocked('mailQueue'))
{
	echo "Email Queue locked. Try again later.<br>";
	zmail::sendToDebug("mailQueue was locked!", "mailQueue was locked when it ran. The maximum batch size may exceed the speed the system is able to send emails");
	exit;
}
else { zsys::lockStart('mailQueue'); } //not locked? lock the system.

$emailQueue = zdb::getArray("SELECT * FROM zl_mail WHERE status = 'queued' LIMIT 0 , " . zl::$set['mailBatchSize'], "error retrieving email queue!",);
$emailQueueCount = count($emailQueue);

//unlock system and bail immediately if there is no work to do.
if($emailQueueCount == 0) { zsys::lockStop('mailQueue'); echo "\n0 emails to process. See ya later."; exit; }

//lock individual emails out from being sent as duplicates by another instance ( prevent multithreading )
$IDsql = "";
foreach($emailQueue as $email) { $IDsql .= $email['ID'] . ", "; }
$IDsql = trim($IDsql, ", ");
zdb::writeSQL("UPDATE zl_mail SET status = 'sending' WHERE ID IN (" . $IDsql . ")", "can't update zl_mail");

//send queue out
$queueResults = zmail::sendArray($emailQueue);
zsys::lockStop('mailQueue'); //unlock system.

//form status message
$m  = "Emails in this batch ( " . zl::$set['mailBatchSize'] . " max ): " . $emailQueueCount . "<br>\n";
$m .= "Emails Sent: " . $queueResults['success'] . "<br>\n";
$m .= "Emails Failed: " . $queueResults['fail'] . "<br><br>\n";
echo $m;

//for debugging - send results to admin only if the batch is large.
if($debugQueue && $emailQueueCount > ( zl::$set['mailBatchSize'] / 2 )) { zmail::sendToDebug("runEmailQueue results", $m); }
?>