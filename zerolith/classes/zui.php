<?php
//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
//
//ZL UI Class v1.5
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
//03/2024 - Updated printTable2 to support $showfields and $THfieldClasses like ZPTA
//10/2024 - Merged printTable1/2, allow sending |disabled to selectbox to indicate the selection is disabled
//03/2025 - Added image selectbox beta

//Design notes:
//Parameter order for all functions should be:
//[ Required parameters relative to the function ] [ extraClasses/extraHTML ] [ rare parameters ]

//TODO: remove generateID() when it's not needed.

class zui
{
	private static $elementIDs = [];        //internal tracking of items.
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
	
	//read a message from the session
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
	//Not used yet
	public static function validMSG($varName)
	{
		if(self::$showZvalidFail && !zs::isBlank(zfilter::$validateChecksFailed[$varName])) { ?><span class="zvalid_<?=$varName?> zl_err"><?=zfilter::$validateChecksFailed[$varName]?></span><?php }
		if(self::$showZvalidSuccess && !zs::isBlank(zfilter::$validateChecksFailed[$varName])) { ?><span class="zvalid_<?=$varName?> zl_ok"><?=self::micon("check","","ok")?></span><?php }
	}

	//produces a list of input variables produced by zui that can be sent to zfilter::array().
	//Not used yet
	public static function getFormVars() { return zarr::toPipe(self::$formVars); }
	
	//visual quip - only use for acceptably nerdy printouts!
	//send any data format into the $quip field and it should work!
	public static function quip($quip = "", $title = "Information", $micon = "message")
	{
		if($micon == "pest_control") { $class = " error"; } else { $class = ""; }
		$quip = zs::pr($quip);
		$html = "\n<table class='zlt_table" . $class . "'>\n<tr><th>" . self::miconR($micon, "", "white") . "&nbsp; " . ucfirst($title) . "</th></tr>";
		$html .= '<tr><td><pre>' . zstr::sanitizeHTML($quip) . '</pre></td></tr></table>';
		echo $html;
	}
	
