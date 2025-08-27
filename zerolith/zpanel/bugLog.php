<?php
//Zerolith Debug Log viewer v1.0 with HTMX
//Modified from Mail Log
require "../zl_init.php";
require "zpanelConfig.php"; //load zpanel settings modification

if(zl_mode == "prod") { zl::fault("bugLog isn't available in production mode."); }

//get variables from search form
extract(zfilter::array("action|zpta|zptafilter|debugID|time|debugReason|visitURL|visitIP|userID|userName|zOrd", "URL"));

if($action == "showDebug")
{
	zperm::requireLevel(5);
	
	zl::setOutFormat("html");
	$debugData = zdb::getRow("SELECT * FROM zl_debug WHERE ID='" . intval($debugID) . "'", "could not load ID: " . $debugID);
	?>
		<div class="zl_left zl_w25p" style="word-break:break-all;">
			<small>
			<?php
			foreach($debugData as $key => $value)
			{
				if($key != "debugDump" && $key != "ID") { echo "<b>" . $key . ":</b> " . $value . "<br>"; }
			}
			?>
			</small>
		</div>
		<div class="zl_right zd_debugStored"><?=gzuncompress($debugData['debugDump'])?></div>
	<?php

	//prevent writing this variable twice in the debugger.
	$debugData['debugDump'] = "[recursive print of debugDump prevented]";

	zl::terminate();
}

//initialize
if($zpta != "Y") 
{ 
	zpage::start("ZL Debug Log"); 
	if(!zperm::hasLevelOrAbove(5)) 
	{ exit("You don't have the permissions to view this page. Make sure an authentication passthrough from your application is in zl_after.php"); }
	zperm::requireLevel(5);
}
else //for htmx output
{
	zperm::requireLevel(5);
	zl::setOutFormat("html"); 
}

/* --------------------- Searchbar calculation --------------------- */

