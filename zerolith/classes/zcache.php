<?php
/////////////////Disk Cache implementation details
//Stolen from: https://medium.com/@dylanwenzlau/500x-faster-caching-than-redis-memcache-apc-in-php-hhvm-dcd26e8447ad
//Production Implementation:
//We use a bit more code in our production application than what is illustrated above. In addition to adding expiration support and a cache_clear function, we also had to implement our own multi-server distributed clearing logic, which data stores like Redis and Memcache handle automatically. Cache keys must also be validated or encoded based on the filesystem to ensure valid filename characters.
//Another production consideration is the PHP opcache configuration (or HHVM .ini configuration). Your opcache.memory_consumption setting needs to be larger than the size of all your code files plus all the data you plan to store in the cache. Your opcache.max_accelerated_files setting needs to be larger than your total number of code files plus the total number of keys you plan to cache. If those settings aren’t high enough, the cache will still work, but its performance may suffer.
//igbinary is faster
//force opcache_invalidate
//Instead of renaming, I just use a lock:`file_put_contents($path, $contents, LOCK_EX);`
//Setting an expiration for the cache can be achieved be storing an additional variable in each file, e.g. $expiration = 1528819551; Then in your cache_get function you can check whether the current time has surpassed the expiration time.

//Despite the fact that reads from and writes to the shared memory are not atomic, reading and writing just ONE byte is always atomic. This can be very useful if your application frequently reads and rarely writes "small" chunks of data (~10-15 bytes). You can avoid using any kind of locks by signing your data by 8-bit checksum (like CRC-8). This is an effective and reliable way to ensure that your data is not corrupted. The redundancy is naturally 8 bits.

//special keys ZL uses:
//zcache_cc_* <-- for concurrency locks
//zcache_gen_* <-- for generation locks

class zcache
{
    //Current status of this library is proof of concept, don't use as-is!
    
    private static $debugVoice = ['libraryName' => "zcache", 'micon' => "storage", 'textClass' => "zl_teal1", 'bgClass' => 'zl_bgTeal6']; //mandatory for debug logging
    private static $memCacheType = "none";  //Can be: none|shmop|apcu|memcached|lmdb <-- lmdb is via dba
    private static $memSerializer = "json"; //Can be: var_export|json|simdjson|igbinary
    private static $diskCacheType = "none"; //Can be: none|sqlite|disk
    private static $diskCachePath =  "";    //TBD by init function
    private static $diskSerializer = "var_export"; //Can be: var_export|json|simdjson|igbinary
    private static $compressor = "none";    //Can be: none|gzip|zstd
    private static $zstdCompLv = 6;         //Compression level for ZSTD
    private static $gzipCompLv = 4;         //Compression level for gzip

    private static $miss = false;           //Internal tracker for if a miss happened

    //to-implement:
    private static $generationLockType = "none";//can be: none|disk|mem; mem is fastest; provides stampede protection
    private static $minStorage = false;         //use basic LRU to minimize cache storage size at a small expense to speed
    private static $addConcurrency = true;      //add concurrency to methods that don't support it; uses mem cache; only works if mem cache enabled

    public static $defaultTTL = "60";  //Default time to live is 1 minute ( in sec )

