__________                  .__  .__  __  .__
\____    /___________  ____ |  | |__|/  |_|  |__
  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
 /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
/_______ \___  >__|   \____/|____/__||__| |___|  /
        \/   \/                                \/

**Server prereqs**

Zerolith assumes you are using a ubuntu-based server environment. 
Zerolith is tested PHP7.2 and is tested up to 8.2.
Zerolith requires the mbstring and mysqli extension be installed

**Install procedure:**

1. git clone in the root folder of your project; say your /var/www<br>
3. Change the configuration file to match the project<br>
4. In your script, require zerolith/zl_init.php at the top to initialize the framework.
5. If you would like to see debugger output, set zl::setDebugMode(4) and make sure zl_mode = dev.
6. Enjoy!

Your end file structure should look like:

/var/www/yourproject/:
        /classes/ <-- your application specific classes; referenced by the ZL autoloader
        ( whatever other files project requires )
        /zerolith/ <-- framework and all it's components
        zl_config.php <-- ZL configuration file; can include dynamic code
        zl_theme.css <-- ZL.css configuration file

**Zerolith database features install**

Zerolith can have it's database tables automatically created for you in the database #1 you specified in zl_config.php.

The framework will function without this installed except for bug logging, email logging, debug logging, cache, etc that require a database/file structure in place.

Run the zerolith installer in a web browser at /zerolith/zl_internal/zl_install.php and it will handle this for you ( this can be used to check install status later; running it again will not wipe these database tables. )

**See it in action!'**

Visit https://yoursite.com/zerolith/zl_internal/demo/zui.php
If your configuration is correct, you should see a nice looking page with a lot of user elements in it. 
