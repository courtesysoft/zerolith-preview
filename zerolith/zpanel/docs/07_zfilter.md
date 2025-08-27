# zfilter

Input filtering is the first line of defense for PHP applications and is a necessary part of a framework.
Zfilter provides a combined data retrieval from $_GET/$_POST with sanitization so that input can be popped into local scope with a single line, making adding security very convenient.
Zfilter provides a number of filters which are designed to work with common types of input data, so that you can easily adjust filter strictness per scenario.
It will also eventually include a validation library, but as of writing, the current code is just a concept.


### Usage

The primary use of zfilter is to either nass accept input variables, or filter a single unit.

Mass input filtering can be done by specifying a pipe delimited list, followed by a filter function that correlates to a function in zfilter::
`extract("zfilter::array("var1|var2|var3", "page");`
This will take the input variables, filter them, and pop them into local scope.

You can also filter a single variable by just using the zfilter function directly:
`$maliciousInput = zfilter::page($maliciousInput)`

That's basically it!
