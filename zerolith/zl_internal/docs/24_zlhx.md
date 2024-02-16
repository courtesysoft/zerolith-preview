**What is ZLHX?**

ZLHX is a convenient automatic router for HTMX calls in the ZL framework. It uses an inline class in a PHP script as an automatic whitelist of what functions are callable to provide some brainless security as well.

It was designed to reduce HTMX-related piping in single file per page scripts, making HTMX functionality more apparent and less messy; the default way to handle HTMX in this coding style is to use a mat of if/then statements.

ZLHX also allows you to easily call the ZLHX functions from another page over Ajax or CURL, enabling easy sharing of HTMX HTML segments across pages.

In addition, it can be used for scripts that need to render a whole or partial page without duplicating database calls or other initialization overhead. It also allows HTMX debugging.

**How to use it**

Create a class, preferably at the bottom of your script, containing:

```php
class zlhx
{
    //--------the below functions are completely optional and don't have to exist for ZLHX to work.
    
    //This will auto-run once before the first zlhx call via htmx to initialize the zlhx container.
    //when using partial + full rendering, this must be manually called during the full rendering part.
    public static function zlhxInit() {}
    
    //This will auto-run before every zlhx call only via HTMX.
    public static function zlhxBefore() {}
    
    //This will auto-run after every zlhx call only via HTMX.
    public static function zlhxAfter() {}

    //--------put whatever functions your application needs here
    
    public static function helloWorld() { echo "hello world!"; }
}
```

The rest is up to you, the only ZL part of this class is just the specially named functions with magic behavior attached to them.

Here's an advanced example, for combined partial and full rendering of a given HTML segment.
In this case, the root script will serve as a container to produce a full page from the zlhx data.

When HTMX calls are made to the same segments, zerolith will initialize, immediately skip the top of the script, execute the given function, then die.

```php
require("zl_init.php");

//load data to forward to init
extract(zfilter::array("customerID"));

//we need to run this loader
zlhx::zlhxInit($customerID);

?>
Showing customer record:<br>
<div id="customer"><?=zlhx::showCustomer()?></div>
<div id="customerAux"><?=zlhx::showCustomerAux()?></div>
<br><br>
<a hx-get="?hxfunc=showCustomer&customerID=<?=$customerID?>" hx-target="#customer">Refresh Customer</a><br>
<a hx-get="?hxfunc=showCustomerAux&customerID=<?=$customerID?>" hx-target="#customerAux">Refresh Customer aux. data</a>
<?php

class zlhx
{
    //optional: any data relevant to all the functions in this zlhx instance go here
    public static $customerData = [];
    public static $auxData = [];

    //will execute before the called func
    public static function zlhxInit($customerID = "")
    {
        //filter and intake input variable(s) for all requests
        if($customerID == "") { extract(zfilter::array("customerID", "number")); }
        
        //commonly loaded data should go here.
        self::$customerData = zdb::getRow("SELECT * FROM customer WHERE ID = '$customerID'");
        self::$auxData =      zdb::getRow("SELECT * FROM customer_aux WHERE ID = '$customerID'");
    }
    
    public static function showCustomer()
    {
        //no input or actions specific to this function in this case. Otherwise, we'd put them here.
        
        echo rand(0,9999);
        zui::printTable(self::$customerData); //raw printout of customer data
    }
    
    public static function showCustomerAux()
    {
        //no input or actions specific to this function in this case. Otherwise, we'd put them here.
        
        echo rand(0,9999);
        zui::printTable(self::$auxData); //raw printout of customer's auxillary data
    }
}
```

Want a living example? see zerolith/zl_internal/demo/zlhx.php


**Execution flow of ZLHX**

Automatic mode depends on application-level bootloaders and authentication mechanisms to be plugged into either requireAfterInit or requireBeforeInit in the Zerolith configuration file. Otherwise, it can override

Automatic mode ( $zl_set['routeZLHXauto'] = true ):

(your_script.php) > require zl_init.php > (requireBeforeInit, if specified) > zl.php > (requireAfterInit, if specified) > zl::routeZLHX() > exit

Manual mode ( $zl_set['routeZLHXauto'] = false ):

(your_script.php) > require zl_init.php > (requireBeforeInit, if specified) > zl.php > (requireAfterInit, if specified) > (your script) > zl::routeZLHX() > exit


**How it works under the hood**

Check out the code for zl::routeZLHX(). It's a relatively simple mechanism.


**Limitations**

ZLHX does not handle input variables for you. We tried it, and it sucked. You need to pull them in on a HTML segment by segment basis.

ZLHX's automatic HTMX routing doesn't work correctly in pure procedural environments; you will see unexpected behavior in these areas:

1. Fully procedural function libraries that expect to use globals($someVariableName) to pull variables out of the global scope cannot do so, because they will be operating in ZLHX's object scope. This makes the globals() pull a blank or null variable into scope, breaking these libraries.

2. Because ZLHX's routing automatically happens immediately after framework load, manual include/require statements at the top of an oldschool procedural script will not be executed, and when the zlhx function is called, your code may fail due to trying to call a function that doesn't exist ( yet ).

If this affects your entire application, set $zl_set['routeZLHXauto'] = false; in your zl_config.php. This way, you can set a precise location in your script ( after includes/authentication etc ) to execute zl::routeZLHX().