    public static function __init()
    {
        //grab settings from zl
        self::$memSerializer = zl::$set['zcacheMemSerializer']; self::$diskSerializer = zl::$set['zcacheDiskSerializer'];
        self::$memCacheType = zl::$set['zcacheMemCacheType'];   self::$diskCacheType = zl::$set['zcacheDiskCacheType'];
        self::$compressor = zl::$set['zcacheCompressor'];       self::$diskCachePath = zl::$set['zcacheDiskCachePath'];
        self::$gzipCompLv = zl::$set['zcacheGzipCompLv'];       self::$zstdCompLv = zl::$set['zcacheCompLv'];
        self::$minStorage = zl::$set['zcacheMinStorage'];       self::$generationLockType = zl::$set['zcacheGenerationLockType'];
        self::$addConcurrency = zl::$set['zcacheAddConcurrency'];

        //do you have the safety on? if so, it's checking time.
        if(zl::$set['envChecks'])
        {
            //compressor checks
            if(!in_array(self::$compressor, ['none', 'gzip', 'zstd']))
            { zl::fault("Zcache Compressor: [" . self::$compressor . "] is invalid."); }
            else
            {
                if(self::$compressor == "gzip" && !function_exists('gzcompress') || self::$compressor == "zstd" && !function_exists('zstd_compress'))
                { zl::fault("Zcache Compressor: [" . self::$compressor . "]'s extension is not installed'."); }
            }

            //check disk and memory serializer settings
            foreach(['Disk','Mem'] as $e)
            {
                if($e = "Disk") { $serializer = self::$diskSerializer; } else { $serializer = self::$memSerializer; }

                if(!in_array($serializer, ['json', 'simdjson','var_export', 'igbinary']))
                { zl::fault("Zcache $e Serializer: [" . $serializer . "] is invalid."); }
                else
                {
                    if
                    (
                        $serializer == "json" && !function_exists('json_encode') ||
                        $serializer == "simdjson" && !function_exists('simdjson_encode') ||
                        $serializer == "igbinary" && !function_exists('igbinary_serialize')
                    )
                    { zl::fault("Zcache $e Serializer: [" . $serializer . "]'s extension is not installed'."); }
                }
            }

            //disk cache type check
            if(!in_array(self::$diskCacheType, ['disk', 'sqlite', 'none']))
            { zl::fault("ZCache: [" . self::$diskCacheType . "] isn't a valid disk cache type"); }

            //disk path check - works for SQLite and disk
            if(!is_readable(self::$diskCachePath) || !is_writable(self::$diskCachePath)) { zl::fault("Zcache: Disk cache path isn't read/writable."); }

            //screech about this situation
            if(self::$addConcurrency && self::$memCacheType == "none") { zl::fault("ZCache cannot use addConcurrency without a memory cache."); }
        }
    }


    //overall frontend for use with single cache type


    //$repopulate func: the function call to make to repopulate the cache value
    //$repopulateFuncArgs: single argument or array of arguments to pass to the function, in the function's recieving order.
    //example: zcache::get("customerRow12", "zdb::getRow", "SELECT * FROM customer WHERE ID = 12")
    public static function memGet($key, $repopulateFunc = "", $repopulateFuncArgs = "", $repopulateTTL = "", $generationLock = false)
    {
        if(self::$diskCacheType == "none") { return $repopulateFunc($repopulateFuncArgs); }


    }

    //$ttl = time to live before expired, can be expressed in ms, sec, hr, day; default is ms
    public static function memSet($key, $val, $ttl = "")
    {
        if(self::$memCacheType == "none") { return true; }
    }


    private static function diskGet($key, $repopulateFunc = "", $repopulateFuncArgs = "", $repopulateTTL = "", $generationLock = false)
    {
        if(self::$diskCacheType == "none") { return $repopulateFunc($repopulateFuncArgs); }

        $val = self::zcGetDisk($key, $repopulateFunc, $repopulateFuncArgs);
        if(self::$miss) ///regenerate on miss from previous statement
        {
            if($generationLock && self::$generationLockType != "none")
            {
                //start generation lock
                $val = $repopulateFunc($repopulateFuncArgs);
                //stop generation lock
            }
            else { $val = $repopulateFunc($repopulateFuncArgs); } //just do the thing

            self::zcSetDisk($key, $val, $repopulateTTL);
            self::$miss = false; //reset the marker for next time
        }
        return $val;
    }

    private static function diskSet($key, $val, $ttl = "")
    {
        if(self::$memCacheType == "none") { return true; }
    }


    //implementation of specific cache interfaces


    private static function zcGetDisk($key, $repopulateFunc = "", $repopulateFuncArgs = "")
    {
        if(self::$diskCacheType == "none") { return $repopulateFunc($repopulateFuncArgs); }
        $miss = false;

        if(self::$diskCacheType == "disk")
        {
            $path = self::$diskCachePath . "/" . $key;
            if(self::$diskSerializer == "var_export") //uses different method, load file directly into PHP for max speed
            {
                if(file_exists($path)){ include $path; } else { $miss = true; }
            }
            else //run the usual routine
            {
                if(file_exists($path)){ $val = self::unpack(file_get_contents($path), "disk"); }
                else { $miss = true; }
            }
        }
        elseif(self::$diskCacheType == "sqlite")
        {
            //to implement; just a forwarder for sqlite
        }

        if($miss) { self::$miss = true; return ""; } //assign something to the value and mark a miss
        return $val;
    }

