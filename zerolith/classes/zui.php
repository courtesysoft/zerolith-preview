<?php
//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
//
//ZL UI Class v1.3 - (c)2023 Courtesy Software
//09/2020 - started.
//06/2021 - removed dependency on ZL framework, added autogeneration of IDs, major cleanup
//01/2022 - Redesigned and readded ZL dependency for syntax ease.
//02/2022 - Implemented CSS variables and themeing system, pseudo-namespaced css, new elements.
//08/2022 - Simplified window buttons with windowAction
//09/2022 - Added readmore
//03/2023 - Improved fork of ZLT; removed auto ID generation for most features, added nameoverride,
//          added extraHTML as a means to write an ID, rearranged parameter orders for uniformity.
//			Jodit integration, cleaner auto-generation of IDs when needed
//07/2023 - Added zvalid messages
//08/2023 - Added R ( return ) variant for more items

class zui
{
	private static $elementIDs = [];        //internal tracking of items. Internal DOM.
	private static $formVars = [];          //internal list of form variables ( hint to zl )
	
	public static $lastIDreadMore = "";     //for coordination of zl::quipD sending quips to two buffers ( readmore ID clash )
	
	//------- toggle switches --------
	public static $autocompleteOff = true;      //automatically add 'autofill="off" to HTML controls.
	public static $readOnly = false;            //produce 'read only' representations of controls.
	public static $showZvalidFail = false;      //render red borders and a message on fail
	public static $showZvalidSuccess = false;   //render green borders and a checkmark on pass
	
	
	//quick aliases for output buffering commands
	public static function bufStart() { ob_start(); }
	public static function bufStop() { return ob_get_clean(); }
	
	//for flash messages session
	private static $flashSess = "zlFlashMsg";
	
	//write a message to the session that you can pick up later.
	public static function flashWrite(string $messageName = '', string $messageText = '', string $notifyCode = '')
	{
		//unset and re-add.
		if(isset($_SESSION[self::$flashSess][$messageName])) { unset($_SESSION[self::$flashSess][$messageName]); }
        $_SESSION[self::$flashSess][$messageName] = ['msg' => $messageText, 'type' => $notifyCode];
	}
	
	//write a message to the session to buffer this later
	public static function flashRead($messageName = "")
	{
		if($messageName !== '') // display single flash message
		{
			if(!isset($_SESSION[self::$flashSess][$messageName])) { return; }
			$fmsg = $_SESSION[self::$flashSess][$messageName];
			unset($_SESSION[self::$flashSess][$messageName]);
			self::notify($fmsg['type'], $fmsg['msg']);
		}
		elseif($messageName === '') // display all flash messages.
		{
			if(!isset($_SESSION[self::$flashSess])) { return; }
			$fmsgs = $_SESSION[self::$flashSess];
			unset($_SESSION[self::$flashSess]);
			foreach ($fmsgs as $fmsg) { self::notify($fmsg['type'], $fmsg['msg']); }
		}
	}
	
	//if there was a validation error, output it's text and give it a javascript-identifiable class name.
	public static function validMSG($varName)
	{
		if(self::$showZvalidFail && !zs::isBlank(zfilter::$validateChecksFailed[$varName])) { ?><span class="zvalid_<?=$varName?> zl_err"><?=zfilter::$validateChecksFailed[$varName]?></span><?php }
		if(self::$showZvalidSuccess && !zs::isBlank(zfilter::$validateChecksFailed[$varName])) { ?><span class="zvalid_<?=$varName?> zl_ok"><?=self::micon("check","","ok")?></span><?php }
	}
	
	//produces a list of input variables produced by zui that can be sent to zfilter::array(). Currently unused
	public static function getFormVars() { return zarr::toPipe(self::$formVars); }
	
	//visual quip - only use for acceptably nerdy printouts!
	public static function quip($quip = "", $title = "Information", $micon = "message")
	{
		if($micon == "pest_control") { $class = " error"; } else { $class = ""; }
		$quip = zs::pr($quip);
		$html = "\n<table class='zlt_table" . $class . "'>\n<tr><th>" . self::miconR($micon, "", "white") . "&nbsp; " . ucfirst($title) . "</th></tr>";
		$html .= '<tr><td><pre>' . zstr::sanitizeHTML($quip) . '</pre></td></tr></table>';
		echo $html;
	}
	
