<?php
//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// [Beta] ZLT Self-Test

require "../../zl_init.php";
zl::$page['wrap'] = true;
zl::setDebugLevel(4);
zpage::start("yeah");

//isBlank test.

$v1 = ""; //true
//v2 - true
$v3 = 3; //false
$v4 = []; //true
$v5 = array("yeah"); //false
$v6 = array("yeah" => "yeah"); //false
$v7 = array("yeah", "yeah"); //false
$v8 = array("yeah" => "yeah", "no" => "no"); //false
$v9 = null; //true
$v10 = false; //false

echo "1 " . zs::pr(zs::isBlank($v1)) . "<br>";
echo "2 " . zs::pr(zs::isBlank($v2)) . "<br>";
echo "3 " . zs::pr(zs::isBlank($v3)) . "<br>";
echo "4 " . zs::pr(zs::isBlank($v4)) . "<br>";
echo "5 " . zs::pr(zs::isBlank($v5)) . "<br>";
echo "6 " . zs::pr(zs::isBlank($v6)) . "<br>";
echo "7 " . zs::pr(zs::isBlank($v7)) . "<br>";
echo "8 " . zs::pr(zs::isBlank($v8)) . "<br>";
echo "9 " . zs::pr(zs::isBlank($v9)) . "<br>";
echo "10" . zs::pr(zs::isBlank($v10)) . "<br>";

zpage::end();