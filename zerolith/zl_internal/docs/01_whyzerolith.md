**What is Zerolith?**

"Abstraction trades an increase in real complexity for a decrease in
perceived complexity. That isn't always a win." - John Carmack

Zerolith is a simple, data oriented programming framework intended to compliment raw PHP code, not replace it. In essence, it is more like a library than a framework. It does not prescribe a programming methodology ( OOP, MVC, etc ) and does not conform to PSR standards.

It has a short learning curve, obscenely fast performance, includes an on-screen debugger, profiler, and a UI library that helps server-side rendering. ( pairs great with HTMX. )

Zerolith throws out the concept of MVC and OO; aiming to producing code that can most often be read from top to bottom by someone unfamiliar with the framework. In pure procedural without a framework, this would usually result in code of unmanageable length. Zerolith provides a way to significantly shorten this style of code into a manageable length which makes single file scripts feasible to write in the $currentYear.

**Why Zerolith was written**

1. We wanted an extremely low learning curve with shorter, more efficient, and more memorizable syntax with absolutely no legacy bloat vs most popular frameworks.

2. Most FWs encourage/mandate a very wordy object oriented style of coding that some people dislike / find unnecessary. This comes with a sizeable performance penalty in PHP.

3. Popular frameworks' performance is poor ( ~5x slower ). The extra optimization and tricks needed to make the resulting applications performant subtracts from the productivity gains they provide. This adds more complexity to the underlying application, and we want the lowest complexity we can get.

4. We wanted a framework that worked similar to using vanilla PHP that didn't control the program flow or organization; more of a stick shift than an automatic transmission.

5. Most frameworks think in terms of MVC; sharding pieces of a script into 3 files makes maintenance difficult and code longer. The intended separation of concerns is also not truly achieved. Some find that more linear code is easier to maintain and modify. Also, PHP is a templating language itself, so the 'V' part of MVC should be optional, not mandatory.

6. The encouraged style adds complexity, wordiness, and slowness which runs counter to PHP's original mission: reduce the amount of thought and lines of code to the bare minimum and make the result as fast as possible. We think there isn't any valid reason why PHP can't still be the short and fast language that Rasmus Lerdorf aimed for when he built it.

**Where does it's speed come from?**

Mostly procedural coding using arrays, strings, and functions turned out to be the fastest route to performance. So Zerolith is, in a way, a proof of concept of how fast PHP can be if you write code in accordance with PHP's underlying C language architecture.

Taking some tips from John Carmack, Zerolith was written with a profiler from the start, trying to find programming techniques in PHP that would result in the highest possible performance without the code getting long or hard to read. We aimed for the center of the venn diagram of programmer convenience and great performance instead of focusing on absolute performance though.

Zerolith utilizes, and encourages the use of PHP templating features over templating engines because native templating is what PHP is fastest at. Performance of PHP's native templating is very close to lower level languages like C, so it'd be stupid to throw out unless we absolutely have to.

Zerolith uses standardized array and data shapes so that data can be piped in and out of functions to lower memory reads and writes whenever possible, taking advantage of some internal optimizations in PHP.

Zerolith generally uses static classes instead of instantiated ones to organize it's functions and variables; static variables are slightly faster to access and use less memory, and static functions are slightly faster to call, and we also eliminate the need for dependency injection, so across the entire framework ( and application code ), we get a speed boost from just that. ( of course, class-less functions and regular variables are fastest )

Extreme attention was put into the most efficient and minimal design we can get away with, while still satiating modern needs.

**Reasons to use Zerolith**

1. You hate modern bloated programming practices that originated after PHP's OOP enlightenment period ( 2010's ) and always liked the 2000's era simple PHP coding style, but struggled to make it as featureful and productive as today's OOP-fest.

1. Zerolith was designed for very fast iteration and ditches the idea of caching, compilation, and other things that get in the way whenever feasible. If you have a low tolerance for BS, you'll appreciate that.

2. If you follow the programming style we prescribe, without much thought about optimization, your code will run significantly faster than on most frameworks by sheer accident, thanks to a diversion away from performance sucking practices, and also optimizations in the framework.

3. Zerolith was designed to run standalone, or hook into existing frameworks so that it can be used to progressively enhance or refactor existing code. It's one of the few PHP frameworks capable of this.

4. It was designed with maximum hackability in mind and is not effectively a black box like larger frameworks. It's structure is simple and it also prescribes almost no order in your code base, except for a /classes folder and configuration files being present.

5. It was built for building modern business-oriented web apps, and this fits your use case.

**Reasons to NOT use Zerolith**

1. Zerolith is a serious departure from how 'industry standard' code is written. Zerolith enables high programmer productivity, which is habit-forming, and also spits in the face of industry standard practices. If your career is important to you, you would be advised to not "take the red pill".

2. The kind of code that you will write with zerolith will beinherently untestable with unit testing frameworks.

3. As of writing, ZL code is non-unit testable, but we are in the middle of working on a very good novel solution for that problem.

4. Large OOP libraries fit poorly; there is no PSR autoloader/composer integration as of writing; we didn't make it easy to slap big external libraries in. This framework is for programmers who appreciate short, simple, and fast code.

**How using Zerolith is different than other frameworks**

Zerolith's programming interface  is essentially a function library disguised as static classes.
Generally speaking, these static class function calls input and return strings, arrays, etc instead of storing/returning objects and require no instantiation to use. 

To put it another way: in zerolith's functions, the 'data' part is generally separate from the logic part whereas with traditional OO classes, data and logic are often married as a rule and are typically accessed via object parameters.

Why do we make this separation? in PHP, we spend a lot of time handling SQL output, which is natively an array already. Also, you can easily manipulate arrays because they are iterable whereas custom defined objects are not. It makes sense to use this data structure often, and tying this data to classes as a rule is unnecessary.

Zerolith also uses a small set of fairly standardized array 'shapes' so that data is easily manipulable/transferrable between functions; as well as being easily iterable/manipulable with native PHP functions.

Another benefit: we can easily 'straight pipe' database outputs into various functions like such:

zlt::printTable(zdb::getArray("SELECT * FROM customers));
^-- printing a table straight out of the database output

zlt::selectBox("status",$status,zdb::getArray("SELECT ID, customername FROM customers));
^-- populating a selectbox with a value=ID and name=customername correlation.

zlt::selectBox("status",$status,zdb::getArray("SELECT customername FROM customers));
^-- even this will work; zerolith forces the customername = value and name parameters in the box.

Also, there is a performance and memory advantage with handling arrays instead of objects in PHP. This is no surprise, because PHP is built on C - a non-object oriented language. For that reason, there will always be extra overhead with objects VS functions/arrays/strings in PHP. 

Interestingly, one of the main ways C++ achieves good speed relative to C is by converting objects into functions/arrays/strings; in C++, you take the overhead of objects in the form of higher compile time. In PHP, we take that overhead during runtime, and it's not as good of a tradeoff.

Some exceptions to the above rules:

Libraries like zcurl, zPTA, etc allow you to incrementally 'queue' data into them and then process that data in a batch and return the output and reset the internal 'queue' of data. This works similar to some mainstream libraries, however the rest of the library uses static functions.

In cases where we think multiple instances may be necessary, instantiated equivalents of these functions exist ( rare ).
