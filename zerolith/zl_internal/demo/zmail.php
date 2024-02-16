<?php
//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
// [Beta] zmail test
require "../../zl_init.php";
zl::$page['wrap'] = true;
zl::setDebugLevel(4);

zpage::start("yeah");
echo zmail::sendLater("yee@haw.com", 0, "Howdy!", "This is a test message.", "nocategory");
zpage::end();