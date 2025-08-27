<?php
//ZL Page Control 1.0
class zpage
{
	private static $navContainer = [];          //an arrayed list of what's in the container.
	private static $addedIncludes = [];         //list of added includes ( facilitates preventing duplicates )

	public static $pageStarted = false;         //marker as to whether we started the page or not.
	public static $pageEnded = false;           //marker as to whether we ended the page or not.
	public static $includesDisplayed = false;   //tracker of whether we displayed includes ( makes the debugger more resilient )
	public static $includesStart = "";          //this HTML will be included in the <head> ( pageIncludes )
	public static $includesEnd = "";            //this HTML will be included in the footer ( pageEnd )
	public static $pauseAtRedirect = false;     //toggle this in code to 'step' through refreshes.
	
	public static $pageEndHTML = "";            //output a chunk of HTML during pageEnd.
	public static $navEndHTML = "";             //output a chunk of HTML at the bottom of the navigation.
	public static $htmxDebug = false;           //turn on/off htmx debugger
	
	//output an inline CSS style that highlights space consumed + outlines
	public static function debugCSS()
	{ ?><style>* { outline: 1px solid rgba(255,0,0,0.5) !important; background-color:rgba(0,255,0,0.15) !important; }</style><?php }
	
	//redirect to another page, no matter what
	public static function redirect($url)
	{
		if(zl::$set['outFormat'] != 'page') { return; }

		if(!headers_sent())
		{
			zl::quipDZL("Redirected via header");
			if(self::$pauseAtRedirect && zl_mode == "dev") { echo '<p>zpage wants to refresh to: <a href="' . $url . '">' . $url . '</a></p>'; }
			else { header("Location: " . $url); }
		}
		else
		{

			zl::quipDZL("Redirected via meta refresh");
			if(self::$pauseAtRedirect && zl_mode == "dev")
			{ echo '<p>zpage wants to refresh to: <a href="' . $url . '">' . $url . '</a></p>'; }
			else
			{
				if(zl_mode == "dev")
				{
					$refFile = ""; $refLine = ""; //form nag string
					$temp = headers_sent($refFile, $refLine); //do not alter this line. This is really how it works in PHP.
					$refString = $refFile . "-line-" . $refLine;

					//autistically screech about it until this bug is fixed
					if(zs::containsCase($url, "?")) { $url .= "&forcedMetaRefreshBecause=" . $refString; }
					else { $url .= "?forcedMetaRefreshBecause=" . $refString; }
				}

				?><meta http-equiv="refresh" content="0;URL='<?=$url?>'"><?php
			}
		}
		zl::terminate();
	}
	
	//Add an include of any sort by simply specifying the HTTP path.
	//position is relative to zpage's state: start,now,end
	//Later on, this will conditionally combine + minify CSS/JS
	public static function addInclude(string $filename, string $position = "start", string $media = "screen")
	{
		//check for previous including of the include; prevent duplicates a particular position.
		if(isset(self::$addedIncludes[$filename]) && self::$addedIncludes[$filename] == $position) { return; }
		else { self::$addedIncludes[$filename] = $position; }

		if(strpos(strtolower($filename),".js",-3))      {$inc='<script src="' . $filename . '"' . "></script>\n";}
		else if(strpos(strtolower($filename),".css",-4)){$inc='<link rel="stylesheet" media="'.$media.'" href="'.$filename.'"'.">\n";}
		else if(strpos(strtolower($filename),".png",-4)){$inc='<link rel="icon" type="image/png" href="' . $filename . '"' . ">\n";} //png used as site favicon

		if($position == "start") { self::$includesStart .= $inc; }
		elseif($position == "now") { echo $inc; }
		elseif($position == "end") { self::$includesEnd .= $inc; }
		else { zl::fault("Invalid include position [" . zstr::sanitizeHTML($position) . "] sent to zpage::addInclude: [" . zstr::sanitizeHTML($filename) . "]"); }
	}

	//load the essentials; you can call this at any time in your code.
	public static function start(...$args)
	{
		if(zl::$set['outFormat'] != 'page') { return; }
		if(!self::$pageStarted) { zl::quipDZL("zpage Starting page."); } else { return false; } //let's not do this twice.
		self::$pageStarted = true;
		?>
		<!-- pageStart -->
		<?php
		//should we run the config file's start function?
		if(zl::$page['wrap'] && zl::$page['startFunc'] != "") { call_user_func_array(zl::$page['startFunc'], $args); }
		else{ self::includes(); } //just includes.
		?>
<!-- /pageStart -->
<?php
		return true;
	}