	//cut the text after
	public static function readMore($text, $charLimit = 512, $lineLimit = 7, $dotdotdot = "...")
	{
		$strlen = mb_strlen($text);
		$lineCount = mb_substr_count($text, "\n");
		if($lineCount <= $lineLimit && $strlen <= $charLimit) { return $text; } //below our limits.
		else
		{
			$ID = zsys::getTimeSerial();
			self::$lastIDreadMore = $ID; //needed for quip buffer hack
			
			if($lineCount > $lineLimit) //line by line ( for debugger etc ).
			{
				$lineArray = explode("\n",$text);
				
				//compile both text strings
				$preview = ""; $rest = "";
				for($i = 0; $i < $lineLimit; $i++) { $preview .= $lineArray[$i] . "\n"; }
				for($i = $lineLimit; $i < $lineCount; $i++) { $rest .= $lineArray[$i] . "\n"; }
			}
			else //simple string logic
			{
				//walk back looking for & characters
				$offset = 0;
				for($i = 0; $i < 10; $i++)
				{
					//make offset to adjust the break point behind the & character
					if(substr($text, ($charLimit - $i), 1) == "&") { $offset = $i; break; }
				}
				
				//$preview = substr($text,0,($charLimit - $offset));
				//$rest = substr($text, -($strlen - $charLimit + $offset));
				$preview = mb_substr($text,0,($charLimit - $offset));
				$rest = mb_substr($text, -($strlen - $charLimit + $offset));
			}
			
			echo $preview . '<span ID="zl_RM_' . $ID . '_dot">' . $dotdotdot . '</span>' .
			'<span id="zl_RM_' . $ID . '_more" class="zlt_RM">' . $rest . '</span><a onclick="zl.readMore(' . "'" . $ID. "'" . ')"><i class="zlt_micon err zl_hovPoint" ID="zl_RM_' . $ID . '_button">expand_circle_down</i></a>';
		}
	}
	
	//handle a fault in this class the way this class thinks.. visually!
	private static function fault($reason = "unspecified") { self::notify("error","<b>zui: </b>".$reason); }
	
	//DEPRECATED! 01/2024 - DS
	//Produce an arbitrary number of columns based on dynamic input.
	//Example: 2 columns: "someExtraClass", $col1HTML, $col1Classes, $col2HTML, $col2Classes
	//$classes determine width or other zl shorthand parameters
	//$behavior determines the collapsing behavior  on small screens eg mobile devices.
	public static function columns($wrapperClass = "", ...$columnData)
	{
		$columnCount = count($columnData);
		if($columnCount & 1 || $columnCount == 0) //& is bitwise for 'odd'.
		{ self::fault("Incorrect no. of column parameters (" . $columnCount . ")."); }
		else
		{
			?>
			<div class="zlt_columns<?=self::zEC($wrapperClass)?>"><?php
			for($i = 0; $i < $columnCount; $i +=2)
			{ ?><div class="zlt_column<?=self::zEC($columnData[$i + 1])?>"><?=$columnData[$i]?></div> <?php }
			?></div>
			<?php
		}
	}
	
	// --------------------- HTML Elements --------------------- //
	
	/* output Google Icons. */
	//variant codes: tt = twin tone, o = outlined version
	public static function micon($miconName, $variantCode = "", $extraClasses = "", string $extraHTML = "", $toolTipText = "")
	{
		//compile micon string
		$miconText = '<i class="zlt_micon' . $variantCode . self::zEC($extraClasses) . '"' . self::zEH($extraHTML) . '>' . $miconName . '</i>';
		
		if($toolTipText != "") { self::toolTip($miconText, $toolTipText); } //Fancy: supposedly no worky
		else { echo $miconText; } //regular
	}
	
	//make a button that doesn't submit - for use with JS.
	public static function buttonJS($buttonTitle, $nameOverride = "", $micon = "", $extraClasses = "", $extraHTML = "")
	{
		if($nameOverride != "") { $name = $nameOverride; } else { $name = $buttonTitle; } //name = $buttontitle by default
		?>
		<button type="button" class="zlt_button<?=self::zEC($extraClasses)?>" name="<?=$name?>"<?=self::zD()?><?=self::zEH($extraHTML)?>><?=self::zMI($micon)?><?=$buttonTitle?></button><?php
	}
	
	//make a button that submits a form.
	public static function buttonSubmit($buttonTitle, $nameOverride = "", $micon = "", $extraClasses = "", $extraHTML = "")
	{
		if($nameOverride != "") { $name = $nameOverride; } else { $name = $buttonTitle; } //name = $buttontitle by default
		self::$formVars[] = $name;
		
		?>
		<button type="submit" value="<?=$buttonTitle?>" class="zlt_button<?=self::zEC($extraClasses)?>" name="<?=$name?>"<?=self::zEH($extraHTML)?><?=self::zD()?>><?=self::zMI($micon)?><?=$buttonTitle?></button><?php
	}
	
	//make a button that goes to a link ( cosmetic link button )
	public static function buttonLink($buttonTitle, $link, $micon = "", $extraClasses = "", $extraHTML = "")
	{
		?>
		<a href="<?=$link?>"><button class="zlt_button<?=self::zEC($extraClasses)?>"<?=self::zEH($extraHTML)?><?=self::zD()?>><?=self::zMI($micon)?><?=$buttonTitle?></button></a>
		<?php
	}
	
