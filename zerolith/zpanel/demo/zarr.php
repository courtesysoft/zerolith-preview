<?php

require "../../zl_init.php";
require "index.nav.php"; //navigation menu

zpage::start("zarr test");

//yer basic array
$singleNum = [];
$singleNum[] = "yahoo";
$singleNum[] = "yipee";
$singleNum[] = "yeehaw";

//a more advanced basic
$multiNum =
	[
		[1, 2, 3, 4],
		["jeff", "bob", "mary", "anne"],
		[1, "potato", 2, "potato"]
	];

//typical output from zdb::getRow()
$singleAssoc = ["date" => "now", "first" => "bub", "last" => "rub", "active" => "Y"];

//typical output from zdb::getArray()
$multiAssoc =
	[
		["saladID" => "1", "name" => "caesar", "leaf" => "iceberg mix", "dressing" => "caesar"],
		["saladID" => "2", "name" => "hippie", "leaf" => "kale", "dressing" => "overpriced yogurt"],
		["saladID" => "3", "name" => "cobb", "leaf" => "iceberg", "dressing" => "ranch"]
	];

//typical output from zdb::getArray()
$multiAssoc2 = [["saladID" => "1", "name" => "caesar", "leaf" => "iceberg mix", "dressing" => "caesar"]];

//typical output from zdb::getArray()
$multiAssocInconsistent =
	[
		["dance" => "tango", "dancer" => "bob"],
		["dance" => "tango", "dancer" => "thedj", "accessories" => "turntable"],
		["dance" => "tango", "dancer" => "jane"]
	];

//typical output from zdb::getArray()
$multiAssocInconsistent2 =
	[
		["move" => "boogaloo", "pika" => "chu"],
		["dance" => "tango", "dj" => "thedj"],
		["dance" => "tango", "dancer" => "jane"]
	];

$multiMixed =
	[
		["dance" => "tango", "thedj"],
		["tango", "dancer" => "jane"]
	];

$empty = [];

//too complex to figure out or work with ( maybe is JSON output etc that must be manually stepped through )
$complex =
	[
		"it's" => "time",
		[
			"yeah" => ["yeah" => "boiiiee"],
			"turtle" => "time"
		],
		"pizza" => "power"
	];

$singleMixed = ["beef jerky", "beefy" => "stick", "beavis", "beef" => "steak"];

//mixed type
class testHamster { public $howdy = true; }

$ham = new testHamster();
$mixedObject = ["yip" => "yeah", $ham, "yeah", [$ham]];

//show the goods
arrayInfo($singleNum, "num");
zui::printTable($singleNum);
arrayInfo($multiNum, "multiNum");
zui::printTable($multiNum);
arrayInfo($singleAssoc, "singleAssoc");
zui::printTable($singleAssoc);
arrayInfo($multiAssoc, "multiAssoc");
zui::printTable($multiAssoc);
arrayInfo($multiAssoc, "multiAssocFancy");
zui::printTable($multiAssoc, ["name" => "Salad Name", "dressing" => "Dressing", "leaf" => "Leaf"], ['name' => 'zl_w250']);
arrayInfo($multiAssoc2, "multiAssoc2");
zui::printTable($multiAssoc2);
arrayInfo($multiAssocInconsistent, "multiAssocInconsistent");
zui::printTable($multiAssocInconsistent);
arrayInfo($multiAssocInconsistent2, "multiAssocInconsistent2");
zui::printTable($multiAssocInconsistent2);
arrayInfo($singleMixed, "singleMixed");
zui::printTable($singleMixed);
arrayInfo($multiMixed, "multiMixed");
zui::printTable($multiMixed);
arrayInfo($mixedObject, "mixedObject");
zui::printTable($mixedObject);
arrayInfo($complex, "complex");
zui::printTable($complex);
arrayInfo($empty, "empty");
zui::printTable($empty);

//show for array
function arrayInfo(array $array, $name)
{
	$left = "Array type: " . $name . "<br><pre>" . zs::pr($array) . "</pre>";
	$right = "Results:<br><pre>" . zs::pr(zarr::getArrayInfo($array, true)) . "</pre>";
	
	?><br><br><br>
	<table class="zl_w100p zlt_table">
		<tr>
			<td class="zl_w50p zl_valignT zl_pad3"><?=$left?></td>
			<td class="zl_w50p zl_valignT zl_pad3"><?=$right?></td>
		</tr>
	</table><br><?php
}

?>