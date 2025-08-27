<?php
//This cronjob handles cleanup tasks for ZL's database
//v1.0 - 04/18/2024 - created
require "../../zl_init.php";

//Generic database cleanup for single table structures
$cleanups =
[
	['table' => 'zl_mail',  'tstampField' => 'timeSent',  'retainDays' => 180],
	['table' => 'zl_debug', 'tstampField' => 'time',      'retainDays' => 14],
];

?><pre><?php
foreach($cleanups as $cleanup)
{
	$numRows = zdb::writeSQL("DELETE FROM " . $cleanup['table'] . " WHERE `" . $cleanup['tstampField'] . "` >= DATE_SUB(CURDATE(), INTERVAL " . $cleanup['retainDays'] . " DAY)","Could not clean up " . $cleanup['table'], true);
	echo $numRows . " rows were deleted from " . $cleanup['table'] . "\n";
}
?></pre><?php