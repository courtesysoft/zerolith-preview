<?php
//ZL Page Control 1.0 - (c)2022 Courtesy Software

class zpage
{
	private static $navPosition = 0;            //visual hierarchy tracker
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

	//redirect to another page
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
					//form nag string
					$refFile = ""; $refLine = "";
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
		if(zl::$set['outFormat'] != 'page') { return; }
		if(self::$includesDisplayed) { return; } //don't do that
		?>
		<!-- Zl frontend -->
		<link rel='stylesheet' href='<?=zl::$site['URLZLpublic']?>zl.css'>
		<link rel='stylesheet' href='<?=zl::$site['URLZLpublic']?>../../zl_theme.css'>
		<link rel='stylesheet' href='<?=zl::$site['URLZLpublic']?>zl_3p.css'>
		<link rel='stylesheet' href='<?=zl::$site['URLZLpublic']?>zui.css'>
		<link rel='stylesheet' href='<?=zl::$site['URLZLpublic']?>zPTA.css'>
		<script src="<?=zl::$site['URLZLpublic']?>3p/jquery.js"></script>
		<script src="<?=zl::$site['URLZLpublic']?>3p/materialize.js"></script>
		<script src="<?=zl::$site['URLZLpublic']?>3p/htmx.js"></script>
		<script src="<?=zl::$site['URLZLpublic']?>zl.js"></script>
		<script src="<?=zl::$site['URLZLpublic']?>zl2.js"></script>
		<?="\n" . self::$includesStart?>
		<!-- /Zl frontend -->
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
		else { zl::quipDZL("nav(): Okay."); }

		?>
		<script> // zpage side navigation
		$(document).ready(function()
		{
			if(document.getElementsByClassName("side-nav").length) //does the side nav exist?
			{
				$('.button-collapse').sideNav({
						menuWidth: <?=zl::$page['navWidth']?>, // Default is 300
						edge: 'left', // Choose the horizontal origin
						closeOnClick: false, // Closes side-nav on <a> clicks, useful for Angular/Meteor
						draggable: true // Choose whether you can dragThis to open on touch screens
						//onOpen: function(el) { } //do something.
					}
				);
			} else { zl_echo("cannot find sideNav.."); }
			//$('.button-collapse').sideNav('show'); //start open regardless of state
		});
		</script>
		<?php
	}

	//end page; automatically triggered during page exit
	public static function end($dontTerminate = false, ...$args)
	{
		//prevent programmer boo-boos.
		if(self::$pageEnded) { return; }
		if(zl::$set['outFormat'] != 'page') { zl::terminate("shutdown"); }
		else { zl::quipDZL("Ending page."); }
		?>
<!-- pageEnd-->
<?php
		//mandatory includes
		?><script src="<?=zl::$site['URLZLpublic']?>zl_footer.js"></script><?php
		
		if(zl_mode == "dev" && self::$htmxDebug)
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
	
	public static function navZL($extraClasses = "")
	{
	
	}
	
	public static function startZL($extraClasses = "")
	{
	
	}
	
	public static function endZL($extraClasses = "")
	{
	
	}
	
	//generate navigation from static array.
	public static function navCourtesy($extraClasses = "")
	{
		?>
<!-- pageNav-->
		<nav class="side-nav fixed">
			<div id="sideNav">
				<div id="zlt_navHead" class="zl_shadowTB3">
					<button data-activates="sideNav" id="zlt_sideNavClose" onClick="zl.hideNav();">X</button><?php
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
			</div>
		</nav>
<!-- /pageNav-->
		<?php
	}

	//default header for courtesy software products
	public static function startCourtesy($pageTitle = "No page title", $bodyClass = "")
	{
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
				
				<a href="#" data-activates="sideNav" class="button-collapse show-on-large">
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
				if(class_exists("cm", false) && isset(cm::$user['avatar']) && cm::$user['avatar'] != "")
				{
					if(zs::contains(cm::$user['avatar'], ".")) //lame way to detect an image
					{
						if(zs::isBlank(zl::$site['profileLinks'])) //no dropdown
						{ ?><div class="zlt_headerAvatar" style="background-image:url(<?=cm::$user['avatar']?>);"></div><?php }
						else
						{
							?>
							<div class="zlt_dropDown">
								<div class="zlt_headerAvatar" style="background-image:url(<?=cm::$user['avatar']?>);" onclick=zl_showdropDown();></div>
								<div id="mydropDown" class="zlt_dropDown-content">
								<?php foreach(zl::$site['profileLinks'] as $k => $v) { ?><a href="<?=$v?>"><?=$k?></a><?php } ?>
								<div class="zlt_dropDown_arrow"></div>
								</div>
							</div>

							<script>
								/* When the user clicks on the button, toggle between hiding and showing the zlt_dropDown content */
								function zl_showdropDown() { document.getElementById("mydropDown").classList.toggle("zl_block"); }

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