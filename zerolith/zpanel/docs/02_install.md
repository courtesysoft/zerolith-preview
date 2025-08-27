```
__________                  .__  .__  __  .__
\____    /___________  ____ |  | |__|/  |_|  |__
  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
 /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
/_______ \___  >__|   \____/|____/__||__| |___|  /
        \/   \/                                \/
```

# Prerequisites:

1. Mysql database ( running on localhost for best speed! )
2. PHP 7.x-8.x installed
3. Linux operating system


# Install procedure:

1. `git clone` in the root folder of your project ( /var/www/, etc )
2. `cd zerolith`
3. `mv * ../` to move everything back a folder
4. Change the configuration file ( `/zerolithData/zl_config.php` ) to match the project ( instructions are in the file )
5. In a php script, require `zerolith/zl_init.php` at the top to initialize the framework.
6. If you would like to see extensive debugger output, call `zl::setDebugMode(4)` and make sure `zl_mode` is set to `dev` in the config file.
7. Enjoy!

Your end file structure should look like:
```
/var/www/yourproject/:
                      /classes/ <-- flat folder with your app specific classes; referenced by the ZL autoloader
                      
                      ( whatever other files project requires )
                      
                      /zerolith/ <-- framework and all it's components
                      /zerolithData/ <-- contains configuration files, cache data, locks, sqlite databases, and other sensitive data specific to your app.
```

### Database features install

The framework will function without installation, except for debug logging, email logging, and other features that require database tables.

Zerolith can automatically create its database tables using the #1 database specified in `zl_config.php`.

Run the Zerolith installer in a web browser at /zerolith/zpanel/index.php and click on 'ZL Installer'. This will handle table creation ( you can use this page later to check installation status ).

Feel free to delete the installer file after you are done.


### Production setup / Bonus Round

1. When you've confirmed the framework is working, set `$zl_set['envCheck'] = false;` to slightly reduce boot time by skipping checks for PHP extensions and valid read/write access to paths. This option defaults to `true` in the configuration file only to increase installation success chances for new users.

2. If you uncommented the authentication bypass in the default `zl_after.php` file, in order to see the bug and email viewer, make sure to comment it out or replace it with a line of code that transmits application's login state to zperm, otherwise sensitive information could be open to the web!

3. If you want email queueing ( zmail's sendLater() function ), add this into your cron:
`*/1 * * * * curl -L http://localhost/zerolith/zl_internal/cron/mailQueue.php`
This will call the mail queue to see if it has emails to process every minute.
Make sure to set the queue size to a realistic rate for how fast your email provider is.
PS - if you can figure out how to get this running without curl, let me know!

4. We strongly recommend restricting access to all files in the /zerolithData folder to protect configuration files, cache data, SQLite databases, and other sensitive information stored by Zerolith. Only the zl_theme.css file needs to be accessible via web requests.

Deepseek R1 suggests this rule for Nginx:
```
location /path/to/your/zerolithData/ {
    # Block all file types except CSS
    if ($request_uri !~* \.css$) {
        return 403;
    }
}
```

And for Apache:
```
<Directory "/path/to/your/zerolithData">
    # Deny all files by default
    Require all denied

    # Allow only .css files
    <FilesMatch "\.css$">
        Require all granted
    </FilesMatch>
</Directory>
```