//don't reprint this
if($zpta != "Y")
{
	$reasonArray = zdb::getFieldEnums("zl_debug", "debugReason");
	?>
	<?=zui::hiddenField("zOrd", $zOrd, "zOrd")?>
	<form method="POST" id="searchBox" hx-trigger="searchButton, submit" hx-target=".zPTA" hx-get="?zpta=Y" hx-indicator=".zPTA" hx-include="#zOrd" autocomplete="off">

	<table class="zlt_table zPTA_searchBox zl_w850">
	<tr>
		<!-- Experimental thin search box -->
		<td rowspan=2 style="border-right: 1px solid var(--zl_black) !important; background:linear-gradient(var(--zl_greyDark), var(--zl_blackLight)); text-shadow:1px 1px 1px var(--zl_black);" class="zl_pad4"><?=zui::micon("search", "", "zl_white")?></td>

		<td>Track reason:</td><td><?=zui::selectBox("debugReason",$debugReason,$reasonArray, "","zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd"')?></td>
		<td class="zl_padT3">Time:</td><td class="zl_padT3"><?=zui::textBox("time", $time, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup delay:250ms, changed delay:250ms, search"', "search")?></td>
		<td class="zl_padT3">URL:</td><td class="zl_padT3"><?=zui::textBox("visitURL", $visitURL, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup delay:250ms, changed delay:250ms, search"', "search")?></td>
	</tr>
    <tr>
	    <td>IP:</td><td><?=zui::textBox("visitIP",$visitIP, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup delay:250ms, changed delay:250ms, search"', "search")?></td>
	    <td>UserID:</td><td><?=zui::textBox("userID", $userID, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup delay:250ms, changed delay:250ms, search"', "search")?></td>
	    <td>UserName:</td><td><?=zui::textBox("userName", $userName, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup delay:250ms, changed delay:250ms, search"', "search")?></td>
    </tr>
    </table>
	</form>
	<br />
	<?php
}

/* --------------------- PTA Output! --------------------- */

$injectSQLwhere = ""; //Assemble WHERE query to inject into PTA.
if($debugReason != "") { $injectSQLwhere .= " AND debugReason = '$debugReason'"; }
if($time != "") { $injectSQLwhere .= " AND time = '$time'"; }
if($visitURL != "") { $injectSQLwhere .= " AND visitURL LIKE '%$visitURL%'"; }
if($visitIP != "") { $injectSQLwhere .= " AND visitIP LIKE '$visitIP%'"; }
if($userID != "") { $injectSQLwhere .= " AND userID LIKE '$userID%'"; }
if($userName != "") { $injectSQLwhere .= " AND userName LIKE '$userName%'"; }

$injectLinkString = zarr::toGetRequest(compact("debugReason", "time", "visitURL", "visitIP", "userID", "userName"));
$selectFields = "ID,debugReason,time,visitURL,visitIP,userID,userName,faultReason,visitOS";
$THfieldClasses = [];
$TDfieldClasses = [];
$showFields = ["debugReason" => "Reason", "time" => "Time", "visitURL" => "Visit URL", "visitIP" => "Visit IP", "userID" => "User ID", "userName" => "User Name"];
$orderByFields = "debugReason|time|visitURL|visitIP|userID|userName";
$defaultOrderBy = ["time" => "DESC"];

$degrade = true;
$degradeWidths = [];
$wrapperClasses = "zl_w100pp";
$htmxIncludeSelector = "#searchBox";
$htmxTargetSelector = ".zPTA";
$htmxGenerateTRIDField = "ID";

//process necessary data and return intermediate database object.
$PTAarray = zPTA::prepare("zl_debug", "DebugDumps", $showFields, $orderByFields, $defaultOrderBy, $injectSQLwhere, $injectLinkString, 10, $selectFields, $THfieldClasses, $TDfieldClasses, $wrapperClasses, $degrade, $degradeWidths, $htmxTargetSelector, $htmxIncludeSelector, $htmxGenerateTRIDField);

//Mutate the PTA DB rows buffer and feed it back in
$bgClass = "";
foreach($PTAarray as $row)
{
	//determine row bgcolor
	if($row['faultReason'] != "") { if($bgClass != "zl_bgRedRow") { $bgClass = "zl_bgRedRow"; } else { $bgClass = "zl_bgRedRowAlt"; } }
	else { if($bgClass != "zl_bgAmberRow") { $bgClass = "zl_bgAmberRow"; } else { $bgClass = "zl_bgAmberRowAlt"; } }

	if($row['debugReason'] == "abuse")
	{ if($bgClass != "zl_bgPurpleRow") { $bgClass = "zl_bgPurpleRow"; } else { $bgClass = "zl_bgPurpleRowAlt"; } }

	//filter functions
	$row['debugReason'] = '<a href="" hx-target=".zPTA" hx-get="?zpta=Y&zptafilter=Y&debugReason=' . $row['debugReason'] .
	zarr::toGetRequest(compact("time","visitURL","visitIP","userID","userName","zOrd")) . '" class="zl_TFFLink">' .
	ucfirst($row['debugReason']) . '<span class="zl_right">' . zui::miconR("filter_alt") . '</a></span>';

	$row['visitURL'] = '<a href="" hx-target=".zPTA" hx-get="?zpta=Y&zptafilter=Y&visitURL=' . $row['visitURL'] .
	zarr::toGetRequest(compact("debugReason","time","visitIP","userID","userName","zOrd")) . '" class="zl_TFFLink">' .
	$row['visitURL'] . '<span class="zl_right">' . zui::miconR("filter_alt") . '</a></span>';

	$row['visitIP'] = '<a href="" hx-target=".zPTA" hx-get="?zpta=Y&zptafilter=Y&visitIP=' . $row['visitIP'] .
	zarr::toGetRequest(compact("debugReason","time","visitURL","userID","userName","zOrd")) . '" class="zl_TFFLink">' .
	$row['visitIP'] . '<span class="zl_right">' . zui::miconR("filter_alt") . '</a></span>';

	$row['userID'] = '<a href="" hx-target=".zPTA" hx-get="?zpta=Y&zptafilter=Y&userID=' . $row['userID'] .
	zarr::toGetRequest(compact("debugReason","time","visitURL","visitIP","userName","zOrd")) . '" class="zl_TFFLink">' .
	$row['userID'] . '<span class="zl_right">' . zui::miconR("filter_alt") . '</a></span>';

	$row['userName'] = '<a href="" hx-target=".zPTA" hx-get="?zpta=Y&zptafilter=Y&userName=' . $row['userName'] .
	zarr::toGetRequest(compact("debugReason","time","visitURL","visitIP","userID","zOrd")) . '" class="zl_TFFLink">' .
	$row['userName'] . '<span class="zl_right">' . zui::miconR("filter_alt") . '</a></span>';

	//show a debug frame.
	$row['time'] = '<a href="" hx-target="#debugPreview" hx-swap="innerHTML" hx-get="?action=showDebug&debugID=' . $row['ID'] . '" onclick="' . "document.querySelectorAll('.zPTA tr').forEach((element) => { element.classList.remove('zl_bgBlueRow'); }); this.parentElement.parentElement.classList.add('zl_bgBlueRow');" . '">' . ztime::formatTimestamp($row['time']) . '</a></span>';

	zPTA::addRow($row, $bgClass); //send 'er back.
}

//back-communicate new values to the search form if filter button presssed
if($zpta == "Y")
{
	if($zptafilter == "Y") //update entire set
	{
		?><script>
			// selectbox
			// zl.getSelector("[name='debugReason']").value = "<?=$debugReason?>";
			document.getElementsByName("debugReason")[0].value = "<?=$debugReason?>";
			document.getElementsByName("visitURL")[0].value = "<?=$visitURL?>";
			document.getElementsByName("visitIP")[0].value = "<?=$visitIP?>";
			document.getElementsByName("userID")[0].value = "<?=$userID?>";
			document.getElementsByName("userName")[0].value = "<?=$userName?>";
			document.getElementById("zOrd").value = "<?=$zOrd?>";
		</script><?php
	}
	else /* just update order in the hidden field */
	{ ?><script>document.getElementById("zOrd").value = "<?=$zOrd?>";</script><?php }
}

zPTA::output(); //produce PTA object

//if we're just getting the table
if($zpta == "Y"){ zl::terminate(); }
else
{
	?>
	<br>
	<div id="debugPreview" class="zl_ib">Debug information will show here when an item is clicked.</div>
	<?php
}

?>