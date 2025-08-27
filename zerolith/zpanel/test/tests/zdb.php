<?
if(strstr(__FILE__, $_SERVER['PHP_SELF'])) { header('Location: /zerolith/zpanel/test'); die(); } // Redirect to Zerolith Tests

//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// Database tests.

require dirname(__FILE__).'/../../../zl_init.php';

ztest::test(zdb::writeSQL('select 1;'), '==', 1, 'Basic database success');
ztest::test(zdb::writeSQL('sele1ct 1;'), '==', 0, 'Basic database fail');