	//make a button that submits a form. Accepts an associative array of hidden fields.
	public static function buttonForm($buttonTitle, array $hiddenFields, $nameOverride = "", $micon = "", $extraClasses = "", string $extraHTMLform = "", string $extraHTMLbutton = "")
	{
		if($nameOverride != "") { $bname = $nameOverride; } else { $bname = $buttonTitle; } //name = $buttontitle by default
		?><form action="" method="post" class="zl_inline-block"<?=self::zEH($extraHTMLform)?>>
        <?php foreach($hiddenFields as $name => $value)
		{
			self::$formVars[] = $name;
			?><input type="hidden" name="<?=$name?>" value="<?=$value?>" /><?php
		} ?>
		<button type="submit" class="zlt_button<?=self::zEC($extraClasses)?>" name="<?=$bname?>"<?=self::zD()?><?=self::zEH($extraHTMLbutton)?>><?=self::zMI($micon)?><?=$buttonTitle?></button>
        </form>
        <?php
	}
	
	//produce a checkbox.
	//checkedIfValue makes the box checked if $varValue is this value.
    public static function checkBox($varName, $varValue, $labelText = "", $checkedIfValue = "Y", $extraClasses = "", $extraHTML = "")
    {
		self::$formVars[] = $varName;
        if(strtolower($varValue) == strtolower($checkedIfValue)){ $checked = true; } else { $checked = false; }
        if($labelText == "" || $labelText == " ") { $labelText = '&nbsp;'; } //display tends to freak out if blank..
	 
		$id = self::generateID("checkBox_" . $varName);
        ?>
        <div class="zlt_checkBox">
        <input type="checkbox" id="<?=$id?>" class="<?=self::zEC($extraClasses)?>" name="<?=$varName?>"<?php if($checked){echo ' CHECKED';}?> value="Y"<?=self::zEH($extraHTML)?>>
	    <?php if($labelText != "" && $labelText != " ") { ?><label for="<?=$id?>"><?=$labelText?></label><?php } ?>
	    </div>
	    <?php
    }
	
	//create a radio box with a label.
	//checkedIfValue makes the box checked if $varValue is this value.
    public static function optionBox($varName, $varValue, $labelText = "", $checkedIfValue = "Y", $extraClasses = "", $extraHTML = "")
    {
		self::$formVars[] = $varName;
        if(strtolower($varValue) == strtolower($checkedIfValue)){ $checked = true; } else { $checked = false; }
		
		$id = self::generateID("optionBox_" . $varName);
        ?>
        <div class="zlt_optionBox">
        <input type="radio" id="<?=$id?>" class="<?=self::zEC($extraClasses)?><?=self::zV($varName)?>" name="<?=$varName?>"<?php if($checked){echo ' CHECKED';}?> value="<?=$checkedIfValue?>"<?=self::zD()?><?=self::zEH($extraHTML)?>>
	    <label for="<?=$id?>"><?=$labelText?></label>
        </div>
	    <?php
    }
    
