### ZDB Overview

Zerolith's MySQL database library is an anti-ORM. It's designed to work with as close of MySQL syntax as possible, for ease of debugging & speed.

The database library allows you to do 3 types of calls:
1. Single liner raw SQL calls with standardized string or associative array output.
2. Parameterized writes by just sending associative arrays ( very convenient ).
3. Parameterized reads using raw SQL with ? in the parameters + associative arrays ( shorter than using an ORM, and still debuggable ).

WARNING: because we allow raw SQL calls, it is the programmer's job to ensure input safety in those situations, otherwise you will create a vulnerability to SQL injection. We recommend that filtering is done at the top of a script as a hard rule if you use these functions. See the zfilter class examples for how to do this.


### Behaviors

The default behavior in the event of a SQL error is to terminate the script with the specified error message.
If you prefer to be returned `false` instead, set `zdb::$returnOnFault = true` in your bootloader or early in the script.

The database library will output standard formats in responses to these commands:
getField()  = a string, like `'bob'`. If the query is empty, it'll return `''`
getRow()    = an associative array, like `['ID' -> '3', 'username' -> 'bob']`. If the query is empty, it'll return `[]`
getArray()  = a numbered array of associative arrays, like:
```
[0]['ID' -> '1', 'username' -> 'jill']
[1]['ID' -> '2', 'username' -> 'jane']
[2]['ID' -> '3', 'username' -> 'bob']
```
If the query is empty, it'll return `[]`


### Basic usage

zl's database library doesn't involve instantiation or initiation; if your settings in zl_config.php are correct, just get data like such:
`$customerRow = zdb::getRow('SELECT * FROM customer WHERE yadayada = 'ya'")`

classes/zdb.php's functions is very well documented, see the source file for instructions on how to use individual commands.
For some more examples of usage, check out zerolith/zpanel/demo.

All in/out of the zdb library is automatically logged in the zerolith debugger, including timing for each call, for your performance assessing and debugging convenience :)


### Gotchas

zdb::writeRow is very convenient but currently has some snags you need to know about:
- if you need to update a row to NULL, the value must exactly === null in PHP instead of "null".
- mysql keywords like now() and curtime() are not supported. Use zdb::now() to output a timestamp instead.


### How the In-memory Database Cache works

The in-memory database cache is used for non-complex situations where a script causes multiple calls to the exact same SQL query; for example when outputting a large + complex table.
In this case, Zerolith stores the result of the first query into memory and returns it on successive calls to that exact query. In these situations, performance can greatly increase.

The cache is designed so that manual cache management of the cache is needed only in rare situations.

Usage: swap `zdb::getArrayMem()` for `zdb::getArray()`.

If for any reason you need to invalidate or update the memory cache, you can also use:
`zdb::memCacheInvalidate()` and `zdb::memCacheUpdate()`

Use the in-memory cache _ONLY_ when:
1) A query is very likely to repeat.
2) You have enough RAM to afford all the data being stored!

To maximize the gained performance, memory cached database reads are not reported as database calls in the debugger, nor timed. The total number of memory cache hits can be seen in the performance tab of the debugger.

Notes:
1. The consequence of using the in-memory cache incorrectly will be a small CPU penalty and a variable RAM penalty.
2. Watch out for rare situations where a code path writes to the database after you've read a cached value. Your cached value could be wrong! zdb::invalidateMemCache($SQL) can be used to manually invalidate after a write.




## the remainder of this doc is invalid and a new, much better caching system is currently being built

**How the Disk Database Cache works**

The database disk cache is useful for writing and reading precomputed complex SQL outputs. It is built to be as performant as possible, hopefully achieving Memcached-like performance on fast disks & short amounts of data due to the much shorter computational path of this cache mechanism.

And we like using disk because it's a lot cheaper than RAM.

To achieve the fastest possible speed, the disk database cache writes the data in a precompiled PHP code form in order to make use of the opcache. With an adequate sized opcache, after the first cache read, the variable representation is effectively precompiled PHP code ( extremely fast to load ). In addition, given ample RAM for disk cache, these cached files can also find themselves in RAM for an additional access speed boost, and outperform >95% of caching systems in that condition.

Because the price you pay for shockingly fast performance is effectively keeping a copy of SQL on disk, you should use this caching mechanism only in situations where you have:

1) Data that is written infrequently but accessed very frequently ( minimum 5:1 ratio of reads to writes )
2) Data that is CPU expensive to generate and is read more often than written, or the processing load must be deferred.

Unlike the memory database cache, management of this disk cache is mostly manual, because with persistent data storage comes plenty of opportunities for accidental cache de-synchronization which can have huge negative impacts to software integrity.