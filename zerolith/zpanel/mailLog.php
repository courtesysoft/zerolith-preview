<?php
//Zerolith Mail Log viewer v1.0 with HTMX
//Modified from Contractor Mate's View Tasks screen
require "../zl_init.php";
zl::setOutFormat("page");
zl::$page['wrap'] = true;
zauth::requireLevel(5);
zl::setDebugLevel(4);

//get variables from search form
extract(zfilter::array("zpta|zptafilter|action|emailID|zOrd|status|to|toUserID|message|subject|category", "stringExtended"));

if($action == "showEmail") //htmx segment
{
	zl::setOutFormat("html");
	
	$emailData = zdb::getRow("SELECT * FROM zl_mail WHERE ID='" . intval($emailID) . "'", "could not load email ID: " . $emailID);
	?>
	Time Sent: <?=$emailData['timeSent']?><br />
	To userID: <?=$emailData['toUserID']?><br />
	To address: <?=trim($emailData['to'], "|")?><br />
	Result Data: <?=$emailData['resultData']?></P>
	<p>Subject: <?=$emailData['subject']?></p>
	<hr />
	<p><?=nl2br(stripslashes($emailData['message']))?></p>
	<?php
	zl::terminate();
}

//initialize
if($zpta != "Y") { zpage::start("ZL Email Log"); }
else { zl::setOutFormat("html"); } //for htmx output

/* --------------------- Searchbar calculation --------------------- */

