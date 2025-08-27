<?php
// Beta zmail test
require "../../zl_init.php";
require "index.nav.php"; //navigation menu

extract(zfilter::array("sendNow|email", "email")); //extract form input

zpage::start("Zmail Library test");

echo "<pre>ZL's mailOn setting = " . zs::pr(zl::$set['mailOn']). "</pre><br>";

//form submission
if($email != "") 
{
    if($sendNow != "") 
	{
        if(zmail::send($email, 0, "Zmail test", "This is a test message sent immediately.", ""))
		{ zui::notify("ok", "Email sent"); }
		else { zui::notify("err", "Email send failed ( check the debugger )"); }
    } 
	else 
	{
        if(zmail::sendLater($email, 0, "Zmail test", "This is a test message scheduled for later.", ""))
		{ zui::notify("ok", "Email queued for cronjob"); }
		else{ zui::notify("err", "Email queued"); }
    }
}

// Display the form
?>
<form method='post' action=''>
    <label for='email'>Email Address: </label><input type='email' id='email' name='email' class ="zlt_i zlt_selectBox" required><br><br>
    <button type='submit' name="sendNow" value="Y" class="zlt_button">Send test now</button>
    <button type='submit' name="sendNow" value="" class="zlt_button">Send test later</button>
</form>