	//Spits out a selectbox from an array
	//Setting $defaultItem to false will disable the default item
	//Input array formats accepted:
	//+ Keyless array ['Desiree Johnson', 'Jeff Warner'] uses the single value for code value AND readable name
	//+ Flat associative array ['32' => 'Desiree Johnson', '33' => 'Jeff Warner'] (code value, readable name)
	//+ 2 field DB array: [['ID' => '32', 'name' => 'Dee Johnson'], ['ID' => '33', 'name' => 'Jeff Warner']] (code value, readable name)
	//+ 3 field DB array: [['ID' => '32', 'name' => 'Dee Johnson', 'avatar' => 'Dee.gif']], etc (code value, readable name, avatar)
	public static function selectBox($varName, $varValue, $listArray = [], $defaultItem = "", $extraClasses = "", $extraHTML = "", $addHTMX = 'N')
	{
		//init
		self::$formVars[] = $varName;
		if(zs::isBlank($listArray)) { $listArray = ["" => ""]; } //produce a blank field..
		if($defaultItem == "") { $defaultItem = "- none -"; } //set a name for the default value
		$hasAvatars = false;
		
		//preprocess the types of arrays selectbox can use
		$arrayInfo = zarr::getArrayInfo($listArray, true);
		if ($arrayInfo['type'] == "singleAssoc") { }     //default flat associative array format, ie ["32" => "Dee Johnson"]
		else if ($arrayInfo['type'] == "singleNum")      //simple format ("bob", "mary", "jane"); convert to "bob" => "bob".
		{
			//zl::quipD("selectbox converted simple format");
			$temp = [];
			foreach ($listArray as $list) { $temp[$list] = $list; }
			$listArray = $temp;
		}
		else if (zs::contains($arrayInfo['type'], "multi") && count($arrayInfo['allKeys']) == 2) //database input, first field is code value, second is readable name
		{
			//force it into a singleAssoc format
			$temp = [];
			foreach ($listArray as $list)
			{
				$tempLet = [];
				foreach ($list as $listLet) { $tempLet[] = $listLet; }
				$temp[$tempLet[0]] = $tempLet[1];
			}
			$listArray = $temp;
		}
		else if(zs::contains($arrayInfo['type'], "multi") && count($arrayInfo['allKeys']) == 3) //3 value (key, value, avatar) format
		{
			//force it into a 3 wide array format
			$temp = [];
			foreach ($listArray as $list)
			{
				$tempLet = [];
				foreach ($list as $listLet) { $tempLet[] = $listLet; }
				$temp[] = $tempLet;
			}
			$listArray = $temp;
			
			//$hasAvatars =false;
			$hasAvatars = true;
			zl::quipD($listArray);
		}
		else { } //this should be an error.
		
		//for triple select box
		if($hasAvatars)
		{
			//var_dump($listArray); die();
			$setfordd = '';
			if ($addHTMX == 'Y') { $htmx = 'hx-get="?zpta=Y" hx-include="#searchBox,#orderBy" hx=trigger="change"'; }
			else { $htmx = ''; }
			?>
			<div class="dropdown">
			<div class="parent">
				<div class="hideme" onclick="showElement('menu-id', true);">hiddendiv</div>
				<img class="cm dropdown_avatar noTransition" id="mainAvatar" src="#" style="position:relative; display:none; margin-left: 3px; top:-20px; width:23px; height:23px; ">
				<div class="child">
					<select <?=$htmx?> name="clientID" id="clientID" class="zlt_i zlt_selectBox<?=self::zEC($extraClasses)?>" style="padding-left:27px !important;" onfocus="showElement('menu-id', true)" <?=self::zEH($extraHTML)?>>
						<option value="">- none -</option>
						
						<?php
						$foundItem = false;
						foreach ($listArray as $list)
						{
							if(!$foundItem && $varValue == $list[0]) { $selected = " SELECTED"; $foundItem = true; }
							else { $selected = ""; }
							?>'
							<option value="<?= $list[0] ?>"<?= $selected ?>><?= $list[1] ?></option><?php
						}
						?>
					</select>
				</div>
			</div>
			
			<ul class="list" id="menu-id" tabindex="0" onmouseleave="showElement('menu-id', false)">
			<?php
		}
		else
		{
			$setfordd = '';
			?><select <?=$setfordd?> name="<?=$varName?>" class="zlt_i zlt_selectBox<?=self::zEC($extraClasses)?><?=self::zV($varName)?>"<?=self::zD() ?><?=self::zEH($extraHTML)?>><?php
		}
		
		
		//create the bottom list.
		$foundItem = false;
		$buf = "";
		
		if(!$hasAvatars) //iterate flat associative array
		{
			foreach ($listArray as $variableName => $humanName)
			{
				if (!$foundItem && $varValue == $variableName) { $selected = " SELECTED"; $foundItem = true; }
				else { $selected = ""; }
				$buf .= '<option value="' . $variableName . '"' . $selected . '>' . $humanName . "</option>\n";
			}
			
			//add default item to the top.
			if(!$foundItem)
			{
				if ($varValue == "") { $selected = " SELECTED"; }
				else { $selected = ""; }
			}
			
			if($defaultItem !== false) { $buf = '<option value="" ' . $selected . '>' . $defaultItem . "</option>\n" . $buf; }
			
		}
		else //iterate 3 wide format
		{
			$buf .= "<li onclick=\"setValue('clientID', '','-none-',''); showElement('menu-id', false);\">
							    <img class=\"cm cm_avatar\" src=\"\" style=\"max-width: 130px;\">-none-
						    </li>";
			foreach ($listArray as $list)
			{
				if (!$foundItem && $varValue == $list[0]) { $selected = " SELECTED"; $foundItem = true; }
				else { $selected = ""; }
				//$buf .= '<option data-img_src="' . $list[2] . '" value="' . $list[0] . '"' . $selected . '>' . $list[1] . "</option>\n";
				$buf .= "<li onclick=\"setValue('clientID', '$list[0]','$list[1]','$list[2]'); \">
							    <img class=\"cm cm_avatar\" src=\"$list[2]\" style=\"max-width: 130px;\">$list[1]
						    </li>";
			}
		}
		
		echo $buf;
		if($hasAvatars)
		{
			?></ul>
			</div>
			</span>
			<?php
		}
		else { ?></select><?php }
	}
    
    //1 line textbox
    public static function textBox($varName, $varValue, $extraClasses = "", $extraHTML = "", $boxType = "text")
    {
		$boxType = strtolower($boxType);
		if($boxType == "password" || $boxType == "search") { $type = $boxType; } else { $boxType = "text"; }
		
		self::$formVars[] = $varName;
		$ea = self::zEH($extraHTML);
		if(!zs::contains(strtolower($ea), "maxlength")) { $ea .= ' maxlength = "255"'; } //set defaults.
		if(!zs::contains(strtolower($ea), "size")) { $ea .= ' size = "10"'; } //set defaults
    	?><input type="<?=$boxType?>" name="<?=$varName?>" value="<?=$varValue?>" class="zlt_i zlt_textBox<?=self::zEC($extraClasses)?><?=self::zV($varName)?>"<?=self::zD()?><?=$ea?>/><?php
    }
    
