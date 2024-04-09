# Zerolith PHP Framework - preview release

![Zerolith Logo](zerolith/zl_internal/docs/zerolith-sega-logo-preview-version.png)

# For research purposes only

This release is provided to the public for research purposes, performance measurement, general feedback, security critique, and signs of interest ahead of the official release. It is not intended for a mainstream use yet.

# Why is it 'for research purposes only'?

- Documentation is incomplete and readers need to look at the zerolith/class files for reference on what features exist and what function calls do. The files in /zerolith/zl_internal/demo are intended to provide examples of various libraries in action.
- Parts of the frontend are rough and unoptimized. Some 3rd party code is slated for demolition.
- Certain safety checks are missing.
- UTF-8 support is not complete or toggleable yet.
- HTML sanitization has not been fully worked out yet, blindly taking input from WYSIWYG editors is a bad idea unless you rig up your own sanitization routine.
- Input validation is incomplete.
- Due to the framework being early in it's life, some aspects of it are certain to change and could cause painful refactorings to the official release version.
- In-framework authentication system is barebones.
- The unknown!

# What does the Zerolith framework offer?

Zerolith is currently a mostly complete substitute for mainstream PHP frameworks.

- Around 5x faster than most frameworks
- Short, consistent syntax with low cogmnitive overhead over vanilla PHP
- Can run standalone or be patched into existing frameworks and used for progressive enhancement
- First class integration with HTMX
- On-screen Debugger and mini profiler in developer mode
- Curl, MySQL, postgreSQL libraries
- Libraries for handling numbers, strings, arrays, filtering, time, system functions, validation, authentication, email, etc
- A bespoke unit + browser testing system designed for procedural code ( not included in preview )
- A frontend library for easy page/partial/UI element handling ( not mandatory to use )
- A CSS framework ( zl.css ) which is like Tailwind but shorter and written in Pure CSS
- ImageTender image recompressor daemon which uses advanced re-coding techniques to reduce images by ~50% without losing picture quality.
- More to come - we're just getting started!

# Why is it called Zerolith?

Two reasons:

#1: Popular frameworks like Symfony and Laravel have their ideological roots in the Spring framework for Java. The Spring framework is designed around a persistent execution model with monolithic structure.

Frameworks like Laravel and Symfony spend an inordinate amount of time creating a virtual monolith structure in order to provide a spring-like environment. In Java, you take the computational/mem overhead of building this large amount of structure one time, but in PHP you take this overhead on every request.

Zerolith is designed around PHP's original execution and thought model instead, which is neither 'microservices' or 'a monolith'. Our best term for PHP is that it's
 'polylithic'.

#2: Zerolith is designed from the start to have as close to zero as possible impact on performance. Early tests indicated it added 3% more computational load to a large vanilla PHP project, and it's been optimized since then. Considering the alternative is to use large frameworks that 5x the computational load, 

So we call it Zerolith because it has virtually zero cpu/mem impact and imposed structure relative to it's competition.

# How the hell is it so fast?

- No monolith to build, no extra structure, the initialization routine takes about 0.1ms as a result.
- No OOP/MVC; the script using zerolith is in control of program flow.
- Instead of using an addon templating engine, we use PHP's inbuilt templating which is the fastest part of PHP and can actually outperform some higher level languages. The difference in cpu/mem vs using Twig, etc is enormous.
- We used John Carmack's programming advice, in particular using the smallest data structures possible for maximum performance and lowest memory usage. Because PHP is built on top of C, a lot of Carmack's low hanging fruit tricks actually worked.
- On top of this, the heavy parts of ZL have been optimized to the tilt with a profiler and the rest was written in a performance conscious way.

# Where is Zerolith headed?

Zerolith has small real world uses right now. It currently powers about 4 applications; a collective 300k LOC.

Zerolith wants to be a Laravel competitor when it grows up. It wants to help users of it create applications as fast, if not faster.
That means being a complete development toolkit that can handle all aspects of modern application needs.

We plan for the full public release version to be completed sometime by the end of 2024.

At some point we want to follow up with v2.0 and include a web-browser based rapid development environment that can generate code, and anything else we discover that can help developers using Zerolith.

# Outro

We hope you enjoy this early taste of Zerolith and look forward to your feedback to improve it so it can become more useful.
Thank you for being willing to take a peek at this early version; happy hacking!

Please see documents in [The docs folder](/zerolith/zl_internal/docs) for further instruction!