    private static function zcSetDisk($key, $val, $ttl = "")
    {
        if(self::$diskCacheType == "none") { return true; }

        if(self::$diskCacheType == "disk")
        {
            $path = self::$diskCachePath . "/$key";
            if(self::$diskSerializer == "var_export") //don't use compression
            {
                $val = var_export($val, true);
                $val = str_replace('stdClass::__set_state', '(object)', $val); // HHVM fails at __set_state, so just use object cast for now
                $tmp = $path . "." . uniqid('', true) . '.tmp'; // Write to temp file first to ensure atomicity
                file_put_contents($tmp, '<?php $val = ' . $val . ';', LOCK_EX);
                rename($tmp, $path);
            }
            else { file_put_contents($path, self::pack($val,"disk")); }
        }
        elseif(self::$diskCacheType == "sqlite")
        {
            //to implement
        }
        return $val;
    }

    private static function zcGetACPU($key)
    {
        $success = false; //next function needs to write to this
        $data = apcu_fetch($key, $success);
        if(!$success) { self::$miss = true; return ""; } else { return $data; }
    }

    private static function zcSetACPU($key, $val, $ttl = "") { return apcu_store($key, $val, self::ttl2time($ttl)); }

    private static function zcGetSQLITE($key)
    {

    }

    private static function zcSetSQLITE($key, $val, $ttl = "")
    {

    }

    private static function zcGetLMDB($key)
    {

    }

    private static function zcSetLMDB($key, $val, $ttl = "")
    {

    }

    private static function zcGetMemcached($key)
    {

    }

    private static function zcSetMemcached($key, $val, $ttl = "")
    {

    }

    //ideas for allowing multiple concurrent readers but 1 writer
    //read: check for lockfile
    //write: write lockfile

    //shmop is a pain in the ass

    //----------------- integer key name problem solution

    //ROUTE 1
    //key must be 1 lowercase english alphabetic characters plus a numeric ID; shmop expects an int key, and text strings don't easily convert to ints
    //due to the 2,147,483,647 limit of a PHP unsigned integer, and losing 3 of those characters, the max ID limit is 7,483,647; bad choice if you're hyperscaling

    //Route 2
    //other idea ( slower but higher ID values possible )
    //first number = first representative of letter ( 1-2 )
    //last number = last representation of letter ( 1-9 )

    //therefore we get this as the max numerical range ( 2x ):
    //14,748,364

    //ROUTE 3
    //create a database table or name to numeric value mapping, with a maximum of 99, clipping off the last two numbers
    //2,147,483,647 = 21,474,836 possible values x 99


    //----------------- size and partial concurrency solution

    //ROUTE 1 ( use total size check after open )
    //if addConcurrency on, last 8 characters are crc32b checksum


    //ROUTE 2 ( see if it's faster to read twice and use 16 extra bytes )

    //first 8 bytes of data represent the shared memory's size, read first 8 bytes first, then we can pass the size. This limits max size to 99,999,999 bytes ( ok )
    //middle segment is the actual data we wanted to store
    //last 8 characters are 8 digit checksum,

    //if addConcurrency on, extra 16 bytes, otherwise extra 8 bytes

    //if the string is <16 bytes OR the size doesn't match OR the checksum doesn't match, we assume a write is happening, so
    //retry for 10ms when reading because writing is not done


    //echo hash('crc32b', $data); //create 8 byte checksum

    private static function zcGetSHMOP($key)
    {
        //shmop_open then run shmop_size to get size


    }

    private static function zcSetSHMOP($key, $val, $ttl = "")
    {
        //write an 8 byte checksum at the end

    }


    //serialize and compress the data before writing
    private static function pack($val, $forMemOrDisk)
    {
        //which serializer to use?
        if($forMemOrDisk == "disk") { $serializer = self::$diskSerializer; } else { $serializer = self::$memSerializer; }

        //serialize
        if($serializer == "simdjson" || $serializer == "json"){ $val = json_encode($val); } //simdjson doesn't have an encode feature, so..
        elseif($serializer == "igbinary"){ $val = igbinary_serialize($val); }
        elseif($serializer == "var_export") { $val = var_export($val, true); }

        //compress
        if($serializer != "igbinary" && $serializer != "var_export") //these two types cannot use compression
        {
            if($serializer == "gzip") { $val = gzcompress($val, self::$gzipCompLv); }
            elseif($serializer == "zstd") { $val = zstd_compress($val, self::$zstdCompLv); }
        }

        return $val;
    }