	//Mandatory for any kind of ZL output.
	public static function includes()
	{
		if(zl::$set['outFormat'] != 'page') { return; } //not applicable to non-page modes (html|api)
		if(self::$includesDisplayed) { return; } //don't do this twice for any reason
		?>
		<!-- ZL frontend -->
		<link rel='stylesheet' href='<?=zl::$site['URLZLpublic']?>zl.css'>
		<link rel='stylesheet' href='/zerolithData/zl_theme.css'>
		<link rel='stylesheet' href='<?=zl::$site['URLZLpublic']?>zl_3p.css'>
		<link rel='stylesheet' href='<?=zl::$site['URLZLpublic']?>zui.css'>
		<link rel='stylesheet' href='<?=zl::$site['URLZLpublic']?>zPTA.css'>
		<?php
		if(isset(zl::$page['deprecatedCSS']) && zl::$page['deprecatedCSS'])
        { ?><link rel='stylesheet' href='<?=zl::$site['URLZLpublic']?>zl.deprecated.css'><?php }
        ?>
		<script src="<?=zl::$site['URLZLpublic']?>3p/jquery.js"></script>
		<script src="<?=zl::$site['URLZLpublic']?>3p/htmx.js"></script>
		<script src="<?=zl::$site['URLZLpublic']?>zl.js"></script>
		<script src="<?=zl::$site['URLZLpublic']?>zl2.js"></script>
		<?="\n" . self::$includesStart?>
		<!-- /ZL frontend -->
		<?php
		self::$includesDisplayed = true;
	}

	//direct passthrough to the queue of items to output during nav()
	public static function addNavItem($name, $url, $iconName, $extraClasses = "white", $variant = "")
	{ zl::$page['navItems'][] = get_defined_vars(); }

	//output side navigation
	public static function nav()
	{
		if(zl::$set['outFormat'] != 'page') { return; }
		if(zl::$page['wrap'] && zl::$page['navFunc'] != "") { call_user_func(zl::$page['navFunc']); }
		if(!self::$pageStarted) { zl::quipDZL("nav(): I don't wanna."); return false; }
	}

	//end page; automatically triggered during page exit
	public static function end($dontTerminate = false, ...$args)
	{
		//prevent programmer boo-boos.
		if(self::$pageEnded) { return; }
        zl::quipDZL("zpage - Ending page.");
		if(zl::$set['outFormat'] != 'page') { zl::terminate("shutdown"); }
		?>
<!-- pageEnd-->
<?php
		//mandatory includes
		?><script src="<?=zl::$site['URLZLpublic']?>zl_footer.js"></script><?php
		
		if(zl_mode == "dev" && self::$htmxDebug || zl_mode == "stage" && self::$htmxDebug && isset(zl::$set['debugInStageMode']) && zl::$set['debugInStageMode'])
		{
			?><script>htmx.logAll();</script><?php //turn on logging
			?><script src="<?=zl::$site['URLZLpublic']?>3p/htmx-ext/debug.js"></script><?php
		}
		
		echo "\n" . self::$includesEnd;
		if(zl::$page['wrap'] && zl::$page['endFunc'] != "") { call_user_func_array(zl::$page['endFunc'], $args); }
		?>
<!-- /pageEnd-->
<?php
		self::$pageEnded = true;
		
		//determine if we're already in a zl::terminate shutdown sequence. If so, prevent running the termination sequence twice.
		$BTrace = debug_backtrace(0,2);
		if(isset($BTrace[1]) && $BTrace[1]['function'] == "terminate") { return; }
		else { if($dontTerminate) { zl::terminate("shutdown"); } } //initiate termination
	}

    //generic zerolith page wrappers
	public static function navZL($extraClasses = "")
	{
	
	}
	
	public static function startZL($extraClasses = "")
	{
	
	}
	
	public static function endZL($extraClasses = "")
	{
	
	}
	