    //multi line text box
    public static function textArea($varName, $varValue, $extraClasses = "zl_h50p", $extraHTML = "")
    {
		self::$formVars[] = $varName;
		$ea = self::zEH($extraHTML);
		if(!zs::contains(strtolower($ea), "rows")) { $ea .= ' rows = "7" '; } //set defaults.
		if(!zs::contains(strtolower($ea), "cols")) { $ea .= ' cols = "40" '; }
    	?><textarea name="<?=$varName?>" class="zlt_i zlt_textArea<?=self::zEC($extraClasses)?><?=self::zV($varName)?>"<?=self::zD()?><?=$ea?>><?=$varValue?></textarea><?php
    }
	
	//multi line text box with CKEditor - only works for one editor currently.
    public static function textAreaCK($varName, $varValue, $featureSet = "basic", $extraClasses = "zl_h50p", $extraHTML = "", $showFiles = false)
    {
		if(self::$readOnly) { echo self::textArea($varName, $varValue, $extraClasses, $extraHTML); }
		else
		{
			//interpret what the sent ID is so we can attach ckeditor to it
			if(zs::contains($extraHTML, "id =") || zs::contains($extraHTML, "id="))
			{
				$matches = [];
				preg_match("/\bid\s*=\s*(?:'|\")(.*?)(?:'|\")/i", $extraHTML, $matches);
				$ID = zarr::last($matches); //take the last one if the programmer goofed and sent two
			}
			else //patch a fake ID on
			{
				$ID = "ckeditor_" . $varName;
				$extraHTML .= ' ID = "' . $ID . '"';
			}
			
			?>
			<script src="/zerolith/public/3p/ckeditor5/build/ckeditor.js"></script>
	        <?=self::textArea($varName, $varValue, $extraClasses, $extraHTML)?>
		    <script type="text/javascript">
		    $(function()
		    {
		        const watchdog = new CKSource.EditorWatchdog();
		        window.watchdog = watchdog;
		
		        watchdog.setCreator((element, config) => { return CKSource.Editor.create(element, config).then(editor => {return editor;})});
		        watchdog.setDestructor(editor => {return editor.destroy();});
		        watchdog.on('error', handleError);
		        watchdog.create(document.querySelector('textarea' + '#<?=$ID?>'), {licenseKey: '',}).catch(handleError);
		
		        function handleError(error) {
		          console.error('Oops, something went wrong!');
		          console.error('Please, report the following error on https://github.com/ckeditor/ckeditor5/issues with the build id and the error stack trace:');
		          console.warn('Build id: x5ww4hg9egrb-zh2jnbipaktx');
		          console.error(error);
	        }
	        });
	        </script>
		    <?php
		}
    }
	
	//produce jodit editor
	public static function textAreaJodit($varName, $varValue, $featureSet = "basic", $extraClasses = "zl_h50p", $extraHTML = "", $showFiles = false)
    {
		if(self::$readOnly) { echo self::textArea($varName, $varValue, $extraClasses, $extraHTML); }
		else
		{
			//interpret what the sent ID is so we can attach jodit to it
			if(zs::contains($extraHTML, "id =") || zs::contains($extraHTML, "id="))
			{
				$matches = [];
				preg_match("/\bid\s*=\s*(?:'|\")(.*?)(?:'|\")/i", $extraHTML, $matches);
				$ID = zarr::last($matches); //take the last one if the programmer goofed and sent two
			}
			else //patch a fake ID on
			{
				$ID = "jodit_" . $varName;
				$extraHTML .= ' ID = "' . $ID . '"';
			}
			
			?>
			<?=self::textArea($varName, $varValue, $extraClasses, $extraHTML)?>
			<link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/jodit/3.24.2/jodit.min.css"/>
			<script src="//cdnjs.cloudflare.com/ajax/libs/jodit/3.24.2/jodit.min.js"></script>
			<script>
			<?php
			if($featureSet == "basic") //basic settings for email editor only
			{
				?>
				const editor = Jodit.make("#<?=$ID?>", {
				  "buttons": "bold,italic,underline,strikethrough,brush,paragraph,|,ul,ol,hr,table,link,|,spellcheck",
				  "toolbarAdaptive": false
				});
				<?php
			}
			elseif($featureSet == "courtesy") //use features of pro edition - incomplete
			{
				?>
				//create the custom notify icon
				Jodit.modules.Icon.set('notifyIcon', '<svg viewBox="0 0 14 14" xmlns="http://www.w3.org/2000/svg"><circle cx="6" cy="6" r="8" /> </svg>');
				Jodit.defaultOptions.controls.notify =
				{
					name: 'notify',
					icon: 'notifyIcon',
					tooltip: 'Insert notify div',
					exec: (editor) => { editor.s.insertHTML('<div class="zlt_notifyerr">message</div>'); }
				}
				
				//create the jodit editor
				const editor = Jodit.make("#<?=$ID?>", {
				  "buttons": "bold,italic,underline,strikethrough,brush,paragraph,|,ul,ol,hr,table,link,|,spellcheck,notify",
				  "toolbarAdaptive": false
				});
				<?php
			}
			
			?>
		</script>
		<?php
		}
    }
	
	public static function hiddenField($varName, $varValue, $ID = "")
	{
		self::$formVars[] = $varName;
		?><input type="hidden" name="<?=$varName?>" id="<?=$ID?>" value="<?=$varValue?>"><?php
	}
	
