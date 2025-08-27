# Backend Features

Zerolith is a PHP framework that works like a library. It's designed for and by people who hate frameworks.

## Key characteristics:

- Designed for a mix of procedural and minimalist OOP programming.
- Offers shorter, simpler, and less abstract functionality compared to most high-feature frameworks.
- Maintains main scope and does not divert natural execution flow. More like a manual transmission than an automatic.
- Highly optimized; ~0.1ms bootup time, performs approximately 2-5x better than mainstream high-feature PHP frameworks, uses a fraction of RAM per request & minimizes GC internally.
- Can be used for progressive enhancement and refactoring of existing apps, compatible between PHP 7.x - 8.x.
- Has simple class autoloading.
- Can hook into Wordpress and other frameworks to bolt functionality to the side of the app.
- Has simple configuration.
- Has developer/production/staging modes

### First-class integration with HTMX

HTMX is a first-class citizen in ZL due to its high compatibility with ZL's simple server rendering approach.
ZL adds error handling, automatic transitions, HTMX debugging, and ZLHX to make HTMX even easier to work with.

### Debugger with mini-profiler

Extensive debugbar that includes global scope variables, input variables, automatically generated debug logs from ZL libraries, error capture, warning/notice capture, visual trace on crash ( in dev mode ), automatic/manually generated performance statistics, library specific debug helpers, debugger state write on crash and warning/notice, and more. Also helps debug HTMX calls.

### zcurl curl library

ZCurl is a high performance, high convenience curl library. It includes debug logging, performance logging, two styles of multi-threaded curl, and easy data post-processing.

### zdb MySQL library

ZDB is a high convenience, low abstraction, non-ORM database library designed to make working with databases maximally easy. Features included are raw writes and reads, safe parameterized writes and reads, in-memory caching, fast in-disk caching, debug logging, performance logging, and multiple database support.

### zstr string library

Just the basics for manipulating, sanitizing, and transforming strings.
Provides the basics for manipulating, sanitizing, and transforming strings.

### zarr array library

Provides array transforming, inspecting, and processing functions. Includes Virtual SQL functions for operating on the output of disparate kinds of databases.

### zfilter filtering library

High convenience input filtering library - encourages use. Provides a variety of time tested filters for common input scenarios.

### ztime time library

Convenient functions for manipulating, checking ,and transforming time.

### zsys system library

Convenient functions for file and systems handling. Includes file locking and serial ID generation. Can provide performance statistics in Linux operating systems.

### znum number library

Just the basics for handling numbers and arrays of numbers. Includes formatting functions.

### zs zero shortcuts library

Library for logic shortcuts, including some polyfills for PHP 7.x


## The following features are not finished, but available for preview:

** = non-working or non-tested
* = partially complete and will change in a future version.

### ZPTA - print table advanced *

Print table advanced is a database driven table generator that supports filtering, ordering, and pagination. Uses HTMX for fast updates. Allows programmer to mutate table before outputting to support any kind of HTML-based formatting inside the table. Very useful for pages that show lists of data.

### zlite mysql library *

A basic library for interacting with SQLite, based on zdb. Currently subject to change.

### zmail mail library *

High convenience wrapper for PHPmailer. Includes email logging, debug logging, performance logging, error capture, queueing with background processing, bounce capture from AWS SES, unsubscribing, email templating, enabling/disabling email, and sending all email to a specified address in dev mode for convenient testing.

### zvalid validation library **

Highly convenient library that accents the functionality of ZUI and ZFilter by making it very short to specify validation rules and display validation error messages. Uses server-side or client-side checking for realtime validation feedback to users in forms.

### zperm permissions library **

Optional authentication library that can help cross-authenticate ZL code to other applications, and in the future, help authentication for ZL applications.



# Zpanel

### bugLog debug logs

Displays the debugbar at the time of a crash or warning, showing past events that occurred in prod/dev/stage mode. Useful for identifying malicious traffic, user errors (can be correlated to user ID/name and IP address), and more. Very convenient to use.

### mailLog mail logs

Displays a history of emails sent out by the system, along with any errors that occurred. Very convenient to use.

### Demo Gallery

A collection of useful examples demonstrating Zerolith's functionality, ideal for learning purposes.

### Documentation viewer

Automatically turns these .md documents into HTML



## The following backend features are not finished, but available for preview:

** = non-working or non-tested
* = partially complete and will change in a future version.

### zTest unit testing + browser testing **

A testing system designed to address the challenges of testing non-OOP code. Uses browser automation. High convenience and ease of use compared to other testing systems. Low installation requirements — just install chromedriver. Very high possible coverage due to unit tests and web browser tests occurring in one file.

### imageTender image size management library **

An industrial-grade image processor that works with any application having images in a folder. Uses gifsicle, mozjpeg, and pngquant to achieve the highest compression ratios without altering image quality. Can resize to a maximum of X/Y pixels. Image tracking to prevent recompression and reprocessing. Won't degrade the image if the compression ratio is not significantly higher. Capable of handling recursive directories with non-image content, missing extensions, corruption, and other irregularities. Strips EXIF tags except for the rotation marker for privacy.



# Frontend parts

### zpage

A page wrapper handler that integrates with Zerolith. Designed to fully render the page wrapper in the event of most script crashes. Customizable header and footer draw functions. Customizable navigation menu output. Easily add includes at the top or bottom of the output during page generation. Mobile compatible.

### zl.css

Frontend utility CSS library that operates like a much shorter, backend application-oriented tailwind. Written in Pure CSS. Skinnable via a configuration file written in CSS. Swappable palettes and automatic dark mode generation*. Can be used with or without namespacing to enable progressive enhancement. Highly hackable. ~15k gzipped.

### zui

Convenient, short, and simple library for frontend. Generates the most common form controls + widgets and handles their dynamic behavior. Controls are tied to colors and options in zl.css theme file, so it can be skinned also.