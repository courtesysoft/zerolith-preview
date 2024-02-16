Preview version notes:
** = non-working or non-tested
* = not complete

-- Backend --

ZL Framework features

Built around Data Oriented Design and light OOP. ~0.1ms bootup time, maintains main scope and does not divert natural execution flow, simple class autoloading, error capture, can hook into wordpress and other frameworks, can be used for progressive enhancement and refactoring, simple configuration, HTMX auto-function routing via ZLHX, developer/production/staging modes.

Zerolith is a PHP framework that works like a library, and it's designed for, and by, people who hate frameworks.

**First-class integration with HTMX**

HTMX is a first-class citizen in ZL due to it's high compatibility with ZL's simple server rednering approach.
ZL adds error handling, automatic transitions, HTMX debugging, and ZLHX to make HTMX even easier to work with.

**Debugger with mini-profiler**

Extensive debugbar that includes global scope variables, input variables, automatically generated debug logs from ZL libraries, error capture, warning/notice capture, visual trace on crash ( in dev mode ), automatic/manually generated performance statistics, library specific debug helpers, debugger state write on crash and warning/notice, and more. Also helps debug HTMX calls.

**zcurl curl library**

ZCurl is a high performance, high convenience curl library. It includes debug logging, performance logging, two styles of multi-threaded curl, and easy data post-processing.

**zdb MySQL library**

ZDB is a high convenience, low abstraction, non-ORM database library designed to make working with databases maximally easy. Features included are raw writes and reads, safe parameterized writes and reads, in-memory caching, fast in-disk caching, debug logging, performance logging, and multiple database support.

**zdp PostgreSQL library** **

ZDB, without the bells and whistles. Supports debug logging, performance logging, multiple database support, and safe parameterized writes and reads.

**zstr string library**

Just the basics for manipulating, sanitizing, and transforming strings.

**zarr array library**

Provides array transforming, inspecting, and processing functions. Includes Virtual SQL functions for operating on the output of disparate kinds of databases.

**zfilter filtering library**

High convenience input filtering library - encourages use. Provides a variety of time tested filters for common input scenarios.

**ztime time library**

Convenient functions for manipulating, checking ,and transforming time.

**zsys system library**

Convenient functions for file and systems handling. Includes file locking and serial ID generation. Can provide performance statistics in Linux operating systems.

**znum number library**

Just the basics for handling numbers and arrays of numbers. Includes formatting functions.

**ZPTA print table advanced**

Print table advanced is a database driven table generator that supports filtering, ordering, and pagination. Uses HTMX for fast updates. Allows programmer to mutate table before outputting to support any kind of HTML-based formatting inside the table. Very useful for pages that show lists of data.

**zvalid validation library** **

Highly convenient library that accents the functionality of ZUI and ZFilter by making it very short to specify validation rules and display validation error messages. Uses server-side or client-side checking for realtime validation feedback to users in forms.

**zauth authentication library** **

Optional authentication library that can help cross-authenticate ZL code to other applications, and in the future, help authentication for ZL applications.

**zmail mail library** *

High convenience wrapper for PHPmailer. Includes email logging, debug logging, performance logging, error capture, queueing with background processing, bounce capture from AWS SES, unsubscribing, email templating, enabling/disabling email, and sending all email to a specified address in dev mode for convenient testing.

**zs zero shortcuts library**

Library for logic shortcuts, including some polyfills for PHP 7.x

-- Zpanel --

**bugLog debug logs**

Displays debugbar at the time of crash or warning for past events that occurred in prod/dev/stage mode. Useful for spotting malicious traffic, user errors ( can correlate to user ID/name and IP address ), and more. Very convenient to use.

**mailLog mail logs**

Displays history of email sent out by the system, along with any errors that occurred. Very convenient to use.

**zTest unit testing + browser testing**

A testing system designed specifically to shore up the pitfalls of testing non-OOP code. Uses browser automation. High convenience and ease of use compared to other testing systems. Low installation requirements - just install chromedriver. Very high possible coverage due to unit test + web browser tests happening in one file.  

**imageTender image size management library** **

Industrial grade image processor. Works with any application that has images in a folder. Uses gifsicle, mozjpeg, and pngquant to achieve the absolutely highest compression ratios without a change in image quality. Can resize to a maximum X/Y pixels. Image tracking to prevent recompression and reprocessing. Won't degrade image if compression ratio not sigificantly higher. Capable of handling recursive directories with non-image content, missing extensions, corruption, and other hijynx. Strips exif tags except rotation marker for privacy.

-- Frontend parts --

**zpage**

Page wrapper handler that ties into ZL. Designed to fully draw page wrapper in the event of most kinds of script crashes. Customizable header and footer draw functions. Customizable navigation menu output. Can easily add includes at top or bottom of output during page generation. Mobile compatible.

**ZL.CSS** *

Frontend utility CSS library that operates like a much shorter, backend application-oriented tailwind. Written in Pure CSS. Skinnable via a configuration file written in CSS. Swappable palettes and automatic dark mode generation. Can be used with or without namespacing to enable progressive enhancement.

**zui** *

Frontend HTML generation library for pure server side rendering. High convenience. Generates the most common form controls and widgets and handles their dynamic behavior. Controls are tied to colors and options in zl.css theme file, so can be skinned also.