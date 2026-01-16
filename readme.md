# Zerolith preview release R2 - for research/solo hacker/small scale purposes only

![Zerolith Logo](zerolith-sega-logo-preview-version.png)


# What is it and what does it offer?

Zerolith is very high performance, minimalist replacement for popular PHP backend frameworks, and also frontend frameworks.
It is designed with the programmer who hates frameworks, complexity, and abstraction in mind.

- ~5x faster than mainstream MVC+OOP based PHP frameworks
- Short, consistent syntax with low cognitive overhead over vanilla PHP
- Enables procedural and light OOP programming approaches
- Non-compliant to PSR
- Can run standalone or be patched into existing frameworks and used for progressive enhancement
- First class integration with HTMX
- On-screen Debugger and mini profiler in developer mode
- Curl, MySQL, SQLite libraries
- Libraries for handling numbers, strings, arrays, filtering, time, system functions, validation, permission, email, etc
- A frontend library for easy page/partial/UI element handling ( not mandatory to use )
- A CSS framework ( zl.css ) which is like Tailwind but shorter and written in pure CSS
- More to come - we're just getting started!


# Why is it called Zerolith?

#1: Popular PHP frameworks like Symfony and Laravel have deep ideological/design roots in Java frameworks.

Java has a persistent execution model, and faster execution, so this overhead is okay because we eat most of it at boot/compile time.
But in PHP, we don't have a persistent execution model. A framework built along these lines creates a virtual monolith and throws it away on each request. This is expensive, and it also turns out, an unnecessary.

Zerolith is designed around PHP's execution model instead; the opposite of a monolithic model. ( the technical name is a 'shared nothing architecture' )

No offense to Java, but we think applying Java programming techniques to PHP is a mis-fit and Zerolith is a clean-slate approach to creating a modern framework without these artifacts.

![Java Did This](java-did-this.jpg)

#2: Zerolith is designed from the start to have as close to zero as possible impact on performance for the large amount of convenience it provides, versus writing raw PHP code.

![Why not both](why-not-both.jpg)

#3: Zerolith imposes virtually zero structure on a project. This allows the programmer to control the flow of execution and do interesting things without having to fight abstractions and artificial limitations.

![Go with the flow](go-with-the-flow.jpg)


# How is it so fast?

- No monolith to build, no extra structure, so the initialization routine takes <1ms.
- Extensive lazy loading.
- The included libraries are built with the lowest computational load in mind.
- Instead of using an addon templating engine, we use PHP's inbuilt templating which is the fastest part of PHP and can actually outperform some higher level languages. Twig, etc can be >= 5x slower.
- Application of many C lang performance tricks gleaned from guys like John Carmack: use the smallest data structure possible, use as little abstraction as possible, inline things when you can, stay as close to the metal as possible, etc. These turn out to work well due to PHP's C underpinning.
- Heavy use of profilers to identify trouble spots.

As a consequence of the emphasis on speed, memory consumption is also greatly reduced.


## Current state of things

The quality and user friendliness of this framework is ~75% between a production ready release and some company's internal tool that is used in a specific environment.

- Documentation is ~70% complete
- The framework can't run anywhere other than the root of a domain ( example: https://yoursite.com/ ) due to hardcoded CSS paths.
- Certain safety checks against drunk-coding are missing.
- UTF-8 support is not complete or toggleable yet.
- HTML sanitization has not been fully worked out yet. Blindly taking input from WYSIWYG editors is a bad idea unless you rig up your own sanitization routine.
- Input filtering exists but input validation doesn't yet.
- Various minor parts are incomplete.
- The unknown!


## Where is Zerolith headed?

This is a very ambitious work. We are interested in improving this framework to the point where it eventually becomes the de facto standard for PHP.
We would love you to file an issue if you encounter a bug, a function that is annoyingly inflexible, or some other gripe.


## Outro

We hope you enjoy this early taste of Zerolith and look forward to your feedback.
Please see the [docs](/zerolith/zpanel/docs) for further instruction on installation and usage!
