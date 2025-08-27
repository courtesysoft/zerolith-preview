<?php
require "../../zl_init.php";
require "index.nav.php"; //navigation menu
zpage::start("zcache test");

exit("( not complete )");

function detectBackends()
{

}

function runBenchmark($advanced = false)
{
    //detect available backends
    $testBackends = [];

    //run through all detected backends
    foreach($testBackends as $testBackend)
    {
        zcache::setCacheType($testBackend);

    }

    //sequential read test ( 100 byte string )

    //sequential write test ( 100 byte string )

    //sequential read test ( 100000 byte array )

    //sequential write test ( 100000 byte array )

    //random parallel read test ( 100 byte string )

    //random parallel write test ( 100 byte string )

    //random parallel read test ( 100000 byte array )

    //random parallel write test ( 100000 byte array )

    //100 concurrency random write-read ( 100 byte string )

    //100 concurrency random write-read ( 100000 byte array )

}

    $testBackends = ['disk','acpu','shmop']; //which backends to test!



$data = array_fill(0, 1000000, "hi"); // your application data here
zcache::set('my_key', $data);
//apc_store('my_key', $data);
//And see how they perform:

// note: make sure you run this on a separate request from cache_set to ensure PHP's opcache will actually cache the file
$t = microtime(true);
$data = zcache::get(‘my_key’);
echo microtime(true) - $t;
// 0.00013017654418945

//$t = microtime(true);
//$data = apc_fetch(‘my_key’);
//echo microtime(true) - $t;
// 0.061056137084961