    //serialize and compress the data before writing
    private static function unpack($val, $forMemOrDisk)
    {
        //which serializer to use?
        if($forMemOrDisk == "disk") { $serializer = self::$diskSerializer; }
        else { $serializer = self::$memSerializer; }

        //uncompress
        if($serializer != "igbinary" && $serializer != "var_export") //these two types cannot use compression
        {
            if(self::$compressor == "gzip") {$val = gzdecompress($val); }
            elseif(self::$compressor == "zstd") { $val = zstd_uncompress($val); }
        }

        //unserialize
        if($serializer == "simdjson"){ $val = simdjson_decode($val); }
        elseif($serializer == "igbinary"){ $val = igbinary_unserialize($val); }
        elseif($serializer == "var_export") //this one's special
        {

        }

        return $val;
    }

    //Produces metadata (expiration time, etc) for cache writing
    private static function metaData($ttl)
    {
        $now = intval(microtime(true) * 1000);  //get now() and multiply to remove the . milliseconds portion
        return ['start' => $now, 'stop' => $now + self::ttl2time($ttl)]; //return the array the cache needs
    }

    //turn a text-based time ( 1, 1sec, 2min, 3hr, 4day) into milliseconds - ms, or seconds - sec( default )
    private static function ttl2time($ttl, $secORms = "sec")
    {
        if($ttl == "") //use the class' default TTL ( written in seconds )
        {
            if($secORms == "sec") { $ttl = self::$defaultTTL; } //yeah, we like seconds
            elseif($secORms == "ms") { $ttl = self::$defaultTTL * 1000; } //convert to milliseconds if requested
            else { zl::fault('ttl2time sent invalid value to secORms'); }
        }
        else
        {
            if(is_int($ttl)) { return $ttl; } //sent a base value ( milliseconds ); no processing to do
            elseif(is_string($ttl)) //was sent a text string with a text signifier
            {
                if($secORms == "ms")
                {
                    //most common first
                    if(strpos($ttl, "sec", -3) !== false)     { $ttl = str_replace("sec", "", $ttl); return $ttl * 1000; }
                    elseif(strpos($ttl, "min", -3) !== false) { $ttl = str_replace("min", "", $ttl); return $ttl * 60000; }
                    elseif(strpos($ttl, "hr", -2) !== false)  { $ttl = str_replace("hr", "", $ttl);  return $ttl * 3600000; }
                    elseif(strpos($ttl, "day", -3) !== false) { $ttl = str_replace("day", "", $ttl); return $ttl * 86400000; }
                    elseif(strpos($ttl, "ms", -2) !== false)  { $ttl = str_replace("ms", "", $ttl);  return $ttl; } //no time conversion needed
                    else { zl::fault("invalid time unit sent to zcache::ttl2ms"); } //bark at programmer; this messes up computations down the line
                }
                else
                {
                    //most common first
                    if(strpos($ttl, "sec", -3) !== false)     { $ttl = str_replace("sec", "", $ttl); return $ttl; } //no time conversion needed
                    elseif(strpos($ttl, "min", -3) !== false) { $ttl = str_replace("min", "", $ttl); return $ttl * 60; }
                    elseif(strpos($ttl, "hr", -2) !== false)  { $ttl = str_replace("hr", "", $ttl);  return $ttl * 3600; }
                    elseif(strpos($ttl, "day", -3) !== false) { $ttl = str_replace("day", "", $ttl); return $ttl * 86400; }
                    elseif(strpos($ttl, "ms", -2) !== false)  { $ttl = str_replace("ms", "", $ttl);  return $ttl / 1000; }
                    else { zl::fault("invalid time unit sent to zcache::ttl2ms"); } //bark at programmer; this messes up computations down the line
                }
            }
        }
    }


    //log class success/fail states to the debug console
    //DS - needs to be reworked
    private static function log($out = "", $success = true, $faultData = "", string $timerToStop = "")
    {
        if(!zl::$set['debugger']) { return; } //if debugger is absolutely off, forget accumulating this data
        $debugObject = self::$debugVoice; //add the data output from the library
        if($success) { $debugObject['callData'] = debug_backtrace(0,2)[1]; }
        else { $debugObject['callData'] = debug_backtrace(0,2)[1]; }
        $debugObject['out'] = $out; //any output of the function
        $debugObject['faultData'] = $faultData;
        $debugObject['success'] = $success;

        //timer calculation - stop the last one, or manually specified one?
        if($timerToStop == "") { $debugObject['time'] = ztime::stopLastTimer(); }
        else { $debugObject['time'] = ztime::stopTimer($timerToStop); }

        zl::deBuffer($debugObject); //out to the debug buffer.
    }
}
zcache::__init();