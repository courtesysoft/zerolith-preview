<?php
require "../../zl_init.php";
zl::$page['wrap'] = true;
zl::setDebugLevel(3);

zpage::start("Zmail test");

echo "email sending...<br>";
if(zmail::send("", 0, "test", "Yeah bwoiee", "failtest"))
{ echo "email sent!"; } else { echo "email failed.."; }

?>