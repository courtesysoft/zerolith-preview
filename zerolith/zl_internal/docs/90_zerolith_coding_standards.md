# Preface - why we have standards

It's ironic that we wrote a PHP framework that totally ignores industry standards, allowing us to write code the way we want, and also included a document telling you, the reader, how to write code.

We would like to just delete this document and hope everyone just contributes good code to Zerolith but have found out that reality doesn't work that way. Multi-person code with various formatting preferences, IDEs, IDE settings, etc can become an tangled, unreadable mess without some ground rules.

These rules apply to anyone contributing to zerolith because we want a clean and easy to work with & understand codebase. These are also the same standards we have at our development shop.


# Code specifications for IDEs

Zerolith code uses tabs with 4 spaces and a soft 120 character width limit which should provide a comfortable amount of editing space on a typical laptop monitor.
Lines are allowed to soft wrap and should be allowed to do so. Sometimes you can't help but break something into multiple lines - try to consider readability when doing so.
Given that our webservers are always Unix machines, the Linux LF file format is mandatory. We suggest using a Mac or Linux environment to edit Zerolith files, or set your IDE to always use the LF format.


# Hard rules - the bare minimum

1. Zerolith utilizes associative arrays and arrays of associated arrays for most of the input/output in many of it's functions. Your data structures should be modeled around this so that code upstream/downstream doesn't need additional array processing or have excessively verbose array manipulation.

Example associated array:
```php
$customerData = ['id' => 12344, 'first' => 'bob', 'last' => 'bobberton'];
```

Seen in: zl::$set, zl::$page, zl::$site, some ZDB input and output ( zdb::getRow for example ), etc

Example array of associated arrays ( database):

```php
$customerArray[0] = ['id' => 12344, 'first' => 'bob', 'last' => 'bobberton']
$customerArray[1] = ['id' => 12344, 'first' => 'jeff', 'last' => 'jefferson']
```

Seen in: zdb::getArray output, zpta intermediate row processing format, etc


2. ALWAYS use input filtering when handling *anything* a browser can send/modify. This should be at the top of script so that you can't miss it. In essence this acts like a list of includes, but for input.

Anything from $_GET, $_POST, $_FILES, and some values in $_SERVER can be considered user input and must be filtered.

We make it stupid easy to do this so there's no excuses not to do things this way.
In a single line, you can pull in 4 variables into your scope:

```php
extract(zfilter::array("hxfunc|perspective|partial|customerID", "stringSafe"));
echo $perspective; //<-- this is in your current scope thanks to extract()
```

Or a single variable from anywhere can be filtered like this:

```php
$fileVariable = zfilter::stringSafe($_FILES['something']);
```


3. Weird PHP operators, uncommon math operations, and anything other than very light syntactic sugar are **expressly forbidden**.

The reason for this is that PHP gives provides too much freedom to write code in goofy ways other people don't understand. Code written in this manner can end up being thrown away one day when another programmer has to work on it and doesn't understand it. We want to at all times try to write code that has maximum understandability so that even a junior developer who comes across it could at least understand what's happening.

It always way better to write longer code that can be understood later than save a few lines and confuse everyone else.

The only exception for this is in libraries where tricky code is worth the understandability disadvantage in the name of performance.


4. If/then statements acceptable forms

There are dozens of ways you can write if/then statements in PHP but a mixture of various different styles among different programmers can slow down everyone who reads the code. For this reason, we'd like to homogenize on the following formats:

Short format for things < 100 chars wide

```php
if($something == "yeah"){ echo "yeah!"; } else { echo "naw!"; }
```
( very ideal for short expressions )


Mid size format

```php
if($array['something'] == strpos($array['something'], "yeah", 0)) { echo "yeah!"; }
elseif($array['something'] == strpos($array['something'], "yeah", 0)) { echo "naw!"; }
```
( 1 line would be ugly, a compact 2 is nice )


Longer format for complex expressions

```php
if($array['something'] == strpos($array['something'], "yeah", 0)) 
{ 
    registerSomething();
    echo "yeah!"; 
}
elseif($array['something'] == strpos($array['something'], "yeah", 0))
{
    registerSomething();
    echo "naw!"; 
}
```

( 2 lines or above inside brackets is a candidate for this )

Jumbo format for complex conditions

