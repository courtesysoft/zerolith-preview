<?php
//data structure shootout - DS's autistic computer science experiment 09/01/2022
//standardized test: create 3 x 3 structure, read all element's values, discard structure
//must include 100% of instantiation and destruction time.
gc_disable();
$iterations = 1000000;
$testString = "Potato, Potato, Ching Chong Tomato";
$testKey1 = "KeyName__1";
$testKey2 = "KeyName__2";
$testKey3 = "KeyName__3";

require "../../zl_init.php";
zl::$page['wrap'] = true;
zl::setDebugLevel(2);
zpage::start("PHP Structure R/W performance: " . $iterations . " iterations.");
echo "PHP version: " . PHP_VERSION . "<br>";

$name = "array associative instantiated object getter setter";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	$a = new instantiatedGetterSetterArray;
	
	$a->set1([$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString]);
	$a->set2([$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString]);
	$a->set3([$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString]);
	
	$var = $a->get1();
	$var = $a->get2();
	$var = $a->get3();
}
report($name, $startMem);

//instantiated objects with array data access

$name = "array associative instantiated object";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	$a = new instantiatedArray;
	
	$a->var[] = [$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString];
	$a->var[] = [$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString];
	$a->var[] = [$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString];
	
	$var = $a->var[0];
	$var = $a->var[1];
	$var = $a->var[2];
}
report($name, $startMem);

//static object via array

$name = "array associative static object";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	staticArray::__init();
	
	staticArray::$var[] = [$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString];
	staticArray::$var[] = [$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString];
	staticArray::$var[] = [$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString];
	
	$var = staticArray::$var[0];
	$var = staticArray::$var[1];
	$var = staticArray::$var[2];
}
report($name, $startMem);

//arrays ( associative )

$name = "array associative";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	$arrayAssoc = [];
	
	$arrayAssoc[] = [$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString];
	$arrayAssoc[] = [$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString];
	$arrayAssoc[] = [$testKey1 => $testString, $testKey2 => $testString, $testKey1 => $testString];
	
	$var = $arrayAssoc[0];
	$var = $arrayAssoc[1];
	$var = $arrayAssoc[2];
}
report($name, $startMem);

//create arrays ( indexed )

$name = "array numeric";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	$arrayAssoc = [];
	
	$arrayAssoc[] = [$testString, $testString, $testString];
	$arrayAssoc[] = [$testString, $testString, $testString];
	$arrayAssoc[] = [$testString, $testString, $testString];
	
	$var = $arrayAssoc[0];
	$var = $arrayAssoc[1];
	$var = $arrayAssoc[2];
}
report($name, $startMem);

//create arrays ( indexed )

$name = "array numeric flat";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	$arrayF = [];
	
	$arrayF[0] = $testString;
	$arrayF[1] = $testString;
	$arrayF[2] = $testString;
	$arrayF[3] = $testString;
	$arrayF[4] = $testString;
	$arrayF[5] = $testString;
	$arrayF[6] = $testString;
	$arrayF[7] = $testString;
	$arrayF[8] = $testString;
	
	$var = $arrayF[0];
	$var = $arrayF[1];
	$var = $arrayF[2];
	$var = $arrayF[3];
	$var = $arrayF[4];
	$var = $arrayF[5];
	$var = $arrayF[6];
	$var = $arrayF[7];
	$var = $arrayF[8];
}
report($name, $startMem);

//splfixedarray ( indexed )

$name = "splfixedarray numeric flat";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	$arrayF = new SplFixedArray(9);
	
	$arrayF[0] = $testString;
	$arrayF[1] = $testString;
	$arrayF[2] = $testString;
	$arrayF[3] = $testString;
	$arrayF[4] = $testString;
	$arrayF[5] = $testString;
	$arrayF[6] = $testString;
	$arrayF[7] = $testString;
	$arrayF[8] = $testString;
	
	$var = $arrayF[0];
	$var = $arrayF[1];
	$var = $arrayF[2];
	$var = $arrayF[3];
	$var = $arrayF[4];
	$var = $arrayF[5];
	$var = $arrayF[6];
	$var = $arrayF[7];
	$var = $arrayF[8];
}
report($name, $startMem);

//instantiated objects with getter and setter

$name = "variable instantiated object getter setter";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	$a = new instantiatedGetterSetter;
	$a->set1($testKey1);    $a->set2($testKey2);    $a->set3($testKey3);
	$a->set4($testKey1);    $a->set5($testKey2);    $a->set6($testKey3);
	$a->set7($testKey1);    $a->set8($testKey2);    $a->set9($testKey3);
	
	$var = $a->set1($testKey1);    $var = $a->set2($testKey2);    $var = $a->set3($testKey3);
	$var = $a->set4($testKey1);    $var = $a->set5($testKey2);    $var = $a->set6($testKey3);
	$var = $a->set7($testKey1);    $var = $a->set8($testKey2);    $var = $a->set9($testKey3);
}
report($name, $startMem);

//instantiated objects with array properties

