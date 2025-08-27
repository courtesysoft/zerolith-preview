v0.90 - Last edited 04/11/2024 - DS


### Why we have standards

It's ironic that we wrote a PHP framework that totally ignores industry standards, allowing us to write code the way we want, and also included a document telling you, the reader, how to write code.
But it turns out when you have multiple people working on a framework, you gotta have standards for code!
These rules apply to anyone contributing to Zerolith. These are the same minimum standards we have at our web development shop.


### Code specifications

Zerolith code uses tabs with 4 spaces and a soft 120 character width limit which should provide a comfortable amount of editing space on a typical laptop monitor.
Lines are allowed to soft wrap and should be allowed to do so. Sometimes you can't help but break something into multiple lines - try to consider readability when doing so.
Given that our webservers are always Unix machines, the Linux LF file format is mandatory.
We suggest using a Mac or Linux environment to edit Zerolith files, or set your IDE to always use the LF format if you're in Windows.


### Hard rules - the minimum

1. Zerolith utilizes associative arrays and arrays of associated arrays for input/output in many of it's functions. Your data structures should be modeled around this when applicable so that code upstream/downstream doesn't need additional array processing or have excessively verbose array manipulation.

Example associative array formatting:
```php
$customerData = ['id' => 12344, 'first' => 'bob', 'last' => 'bobberton'];
```

Seen in: zl::$set, zl::$page, zl::$site, some ZDB input and output ( zdb::getRow for example ), etc

Example array of arrays of associative arrays ( database output ):

```php
$customerArray[0] = ['id' => 12344, 'first' => 'bob', 'last' => 'bobberton'];
$customerArray[1] = ['id' => 12344, 'first' => 'jeff', 'last' => 'jefferson'];
```

Seen in: zdb::getArray output, zpta intermediate row processing format, etc


2. ALWAYS use input filtering when handling *anything* a browser can send/modify. This should be at the top of script so that you can't miss it.
Think of this as a list of includes, but for input.

Anything from $_GET, $_POST, $_FILES, and some values in $_SERVER can be considered user input and must be filtered.

We make it stupid easy to do this, so there's no excuses not to do things this way.
In a single line, you can pull in 4 variables in pipe delimited formatinto your scope:

```php
extract(zfilter::array("hxfunc|perspective|partial|customerID", "stringSafe"));
echo $perspective; //<-- this is in your current scope thanks to extract(), and echoes successfully.
```

Or a single variable from anywhere can be filtered like this:

```php
$fileVariable = zfilter::stringSafe($_FILES['something']);
```
Overall, we think aspects that involve security should be at the top of the script and extremely high visibility.
It is easy to make a large mistake with security when you don't have an established pattern.


3. Weird PHP operators, uncommon math operations, ternary, and anything other than very light syntactic sugar are **expressly forbidden**.

The downside of PHP's libertarianism is that it gives too much freedom to write code in goofy ways other people don't understand.
Code written in this manner can end up being thrown away one day when another programmer has to work on it and doesn't understand it.
We want to at all times try to write code that has maximum understandability so that even a junior developer who comes across it could at least understand what's happening.

It always way better to write longer code that can be understood later than save a few lines and confuse everyone else.
The only exception for this is in libraries where tricky code is worth the understandability disadvantage in the name of performance or compactness.

We like ternary for shortness but we don't like to read it because surrounding syntax will be using brackets { } and it represents a speedbump during code review because the format of this fancy 'if' is completely different, and our entire codebase doesn't include this, so it clashes.

Example of ternary:

```php
(Condition) ? (Statement1) : (Statement2);
```

![grug_thinking.jpg](grug_thinking.jpg)

Ask yourself... would Grug understand it?


4. If/then statements acceptable forms

There are dozens of ways you can write if/then statements in PHP but a mixture of various different styles among different programmers can slow down everyone who reads the code. 

