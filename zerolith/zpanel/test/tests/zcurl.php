<?
if(strstr(__FILE__, $_SERVER['PHP_SELF'])) { header('Location: /zerolith/zpanel/test'); die(); } // Redirect to Zerolith Tests

//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// zcurl tests.

require dirname(__FILE__).'/../../../zl_init.php';

ztest::test(zcurl::curl("https://google.com"), '~has', 'feeling lucky', "Google is feeling lucky");