$name = "variable static object";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	staticVariable::__init();
	staticVariable::$v1 = $testKey1;    staticVariable::$v2 = $testKey2;    staticVariable::$v3 = $testKey3;
	staticVariable::$v4 = $testKey1;    staticVariable::$v5 = $testKey2;    staticVariable::$v6 = $testKey3;
	staticVariable::$v7 = $testKey1;    staticVariable::$v8 = $testKey2;    staticVariable::$v9 = $testKey3;
	
	$var = staticVariable::$v1; $var = staticVariable::$v2; $var = staticVariable::$v3;
	$var = staticVariable::$v4; $var = staticVariable::$v5; $var = staticVariable::$v6;
	$var = staticVariable::$v7; $var = staticVariable::$v8; $var = staticVariable::$v9;
}
report($name, $startMem);

//instantiated objects with array properties

$name = "variable instantiated object";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	$a = new instantiated;
	$a->v1 = $testKey1;    $a->v2 = $testKey2;    $a->v3 = $testKey3;
	$a->v4 = $testKey1;    $a->v5 = $testKey2;    $a->v6 = $testKey3;
	$a->v7 = $testKey1;    $a->v8 = $testKey2;    $a->v9 = $testKey3;
	
	$var = $a->v1; $var = $a->v2; $var = $a->v3;
	$var = $a->v4; $var = $a->v5; $var = $a->v6;
	$var = $a->v7; $var = $a->v8; $var = $a->v9;
}
report($name, $startMem);

//create variable

$name = "variable string";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	$v1 = $testKey1;    $v2 = $testKey2;    $v3 = $testKey3;
	$v4 = $testKey1;    $v5 = $testKey2;    $v6 = $testKey3;
	$v7 = $testKey1;    $v8 = $testKey2;    $v9 = $testKey3;
	
	$var = $v1; $var = $v2; $var = $v3;
	$var = $v4; $var = $v5; $var = $v6;
	$var = $v7; $var = $v8; $var = $v9;
	
}
report($name, $startMem);

//create bool variable

$name = "variable bool";
$startMem = memory_get_usage(); ztime::startTimer($name);

for($i = 0; $i < $iterations; $i++)
{
	$v1 = true;    $v2 = false;    $v3 = true;
	$v4 = false;   $v5 = true;     $v6 = false;
	$v7 = true;    $v8 = false;    $v9 = true;
	
	$var = $v1; $var = $v2; $var = $v3;
	$var = $v4; $var = $v5; $var = $v6;
	$var = $v7; $var = $v8; $var = $v9;
	
}
report($name, $startMem);

class staticArray
{
	public static $var = [];
	public static function __init() { self::$var = []; }
}

class staticVariable
{
	public static $v1 = "";
	public static $v2 = "";
	public static $v3 = "";
	public static $v4 = "";
	public static $v5 = "";
	public static $v6 = "";
	public static $v7 = "";
	public static $v8 = "";
	public static $v9 = "";
	public static function __init() { self::$v1 = ""; }
}

class instantiatedGetterSetter
{
	private $v1 = "";
	private $v2 = "";
	private $v3 = "";
	private $v4 = "";
	private $v5 = "";
	private $v6 = "";
	private $v7 = "";
	private $v8 = "";
	private $v9 = "";
	
	public function __construct() { $this->v1 = ""; }
	public function get1() { return $this->v1; }
	public function get2() { return $this->v2; }
	public function get3() { return $this->v3; }
	public function get4() { return $this->v4; }
	public function get5() { return $this->v5; }
	public function get6() { return $this->v6; }
	public function get7() { return $this->v7; }
	public function get8() { return $this->v8; }
	public function get9() { return $this->v9; }
	public function set1($in) { $this->v1 = $in; }
	public function set2($in) { $this->v2 = $in; }
	public function set3($in) { $this->v3 = $in; }
	public function set4($in) { $this->v4 = $in; }
	public function set5($in) { $this->v5 = $in; }
	public function set6($in) { $this->v6 = $in; }
	public function set7($in) { $this->v7 = $in; }
	public function set8($in) { $this->v8 = $in; }
	public function set9($in) { $this->v9 = $in; }
}

class instantiatedGetterSetterArray
{
	private $v1 = [];
	private $v2 = [];
	private $v3 = [];
	public function __construct() { $this->v1 = []; }
	
	public function get1() { return $this->v1; }
	public function get2() { return $this->v2; }
	public function get3() { return $this->v3; }
	public function set1($in) { $this->v1 = $in; }
	public function set2($in) { $this->v2 = $in; }
	public function set3($in) { $this->v3 = $in; }
}

class instantiated
{
	public $v1 = "";
	public $v2 = "";
	public $v3 = "";
	public $v4 = "";
	public $v5 = "";
	public $v6 = "";
	public $v7 = "";
	public $v8 = "";
	public $v9 = "";
	public function __construct() { $this->v1 = ""; }
}

class instantiatedArray
{
	public $var = [];
	public function __construct() { $this->var = []; }
}

//stop last performance counter and report details.
function report($name, $startMem)
{
	$memNow = memory_get_usage();
	$memUsed = $memNow - $startMem;
	echo "<P><b>" . ucfirst($name) . "</b><br>Finished in <b>" . ztime::stopLastTimer() . "</b> and grew memory by <b>" . $memUsed . " bytes.</b></P>";
	//echo "<P><b>" . ucfirst($name) . "</b><br>Finished in <b>" . ztime::stopLastTimer() . "</b>.</P>";
}

//for 2.0

//create and access SplFixedArrays

//create and access judy arrays