# Zerolith PHP Framework - preview release

![Zerolith Logo](zerolith/zl_internal/docs/zerolith-sega-logo-preview-version.png)

# For research purposes only

This release is provided to the public for research purposes, performance measurement, general feedback, security critique, and signs of interest ahead of the official release. It is not intended for a mainstream use yet.

# Warning

You should consider this an alpha. We cannot guarantee the safety or support this preview release in any way.

We strongly warn against running it in a production environment, because:
- Documentation is incomplete and readers need to look at the zerolith/class files for reference on what features exist and what function calls do. The files in /zerolith/zl_internal/demo are intended to provide examples of various libraries in action.
- Parts of the frontend are rough and unoptimized. Some 3rd party code is slated for demolition.
- Certain safety checks are missing.
- UTF-8 support is not complete or toggleable yet.
- HTML sanitization has not been fully worked out yet, blindly taking input from WYSIWYG editors is a bad idea unless you rig up your own sanitization routine.
- Input validation is incomplete.
- Due to the framework being early in it's life, some aspects of it are certain to change and could cause painful refactorings to the official release version.
- In-framework authentication system is barebones.
- The unknown!

# Where is Zerolith headed?

Zerolith has pretty small use right now. It currently powers about 4 applications, a collective 300k LOC.

Zerolith wants to be a Laravel competitor when it grows up. It wants to help users of it create applications as fast, if not faster.
That means being a complete development tooklit that can handle all aspects of modern application needs.

We plan for the full public release version to be completed sometime by the end of 2024.

At some point we want to follow up with v2.0 and include a web-browser based rapid development environment that can generate code, and anything else we discover that can help developers using Zerolith.

# Outro

We hope you enjoy this early taste of Zerolith and look forward to your feedback to improve it so it can become more useful.
Thank you for being willing to take a peek at this unfinished work, and Happy hacking!


Please see documents in [The docs folder](/zerolith/zl_internal/docs) for further instruction!
