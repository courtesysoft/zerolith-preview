<?php
//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// v1.0 ZUI Self-Test
require "../../zl_init.php";
require "index.nav.php"; //navigation menu

$whatever = zsys::getTimeSerial();

extract(zfilter::array("chunk", "stringExtended"));
if($chunk == "yep")
{
	echo "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.<br><br>";
	echo "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.";
	echo "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.<br><br>";
	echo "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged. It was popularised in the 1960s with the release of Letraset sheets containing Lorem Ipsum passages, and more recently with desktop publishing software like Aldus PageMaker including versions of Lorem Ipsum.";
	exit;
}

$ipsumLorem = 'Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source. Lorem Ipsum comes from sections 1.10.32 and 1.10.33 of "de Finibus Bonorum et Malorum" (The Extremes of Good and Evil) by Cicero, written in 45 BC. This book is a treatise on the theory of ethics, very popular during the Renaissance. The first line of Lorem Ipsum, "Lorem ipsum dolor sit amet..", comes from a line in section 1.10.32.

The standard chunk of Lorem Ipsum used since the 1500s is reproduced below for those interested. Sections 1.10.32 and 1.10.33 from "de Finibus Bonorum et Malorum" by Cicero are also reproduced in their exact original form, accompanied by English versions from the 1914 translation by H. Rackham.';

//associative array for printTable example
$pa = [];
$pa[] = array("some data" => "cat", "another data" => "dog");
$pa[] = array("some data" => "reptile", "another data" => "crustacean");
$pa[] = array("some data" => "panda", "another data" => "snake");
$pa[] = array("some data" => "pikachu", "another data" => "mouse");

//start the page.
zpage::start("ZUI 1.2 Interface demo 08/2025");

//left section
zui::bufStart();
	zui::quip("This is a demonstration of the Zerolith UI Library.", "Hello World"); ?><br><?php
	zui::box("howdy, i'm a box.", "zl_w300"); ?><br><?php
	zui::notify("warn", "I'm a warning.");
	zui::notify("ok", "I'm an 'ok'");
	zui::notify("error", "I'm an error, and this is a really long and verbose message, so it should wrap around the box.");
	?><br>Multicolored Material Icons Test:<br><?php
	zui::micon("settings", "TT"); zui::micon("delete_sweep", "TT", "error"); zui::micon("check", "", "ok");
	zui::micon("assignment", "", "warn"); zui::micon("attachment", "", "link"); ?><br><br><?php
	zui::printTable($pa);?><br><?php
	zui::readMore($ipsumLorem);
	?><br><br>
	Regular Button <?=zui::buttonSubmit("yeah", "", "save")?><br><br>
	Error Button <?=zui::buttonSubmit("yeah", "", "save","err")?><br><br>
	OK Button <?=zui::buttonSubmit("yeah", "", "save","ok")?><br><br>
	Warn Button <?=zui::buttonSubmit("yeah", "", "save","warn")?><br><br>
	Regular Disabled Button <?=zui::buttonSubmit("yeah", "", "save","","disabled")?><br><br>
	Hollow Button <?=zui::buttonSubmit("yeah", "", "save","hollow")?><br><br>
	Hollow Button Small <?=zui::buttonSubmit("yeah", "", "save","hollow small")?><br><br>
	<?php
$leftHTML = zui::bufStop();

//right section
zui::bufStart();
	zui::selectBox("select1", "middle", ["first","middle","last"], "I'm a selectbox."); ?><br><br><?php
	zui::selectBox("select2", "2", ["0" => "first", "2" => "middle", "1" => "last"], "I'm a selectbox."); ?><br><br><?php
	zui::selectBox("select3", "2", [["ID" => "0", "name" => "first"],["ID" => "2", "name" => "middle"],["ID" => "1", "name" => "last"]], "I'm a selectbox."); ?><br><br><?php
    zui::selectBox_new("select3", "2",
    [
        ["ID" => "0", "name" => "first", "avatar" => "https://randomfox.ca/images/61.jpg"],
        ["ID" => "2", "name" => "middle", "avatar" => "https://randomfox.ca/images/84.jpg"],
        ["ID" => "1", "name" => "last", "avatar" => "https://randomfox.ca/images/76.jpg"]
    ], "I'm a selectbox."); ?><br><br><?php
    zui::checkBox("checkbox", "A", "I'm a checkbox."); ?><br><?php
	zui::optionBox("optionbox", "A", "I'm option box A."); ?><br><?php
	zui::optionBox("optionbox", "B", "I'm option box B."); ?><br><br><?php
	zui::textArea("text", "I'm a textarea.", "zl_w100p", 'cols=""'); ?><br><br><?php
	zui::textBox("text2", "I'm a textbox."); ?><br><br><?php
	
	zui::buttonForm("Submit(hiddenForm)", array("fakeField" => "fake"), "search"); ?><br><br><?php
	zui::buttonSubmit("Submit(post)", "refresh"); ?><br><br><?php
	zui::toolTip("<a href=''>Link with a tooltip</a>", "Yee haw!"); ?><br><br><?php
	zui::buttonJS("Modal - fast response", "", "", "", "onclick=\"zl.modalOpen('?chunk=yep', 'Lorem Ipsum');\"");
	?><br><br><?php
	zui::buttonJS("Modal - slow response", "", "", "", "onclick=\"zl.modalOpen('zsys.php', 'Zsys Test');\"");
	?><br><br><?php
	zui::tabs(array("Tab1" => "ZL JS tabs","Tab2" => "and yup","Tab3" => "and yup!"), "", "zl_w400"); ?><br><?php
	zui::tabsCSS(array("qTab" => "ZL CSS tabs","1" => "and yup","Really Long Tab" => "and yup!"), 3, "", "", "Side text area"); ?><br><?php

$rightHTML = zui::bufStop();

?>
<div class="zl_cols gap5">
	<div class="col zlt_box"><?=$leftHTML?></div>
	<div class="col zlt_box"><?=$rightHTML?></div>
</div>
<?php

$yeah = "Lorem Ipsum is simply dummy text of the printing and typesetting industry. Lorem Ipsum has been the industry's standard dummy text ever since the 1500s, when an unknown printer took a galley of type and scrambled it to make a type specimen book. It has survived not only five centuries, but also the leap into electronic typesetting, remaining essentially unchanged...";
$woo = "It is a long established fact that a reader will be distracted by the readable content of a page when looking at its layout. The point of using Lorem Ipsum is that it has a more-or-less normal distribution of letters, as opposed to using 'Content here, content here', making it look like readable English...";
$uhhuh = 'Contrary to popular belief, Lorem Ipsum is not simply random text. It has roots in a piece of classical Latin literature from 45 BC, making it over 2000 years old. Richard McClintock, a Latin professor at Hampden-Sydney College in Virginia, looked up one of the more obscure Latin words, consectetur, from a Lorem Ipsum passage, and going through the cites of the word in classical literature, discovered the undoubtable source...';
$allright = "There are many variations of passages of Lorem Ipsum available, but the majority have suffered alteration in some form, by injected humour, or randomised words which don't look even slightly believable. If you are going to use a passage of Lorem Ipsum, you need to be sure there isn't anything embarrassing hidden in the middle of text...";

?><br>

<br><br>
<?php zui::quip(zui::getFormVars(), "Pass me to extract(zfilter::array('thebelowvalue', 'yourSelectedFilter').");?>