```php
if
(
    $array['something'] == strpos($array['something'], "yeah", 0) ||
    $array['something'] == strpos($array['something'], "yeah", 1) ||
    $array['something'] == strpos($array['something'], "yeah", 2) ||
) 
{ echo "yeah!"; }
else 
{ echo "naw!"; }

```
( notice that the conditions are lined up like code statements. This makes if/thens with lots of crazy expressions a lot easier to read. If we hit a >120 character width limit then it's time to start thinking about this format )

OK, but not preferred:

```php
if($something == "yeah"){
    echo "yeah!";
}
```
( The diagonal brace can save a line but some of us don't like it. )

We encourage programmers to use the most compact format possible but also think about readability. Permutations of listed formats are OK.

You should have spaces between operators (example: ==, &&, ||) and spaces after commas between variables. It's okay to remove spaces if you are desperate to make a line fit. Brackets should also have at least a space around them, if not a return. This is for readability.


5. Brackets + indentation should be written as if we had the strictness of Python.

We consider lost / misplaced brackets or imperfect indentation to be a time bomb waiting to go off in a piece of code because PHP cannot help you in this case, and you often have to audit the brackets and indentation from top to bottom to fix it.

Statistically speaking, not doing an absolute perfect job of maintaining this ends up consuming more time than if you cleaned this up as you go. It also confuses people reading it. So it's both a time bomb and a time suck.

We demand this is clean because the consequences of getting it slightly wrong are so high.


6. Script flow for pages standardized format.

When you are writing a PHP script that will constitute a single page, adhere to the following code outline:

```php
//framework initialization
//other includes, if any
//authentication, if any
//input filtering ( + validation if you need it )
//giant if-then branch for action routing, if not using zlhx for this purpose
//
//all your other code goes here
//
//any script-specific functions that only this script needs go at the bottom
//zlhx inline class ( if you are using it) at the absolute bottom.

```

This design minimizes 'spaghetti' by sectioning out various aspects of the code so that certain concerns are in one place, not interpolated all over the place.


7. Where does X block of code go?

The code is specific to the script:

If it's used once, and not a complex routine, it should be inline.
If it's used >2 times, and a complex routine, it should be a function in the script instead of being duplicated.
If it's a 1-2 liner, just inline it, unless you are doing this >5 times or really need the logic in one place.

The code is used once, but the script is >1500 lines and some code must be offloaded:
It should be a function in an app-specific static class.

The code is shared among scripts:
It should be a function in an app-specific static class.

If the code will be used across the entire system, isn't very specific to the app, and broadly applicable:
It probably belongs in zerolith. Let us know about it :)

What we want to avoid overall, is wrapping things in functions and abstraction excessively. Less is more.
Always start with the bare minimum of structure and then add functions and abstractions only when necessary.


8. Variable naming

Variable naming should be predictable, explanatory, and somewhat uniform. Shortness should usually take a backseat to understandability. Good variable names help document the code.

- use $camelCase variable names all the time.
- handle the camel case edge case issues like HTMLwhatIsIt however you feel is best, we have autocomplete :)
- Name the variable after the thing whenever possible, for example, a customer's billing data row should be $customerBillingData; if really long it's okay to abbreviate parts of it, making the previous string $custBillData.
- Iterators like $i in for statements, $k => $v in foreach expressions on associative arrays, and $c for count are totally fine because these are effectively throwaway variables.
- The last part of the variable name, if in the format of database output, should relate to the array format the data is in. For example, zdb::getRow() returns a single row of customer data in an associative array format, so it could be called $customerData or $customerRow. If you are getting multiple rows with zdb::getArray(), you should call it $customerArray.
- When we have an array of something that is not database output, a single item should be called $customer and an array of customers should be called $customers. In english we call this plural/singular. It makes sense in programming, too.


9. Function naming

Function names aren't unlike variables in the fact that poorly named ones can make a program hard to understand. The same emphasis on making variable names clear applies here:

- if the function just gets a piece of data, the first word in it should be get. Example: getCustomer()
- if the function writes data, writeCustomer or saveCustomer would be acceptably obvious.
- if there are a series of related functions, group them by a prefix if possible. This makes them easier to locate in an IDE, and easier to autocomplete.
- Otherwise, write function names so obvious someone with -30 IQ could understand them.


10. If it's written by ChatGPT or some other AI, and you don't 100% understand the code, note it.

The biggest problem with AI is that it produces imperfect solutions which could technically work in one condition, but not in a future condition. Because we can't expect code coming out of AI was completely thought out by a human, we should consider it somewhat suspect, and mark the code portion with a comment thanking GPT/AI for the code.


11. Use parameterized database reads and writes whenever possible.