	/* v-- unfinished */
	
	public static function colorPicker($varName, $varValue)
	{
		self::$formVars[] = $varName;
		?><input type="color" class ="zlt_colorPicker" name="<?=$varName?>" value="<?=$varValue?>"><?php
	}
	
	public static function datePicker($varName, $varValue, $minDate = "", $maxDate = "")
	{
		self::$formVars[] = $varName;
		if($minDate != "") { $minDate = ' min="' . $minDate . '"'; }
		if($maxDate != "") { $maxDate = ' max="' . $maxDate . '"'; }
		?><input type="date" class = "zlt_datePicker" name="<?=$varName?>" value="<?=$varValue?>"<?=$minDate . $maxDate?>><?php
	}
	
	public static function dateTimePicker($varName, $varValue, $minDate = "", $maxDate = "")
	{
		self::$formVars[] = $varName;
		if($minDate != "") { $minDate = ' min="' . $minDate . '"'; }
		if($maxDate != "") { $maxDate = ' max="' . $maxDate . '"'; }
		?><input type="dateTime" class = "zlt_datePicker" name="<?=$varName?>" value="<?=$varValue?>"<?=$minDate .
    $maxDate?>><?php
	}
    
    // --------------------- Abstractions --------------------- //
	
	//surround something in a box.
	public static function box($html, $extraClasses = ""){ echo '<div class="zlt_box' . self::zEC($extraClasses) . '">' . $html . "</div>"; }
	
    //print a nice looking notification to screen.
    public static function notify($type, $message)
	{
		//correct mistypes
	    if($type == 'warning') { $type = 'warn'; }
		elseif($type == 'error') { $type = 'err'; }
		elseif($type == 'success') { $type = 'ok'; }
	 
		//force a default if the programmer screwed up
		if($type != 'warn' && $type != 'err' && $type != 'ok') { $type = 'warn'; }
		
		//assign a micon
		if($type == "warn") { $micon = self::miconR("warning_amber", "", "warn"); }
		elseif($type == "ok") { $micon = self::miconR("check", "TT", "ok"); }
		elseif($type == "err") { $micon = self::miconR("close", "O", "err"); }
		
		//long or short format?
		if(strlen($message) <= 15) { ?><div class="zlt_notify<?=$type?>"><?=$micon?>&nbsp;<?=$message?></div><?php }
		else { ?><div class="zlt_notify<?=$type?>"><table><tr><td class="zl_pad0"><?=$micon?>&nbsp;</td><td class="zl_pad0"><?=$message?></td></tr></table></div><?php }
	}
	
    //wrap a tooltip around a HTML chunk
    public static function toolTip($htmlChunk, $tipCaption)
    { ?><div class="zlt_toolTip"><?=$htmlChunk?><span class="zlt_toolTipText"><?=$tipCaption?></span></div><?php }
	
	//shows minimize/maximize/exit
	public static function windowAction(string $type, string $divToToggle, string $showMode = "inline-block")
	{
		switch($type)
		{
			case "min": ?><span class = "zlt_fakeButton" onclick="zl.hideID('<?=$divToToggle?>')"><?=self::miconR("remove", "", "white")?></span><?php break;
			case "max": ?><span class = "zlt_fakeButton" onclick="zl.showID('<?=$divToToggle?>', '<?=$showMode?>')"><?=self::miconR("add", "", "white")?></span><?php break;
			case "close": ?><span class = "zlt_fakeButton" onclick="zl.deleteID('<?=$divToToggle?>')"><?=self::miconR("close", "", "white")?></span><?php break;
			default: zl::quipDZL("zui::windowAction sent wrong type");
		}
	}
	
	//Send a basic associative array with the key as the title, and the value as the content, and get tabs.
	//Could use some serious optimization.
	//Does not work with multiple instances - deprecated
	public static function tabs($tabData, $extraClassesTabs = "", $extraClassesContent = "", $openFirstTab = false, $rightOfTabsHTML = "")
	{
		if(!is_array($tabData)){ self::fault("Invalid array passed to zui::tabs"); return; }
		$ID = self::generateID("zui_tabset");
		$tabID = 1;
		?>
		<!-- tabs -->
		<div class="zlt_tabSet<?=self::zEC($extraClassesTabs)?>" id="<?=$ID?>">
			<div class = "zl_w100p">
				<ul class="zlt_tabs"><?php
				foreach($tabData as $title => $html)
				{ ?><li><a href="#ztabs<?=$tabID?>" id="ztab<?=$tabID?>" onclick="zl_tabSel<?=$ID?>('<?=$tabID?>')"><?=$title?></a></li><?php $tabID++; }
				?></ul><?php if($rightOfTabsHTML != "") { echo '<span class="zl_right">' . $rightOfTabsHTML . '</span>'; } ?>
			</div>
			<div class="zlt_tabContent<?=self::zEC($extraClassesContent)?>">
			<?php
			
			foreach($tabData as $title => $html) { ?><section><?=$html?></section><?php }
			?></div>
		</div>
		
		<script>
		//Tabs controller.
		function zl_tabSel<?=$ID?>(tabNumber) //Select a tab
		{
			//affect tab
			tabs = document.querySelectorAll("#<?=$ID?> .zlt_tabs > li a");
			found = false;
			for (let i = 0; i < tabs.length; i++)
			{
				if(i == (tabNumber - 1)) { tabs[i].classList.add("active"); found = false; }
				else{ tabs[i].classList.remove("active"); }
			}
			
			//affect tab section
			tabSections = document.querySelectorAll("#<?=$ID?> .zlt_tabContent > section");
			for (let i = 0; i < tabSections.length; i++)
			{
	            if(i == (tabNumber -1)) { tabSections[i].classList.remove("zl_hide"); }
				else { tabSections[i].classList.add("zl_hide"); }
			}
		}
		
		document.addEventListener('DOMContentLoaded', function ()
		{
			defTab = window.location.hash.replace("#ztabs", "");
			openFirst = <?php if($openFirstTab) echo "true;"; else echo "false;" ?>
			if(defTab == "" || openFirst) { defTab = "1"; }
			zl_tabSel<?=$ID?>(defTab); //select that tab.
		});
		</script>
		<?php
	}
	
