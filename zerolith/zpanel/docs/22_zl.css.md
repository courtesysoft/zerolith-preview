# zl.css design principles
What we want zl.css to be: Shortly typed, memorizable, hackable, and always written in pure CSS ( and therefore a little chonky )

How we get there:
1. zl.css uses camelCase to shorten CSS class names and therefore is best used with a IDE that can autocomplete CSS classes.
2. zl.css uses abbreviations whenever reasonable to shorten CSS class names, but not at the expense of clarity.
3. zl.css is designed to not interfere with existing CSS and allow for progressive enhancement.
4. zl.css should never override default HTML elements, unless asked ( future: zl.HTMLoverride.css ), by default, .zl needs to be added to an element to accent it's styling.
5. zl.css can be used in a namespaced way ( zl_className ) or non-namespaced way ( className ), as long as a .zl class precedes the non-namespaced usage.
6. zl.css has a columns system, where by default, columns are divisions of 450px wide, expecting the inner contents to be flexible
7. zl.css' breakpoints are even intervals: mobile 0-450px, tablet 450-900px, laptop 900-1350px, desktop >=1350px
8. zl.css uses simple and easily memorable scales and syntax whenever possible.


### Scales

Spaces:   0 to 6 scale settable with zl_theme.css
Shadows:  0 to 6 scale, with 1 being a 1px shadow, 6 being a non-blurry, strong and far shadow
Colors:   1 to 11 scale, slightly biased towards lighter shades, with 1 being a hair from white, and 11 being very close to black. 0 and 12 are omitted because they'd correlate to a pure white or black.
Opacity:  0 to 10 scale, exactly correlating with opacity percentage
Fades:    Arbitrary percentage scale with 11 units
W/H:      Arbitrary percentage scale or arbitrary pixel scale
Breakpoints: 1 to 4 scale; mobile, tablet, laptop, desktop


### Syntax rules

After the zl_ part, camelCase is always the remainder. The zl_ prefix is there to prevent conflict with existing CSS.
If you put a .zl in the document, any CSS class can be used without the zl_ prefix, turning `zl_bordRBlue5` into `bordRBlue5`

zl.css is highly optimized for autocomplete according to how autocomplete works in VS Code and IntelliJ IDEs such as PHPStorm.
It follows a logical order; here are some examples:

Class name    [n.space][function][variant(s)][operator][scale num.]  Translation:
zl_bordRBlue5    zl_     bord        RBlue                5          border, right, blue, scale 5
zl_mar-5         zl_     mar                      -       5          margin, negative, 5
zl_bgAmber3      zl_     b.ground    Amber                3          background, amber, 3


### How to use it?

See zl.css; the file is simple enough to read and learn.
See demo html file for examples of usage.


### How does mobile/tablet compatibility work?

( to be documented )


### Flexbox columns system

( to be documented )