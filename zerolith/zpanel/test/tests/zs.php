<?
if(strstr(__FILE__, $_SERVER['PHP_SELF'])) { header('Location: /zerolith/zpanel/test'); die(); } // Redirect to Zerolith Tests

//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// zs (zerolith shortcuts) tests.

require dirname(__FILE__).'/../../../zl_init.php';

ztest::test(zs::contains('hello world', 'world'), '==', 1, 'zs::contains()');
ztest::test(zs::contains('hello world', 'WORLD'), '==', 1, 'zs::contains()');

$v = "";
ztest::test(zs::pr(zs::isBlank($v)), '==', '[bool: true]', 'zs::pr() and zs::isBlank()');
$v = 3;
ztest::test(zs::pr(zs::isBlank($v)), '==', '[bool: false]', 'zs::pr() and zs::isBlank()');
$v = [];
ztest::test(zs::pr(zs::isBlank($v)), '==', '[bool: true]', 'zs::pr() and zs::isBlank()');
$v = ["yeah"];
ztest::test(zs::pr(zs::isBlank($v)), '==', '[bool: false]', 'zs::pr() and zs::isBlank()');
$v = ["yeah" => "yeah"];
ztest::test(zs::pr(zs::isBlank($v)), '==', '[bool: false]', 'zs::pr() and zs::isBlank()');
$v = ["yeah", "yeah"];
ztest::test(zs::pr(zs::isBlank($v)), '==', '[bool: false]', 'zs::pr() and zs::isBlank()');
$v = ["yeah" => "yeah", "no" => "no"];
ztest::test(zs::pr(zs::isBlank($v)), '==', '[bool: false]', 'zs::pr() and zs::isBlank()');
$v = null;
ztest::test(zs::pr(zs::isBlank($v)), '==', '[bool: true]', 'zs::pr() and zs::isBlank()');
$v = false;
ztest::test(zs::pr(zs::isBlank($v)), '==', '[bool: false]', 'zs::pr() and zs::isBlank()');
