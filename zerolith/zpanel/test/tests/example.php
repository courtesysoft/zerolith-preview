<?
if(strstr(__FILE__, $_SERVER['PHP_SELF'])) { header('Location: /zerolith/zpanel/test'); die(); } // Redirect to Zerolith Tests

//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// example test.

require dirname(__FILE__).'/../../../zl_init.php';

$foo = 'yes';
ztest::test($foo, '==', 'yes', 'Normal syntax');
ztest::test($foo, 'yes', '', 'Shorthand equals syntax');