Input filtering in Zerolith gives us a large degree of safety from end-user nincompoopery, but using parameterized, array based writes and reads whenever possible provides another safety layer. We need both. Use functions like zdb::writeRow() whenever possible and use the raw SQL functions whenever you hit a condition where the parameterized functions can't support some edge case SQL feature you need.


# What we consider good programming

1. Short code, but not to a fault

A good programmer will distill the solution to the lowest cognitive complexity and lowest lines of code possible. The reason why a good programmer does this is so that when they have to bend the code to customer demands, it's easier to work on, even a decade later when he/she has totally forgot how the code works.

A good programmer also knows that writing nasty looking code to achieve ultimate code shortness is never worth it, when something needs to be long and verbose, it probably should stay that way for readability/understandability.


2. Comment blocks for each logic section and for notable spots

A good programmer works on so much code and for so long that he knows he/she'll forget it. He/she uses both obvious and somewhat verbose variable naming as a way to add documentation, and also comments code blocks and pieces of code that are tricky, cover edge cases, or work but don't look like they work.

Here is an example of good commenting with comment blocks:

```php
//this would be input through the form if it was empty in the function call
if($customerID == "") { extract(zfilter::array("customerID", "number")); }
if($customerID == "") { zl::fault("customerID not sent in zlhx request."); } //was blank?
if($perspective == "") { extract(zfilter::array("perspective", "stringSafe")); }

zlhx::$perspective = app::$userType; //default is locked to user type

//check if the user has permission to do this ( universal privilege check for the whole shooting match ).
if(app::$permissions == 0) { zl::faultUser("You cannot view this page because you are not logged in"); }
if(app::$userType == "student")
{
    if(app::$userData['id'] != $customerID)
    {
        //yeah, well, does the student have an associated ID?
        if(!in_array($customerID, associationList($customerID)['accounts']))
        { zl::fault("You aren't logged in as the user's account that you're trying to view."); }
    }
}
elseif(app::$userType == "teacher") { } //insert permissions check for teacher ownership of student
elseif(app::$userType == "admin")       //admin can masquerade for testing/maintaining the system
{ if($perspective != "") { zlhx::$perspective = $perspective; } }

```

Good comments explain what we're trying to accomplish with each block of code in human, not computer, terms.


3. Well organized code

Proper organization can make or break code understandability and maintaineability, especially as the complexity and length of the file grows. A good programmer is always thinking about this as he/she modifies the code and removing/adding/modifying/clarifying structure and willing to spend extra time to ensure the structure is as clean and understandable as possible at all times.


4. Do your research when working on someone else's code

A good programmer spends as much time as he/she needs to understand the code they are working on before operating on it. This is so the modification can have an OEM-like fit & finish and look like it was a piece of the original code, not an addon. This is time consuming, but a good programmer knows this attention to detail pays off in the long run.

A good programmer also comments these modifications so the original author understands it was not part of his/her original design.

It is also a nice practice to comment out large sections of cdoe if we are replacing them so that we have a reference to how the original code worked. We can remove these code after a number of years.


5. Test the hell out of your code when finishing it

Programmers are famous for being bad at testing their code. Programmers suffer of blind spots & tunnel vision which interfere with being able to consistently output a good product. And there are situations the programmer never thought of which can pop up after release.

Luckily it's not hard to beat the national average here.

A good programmer, before submitting their code or uploading it to prod, will devise a test routine for the code and manually or automatically run this test routine before approving their own code. This is time consuming, but worth the time.

The real world acceptable margin of error for code is less than 1%. It can take an inordinate mount of time to get another percent of accuracy out of finished code, but it is worth doing so because you won't have to load the code back into your brain again after disappointing others. Shipping broken code and fixing it later consumes more time and lowers morale, so it never pays to skip the testing step.

We don't expect a good programmer to always be perfect here. Programming is hard, and we can't reasonably test every permutation. But a good programmer does their best to test their code every time, and rarely ships broken code.


6. Use the zerolith debugger

The zerolith debugger solves all the problems associated with adding echo() and print_r() to discover variable values while debugging. zl::quipD() will accept any data format, and print it in the debugger which is only viewable in developer mode. The ZL debugger also automatically prints the output and input of database, curl, and other operations so that you don't have to print_r() any data.

A good programmer uses the ZL debugger to the fullest knowing that it's easy to put a print_r() or echo statement in code but not always easy to find them - this means that eventually sensitive data, or what looks like computer garbage may be shown on production, and the small chance of human error makes it not worth it.