	//Cut the text after x characters and/or lines
	public static function readMore($text, $charLimit = 512, $lineLimit = 7, $dotdotdot = "...")
	{
		$maxLimit = 2097152; //2mb sanity limit
		
		$strlen = mb_strlen($text);
		
		//if the string is over limit, pre-truncate it for compoutational sanity
		if($strlen > $maxLimit) { $text = mb_substr($text,0, $maxLimit) . "/n/<br>ZL truncated the remaining text because the input string was " . ( znum::bytesToUnits($strlen)); }
		
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
	
	// --------------------- HTML Elements --------------------- //
	
	/* Output Google Icons. */
	//variant codes: tt = twin tone, o = outlined version, "" = default
	public static function micon($miconName, $variantCode = "", $extraClasses = "", string $extraHTML = "", $toolTipText = "")
	{
		//compile micon string
		$miconText = '<i class="zlt_micon' . $variantCode . self::zEC($extraClasses) . '"' . self::zEH($extraHTML) . '>' . $miconName . '</i>';
		
		if($toolTipText != "") { self::toolTip($miconText, $toolTipText); } //Fancy: supposedly no worky
		else { echo $miconText; } //regular
	}

	//Make a button that doesn't submit - for use with JS.
	//buttonTitle is the text label on the button.
	//nameOverride sets a HTML form variable name. If left blank, it will use the buttonTitle
	//micon is a google icon code
	//extraClasses specifies any extra classes to add to the input element.
	//extraHTML appends extra HTML to the end of the input element.
	public static function buttonJS($buttonTitle, $nameOverride = "", $micon = "", $extraClasses = "", $extraHTML = "")
	{
		if($buttonTitle == "") { $extraClasses .= " iconOnly"; }
		if($nameOverride != "") { $name = $nameOverride; } else { $name = $buttonTitle; } //name = $buttontitle by default
		?>
		<button type="button" class="zlt_button<?=self::zEC($extraClasses)?>" name="<?=$name?>"<?=self::zD()?><?=self::zEH($extraHTML)?>><?=self::zMI($micon)?><?=$buttonTitle?></button><?php
	}
	
	//make a button that submits a form.
	public static function buttonSubmit($buttonTitle, $nameOverride = "", $micon = "", $extraClasses = "", $extraHTML = "")
	{
		if($buttonTitle == "") { $extraClasses .= " iconOnly"; }
		if($nameOverride != "") { $name = $nameOverride; } else { $name = $buttonTitle; } //name = $buttontitle by default
		self::$formVars[] = $name;
		
		?>
		<button type="submit" value="<?=$buttonTitle?>" class="zlt_button<?=self::zEC($extraClasses)?>" name="<?=$name?>"<?=self::zEH($extraHTML)?><?=self::zD()?>><?=self::zMI($micon)?><?=$buttonTitle?></button><?php
	}
	
	//make a button that goes to a link ( cosmetic link button )
	public static function buttonLink($buttonTitle, $link, $micon = "", $extraClasses = "", $extraHTML = "")
	{
		if($buttonTitle == "") { $extraClasses .= " iconOnly"; }
		?>
		<a href="<?=$link?>"><button class="zlt_button<?=self::zEC($extraClasses)?>"<?=self::zEH($extraHTML)?><?=self::zD()?>><?=self::zMI($micon)?><?=$buttonTitle?></button></a>
		<?php
	}

	//make a button that submits a form. Accepts an associative array of hidden fields; example: ['userID' => 1234, 'username' => 'bubba'].
	public static function buttonForm($buttonTitle, array $hiddenFields, $nameOverride = "", $micon = "", $extraClasses = "", string $extraHTMLform = "", string $extraHTMLbutton = "")
	{
		if($buttonTitle == "") { $extraClasses .= " iconOnly"; }
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
	//varName is the HTML form variable name.
	//varValue is the default value, if there is one.
	//labelText is text that comes after the checkbox.
	//checkedIfValue makes the box checked if $varValue is this value.
	//extraClasses specifies any extra classes to add to the input element.
	//extraHTML appends extra HTML to the end of the input element.
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

	//WARNING: under construction
	//Spits out a selectbox from an array, optionally allowing for images to be used inside.
	//Setting $defaultItem to false will disable the default item
	//Input array formats accepted:
	//+ Keyless array ['Desiree Johnson', 'Jeff Warner'] uses the single value for code value AND readable name
	//+ Flat associative array ['32' => 'Desiree Johnson', '33' => 'Jeff Warner'] (code value, readable name)
	//+ 2 field DB array: [['ID' => '32', 'name' => 'Dee Johnson'], ['ID' => '33', 'name' => 'Jeff Warner']] (code value, readable name)
	//+ 3 field DB array: [['ID' => '32', 'name' => 'Dee Johnson', 'avatar' => 'Dee.gif']], etc (code value, readable name, avatar)
	//Note: if you send |disabled into an array item's value, it will print the item as disabled.
	static function selectBox_new(string $name, string $value, array $listArray = [], string $defaultItem = '- none -', string $extraClasses = '', string $extraHTML = '')
	{
		?>
		<zui-select name="<?= $name ?>" id="<?= $name ?>" class="<?= $extraClasses ?>" <?= self::zD() ?> <?= $extraHTML ?> hx-trigger="change">
			<?php
				if($defaultItem !== false) 
				{ 
					if(!$defaultItem) { $defaultItem = '- none -'; }
					echo "<option value=''>$defaultItem</option>"; 
				}

				$arrayInfo = zarr::getArrayInfo($listArray, true);
				$optionArray = [];
				
				// Reformat option data into a consistent format
				if($arrayInfo['type'] == 'blank') {} // That was easy
				elseif($arrayInfo['type'] == 'singleNum') 
				{ foreach($listArray as $optionData) { $optionArray[$optionData] = [$optionData, '']; } } 
				elseif($arrayInfo['type'] == 'singleAssoc') 
				{ foreach($listArray as $optionValue => $optionData) { $optionArray[$optionValue] = [$optionData, '']; } } 
				elseif(zs::contains($arrayInfo['type'], "multi"))
				{ foreach($listArray as $optionData) { $optionArray[$optionData['ID']] = [$optionData['name'], @$optionData['avatar']]; } }
				else
				{ zl::fault('Invalid option data array type for zui::selectBox'); }
				
				// Output options from data
				foreach($optionArray as $optionValue => [$optionLabel, $optionAvatar])
				{
					if(strlen($optionValue) > 9 && stripos($optionValue, '|disabled', -8) == 0) 
					{
						$disabled = 'disabled';
						if($optionLabel == $optionValue) { $optionLabel = substr($optionValue, 0, -9); }
						$optionValue = substr($optionValue, 0, -9);
					}
					else { $disabled = ''; }

					if($optionValue == $value) { $selected = 'selected'; } else { $selected = ''; }
					?><option value="<?= $optionValue ?>" data-icon="<?= $optionAvatar ?>" <?= $selected ?> <?= $disabled ?>><?= $optionLabel ?></option><?php
				}
			?>
		</zui-select>
		<?php
	}

	//same as above, but not as fancy
	public static function selectBox($varName, $varValue, $listArray = [], $defaultItem = "", $extraClasses = "", $extraHTML = "")
	{
		//init
		self::$formVars[] = $varName;
		if(zs::isBlank($listArray)) { $listArray = ["" => ""]; } //produce a blank field..
		if($defaultItem == "") { $defaultItem = "- none -"; } //set a name for the default value

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
		else { } //this should be an error.

		?><select name="<?=$varName?>" class="zlt_i zlt_selectBox<?=self::zEC($extraClasses)?><?=self::zV($varName)?>"<?=self::zD() ?><?=self::zEH($extraHTML)?>><?php

		//create the bottom list.
		$foundItem = false;
		$buf = "";

		foreach ($listArray as $variableName => $humanName)
		{
			if (!$foundItem && $varValue == $variableName) { $selected = " SELECTED"; $foundItem = true; }
			else { $selected = ""; }

			//handle |disabled addon in human name
			$disabled = "";
			if(zs::containsCase($humanName, "|disabled"))
			{
				$humanName = str_replace("|disabled", "", $humanName);
				$disabled = " disabled";
			}

			$buf .= '<option value="' . $variableName . '"' . $selected . $disabled . '>' . $humanName . "</option>\n";
		}

		//add default item to the top of the list
		if(!$foundItem)
		{
			if ($varValue == "") { $selected = " SELECTED"; } else { $selected = ""; }
		}

		if($defaultItem !== false) { $buf = '<option value="" ' . $selected . '>' . $defaultItem . "</option>\n" . $buf; }

		echo $buf;
		?></select><?php
	}
    
    //1 line textbox
    public static function textBox($varName, $varValue, $extraClasses = "", $extraHTML = "", $boxType = "text")
    {
		$boxType = strtolower($boxType);
		if($boxType != "password" && $boxType == "search") { $boxType = "text"; } //restrict to known types
		
		self::$formVars[] = $varName;
		$ea = self::zEH($extraHTML);
		if(!zs::contains(strtolower($ea), "maxlength")) { $ea .= ' maxlength = "255"'; } //set defaults.
		if(!zs::contains(strtolower($ea), "size")) { $ea .= ' size = "10"'; } //set defaults
    	?><input type="<?=$boxType?>" name="<?=$varName?>" value="<?=$varValue?>" class="zlt_i zlt_textBox<?=self::zEC($extraClasses)?><?=self::zV($varName)?>"<?=self::zD()?><?=$ea?>/><?php
    }
    
    //multi line text box
    public static function textArea($varName, $varValue, $extraClasses = "zl_h25p", $extraHTML = "")
    {
		self::$formVars[] = $varName;
		$ea = self::zEH($extraHTML);
		if(!zs::contains(strtolower($ea), "rows")) { $ea .= ' rows = "7" '; } //set defaults.
		if(!zs::contains(strtolower($ea), "cols")) { $ea .= ' cols = "40" '; }
    	?><textarea name="<?=$varName?>" class="zlt_i zlt_textArea<?=self::zEC($extraClasses)?><?=self::zV($varName)?>"<?=self::zD()?><?=$ea?>><?=$varValue?></textarea><?php
    }
	
	//multi line text box with CKEditor - only works for one editor currently.
	//will possibly remove in a future version for tiptap
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
	//will possibly remove in a future version for tiptap
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

            <link rel="stylesheet" href="https://unpkg.com/jodit@4.0.1/es2021/jodit.min.css"/>
            <script src="https://unpkg.com/jodit@4.0.1/es2021/jodit.min.js"></script>
			<script>
			<?php
			if($featureSet == "basic") //basic settings for email editor only
			{
				?>
				var editor = Jodit.make("#<?=$ID?>", {
                   // autofocus: true,
                   // cursorAfterAutofocus: 'end', // 'end';
                   // saveSelectionOnBlur: true,
				  "buttons": "bold,italic,underline,strikethrough,brush,paragraph,|,ul,ol,hr,table,link,|,spellcheck",
				  "toolbarAdaptive": false,
                   //"cursorAfterAutofocus":  'end',
                   //"saveSelectionOnBlur": true,

                    events: {
                        afterInit: (instance) => { this.jodit = instance; }
                        }
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
	
	//produce hidden field
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
		?><input type="dateTime-local" class = "zlt_datePicker" name="<?=$varName?>" value="<?=$varValue?>"<?=$minDate .
    $maxDate?>><?php
	}
    
    // --------------------- Abstractions --------------------- //
	
	//surround something in a box.
	public static function box($html, $extraClasses = ""){ echo '<div class="zlt_box' . self::zEC($extraClasses) . '">' . $html . "</div>"; }
	
    //print a nice looking notification to screen.
	//$fadeOutAfterMS is ignored if left blank
    public static function notify($type, $message, $fadeoutAfterMS = "", $extraClasses = "", $extraHTML = "")
	{
		$fadeoutAfterMS = intval($fadeoutAfterMS); //force to 0 if blank

		//correct mistypes
	    if($type == 'warning') { $type = 'warn'; }
		elseif($type == 'error') { $type = 'err'; }
		elseif($type == 'success') { $type = 'ok'; }
	 
		//force a default if the programmer screwed up
		if($type != 'warn' && $type != 'err' && $type != 'ok') { $type = 'warn'; }
		
		//assign a micon
		if($type == "warn") { $micon = self::miconR("warning_amber", "", "warn"); }
		elseif($type == "ok") { $micon = self::miconR("check", "", "ok"); }
		elseif($type == "err") { $micon = self::miconR("close", "", "err"); }

		//setup fadeout, if applies
		if($fadeoutAfterMS != 0) { $fadeout = "style='animation: zui_fadeOut " . intval($fadeoutAfterMS * 0.66) . "ms " . $fadeoutAfterMS . "ms' onanimationend='this.remove()'"; }
		else { $fadeout = ''; }
		
		//long or short format?
		if(strlen($message) <= 15)
		{ ?><div class="zlt_notify<?=$type?><?=self::zEC($extraClasses)?>" style="position:relative;" <?=$fadeout?><?=self::zEH($extraHTML)?>><?=$micon?>&nbsp;<?=$message?></div><?php }
		else
		{ ?><div class="zlt_notify<?=$type?><?=self::zEC($extraClasses)?>" style="position:relative;" <?=$fadeout?><?=self::zEH($extraHTML)?>><div class="nicon"><?=$micon?></div><div class = "ntext"><?=$message?></div></div><?php }
	}
	
    //wrap a tooltip around a HTML chunk
    public static function toolTip($htmlChunk, $tipCaption)
    { ?><div class="zlt_toolTip"><?=$htmlChunk?><span class="zlt_toolTipText"><?=$tipCaption?></span></div><?php }
	
	//shows minimize/maximize/exit ( used by debugger )
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
	
	//DEPRECATED - cannot handle multiple instances.
	//Send a basic associative array with the key as the title, and the value as the content, and get tabs.
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
			</ul>
<?php
			//produce tab contents
			foreach($tabData as $k => $v) { ?><div class="tab-content<?=self::zEC($extraClassesContent)?>"><?=$v?></div><?php }
			?>
		</div>
		<?php
	}
	
	//Array to table printer
    public static function printTable
	(
		$tableArray,          //Array input, preferably zdb::array() output ( array of assoc arrays ), but accepts  other formats.
	    $showFields = [],     //SQL field => English name for given TH field. Input determines order of field display. If blank array, default ordering is used
	    $THfieldClasses = [], //SQL field => className for given TH field. Will apply a class to TH ( affect width, effects, etc )
	    $extraClasses = "",   //Any extra CSS classes in the <table> tag
	    $extraHTML = ""       //Any extra HTML in the <table> tag
	)
    {
        //init and sanity checks
    	if(zs::isBlank($tableArray)) { self::notify("warn", "There isn't any information to display."); return; } //user friendly
		if(!is_array($THfieldClasses)) { zl::fault("non-array sent to zui::printTable THfieldClasses"); } //programmer hostile
        if($extraClasses != "") { $ec = " " . $extraClasses; } else { $ec = ""; }
		$filterFields = !zs::isBlank($showFields); //speed hack because we do this check a lot
	    
	    $arrayInfo = zarr::getArrayInfo($tableArray, true); //identify type of array
	    
        //add another layer of depth if we don't have an array of arrays
        if($arrayInfo['depth'] == 1 && $arrayInfo['type'] != "singleNum") { $tableArray = [$tableArray]; }
        elseif($arrayInfo['depth'] > 2) { self::notify("error", "The array is > 2 layers deep; can't display"); return; }
        elseif(!$arrayInfo['canLoop']){ self::notify("error", "The array is non-iterable ( contains objects, etc )"); return; }
	    
	    //if $showFields exists, re-order array
        if(!zs::isBlank($showFields))
        {
	        $order = []; $temp = [];
            foreach ($showFields as $k => $v) { $order[] = $k; }
            foreach($tableArray as $k => $v) { $temp[] = array_replace(array_flip($order), $v); }
            $tableArray = $temp;
        }

        //quick, uncomplicated output & GTFO
        if($arrayInfo['type'] == "singleNum")
        {
            echo "\n" . '<table class="zlt_table' . $ec . '"' . $extraHTML . '>' . "\n";
            echo '<tr class = "zl_stickyT0"><th>Array</th></tr>';
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
		
		//start output of TH
	    echo "\n" . '<table class="zlt_table' . $ec . '"' . $extraHTML . '>' . "\n";
	    echo '<tr class = "zl_stickyT0">';
		
	    foreach($firstData as $key => $value)
	    {
	        if(!zs::isBlank($THfieldClasses[$key])) { $class = ' class="' . $THfieldClasses[$key] . '"'; }
			else { $class = ""; }
			
	        if($filterFields)
	        {
	            if(isset($showFields[$key])) { echo "<th " . $class . ">" . $showFields[$key] . "</th>"; }
				//otherwise skip display of that TH in this mode
	        }
	        else { echo "<th>" . $key . "</th>"; }
	    }
	
	    echo "</tr>\n";
		
		//start output of TD
	    $x = 0;
	    foreach($tableArray as $tableItem)
	    {
			echo "<tr id ='$x'>";
	        foreach($tableItem as $key => $value)
	        {
	            if($filterFields)
	            {
					if(isset($showFields[$key])) { echo "<td>" . zs::pr($value) . "</td>"; }
					//otherwise skip display of TD in this mode
		        }
	            else { echo "<td>" . zs::pr($value) . "</td>"; }
	        }
	        echo "</tr>";
	        $x++;
	    }
		
	    ?></table>
        <?php
    }
		
	//shortcuts for return versions.
	public static function windowActionR(...$x) { self::bufStart(); self::windowAction(...$x); return self::bufStop(); }
	public static function boxR(...$x) { self::bufStart(); self::box(...$x); return self::bufStop(); }
	public static function notifyR(...$x) { self::bufStart(); self::notify(...$x); return self::bufStop(); }
	public static function selectBoxR_new(...$x) { self::bufStart(); self::selectBox_new(...$x); return self::bufStop(); }
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
	public static function toolTipR(...$x) { self::bufStart(); self::toolTip(...$x); return self::bufStop(); }
	
	//zui internal shortcut functions.
	
	//inject class to colorize border if valid/invalid field
	private static function zV($varName)
	{
		if(self::$showZvalidFail && !zs::isBlank(zfilter::$validateChecksFailed[$varName])) { echo " zl_bordErr"; }
		if(self::$showZvalidSuccess && !zs::isBlank(zfilter::$validateChecksFailed[$varName])) { echo " zl_bordOk"; }
	}
	
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