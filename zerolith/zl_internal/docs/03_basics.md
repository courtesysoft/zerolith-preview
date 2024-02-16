**Where's the documentation for the other libraries?**

Because Zerolith is still very young and has undergone significant structural changes since v0.1 ( originally was MVC-based! ), we expect it to not be 'mature' enough to write extensive documentation for until v1.25-v1.5.

In each class library, you will find good comment blocks explaining how each function works for the time being.

If you learn by example, the **zerolith/zl_internal/demo** folder is chock full of example code that is easy to read and learn from.

We promise there will be better documentation later and hope the existing examples serve as a good learning tool.

**What about routing?**

We have an example router that uses URL > filepath style routing ( automatic ) and can also automatically turn variables into PHP compatible GETs. It has not been used in a production environment yet, so we can't vouch for it.

**Control Flow**

Zerolith is designed to impose as little control flow as possible. It's initialization routine is simple.
Generally speaking, it will load, then immediately give control back to the file that loaded the framework.
Loading zerolith is closer to loading a library than a typical PHP framework.

Nuance:
- $zl_set['requireBeforeInit'] = "" will optionally require a file after the config file and before ZL initializes.
- $zl_set['requireAfterInit'] = "" will optionally require a file after ZL is initialized.
- Automatic zlhx routing, if turned on, happens immediately after ['requireAfterInit'].

If you are curious about sub-detail, check out zl_init.php and zl::init(). The routines are short and easy to follow.

**Termination and zpage::start/end**

Zerolith attaches a function ( zl::terminate() ) to both PHP's exception handler and shutdown handler. If you started a page with zpage::start, then during the zl::terminate routine, zpage::end is called automatically to close out the page and make the error look as nice as it can.

zl::terminate is always called regardless of whether there's an exception, exit() / die(), or the script ended gracefully.

If you need to, you can specify a function to run during the zl::terminate() routine before the final exit; see the config file for 'envExitFunc'.

In your code, it's best to call **zl::fault()** functions to signify a hard error ( this sends a message to the bug log/debugger ). The zerolith libraries all call fault() themselves.

If you do not want ZL to produce page wrapper HTML when pageStart() is called, set **$zl_page['wrap'] = false** in the configuration file, or set an Output Mode other than 'page'.

**Output Modes**

ZL has 3 output modes:

$zl_set['outFormat'] = 'page' - this will output HTML + includes + a full page wrapper that begins at zpage::pageStart() and automatically ends the page during zl::terminate() + show the debugger ( if turned on ).

$zl_set['outFormat'] = 'htmx' - this will output HTML + the debugger ( if turned on ) only.

$zl_set['outFormat'] = 'api' - only text/HTML; debugger output is always disabled. If $zl_set['envExitHandler'] is blank, zl::exitAPI is called during the termination routine by default to provide an interpretable message. Feel free to replace this with a $zl_set['envExitHandler'] function you prefer.

These modes can be toggled within the script using **zl::setOutFormat** if needed, but it's suggested that you toggle as early as possible in the script.

**Some basics about ZUI**

ZUI is zerolith's UI library and is complimented by zpage ( page wrapper engine ) and zPTA ( advanced table printing library ).

The default output is to echo() directly to the output.
This allows you to do a short call in a HTML section of your script like <?=zui::somefunc()?>

If you prefer 'return' behavior instead of 'echo' behavior then there is a variant of each functions with the character 'R' appended to them for convenience.

**Notes on the debugger/profiler**

The debugger can completely be turned off with $zl_set['debug'] = false in the configuration file.

The debugger must be manually turned on with zl::setDebugLevel(1);
Messages can be sent to the debugger using the zl::quipD() command. These will end up in the 'debug' tab.

The debugger is extremely optimized. It has been tested in systems as large as wordpress and successfully avoids blowing out the PHP memory limit in situations where most debuggers would.

The profiler is ZL mostly library-assisted but allows you to add your own custom timers using the ztime class. If you use ZL libraries, it will produces very useful reports automatically.

In zl_mode = dev, the debugger also captures PHP warning messages with a forced E_ALL setting. This makes PHP warnings that would normally be invisible ( hidden ) due to being buried in a HTML tag visible.

It is strongly suggested that the debugger is turned on during development. It is turned off by default to help prevent a misconfiguration from displaying sensitive data accidentally. Display is also forced off when zl_mode = prod for extra safety.

**About hijack mode:**

Zerolith has the ability to be ran inside and "hijack" other PHP frameworks and release control to them during zl::terminate(). It automatically enters hijack mode when it detects class autoloaders in the environment. You can also specify an 'exit' function that zl::terminate() calls, if you need specific functionality for ending the host application's page rendering, or doing something other than exit().

In some situations, you need to turn off some parts of the debugger to make this successfully work.

**----- The best way to hijack:** 

For example with hijacking wordpress;

Using a function like zl::exitWordpress(), which fake-renders the footer of the site ( you must pre-scrape it using zerolith/zl_internal/wpscrape.php ), allows you to retain regular zerolith error handling with the caveat that wordpress won't have a chance to render the footer ( and thus may miss out on dynamically rendered things at the bottom - rare, but possible )

Use these settings in the config file:<br>
**$zl_set['envDebugger'] = false;<br>
$zl_set['envExitFunc'] = "zl::exitWordpress";** <-- or any other function you need to call when exiting hijack mode

**----- An iffy way to hijack:**

The default behavior for zl::terminate() in hijack mode is to deinitialize zerolith and allow the script to continue ( hopefully leading back to our host application! ). This means that the zerolith code may continue running after being exited, unless you are using an exit function specially crafted for the host application.

This means that scripts written for hijack mode need to communicate errors backwards as a rule instead of relying on an instant crash. A not pretty, but workable way to handle this is make sure the procedural part of the script catches all errors and uses goto statements and jumps to the end on error. You will also need **zdb::$returnErrors = true** set, to prevent

This is a ghetto, but workable solution for certain types of includes in wordpress (such as in oxygen builder) where you cannot exit() without ending rendering the page on an error - ie you cannot exit zerolith land and go back to wordpress as usual.