	//Send a basic associative array with the key as the title, and the value as the content, and get tabs.
	//ZL adaptation of https://codepen.io/MPDoctor/pen/mpJdYe
	public static function tabsCSS($tabData, $defaultTab = 1, $extraClassesTabs = "", $extraClassesContent = "", $rightOfTabsHTML = "")
	{
		$tabSerial = zsys::getTimeSerial();
		
		if(!is_array($tabData)){ self::fault("Invalid array passed to zui::tabs"); return; }
		$ID = self::generateID("zui_tabset");
		$tabID = 1;
		?>
		<!-- tabs CSS -->
		<div class="zui_tabWrap<?=self::zEC($extraClassesTabs)?>">
			<?php
			//produce virtual radio buttons
			$i = 1;
			foreach($tabData as $k => $v)
			{
				if($i == $defaultTab) { $checked = " checked"; } else { $checked = ""; }
				?><input type="radio" id="tab<?=$i?>_s<?=$tabSerial?>" name="tabs_<?=$tabSerial?>"<?=$checked?>><?php
				$i++;
			}
			
			//produce tab visual
			?>
			<ul class="tabs">
				<?php
				$i = 1;
				foreach($tabData as $k => $v)
				{
					?><li class="tab"><label for="tab<?=$i?>_s<?=$tabSerial?>"><?=$k?></label></li><?php
					$i++;
				}
				if($rightOfTabsHTML != "") { echo '<div class="zl_w100pp"><span>' . $rightOfTabsHTML . '</span></div>'; }
				?>
			</ul><?php
			
			//produce tab contents
			foreach($tabData as $k => $v) { ?><div class="tab-content<?=self::zEC($extraClassesContent)?>"><?=$v?></div><?php }
			?>
		</div>
		<?php
	}
	
	
    //prints a table from an associative or flat array.
	//Best for printing database output.
	//$showFields optionally renames TH titles based on SQL field => english names relations. Works exactly like ZPTA;
	public static function printTable($tableArray, $extraClasses = "", $extraHTML = "", $showFields = [], $isMagic = false)
    {
		//init
    	if(zs::isBlank($tableArray)) { self::notify("warn", "There isn't any information to display."); return; }
		if($extraClasses != "") { $ec = " " . $extraClasses; } else { $ec = ""; }
	    $arrayInfo = zarr::getArrayInfo($tableArray, true); //identify type of array
		
		//add another layer of depth if we don't have an array of arrays
		if($arrayInfo['depth'] == 1 && $arrayInfo['type'] != "singleNum") { $tableArray = [$tableArray]; }
		elseif($arrayInfo['depth'] > 2) //can't
		{ self::notify("error", "The sent array is more than 2 layers deep; cannot display"); return; }
		elseif(!$arrayInfo['canLoop']) //nope
		{ self::notify("error", "The sent array un-loopable ( contains objects, strange structure, etc )"); return; }
		
		//quick, uncomplicated output
		if($arrayInfo['type'] == "singleNum")
		{
	        echo "\n" . '<table class="zlt_table' . $ec . '"' . $extraHTML . '>' . "\n";
	        echo '<tr class = "zl_stickyT"><th>Array</th></tr>';
		    foreach($tableArray as $value) { echo "<tr><td>" . $value . "</td></tr>"; }
		    echo "</table>\n";
			return;
		}
	    
		//determine first keys.
	    if(!isset($tableArray[0]))
	    {
			$firstData = zarr::first($tableArray); //multi row
	    	if(!zarr::isAssociative($firstData)) { $firstData = $tableArray; } //must be a single row single line
	    }
	    else { $firstData = zarr::first($tableArray); }
	    
    	echo "\n" . '<table class="zlt_table' . $ec . '"' . $extraHTML . '>' . "\n";
    	echo '<tr class = "zl_stickyT">';
	    
		$filterFields = !zs::isBlank($showFields); //for speed
		
		foreach($firstData as $key => $value)
		{
			if($filterFields){ if(isset($showFields[$key])) { echo "<th>" . $showFields[$key] . "</th>"; } }
			else { echo "<th>" . $key . "</th>"; }
		}
		
	    echo "</tr>\n";
	    
		$x = 0;
	    foreach($tableArray as $tableItem)
        {
            echo "<tr id=$x>";
			foreach($tableItem as $key => $value)
			{
				if($filterFields) { if(isset($showFields[$key])) { echo "<td>" . zs::pr($value) . "</td>"; } }
				else { echo "<td>" . zs::pr($value) . "</td>"; }
			}
			echo "</tr>";
			$x++;
        }
		
		//make fake end row based on first row - this should be removed in the future.
		if($isMagic)
		{
			$tdCount = count($tableArray[0]);
			
			echo "<tr>";
			for($i = 0; $i < $tdCount; $i++)
			{
				if($i == 0) { echo '<td>' . '<i class="zlt_miconTT link edit_data " ">add</i>'. '</td>'; }
				else if($i == ($tdCount-1)){ echo '<td>' . '<i  style="display:none;"class="zlt_miconTT link  save" " onclick="saveData($(this))">done_outline</i>'. '</td>';  }
				else { echo '<td></td>'; }
			}
			echo "</tr>";
		}
		
        ?>
	    </table>
	    <?php
    }
		