	//generate navigation from static array created by addNavItem() calls.
	public static function navCourtesy()
	{
		?>
<!-- pageNav-->
		<nav id="zl_sideNav">
			<div id="zlt_navHead" class="zl_shadowTB3">
				<button id="zlt_sideNavClose" onClick="zl_sideNav.toggleAttribute('open')">X</button><?php
				if(zl::$page['logoLink'] != "")
				{
					?><a href="<?=zl::$page['logoLink']?>"><IMG SRC="<?=zl::$site['logo']?>" alt="<?=zl::$site['name']?> Logo" class="zlt_siteLogo"></a><?php
				}
				else
				{ ?><IMG SRC="<?=zl::$site['logo']?>" alt="<?=zl::$site['name']?> Logo" class="zlt_siteLogo"><?php }
				?><b><?=zl::$site['name']?></b>
			</div>
			<div id="zlt_navMenu">
				<?php
				//render each queued item.
				foreach(zl::$page['navItems'] as $navItem)
				{
					if(zs::isBlank($navItem['url'])) { ?><div class="zlt_navItem"><br></div><?php } //fake space.
					else
					{
						//navitem matches? highlight it..
						if(strpos(str_replace("/", "", $_SERVER['REQUEST_URI']), str_replace("/", "", $navItem['url'])) === 0)
						{ $c = ' class= "active" '; } else { $c = ""; }

						?><div class="zlt_navItem"><a href="<?=$navItem['url']?>"<?=$c?>><?=zui::micon($navItem['iconName'], $navItem['variant'], $navItem['extraClasses'])?> &nbsp;<?=$navItem['name']?></a></div><?php
					}
				}
				?>
			</div>
			<?=self::$navEndHTML?>
		</nav>
<!-- /pageNav-->
		<?php
	}

