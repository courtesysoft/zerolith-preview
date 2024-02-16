<?php
//ZL email queue processor
//02-08-23 - created and adapted from W code circa 03-29-2016
//not tested
require "../../zl_init.php";
zl::setDebugLevel(4);

if(!zl::$set['mailOn']) { exit("dev mode enabled - not processing queue."); } //lockout in dev mode.
$maximum = 50; //max email messages this system can send out in one batch.

//check lock state first.
if(zsys::isLocked('mailQueue'))
{
	echo "Email Queue locked. Try again later.<br>";
	zmail::sendToDebug("mailQueue was locked!", "mailQueue was locked when it ran.");
	exit;
}
else { zsys::lockStart('mailQueue'); } //not locked? lock the system.

$emailQueue = zdb::getArray("SELECT * FROM zl_mail WHERE status = 'queued' LIMIT 0 , " . $maximum, "error retrieving email queue!",);
$emailQueueCount = count($emailQueue);

//unlock system and bail immediately if there is no work to do.
if($emailQueueCount == 0) { zsys::lockStop('mailQueue'); echo "0 emails to process. See ya later."; exit; }

//lock individual emails out from being sent as duplicates by another instance ( prevent multithreading )
$IDsql = "";
foreach($emailQueue as $email) { $IDsql .= $email['ID'] . ", "; }
$IDsql = trim($IDsql, ", ");
zdb::writeSQL("UPDATE zl_mail SET status = 'sending' WHERE ID IN (" . $IDsql . ")", "can't update zl_mail");

//send queue out
$queueResults = zmail::sendArray($emailQueue);
zsys::lockStop('mailQueue'); //unlock system.

//form status message
$m = "Emails in this batch ( " . $maximum . " max ): " . $emailQueueCount . "<br>\n";
$m .= "Emails Sent: " . $queueResults['success'] . "<br>\n";
$m .= "Emails Failed: " . $queueResults['fail'] . "<br><br>\n";
echo $m;

//for debugging - send results to admin only if the batch is large.
if($emailQueueCount > ( $maximum / 4 )) { zmail::sendToDebug("runEmailQueue results", $m); }
?>