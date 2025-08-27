### What is Zpage?

Zpage draws page wrappers with includes in concert with the main zl class.

It has the following functions:
1. Automatic footer production on termination means that the programmer only has to use `zpage::start()`, then forget about ending the page.
2. Include paths can be injected into the page header
3. Includes a basic prebuilt page start/nav/end function with the option to assign other user-settable functions to create a custom layout.
4. Function that adds CSS debugging visual aids - `zpage::debugCSS()`
5. Nav menu generation with icons.
6. Handle page redirections regardless of whether you started the session or output text already.
7. Turn on HTMX debugging.


### Starting a page

Using zpage is easy, but before getting output, you need the following in your zl_config.php file:

```
$zl_page['wrap'] = true; //Produce HTML wrapper? ( use below functions )
$zl_set['outFormat'] = "page"; //default output format: page (zpage), html (no zpage), api (no zpage, no custom exit func)
```

If these are not set this way, because you chose another default mode, you can toggle them in the script as such:

```
zl::setOutFormat('page');
zl::$set['wrap'] = true;
```

Then the next step to start your page is:
`zpage::start("Insert your page title here");`

..and when ZL terminates, whether it was a natural end, exit(), or error, it will automatically call:
`zpage::end()`
..and successfully draw the page for you.


### Adding includes into the page header/footer

Easy. Just send the web path, plus the position where the include should be, and zpage will output it for you.
zpage can handle png ( site icon ), css, and js extensions.

Positions:
'start' - will output on zpage::start() ( make sure you don't add includes to the start after starting the page! )
'now' - output the include immediately
'end' - will output on page end

Example: `zpage:: addInclude('/css/styles.css', 'start');`