	//default header for courtesy software products
	public static function startCourtesy($pageTitle = "No page title", $bodyClass = "")
	{	
		//if contractor mate, run Contractor Mate's special init routine
		if(class_exists("cm", false) && isset(cm::$user['avatar']) && cm::$user['avatar'] != "") { cm::zpageinit(); }
		
		if($pageTitle == "") { $title = zl::$site['name']; }
		else
		{
			if(zs::containsCase($pageTitle, "*") ) { $pageTitle = ""; $title = str_replace("*", "", $pageTitle); }
			else { $title = $pageTitle; }
		}

		?>
		<!DOCTYPE html><html lang="en">
		<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0"/>
		<title><?=$title?></title>
		<?=self::includes()?>
		</head>
		<body class="zlt_body <?=$bodyClass?>">
		
		<div id="zlt_pageWrap">
			<div id="zlt_headerWrap">
				
				<a href="#" class="show-on-large" onclick="zl_sideNav.toggleAttribute('open')">
				<?=zui::miconR("menu", "", "zlt_burger")?></a>
				<div class = "zl_left zl_padTB1"><?php
					if(zl::$page['logoLink'] != "")
					{
						?><a href="<?=zl::$page['logoLink']?>"><IMG SRC="<?=zl::$site['logo']?>" alt="<?=zl::$site['name']?> Logo" class="zlt_siteLogo"></a><?php
					}
					else
					{ ?><IMG SRC="<?=zl::$site['logo']?>" alt="<?=zl::$site['name']?> Logo" class="zlt_siteLogo"><?php }
				?></div>
				<span class ="zlt_pageTitle"><?=$pageTitle?></span><?php
				
				//start cmate code if applicable
				if(class_exists("cm", false) && isset(cm::$user['avatar']) && cm::$user['avatar'] != "")
				{
					if(zs::contains(cm::$user['avatar'], ".")) //lame way to detect an image
					{
						if(zs::isBlank(zl::$site['profileLinks'])) //no dropdown
						{ ?><div class="zlt_headerAvatar" style="background-image:url(<?=cm::$user['avatar']?>);"></div><?php }
						else
						{
							$numNotifications = get::numberOfNotifications(cm::$user['ID']);
							?>
							<div class="zlt_dropDown" id="toolbar">
							<div class="notification_icon zl_padLR4 zl_marT3" id="notification_icon">
							<?php
							if($numNotifications > 0) { $fadeClass = ""; } else { $fadeClass = "zl_bw5"; }
							zui::micon("construction", "", $fadeClass . " zl_marT-2 zl_hovPoint","onclick=toggleNotificationDropdown()");
							
							if($numNotifications > 0)
                            {
                                ?>
							    <span class="notification_icon__badge zl_marL-3"><?= $numNotifications?></span>
							    <?php
                            }
                            ?>
							</div>
								<div class="zlt_headerAvatar" style="background-image:url(<?=cm::$user['avatar']?>);" onclick=zl_showdropDown();></div>
								<div id="mydropDown" class="zlt_dropDown-content">
								<?php foreach(zl::$site['profileLinks'] as $k => $v) {
                                    ?><a
								href="<?=$v?>"><?=$k?></a><?php } ?>
								<div class="zlt_dropDown_arrow"></div>
								</div>
								<div id="myNotificationdropDown" class="zlt_notification_dropDown-content"
                                     style="width: 350px; height: 400px; overflow: auto"">
								<?php
								$x=1;

								foreach(cm::$user['notifications'] as $k => $v)
                                {
                                      $notificationRowClass = get::notificationRowClass($v['about']);
                                      $opacity = get::notificationStatus($v['userHasRead']);
                                      $x % 2 == 0 ? $rowColor = "even" : $rowColor="odd";
                                      $notificationID = $v['ID'];
                                    ?>
                                    <table class = "notificationTable" id="notification<?=$notificationID?>" <?=$opacity.' '.
                                $rowColor?>">
                                    <tr id="sys1">
                                    <td id="agg_data" class="data agg" rowspan="3">
                                        <div class=''><?=get::iconForNotification($v['about'])?></div>
                                    </td><td id="sys1_data" class="data sys1" colspan="3">  <img src="<?=$v['fromUserAvatar']?>" class="cm_avatar notificationAvatar"><?=$v['fromUserName'].' '.get::notificationAction($v['about'])?>
                                       </td>
                                    </tr>
                                    <tr id="sys2">
                                    <td id="sys2_data" class="data" colspan="3" ><a href="editTask.php?id=<?=$v['taskID']?>&action=edit&clearNotificationID=<?=$v['ID']?>"><?= $v['taskName']?>
                                    </td>
                                    </tr>
                                    <tr id="arc">
                                    <td id="arc_data" class="data timeAgo" colspan="3"><?=ztime::timeAgo($v['timestamp'])?>
                                    </td><td><?=zui::micon("delete","","err","hx-get='ajax/ajax.php?function=deleteNotification&notificationID=$notificationID' hx-target='#notification$notificationID' hx-swap='delete' hx-trigger='click'")?></td>
                                    </tr>
                                    </table>
								    <?php
								    $x++;
                                }
                                ?>

								<div class="zlt_notification_dropDown_arrow"><?php
								 $userID = cm::$user['ID'];
								 if
								(notifications::checkAutoMarkReadSetting(cm::$user['ID'])){
                                    zui::buttonJS("mark all read",
								"allMark","check","","data='allMark' onclick='markAllRead($userID)'");}?>
								</div>
								</div>
							</div>

							<script>
								/* When the user clicks on the button, toggle between hiding and showing the zlt_dropDown content */
								function zl_showdropDown() { document.getElementById("mydropDown").classList.toggle("zl_block"); }

                                function toggleNotificationDropdown() {
                                var theBox = document.getElementById('myNotificationdropDown');

                                if (theBox.className.indexOf('open') > -1) {
                                // indexOf returns -1 when the string is not found,
                                // therefore 'open' is found if the index is
                                // greater than -1; so 'open', so here
                                // we close it:
                                theBox.className = theBox.className.replace('open', '');

                                //check if 'auto mark read' is set. if so, mark all notifications as read;
                                var link = '/ajax/ajax.php?function=reloadNotificationIcon';
                                htmx.ajax('GET', link, {target:'#notification_icon', swap:'innerHTML'})
                               //var link = '/ajax/ajax.php?function=updateDropdownDiv';
                                //htmx.ajax('GET', link, {target:'#toolbar', swap:'innerHTML'})


                                } else {
                                   // the else here means that the string was not found,
                                // returning an index of -1 (or, technically, -1 or less;
                                // but indexOf only returns -1, 0 or positive indexes.
                                // so the string was not found, means the 'open' is
                                // not there and so it must be added:
                                theBox.className = theBox.className + ' open';
                              //  var link = '/ajax/ajax.php?function=updateNotificationArray';
                               // htmx.ajax('GET', link, {target:'#notification_icon', swap:'innerHTML'})
                                }
}


								// Close the zl_dropDown menu if the user clicks outside of it
								window.onclick = function(event)
								{
									if (!event.target.matches('.zlt_headerAvatar'))
									{
										var i; var zlt_dropDowns = document.getElementsByClassName("zlt_dropDown-content");
										for (i = 0; i < zlt_dropDowns.length; i++)
										{ if (zlt_dropDowns[i].classList.contains('zl_block')) { zlt_dropDowns[i].classList.remove('zl_block'); } }
									}
								}
							</script>
						<?php
						}
					}
				}
				//end cmate code
				?>
			</div>
		<?php
		if($pageTitle != "" && $pageTitle != "No page title") { zpage::nav(); $noPad = ""; } //no sidebar.
		else { $noPad = ' class="noSidebarPad" style="padding-top:5px;"'; }
		
		if(zl::$page['navFunc'] == "") { ?><div id="zlt_mainWrap" class='noNav'><?php }
		else { ?><div id="zlt_mainWrap"<?=$noPad?>><?php }
	}

	//page end for courtesy
	public static function endCourtesy($msg = "")
	{
		?>
			</div>
		</div>
		<?=self::$pageEndHTML?>
		</body>
		</html>
		<?php
	}
}
?>