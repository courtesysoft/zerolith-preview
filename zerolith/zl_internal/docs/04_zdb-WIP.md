**Notes on the database library**

The database library allows you to do 3 types of calls:

1. Single liner raw SQL calls with standardized string or associative array output.
2. Parameterized writes by just sending associative arrays ( very convenient ).
3. Parameterized reads using raw SQL + associative arrays ( not complete ).

The default behavior in the event of a SQL error is to terminate the script with the specified error message.
If you prefer to be returned 'false' instead, set zdb::$returnOnFault = true in your bootloader or early in the script.

There is no ORM in Zerolith. If you use an external database library, you will unfortunately lose database debugging functionality. Maybe one day we will add one.

WARNING: because we allow raw SQL calls currently, it is the programmer's job to ensure input safety in those situations, otherwise you will create a vulnerability to SQL injection. We recommend filtering is done at the top of a script as a hard rule if you use these functions. See the zfilter class examples for how to do this.

**Gotchas**

zdb::writeRow is very convenient but currently has some snags you need to know about:
- if you need to update a row to NULL, the value must exactly === null in PHP instead of "null".
- mysql keywords like now() and curtime() are not supported. Use zdb::now() to output a timestamp instead.

**How the In-memory Database Cache works**

The in-memory database cache is used for situations where a script causes multiple calls to the exact same SQL query. In this case, Zerolith stores the result of the first query into memory and returns it on successive calls to that query. In these situations, performance can greatly increase.

The cache is designed so that manual cache management of the cache is needed only in rare situations.

Usage: swap zdb::getArrayMem() for zdb::getArray().. that's it!

If for any reason you need to invalidate or update the memory cache, you can also use:
zdb::memCacheInvalidate() and zdb::memCacheUpdate()

Use the in-memory cache _ONLY_ when:
1) A query is very likely to repeat.
2) You have enough RAM to afford all the data being stored!

To maximize the gained performance, memory cached database reads are not reported as database calls in the debugger, nor timed. The total number of memory cache hits can be seen in the performance tab of the debugger.

Notes:
1) The consequence of using the in-memory cache incorrectly will be a small CPU penalty and a variable RAM penalty.
2) If you make an uncached SQL call identical to a SQL query that has previously been cached, the cached value will be updated to preserve the program integrity in the rare case that you update that portion of the database, read that portion uncached, then attempt to read that portion from the cache.
3) Watch out for rare situations where a code path writes to the database after you've read a cached value. Your cached value could be wrong! zdb::invalidateMemCache($SQL) can be used to manually invalidate after a write.



# the remainder of this page is invalid and a new, much better caching system is currently being built



**How the Disk Database Cache works**

The database disk cache is useful for writing and reading precomputed complex SQL outputs. It is built to be as performant as possible, hopefully achieving Memcached-like performance on fast disks & short amounts of data due to the much shorter computational path of this cache mechanism.

And we like using disk because it's a lot cheaper than RAM.

To achieve the fastest possible speed, the disk database cache writes the data in a precompiled PHP code form in order to make use of the opcache. With an adequate sized opcache, after the first cache read, the variable representation is effectively precompiled PHP code ( extremely fast to load ). In addition, given ample RAM for disk cache, these cached files can also find themselves in RAM for an additional access speed boost, and outperform >95% of caching systems in that condition.

Because the price you pay for shockingly fast performance is effectively keeping a copy of SQL on disk, you should use this caching mechanism only in situations where you have:

1) Data that is written infrequently but accessed very frequently ( minimum 5:1 ratio of reads to writes )
2) Data that is CPU expensive to generate and is read more often than written, or the processing load must be deferred.

Unlike the memory database cache, management of this disk cache is mostly manual, because with persistent data storage comes plenty of opportunities for accidental cache de-synchronization which can have huge negative impacts to software integrity.


**SECURITY REQUIREMENTS**

With great power requires great responsibility.
The path where the database disk cache stores it's data must ABSOLUTELY NOT BE ABLE TO BE READ BY the world.

Insanely huge dangers are possible:
1. Hackers finding a way to include or read in plaintext the contents of your database (rare but non-zero possibility).
2. Hackers being able to reverse-engineer what SQL calls you make by brute-force attempting to access file names because the file names correlate with SQL calls. ( extremely possible and very dangerous )
3. In order to provide excellent performance, cache files aren't encrypted. The database cache can increase your liability in the event of a full system compromise, so we STRONGLY suggest you DON'T use it when accessing sensitive data such as password decryption details, credit cards, email addresses, etc. The same precaution applies to any kind of caching system.

To ensure you don't run into problems #1 and #2:
1. Apache: Deny all access to (your web path)/zerolith/zl_internal/cache/database
2. Nginx: Deny all access to (your web path)/zerolith/zl_internal/cache/database
3. Chmod 600 -R the (your web path)/zerolith/zl_internal/cache/database folder to prevent mass reads by other users/processes
4. You may even want to investigate FACL to force all files to be written in these folders with a particular username or password so that rogue processes can't access them unless running as root.

Zerolith will render the disk cache inoperable, as well as wipe it's contents if it detects a security misconfiguration in order to prevent you from shooting yourself in the foot.


**How do i use the disk cache?**

Basic - automatic timed management:

zdb::getRowDiskCache("SELECT * FROM whatever WHERE ID = '123'", ... , '30m');

This will attempt to read the cached version, and if it's older than 30 minutes, update the cache with a fresh pull and return the data.
This is a mostly automatic mode for data where it is acceptable to be out of date. This can be self-managing. You can also pair this with zdb::invalidateDiskCache() to very quickly ensure newly written data is rewritten to the cache later.

zdb::invalidateDiskCache("SELECT * FROM whatever WHERE ID = '123'");

This will completely remove the cache entry for a given SQL query.
This is useful for the timed disk cache read because it will force a refresh on the next read.

These two commands are enough to loosely or precisely ensure a cache is up to date in a very simple way.
Using the cache like this will incur an occasional stutter in application responsiveness and CPU load however; it's not ideal for all use cases.

Advanced - Manual Management:

zdb::getRowDiskCache("SELECT * FROM whatever WHERE ID = '123'", ...);

This will attempt to read the cached version, and if not present, load from a fresh pull every time.
This is useful for when data cannot be out of date, in the name of integrity.

zdb::updateDiskCache("SELECT * FROM whatever WHERE ID = '123'");

This will load a SQL query and then write the precomputed result to the disk cache.
This is useful for updating a cache immediately after a MySQL UPDATE or INSERT, where you don't have complete data in PHP memory already.

zdb::updateDiskCache("SELECT * FROM whatever WHERE ID = '123'", $array or $string);

This will write a precached database output for a given SQL query without executing the SQL query to generate it.
This is useful if you want to save some write speed by writing data you already have in memory to the cache.