Zerolith is written in [Allman style](https://en.wikipedia.org/wiki/Indentation_style#Allman).

For this reason, we'd need to homogenize on the following formats:

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


Longer format for complex conditions/expressions

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
( The diagonal brace can save a line sometimes. It doesn't cause a significant speed bump when reading )

We encourage programmers to use the most compact format possible but also think about readability. Permutations of listed formats are OK, but try to keep to as little permutations of formatting as possible.
You should have spaces between operators (example: ==, &&, ||) and spaces after commas between variables. It's okay to remove spaces if you are desperate to make a line fit. Brackets { } should also have at least a space around them, if not a return. This helps.


5. Brackets + indentation should be written as if we had the strictness of Python.

We consider imperfect indentation to be a time bomb waiting to go off in a piece of code because PHP cannot help you in this case,
and you often have to audit the brackets and indentation from top to bottom to fix it.

Statistically speaking, not doing an absolute perfect job of maintaining this ends up consuming more time than if you cleaned this up as you go. It also confuses people reading it.

We demand this is clean because the consequences of getting it a little wrong are very high.
If your code is hitting a line width restriction hard and getting ugly to read, some strategic indentation cuts are OK. But try to keep this clean.

For HTML, indentation is not as critical and it is okay to fudge things so they fit inline with code, get shorter etc.
It doesn't represent as large of a speed bump when reading code. Try to keep to well formatted before you start using proprietary formatting.

![bracket on line 32.jpg](bracket%20on%20line%2032.jpg)


6. Script flow for pages standardized format.

When you are writing a PHP script that is a single page per file design, adhere to the following code outline:

```php
//framework initialization
//other includes, if any
//authentication, if any
//input filtering ( + validation if you need it )
//( if needed ) giant if-then branch for action routing, if not using zlhx for this purpose
//
//all your other code goes here, preferably it looks like this ( this part is bendable ):
//  initial data processing
//  data processing/HTML processing gradient zone
//  HTML output <-- OK to contain mild data processing
//
//any script-specific functions that only this script needs go at the bottom.
//zlhx inline class ( if you are using it) at the absolute bottom.

```

This design minimizes 'spaghetti' that naturally accumulates in procedural code by sectioning out various aspects of the code so that certain concerns are in predictable places, reducing interpolation. This organization style is the tradeoff that makes non-MVC procedural code liveable. Otherwise, procedural code, by default, is a mess.


7. Where does X code go?

**The code is specific to the script:**

If it's used once, and not a complex routine, it should be inline.
If it's used >2 times, and a complex routine, it should be a function in the script instead of being duplicated.
If it's a 1-2 liner, just inline it, unless you are doing this >5 times or really need the logic in one place.

**The code is used once, but the script is >1500 lines and some code must be offloaded:**
It should be a function in an app-specific static class. See if you can reduce code first.

**The code is shared among scripts:**
It should be a function in an app-specific static class.

**If the code will be used across the entire system, isn't very specific to the app, and broadly applicable:**
It probably belongs in Zerolith. Let us know about it :)

What we want to avoid overall, is wrapping things in functions and abstraction excessively. Less is more.
Always start with the bare minimum of structure and then add functions and abstractions only when necessary.

![programming preferences over time - Lea Verou.png](programming%20preferences%20over%20time%20-%20Lea%20Verou.png)

Ideally, the amount of DRY we apply to our code is the halfway point at the end of this curve, not too much, not too little.


8. Variable naming

Variable naming should be predictable, explanatory, and fairly uniform. Shortness should usually take a backseat to understandability.
Good variable names help document the code on accident when you use them, and make your life, and the lives of other programmers much easier.

We also use a non-compiled dynamic typed language; this makes variable naming even more important to help convey what kind of data it is so nobody is constantly scrolling up when reading code to find that out.

- Use $camelCase variable names all the time.
- Handle the camel case edge case issues like HTMLwhatIsIt however you feel is best, we have case-insensitive autocomplete :)
- Name the variable after the thing whenever possible, for example, a customer's billing data row should be $customerBillingData; if really long it's okay to abbreviate parts of it, making the previous string $custBillData.
- The last part of the variable name, if in the format of database output, should relate to the array format the data is in. For example, zdb::getRow() returns a single row of customer data in an associative array format, so it could be called $customerData or $customerRow. If you are getting multiple rows with zdb::getArray(), you should call it $customerArray. This makes predicting what kind of data is in the variable extremely easy, if done consistently, and helps document code.
- When we have an array of something that is not database output, a single item should be called $customer and an array of customers should be called $customers. In english we call this plural/singular. It makes sense in programming, too.
- Short, non descriptive variables for iterators like $i in `for` statements, $k => $v in `foreach` expressions on associative arrays, and $c for count, etc are totally fine because these are effectively throwaway variables.


9. Function naming

Function names aren't unlike variables; poorly named ones can make a program hard to understand. The same emphasis on making variable names clear applies here:

- if the function just gets a piece of data, the first word in it should be get. Example: getCustomer()
- if the function writes data, setCustomer or saveCustomer would be acceptably obvious.
- if there are a series of related functions, group them by a prefix if possible. This makes them easier to locate in an IDE, and easier to autocomplete.
- Otherwise, write function names so obvious that people would understand them even when drunk-coding.


10. Function commenting and variable choices

If you are creating a simple function with few parameters, then clearly named variables and a 1 line comment totally suffices!

```php
//surround something in a box.
function box($html, $extraClasses = ""){ echo '<div class="zlt_box' . self::zEC($extraClasses) . '">' . $html . "</div>"; }
```