	//shortcuts for return versions.
	public static function windowActionR(...$x) { self::bufStart(); self::windowAction(...$x); return self::bufStop(); }
	public static function boxR(...$x) { self::bufStart(); self::box(...$x); return self::bufStop(); }
	public static function notifyR(...$x) { self::bufStart(); self::notify(...$x); return self::bufStop(); }
	public static function selectBoxR(...$x) { self::bufStart(); self::selectBox(...$x); return self::bufStop(); }
	public static function textBoxR(...$x) { self::bufStart(); self::textBoxR(...$x); return self::bufStop(); }
	public static function textAreaR(...$x) { self::bufStart(); self::textArea(...$x); return self::bufStop(); }
	public static function checkBoxR(...$x) { self::bufStart(); self::checkBox(...$x); return self::bufStop(); }
	public static function optionBoxR(...$x) { self::bufStart(); self::optionBox(...$x); return self::bufStop(); }
	public static function buttonJSR(...$x) { self::bufStart(); self::buttonJS(...$x); return self::bufStop(); }
	public static function buttonSubmitR(...$x) { self::bufStart(); self::buttonSubmit(...$x); return self::bufStop(); }
	public static function buttonFormR(...$x) { self::bufStart(); self::buttonForm(...$x); return self::bufStop(); }
	public static function buttonLinkR(...$x) { self::bufStart(); self::buttonLink(...$x); return self::bufStop(); }
	public static function printTableR(...$x) { self::bufStart(); self::printTable(...$x); return self::bufStop(); }
	public static function readMoreR(...$x) { self::bufStart(); self::readMore(...$x); return self::bufStop(); }
	public static function miconR(...$x) { self::bufStart(); self::micon(...$x); return self::bufStop(); }
	public static function tabsR(...$x) { self::bufStart(); self::tabs(...$x); return self::bufStop(); }
	public static function tabsCSSR(...$x) { self::bufStart(); self::tabsCSS(...$x); return self::bufStop(); }
	
	//zui internal shortcut functions.
	
	//process extra HTML
	private static function zEH($extraHTML = "") { if($extraHTML != "") { $extraHTML = " " . trim($extraHTML); } return $extraHTML; }
	//process extra classes
	private static function zEC($extraClasses = "") { if($extraClasses != "") { return " " . trim($extraClasses); } else { return ""; } }
	//micon shortcut
	private static function zMI($miconName) { { if($miconName != "") { return " " . self::miconR(trim($miconName)); } else { return ""; } } }
	//inject 'disabled and autocomplete=off' into input field when readOnly is turned on
	private static function zD()
	{
		$add = "";
		if(self::$readOnly) { $add .= " disabled"; }
		if(self::$autocompleteOff) { $add .= ' autocomplete="off"'; }
		return $add;
	}
	//inject class to colorize border if valid/invalid field
	private static function zV($varName)
	{
		if(self::$showZvalidFail && !zs::isBlank(zfilter::$validateChecksFailed[$varName])) { echo " zl_bordErr"; }
		if(self::$showZvalidSuccess && !zs::isBlank(zfilter::$validateChecksFailed[$varName])) { echo " zl_bordOk"; }
	}
	
	//generate an ID and add it to the array that tracks them.
	private static function generateID($varName)
	{
		//first label of it's type? don't add a number. Otherwise add up labels.
		if(!isset(self::$elementIDs[$varName])) { $nextID = ""; }
		else { $nextID = count(self::$elementIDs[$varName]) + 1; }
		$newID = $varName . $nextID;
		
		self::$elementIDs[$varName][] = $newID;
		return $newID;
	}
}
