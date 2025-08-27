<?php
//zPTA demo 1.0 08/12/2025 - DS
require "../../zl_init.php";
require "index.nav.php"; //navigation menu

$zptaClass = "zPTA"; //switch to change the class

//get variables from search form
extract(zfilter::array("zpta|zptafilter|action|zOrd|created_at|id|username|email|status", "URL"));

//initialize
if($zpta != "Y") { zpage::start("zPTA demo"); }
else { zl::setOutFormat("html"); } //for htmx output

if($action == "installDB") { installDB(); }
elseif($action == "uninstallDB") { uninstallDB(); }

/* --------------------- Searchbar calculation --------------------- */

//don't reprint this in zpta mode
if($zpta != "Y")
{
	//show database install/uninstall
	if(!zdb::tableExists('zl_zpta_test_data_deleteme'))
	{ ?>zPTA demo data must installed first <b><a href="?action=installDB">Install it</a></b><br><br><?php exit; }
	else
	{ ?>zPTA demo data installed. <b><a href="?action=uninstallDB">Uninstall it</a></b><br><br><?php }

	$reasonArray = zdb::getFieldEnums("zl_zpta_test_data_deleteme", "status");
	?>
	<?=zui::hiddenField("zOrd", $zOrd, "zOrd")?>
	<form method="POST" id="searchBox" hx-trigger="searchButton, submit" hx-target=".zPTA" hx-get="?zpta=Y" hx-indicator=".zPTA" hx-include="#zOrd" autocomplete="off">
	
	<table class="zlt_table zPTA_searchBox zl_w100p">
	<tr>
		<td rowspan=4 style="border-right: 1px solid var(--zl_black) !important; background:linear-gradient(var(--zl_greyDark), var(--zl_blackLight)); text-shadow:1px 1px 1px var(--zl_black);" class="zl_pad4"><?=zui::micon("search", "", "zl_white")?></td>
		</tr>
    <tr>
	    <td class="zl_padT3">Email:</td><td class="zl_padT3"><?=zui::textBox("email", $email, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup delay:250ms, changed delay:250ms, search"', "search")?></td>
	    <td>User ID:</td><td><?=zui::textBox("id", $id, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup delay:250ms, changed delay:250ms, search"', "search")?></td>
	    <td>Username:</td><td><?=zui::textBox("username", $username, "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="keyup delay:250ms, changed delay:250ms, search"', "search")?></td>
	    <td>Status:</td><td><?=zui::selectBox("status", $status, $reasonArray, "", "zl_w150", 'hx-get="?zpta=Y" hx-include="#searchBox, #zOrd" hx-trigger="change"', "search")?></td>
    </tr>
    </table>
	</form>
	<br />
	<?php
}

/* --------------------- PTA Output! --------------------- */

$injectLinkString = "";
$injectSQLwhere = ""; //Assemble WHERE query to inject into PTA.
if($email != "") { $injectSQLwhere .= " AND email LIKE '$email%'"; }
if($id != "") { $injectSQLwhere .= " AND id LIKE '$id%'"; }
if($username != "") { $injectSQLwhere .= " AND username LIKE '$username%'"; }
if($status != "") { $injectSQLwhere .= " AND status = '$status'"; }

$selectFields = "created_at,id,username,email,status";
$THfieldClasses = [];
$TDfieldClasses = [];
$showFields = ["id" => "id", "created_at" => "Created At", "username" => "Username", "email" => "Email", "status" => "Status"];
$orderByFields = "id|created_at|email|username|status";
$defaultOrderBy = "";
$rowsPerPage = 16;
$degrade = true;
$degradeWidths = [];
$wrapperClasses = "zl_w100pp";
$htmxIncludeSelector = "#searchBox";
$htmxTargetSelector = ".zPTA";
$htmxGenerateTRIDField = "id";

//process necessary data and return intermediate database object.
$PTAarray = $zptaClass::prepare("zl_zpta_test_data_deleteme", "Users", $showFields, $orderByFields, $defaultOrderBy, $injectSQLwhere, $injectLinkString, $rowsPerPage, $selectFields, $THfieldClasses, $TDfieldClasses, $wrapperClasses, $degrade, $degradeWidths, $htmxTargetSelector, $htmxIncludeSelector, $htmxGenerateTRIDField);

//Mutate the PTA DB rows buffer and feed it back in
$bgClass = "";
foreach($PTAarray as $row)
{
	//show a debug frame.
	$row['created_at'] = '<a href="" hx-target="#debugPreview" hx-swap="innerHTML" hx-get="?action=showDebug&debugID=' . $row['id'] . '" onclick="' . "document.querySelectorAll('.zPTA tr').forEach((element) => { element.classList.remove('zl_bgBlueRow'); }); this.parentElement.parentElement.classList.add('zl_bgBlueRow');" . '">' . ztime::formatTimestamp($row['created_at']) . '</a></span>';
	
	$row['status'] = '<a href="" hx-target=".zPTA" hx-get="?zpta=Y&zptafilter=Y&status=' . $row['status'] .
	zarr::toGetRequest(compact("email","created_at","id","username","zOrd")) . '" class="zl_TFFLink">' .
	$row['status'] . '<span class="zl_right">' . zui::miconR("filter_alt") . '</a></span>';
	
	$zptaClass::addRow($row, $bgClass); //send 'er back.
}

//back-communicate new values to the search form if filter button presssed
if($zpta == "Y")
{
	if($zptafilter == "Y") //update entire set
	{
		?><script>
			document.getElementsByName("email")[0].value = "<?=$email?>";
			document.getElementsByName("id")[0].value = "<?=$id?>";
			document.getElementsByName("username")[0].value = "<?=$username?>";
			document.getElementsByName("status")[0].value = "<?=$status?>";
			document.getElementById("zOrd").value = "<?=$zOrd?>";
		</script><?php
	}
	else /* just update order in the hidden field */
	{ ?><script>document.getElementById("zOrd").value = "<?=$zOrd?>";</script><?php }
}

$zptaClass::output(); //produce PTA object


//utility functions zone


//create test database
function installDB()
{
	// does database exist?
	if(!zdb::tableExists("zl_zpta_test_data_deleteme"))
	{
		// create database & structure
		zdb::writeSQL
		(
			"CREATE TABLE `zl_zpta_test_data_deleteme` (
			`id` int NOT NULL AUTO_INCREMENT,
			`username` char(50) NOT NULL,
			`email` char(100) NOT NULL,
			`status` enum('active','inactive','disabled') NOT NULL DEFAULT 'active',
			`created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY (`id`),
			KEY `email` (`email`),
			KEY `username` (`username`),
			KEY `created_at` (`created_at`)
			) ENGINE=InnoDB AUTO_INCREMENT=100201 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci"
		);
		
		// Insert dem rows
		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		for($i = 0; $i < 500; $i++) 
		{
		    $username = '';
		    for ($j = 0; $j < 10; $j++) { $username .= $chars[rand(0, 40)]; }
			$statuses = ['active', 'inactive', 'disabled'];
			$randomStatus = $statuses[array_rand($statuses)];
			zdb::writeRow("INSERT", "zl_zpta_test_data_deleteme", ['username' => $username, 'email' => $username . "@example.com", 'status' => $randomStatus]);
		}
		zui::notify("ok", "Created test database for use with zpta demo.", "2000");
	}
	else { zui::notify("warn", "But, the database already exists..", "2000"); }
}

//delete all zl tables
function uninstallDB() 
{
    if(zdb::tableExists("zl_zpta_test_data_deleteme") && !zdb::writeSQL("DROP TABLE IF EXISTS `zl_zpta_test_data_deleteme`"))
     { zl::fault("Can't delete the zpta demo data for some reason."); }
	else { zui::notify("ok", "ZPTA demo tables successfully deleted.", "2000"); }
}

?>