<?
if(strstr(__FILE__, $_SERVER['PHP_SELF'])) { header('Location: /zerolith/zpanel/test'); die(); } // Redirect to Zerolith Tests

//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// zsys tests.

require __DIR__.'/../../../zl_init.php';

ztest::test(zsys::getTimeSerial(), '>0', '', 'zsys::getTimeSerial() is ok');
$value = (zsys::getMemUsed()['total'] ?? false);
ztest::test($value, '!=', false, 'zsys::getMemUsed() got total: '.$value.' MB');
$value = (zsys::getMemUsed()['free'] ?? false);
ztest::test($value, '!=', false, 'zsys::getMemUsed() got free: '.$value.' MB');
$value = (current(zsys::getDiskSpace())['size'] ?? false);
ztest::test($value, '!=', false, 'zsys::getDiskSpace() got size: '.$value);
$value = (current(zsys::getDiskSpace())['used'] ?? false);
ztest::test($value, '!=', false, 'zsys::getDiskSpace() got used: '.$value);
$value = (zsys::getCpuUsedPct() ?? false);
ztest::test($value, 'numeric', '', 'zsys::getCpuUsedPct() got: '.$value.'%');