When you are writing a complex function that can take many different variable types, is picky about array formats, and you have a significant number of parameters, the documentation needs to be above the minimum.

This format provides plenty of room to document the parameter and allows sufficient notes:

```php
//Array to table printer
function printTable
(
    $tableArray,          //Array input, preferably zdb::array() output ( array of assoc arrays ), but accepts almost all other formats.
    $showFields = [],     //SQL field => English name for given TH field. Input also determines order of field display. If omitted, default ordering is used
    $THfieldClasses = [], //SQL field => className for given TH field. Will apply a class to TH ( affect width, effects, etc )
    $extraClasses = "",   //Any extra CSS classes in the <table> tag
    $extraHTML = ""       //Any extra HTML in the <table> tag
)
{
    //init and sanity checks
    if(zs::isBlank($tableArray)) { self::notify("warn", "There isn't any information to display."); return; } //user friendly
    if(!is_array($THfieldClasses)) { zl::fault("non-array sent to zui::printTable THfieldClasses"); } //programmer hostile
    if($extraClasses != "") { $ec = " " . $extraClasses; } else { $ec = ""; }
    $filterFields = !zs::isBlank($showFields); //speed hack because we do this check a lot
    
    $arrayInfo = zarr::getArrayInfo($tableArray, true); //identify type of array
    
    //add another layer of depth if we don't have an array of arrays
    if($arrayInfo['depth'] == 1 && $arrayInfo['type'] != "singleNum") { $tableArray = [$tableArray]; }
    elseif($arrayInfo['depth'] > 2) { self::notify("error", "The array is > 2 layers deep; can't display"); return; }
    elseif(!$arrayInfo['canLoop']){ self::notify("error", "The array is non-iterable ( contains objects, etc )"); return; }
    ...
```

You don't have to use this format, but this level of detail is great.

Notice that we have:
- Notes on array shapes
- Notes on what happens if a certain field is blank or not
- No more parameters than necessary, we could have had a flag to turn on ordering according to $THfieldClasses but we were able to avoid that.

This is pretty decent documentation and makes a nice starter for more detailed documentation if we need to write it later.


11. If it's written by ChatGPT or some other AI, and you don't 100% understand the code, note it.

The biggest problem with AI is that it produces imperfect solutions which could technically work in one condition, but not in a future condition.
Because we can't expect code coming out of AI was completely thought out by a human, we should consider it somewhat suspect, and mark the code portion with a comment thanking the large language model of choice for the code.

We strongly prefer no AI code is written, but we understand AI is useful for punching above one's weight & sometimes it can save an inordinate amount of time.

At our dev shop, AI assistants like ones that plug into the IDE are forbidden if they transmit large swaths of code to a remote server.
Zerolith is an open-source project, so it doesn't have this restriction.

![grug_thinking.jpg](grug_thinking.jpg)

Would Grug understand this AI code?


12. Use parameterized database reads and writes whenever possible.

Input filtering in Zerolith gives us a large degree of safety from end-user tomfoolery, but using parameterized,
array-based writes and reads whenever possible provides another safety layer.
Use the raw SQL write functions only when you hit a condition where the parameterized functions can't support some edge case SQL feature you need.


### What we consider good programming - from the perspective of the code factory

1. Short code, but not to a fault

A good programmer will distill the solution to the lowest cognitive complexity and lowest lines of code possible.
The reason why a good programmer does this is so that when they have to bend the code to customer demands, it's easier to work on, even a decade later when he/she has totally forgot how the code works.
A good programmer also knows that writing nasty looking code to achieve ultimate code shortness is never worth it, they've been bitten by that when they had to work on some code they wrote 5 years ago and forgot the tricks!
when something needs to be long and verbose, it probably should stay that way for readability/understandability.


2. Comment blocks for each logic section and for notable spots

A good programmer works on so much code, and for so long, that they know they'll eventually forget some, if not all of it.
He/she uses both obvious and somewhat verbose variable naming as a way to add documentation, and also comments code blocks and pieces of code that are tricky, cover edge cases, or work but don't look like they work.

Here is an example of good commenting with comment blocks:

```php
//this would be input through the form if it was empty in the function call
if($customerID == "") { extract(zfilter::array("customerID", "number")); }
if($customerID == "") { zl::fault("customerID not sent in zlhx request."); } //was blank?
if($perspective == "") { extract(zfilter::array("perspective", "stringSafe")); }

zlhx::$perspective = app::$userType; //default is locked to user type

//check if the user has permission to do this ( universal privilege check for the whole shooting match ).
if(app::$permissions == 0) { zl::faultUser("You cannot view this page because you are not logged in"); }
if(app::$userType == "customer")
{
    if(app::$userData['id'] != $customerID)
    {
        //yeah, well, does the student have an associated ID?
        if(!in_array($customerID, associationList($customerID)['accounts']))
        { zl::fault("You aren't logged in as the user's account that you're trying to view."); }
    }
}
elseif(app::$userType == "manager") { } //insert permissions check for ownership
elseif(app::$userType == "admin")       //admin can masquerade for testing/maintaining the system
{ if($perspective != "") { zlhx::$perspective = $perspective; } }

```

Good comments explain what we're trying to accomplish with each block of code in human, not computer, terms.


3. Well-organized code

Proper organization can make or break code understandability and maintain-ability, especially as the complexity and length of the codebase grows.
A good programmer is always thinking about this as he/she modifies the code and removing/adding/modifying/clarifying structure and willing to spend extra time to ensure the structure is as clean and understandable as possible at all times.

![kinopio man.png](kinopio%20man.png)


4. Do your research when working on someone else's code

A good programmer spends as much time as he/she needs to understand the code they are working on before operating on it.
This is so the modification can have an OEM-like fit & finish and look like it was a piece of the original code, not an addon. This is time consuming; but a good programmer knows this attention to detail pays off in the long run.

A good programmer also comments these modifications so the original author understands it was not part of his/her original design.
It is also a nice practice to comment out large sections of code if we are replacing them so that we have a reference to how the original code worked, if the edit is possibly controversial. We can remove these comments after a number of years.


5. Test the hell out of your code when finishing it

Programmers are famous for being bad at testing their code. Programmers suffer of blind spots & tunnel vision which interfere with being able to consistently output a good product. And there are situations the programmer never thought of which can pop up after release.

![code review.jpg](code%20review.jpg)

Luckily it's not hard to beat the national average here.

A good programmer, before submitting their code for testing/review or uploading it to prod, will devise a test routine for the code and manually or automatically run this test routine before approving their own code. This is time consuming, but worth the time.
Conversely, a bad programmer producing lower quality work frustrates users of the product and management by wasting their time with broken software. This lowers everyone's morale.

The real world acceptable margin of error for code is less than 2%. It can take an inordinate amount of time to get another percent of accuracy out of finished code, but it is worth doing so because you won't have to load the code back into your brain shortly after starting on another task.
A complete job ends up taking less time than a 98% complete job you need to revisit months later.
We don't expect a good programmer to be perfect here. Programming is hard, and we can't reasonably test every permutation of the code's behavior. Even a good programmer develops blind spots and gets burned out on testing.
But a good programmer does their best to test their code every time, and rarely ships broken code.


6. Use the zerolith debugger whenever possible

The zerolith debugger is good at the following:
- Using Quip, putting an echo statement into a global buffer that is displayed after page render, this ensures it's viewable despite where you are in HTML.
- Quip allows you to leave debugging statements in scripts that don't end up displaying in prod accidentally.
- Providing a transcript of database and API calls
- Providing large grain detail on what aspects of the script used the most CPU/time.
- Providing detailed debug information in the event of a script crash.
- Logging all the generated data for later analysis of bugs happening in production, greatly improving efficiency.
- Overall, if utilized, a programmer's debugging time will be greatly reduced and they will be able to spend more time building.

A good programmer uses the ZL debugger to the fullest degree possible.


### Bonus: what a client considers a good programmer

Here's some career advice from a successful programmer about what a majority of clients are going to want.

1. Be efficient with your time, optimize for business impact whenever possible. Programmers can easily lose sight of what a business needs when they spend all their time in the code dungeon. Stay in touch with the client/manager so you can keep an eye on what's happening outside of the dungeon - you'll produce better results if you do.

2. Communicate frequently and well; clients get anxious or mad when things take too long. If there are delays, explain why.

4. Attempt to delay or defer refactoring whenever reasonable. Some areas of the code can accept patchwork; find out what part of the codebase needs to stay clean and which can stand to get dirtier. Refactoring provides 0 value from the client's perspective, so they never want you to do it.

4. Consider your client 100% ignorant of the code, no matter how much you tell them about it. They cannot think like you. They never know if a new task is a 10 minute slam dunk or a months long slog. Give your best estimate of the hardness of each task to the client, but never set hard time expectations because what's going to be actually involved is unpredictable no matter your skill or familiarity level.

5. Write code that is flexible to change. This way, future refactoring will be minimized. If you over-define and over-hard code things, that makes your code brittle.

6. Deliver shareholder value! focus on bang per buck in your programming whenever possible; get your client to associate handing you a paycheck with stuff getting done, and you'll never end up on the short leash.

7. Don't do the above at your own expense!