//don't reprint this
if($zpta != "Y")
{
	$statusArray = zdb::getFieldEnums("zl_mail", "status");
	zui::hiddenField("zOrd", $zOrd, "zOrd"); //this field sits outside of the form
	?>
	<form method="POST" id="searchBox" hx-trigger="searchButton, submit" hx-target=".zPTA" hx-get="?zpta=Y" hx-indicator=".zPTA" hx-include="#zOrd" autocomplete="off">
	
	<table class="zlt_table zPTA_searchBox zl_w850">
	<tr>
		<!-- Experimental thin search box -->
		<td rowspan=2 style="border-right: 1px solid var(--zl_black) !important; background:linear-gradient(var(--zl_greyDark), var(--zl_blackLight)); text-shadow:1px 1px 1px var(--zl_black);" class="zl_pad4"><?=zui::micon("search", "", "zl_white")?></td>
		
		<td class="zl_padT3">To:</td><td class="zl_padT3"><?=zui::textBox("to",$to, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup changed delay:250ms"')?></td>
		<td class="zl_padT3">Subject:</td><td class="zl_padT3"><?=zui::textBox("subject",$subject, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup changed delay:250ms"')?></td>
		<td class="zl_padT3">Message:</td><td class="zl_padT3"><?=zui::textBox("message",$message, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup changed delay:250ms"')?></td>
	</tr>
    <tr>
	    <td>ToUserID:</td><td><?=zui::textBox("toUserID",$toUserID, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup changed delay:250ms"')?></td>
	    <td>Category:</td><td><?=zui::textBox("category", $category, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup changed delay:250ms"')?></td>
	    <td>Sent Status:</td><td><?=zui::selectBox("status",$status,$statusArray, "","zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd"')?></td>
    </tr>
    </table>
	</form>
	<br />
	<?php
}

/* --------------------- PTA Output! --------------------- */

$injectSQLwhere = ""; //Assemble WHERE query to inject into PTA.
if($category != "") { $injectSQLwhere .= " AND category LIKE '$category%'"; }
if($status != "") { $injectSQLwhere .= " AND status = '$status'"; }
if($to != "") { $injectSQLwhere .= " AND `to` LIKE '%$to%'"; }
if($toUserID != "") { $injectSQLwhere .= " AND toUserID = '$toUserID'"; }
if($subject != "") { $injectSQLwhere .= " AND subject LIKE '$subject%'"; }
if($message != "") { $injectSQLwhere .= " AND message LIKE '%$message%'"; }

$injectLinkString = zarr::toGetRequest(compact("status","to","toUserID","subject","message"));
$selectFields = "ID,status,timeSent,`to`,toUserID,subject,category";
$THfieldClasses = [];
$TDfieldClasses = [];
$showFields = ["timeSent" => "Time Sent", "category" => "Category", "subject" => "Subject", "to" => "Sent To", "status" => "Status"];
$orderByFields = "status|subject|to|timeSent|category";
$defaultOrderBy = ["timeSent" => "DESC"];

$degrade = true;
$degradeWidths = [];
$wrapperClasses = "zl_w100pp";
$htmxIncludeSelector = "#searchBox";
$htmxTargetSelector = ".zPTA";

//process necessary data and return intermediate database object.
$PTAarray = zPTA::prepare("zl_mail", "emails", $showFields, $orderByFields, $defaultOrderBy, $injectSQLwhere, $injectLinkString, 10, $selectFields, $THfieldClasses, $TDfieldClasses, $wrapperClasses, $degrade, $degradeWidths, $htmxTargetSelector, $htmxIncludeSelector);

//Mutate the PTA DB rows buffer and feed it back in
$bgClass = "";
foreach($PTAarray as $row)
{
	//determine row bgcolor
	if($row['status'] == "sent")
	{ if($bgClass != "zl_bgGreenRow") { $bgClass = "zl_bgGreenRow"; } else { $bgClass = "zl_bgGreenRowAlt"; } }
	else if($row['status'] == "queued")
	{ if($bgClass != "zl_bgAmberRow") { $bgClass = "zl_bgAmberRow"; } else { $bgClass = "zl_bgAmberRowAlt"; } }
	else if($row['status'] == "failed")
	{ if($bgClass != "zl_bgRedRow") { $bgClass = "zl_bgRedRow"; } else { $bgClass = "zl_bgRedRowAlt"; } }
	else if($row['status'] == "frozen")
	{ if($bgClass != "zl_bgBlueRow") { $bgClass = "zl_bgBlueRow"; } else { $bgClass = "zl_bgBlueRowAlt"; } }
	else { $bgClass = ""; }
	
	//filter functions
	$row['status'] = '<a href="" hx-target=".zPTA" hx-get="?zpta=Y&zptafilter=Y&status=' . $row['status'] .
	zarr::toGetRequest(compact("subject","message","to","category","toUserID","zOrd")) . '" class="zl_TFFLink">' .
	ucfirst($row['status']) . '<span class="zl_right">' . zui::miconR("filter_alt") . '</a></span>';
	
	$row['to'] = '<a href="" hx-target=".zPTA" hx-get="?zpta=Y&zptafilter=Y&to=' . $row['to'] .
	zarr::toGetRequest(compact("status","subject","message","category","toUserID","zOrd")) . '" class="zl_TFFLink">' .
	$row['to'] . '<span class="zl_right">' . zui::miconR("filter_alt") . '</a></span>';
	
	$row['subject'] = '<a href="" hx-target=".zPTA" hx-get="?zpta=Y&zptafilter=Y&subject=' . $row['subject'] .
	zarr::toGetRequest(compact("status","message","to","category","toUserID","zOrd")) . '" class="zl_TFFLink">' .
	$row['subject'] . '<span class="zl_right">' . zui::miconR("filter_alt") . '</a></span>';
	
	$row['category'] = '<a href="" hx-target=".zPTA" hx-get="?zpta=Y&zptafilter=Y&category=' . $row['category'] .
	zarr::toGetRequest(compact("status","subject","message","to","toUserID","zOrd")) . '" class="zl_TFFLink">' .
	$row['category'] . '<span class="zl_right">' . zui::miconR("filter_alt") . '</a></span>';
	
	//direct edit
	$row['timeSent'] = '<a href="" hx-target="#emailPreview" hx-get="?action=showEmail&emailID=' . $row['ID'] . '">' . ztime::formatTimestamp($row['timeSent']) . '</a></span>';
	
	zPTA::addRow($row, $bgClass); //send 'er back.
}

//back-communicate new values to the search form if filter button presssed
if($zpta == "Y")
{
	if($zptafilter == "Y") //update entire set
	{
		?><script>
			document.getElementsByName("to")[0].value = "<?=$to?>";
			document.getElementsByName("toUserID")[0].value = "<?=$toUserID?>";
			document.getElementsByName("category")[0].value = "<?=$category?>";
			document.getElementsByName("subject")[0].value = "<?=$subject?>";
			document.getElementsByName("status")[0].value = "<?=$status?>";
			document.getElementById("zOrd").value = "<?=$zOrd?>";
		</script><?php
	}
	else { ?><script>document.getElementById("zOrd").value = "<?=$zOrd?>";</script><?php }
}

zPTA::output(); //produce PTA object

//if we're just getting the table
if($zpta == "Y"){ zl::terminate(); }
else
{
	?>
	<br>
	<div class="zl_black zl_padTB4">Email Display:</div>
	<div id="emailPreview" class="zl_borderBlack zl_mw850 zl_pad3">
		yeah
	</div>
	<?php
}

?>