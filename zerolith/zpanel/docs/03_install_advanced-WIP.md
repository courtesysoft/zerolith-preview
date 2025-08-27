This is a WIP document as of 04/28/2025 and may contain inaccuracies.

# Advanced installation/configuration

### Multiple applications running the same copy of ZL with different configs

At our shop, we use several servers acting as application incubators that run multiple Zerolith instances.
Maintaining multiple Zerolith copies across different applications is cumbersome.

By symlinking `/zerolith` in each application's webroot, multiple applications on the same server can use different `/zerolithData` folders while sharing the same Zerolith codebase.

This approach works because of our design that puts application-specific data in it's own folder.

### About hijack mode:

Zerolith can run inside and "hijack" other PHP frameworks and release control to them during `zl::terminate()`. It automatically enters hijack mode when it detects class autoloaders in the environment. You can also specify an 'exit' function that `zl::terminate()` calls, if you need specific functionality for ending the host application's page rendering, or doing something other than `exit()`.

In some cases, disabling parts of the debugger is required to make this work successfully.


### The best way to hijack:

For example, when hijacking WordPress:

Using a function like `zl::exitWordpress()`, which fakes rendering the site footer (you must pre-scrape it using the provided tool at zerolith/zl_internal/wpscrape.php), allows you to retain regular Zerolith error handling. Note that WordPress won't have an opportunity to render the footer normally, which may result in missing dynamically rendered elements at the bottom of the page (rare but possible).

Use these settings in your config file:
`$zl_set['envDebugger'] = false;`
`$zl_set['envExitFunc'] = "zl::exitWordpress";` <-- or any other function needed for exiting hijack mode


### An iffy way to hijack:

The default behavior of `zl::terminate()` in hijack mode is to deinitialize Zerolith and let the script continue execution (hopefully returning control to the host application). This means Zerolith code might continue running after being exited unless you're using an exit function specifically crafted for the host application.

For hijack mode scripts, error handling must be implemented differently - errors should be communicated back rather than relying on instant crashes. An unattractive but functional approach involves ensuring the procedural part of your script catches all errors and uses `goto` statements to jump to the end of execution. You'll also need to set **zdb::$returnErrors = true** to prevent silent failures.

This is particularly useful for when Zerolith is running inside an `exec()` inside Wordpress (such as in Oxygen Builder), where using `exit()` would prematurely terminate page rendering - meaning you can't exit Zerolith context and return to WordPress normally.