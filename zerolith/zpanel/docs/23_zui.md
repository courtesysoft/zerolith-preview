# Zui

zui.css is a html/js library for outputting complex HTML elements in an easy way.
It ties in with zui.css for detail definitions and zl_theme.css for colors/padding/etc. This makes the library themeable in the future.

By default, every function outputs directly to HTML. Every major function that needs to return HTML instead has the same name with an 'R' at the end to indicate that it returns HTML instead of directly outputting it.


### Basic Usage

This library is very easy to learn by example, so it doesn't deserve exquisite documentation yet.

See the zui demo in zpanel for some examples of usage.
See the source code in /zerolith/classes/zui.php for detailed comments on how to use each function.
For example:
```
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
```
