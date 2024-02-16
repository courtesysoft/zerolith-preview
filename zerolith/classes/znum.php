<?php
//zerolith number library
class znum
{
	//returns human readable units for bytes: mb, gb, t..
	public static function bytesToUnits($size, $precision = 2)
	{
		if($size == 0) { return 0; }
	    $base = log($size, 1024);
	    $suffixes = array('B', 'K', 'M', 'G', 'T');
	    return round(pow(1024, $base - floor($base)), $precision) .''. $suffixes[floor($base)];
	}
	
	//also known as moneyFormat: 4444.44
	public static function shortFloat($number, $digits = 2) { return round(floatval($number), $digits); }
	
	//return the closest number in a simple array.
	public static function getClosestNumber($search, $arr)
	{
	   $closest = null;
	   foreach ($arr as $item) { if ($closest === null || abs($search - $closest) > abs($item - $search)) { $closest = $item; } }
	   return $closest;
	}
}
