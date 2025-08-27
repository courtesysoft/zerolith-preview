# Basic Things You Need to Know

### Control Flow

Zerolith is designed to impose as little control flow as possible. Its initialization routine is simple. 
Generally speaking, it will load, then immediately give control back to the file that loaded the framework.
On exit or fault, Zerolith will compute debug data, attempt to draw the page (if you started it), and exit.
Loading Zerolith is closer to loading a library than a typical PHP framework.

Nuance:
- `$zl_set['requireBeforeInit'] = ""` will optionally require a file after the config file and before ZL initializes.
- `$zl_set['requireAfterInit'] = ""` will optionally require a file after ZL is initialized.
- Automatic ZLHX routing, if turned on, happens immediately after `$zl_set['requireAfterInit']`.

If you want to explore the implementation details, check out `zl_init.php` and `zl::init()`. The routines are short and easy to follow.


### Termination and zpage::start/end

Zerolith attaches a function ( `zl::terminate()` ) to both PHP's exception handler and shutdown handler. If you started a page with `zpage::start`, then during the `zl::terminate` routine, `zpage::end` is called automatically to close out the page and make the error look as nice as it can.
`zl::terminate` is always called regardless of whether there's an exception, `exit()` / `die()`, or the script ended gracefully.

If needed, you can specify a function to run during the `zl::terminate()` routine before the final exit; see the config file for 'envExitFunc'.

In your code, it's best to call `zl::fault()` functions to signify a hard error (this sends a message to the bug log/debugger). All Zerolith libraries call `fault()` themselves when necessary.

If you don't want ZL to produce page wrapper HTML when `zpage::start()` is called, set `$zl_page['wrap'] = false` in the configuration file, or set an Output Mode other than 'page'. See the zpage documentation for more information.


### Development/Staging/Production Environments

Zerolith supports 3 different environment types:

- **prod**: Zerolith will run without the visible debugger but still generates debug data according to the set debugger level (0-4) if you have debugLog, debugLogOnWarn, or debugLogOnFault turned on.

- **dev**: This mode allows the debugger to be shown if the debug level is over 0. The mail library will not output mail by default (not fully implemented yet), preventing the dev server from accidentally emailing live users. Email can be forwarded to a selected address for debugging purposes, or it will appear in the debugger's zmail tab by default. There are also other subtle changes that enhance debugging ease.

- **stage**: This mode functions like development mode except some debugging features are disabled; the debugger still displays if `debugInStage = true`.


### Notes on the Debugger/Mini-Profiler

The debugger can be completely turned off with `$zl_set['debug'] = false` in the configuration file.
To activate the debugger, use `zl::setDebugLevel(1)` in your code.

Debug levels range from 0 to 4, with each one getting more computationally expensive in order to produce expanded results.

Messages can be sent to the debugger using the `zl::quipD()` command. These will appear in the 'debug' and 'quip' tab.

The debugger is well-optimized and has been tested in systems as large as WordPress. It successfully avoids exceeding PHP memory limits in situations where most debuggers would fail.

The mini-profiler is mostly ZL library-assisted but allows you to add your own custom timers using the ztime class. If you use ZL libraries, it automatically produces useful performance reports for those libraries.

In `zl_mode = dev`, the debugger also captures PHP warning messages with a forced E_ALL setting. This makes PHP warnings visible that would normally be hidden within HTML tags.

It is strongly recommended to enable the debugger during development. It's turned off by default to prevent potentially exposing sensitive data through misconfiguration. Display is also forcibly disabled when `zl_mode = prod` for additional safety.


### Where's the documentation for other libraries?

Because Zerolith is still evolving, comprehensive documentation will not be available until v1.3-v1.4.

For now, you'll find detailed comment blocks in each class library explaining how individual functions work.
If you learn best by example, the `zerolith/zpanel/demo` folder contains numerous easy to follow code examples.

We promise there will be better documentation later and hope the existing examples serve as a good learning tool in the meantime.