<?php
//06-2021 - removed dependency on zerolith framework
//01-2023 - dependency re-introduced with zl init timer :(
//05-2023 - v1.0 - added timeBetween function

class ztime
{
	private static $timers = [];                   //List of start and stop times
	private static $reportedPerf = false;          //Tracker to prevent this from happening more than once.

	public static $lastTimer = "";                 //Last timer ( for automatic stopping of timers )
	public static $dateFormat = 'm/d/y g:i:sa';   //Human-readable date format
	public static $dateFormatSQL = 'Y-m-d G:i:s';  //SQL timestamp/datetime compatible format
	public static $dateFormatNoSec = 'm/d/y g:ia';//Human-readable date format, without seconds
	public static $DBMemCacheHits = 0;             //# of free database calls thanks to zdb mem cache
	public static $DBDiskCacheHits = 0;            //# of free database calls thanks to zdb disk cache
	
	//now in PHP time
	public static function now() { return date(self::$dateFormat); }        //speak human version.
	
	//DEPRECATED
	public static function nowSQL() { return date(self::$dateFormatSQL); }  //speak the SQL version.
	
	//DEPRECATED
	//Find distance ( in days ) between two mySQL TIMESTAMP strings. Possibly inaccurate edge cases - need a better method.
    public static function dateDiffSQL($CheckIn, $CheckOut = "")
    {
        $CheckInX = explode("-", $CheckIn); $CheckOutX = explode("-", $CheckOut);
        $date1 = mktime(0, 0, 0, intval($CheckInX[1]),intval($CheckInX[2]),intval($CheckInX[0]));
        $date2 = mktime(0, 0, 0, intval($CheckOutX[1]),intval($CheckOutX[2]),intval($CheckOutX[0]));
        return($date2 - $date1)/(3600*24);
    }

    //Format a mySQL timestamp nicely for text.
    public static function formatTimestamp($dateString, $dateOnly = false, $noSeconds = false)
    {
        if($dateString == "") { return ""; }
        if($dateOnly) { return(date('m/d/y', strtotime($dateString))); }
        else
		{
			if($noSeconds) { $df = self::$dateFormatNoSec; } else { $df = self::$dateFormatNoSec; }
			return(date($df, strtotime($dateString)));
		}
    }
	
	//convert ruby timestamps ( with TZ characters ) to PHP ones.
	public static function convertRubyTimestamp($dateString) { return str_replace(["T", "Z"], [" ", ""], $dateString); }
	
	//convert seconds to human-understandeable units.
	public static function secsToUnits($numSeconds, $decimals = 2)
    {
		//prevent division by 0
	    if(!is_numeric($numSeconds) || $numSeconds <= 0) { return 0; }
    	
        if($numSeconds >= 86400) { return znum::shortFloat($numSeconds / 86400, $decimals) . " days"; }
        else if($numSeconds >= 3600) { return znum::shortFloat($numSeconds / 3600, $decimals) . " hours"; }
        else if($numSeconds >= 60) { return znum::shortFloat($numSeconds / 60, $decimals) . " mins"; }
        else if($numSeconds <= 60 && $numSeconds >= 1) { return znum::shortFloat($numSeconds, $decimals) . " secs"; }
        else if($numSeconds > 0) { return znum::shortFloat($numSeconds * 1000, $decimals) . " ms"; }
        else { return 0; }
    }
	
	//Returns time numerically ( in the specified units ) between two MySQL timestamps.
	//$timeUnit = sec/min/hr/day/wk/mo/yr
	//$returnFloat returns a 2 digit decimal
	//$absolute takes the minus off the number if present.
	//Note: ~66% written by phind (AI); good boy!
	//Todo: Accept MySQL DATE for both sides by appending " 00:00:00" - help a programmer out!
	public static function timeBetween($timeStart, $timeEnd, $timeUnit, $returnFloat = false, $absolute = true, $crashOnError = true)
	{
		$timeUnit = rtrim(strtolower($timeUnit), "s"); //lowercase and strip 's' character for extra fault tolerance
		if($timeEnd == "") { $timeEnd = self::now(); }
		$timeStart = strtotime($timeStart);
		$timeEnd = strtotime($timeEnd);
		
		//integrity check
		if(!$timeEnd || !$timeStart)
		{
			if($crashOnError) { zl::terminate("timeBetween thinks one of the timestamps is invalid"); }
			else { return false; } //emulate PHP function behavior
		}
		
		$diff = $timeEnd - $timeStart;
		switch ($timeUnit)
		{
			case 'sec': $result = $diff; break;
			case 'min': $result = $diff / 60; break;
			case 'hr':  $result = $diff / 3600; break;
			case 'day': $result = $diff / 86400; break;
			case 'wk':  $result = $diff / 604800; break;
			case 'mo':  $result = $diff / 2628000; break;
			case 'yr':  $result = $diff / 31536000; break;
			default:
				if($crashOnError) { zl::fault("timeBetween was sent an invalid time unit; please use: sec|min|hr|day|wk|mo|yr"); }
				else { return false; } //emulate PHP function behavior
			break;
		}
		
		if($absolute) { $result = abs($result); } //absolutely
		if($returnFloat) { return znum::shortFloat($result); }
		else { return round($result); }
	}
	
	//calculate time from a mysql timestamp to now in text
	public static function timeAgo($timestamp, $short = true, $fadeTimeText = true)
	{

        if(!isset($timestamp)){return "never";}
        if (zs::contains($timestamp, '-')) { $timeSince = time() - strtotime($timestamp); }
		else { $timeSince = time() - $timestamp; }

	    if($timeSince < 1) { return 'just now'; }
	    
		if($short)
		{
			$a = array(12 * 30 * 24 * 60 * 60  =>  'yr', 30 * 24 * 60 * 60 => 'mo', 24 * 60 * 60 => 'd', 60 * 60 => 'hr', 60 => 'min', 1 => 'sec');
		}
		else
		{
			$a = array(12 * 30 * 24 * 60 * 60  =>  'year', 30 * 24 * 60 * 60 => 'month', 24 * 60 * 60 => 'day', 60 * 60 => 'hour', 60 => 'minute', 1 => 'second');
		}
	 
	    foreach ($a as $secs => $unit)
	    {
	        $d = $timeSince / $secs;
	        if($d >= 1)
	        {
	            $r = round($d);
				
				//fade text with time ( cm uses )
		        if($fadeTimeText && $short)
				{
					if($unit == "d") //recentish
					{
						if($r >= 15) { $r = '<span class="zl_opa7">'.$r; $unit .= " ago</span>"; }
						else { return $r . $unit . " ago"; }
					}
					else if($unit == "mo" || $unit == "yr") { $r = '<span class="zl_opa5">' . $r; $unit .= " ago</span>"; } //old and crusty
					else { $r = '<span class="zl_blackDark">' . $r; $unit .= " ago</span>"; } //pretty new
					
					return $r . $unit;
				}
				else if($short) { return $r . $unit . " ago"; }
				else { return $r . ' ' . $unit . ($r > 1 ? 's' : '') . ' ago'; }
	        }
	    }
	}
	
	
	//----- ZL/profiler stuffs -----
	
	
	//inject the zl_init time from bootup ( special case )
    public static function injectInitTimer($initTimer, $beforeTimer = "")
    {
    	//no monkeying please.
    	if(isset(self::$timers['zl_time_total']) && is_array(self::$timers['zl_time_total'])) { return; }
    	
    	//calculate initialization overhead.
    	self::$timers['zl_init'] = ["total" => floatval(microtime(true) - $initTimer), "events" => 1];
		
		//reduce the ZL init time by the beforeload amount and register a timer event
		if($beforeTimer != "")
		{
			self::$timers['zl_init']['total'] =- $beforeTimer;
			self::$timers['zl_init_before'] = ["total" => $beforeTimer, "events" => 1];
		}
    	
    	//time_total continues to run.
    	self::$timers['zl_time_total'] = ["total" => 0, "events" => 0, 'time' => $initTimer];
    }
	
	//again for zl_init
	public static function injectAfterTimer($afterTimer)
	{
		//no monkeying please.
    	if(isset(self::$timers['zl_init_after']) && is_array(self::$timers['zl_init_after'])) { return; }
		self::$timers['zl_init_after'] = ["total" => $afterTimer, "events" => 1];
	}
	
	//a toggle between both start and stop functions.
    public static function stopWatch($timer)
    {
        if(isset(self::$timers[$timer]['time']) && self::$timers[$timer]['time'] != "") { return(self::stopTimer($timer)); }
        else { self::startTimer($timer); }
    }
	
	//Cheap trick to stop the last timer; used in logging and debug functions across Zerolith.
	//WARNING: make 100% sure you are free of timer changes during a debug/log function before
	//using this or you will suffer inaccurate results.
	public static function stopLastTimer() { return self::stopTimer(self::$lastTimer); }
	
	//start profiler timer.
	public static function startTimer($timer = "user")
	{
		if(!isset(self::$timers[$timer])) { self::$timers[$timer] = ["total" => 0, "events" => 0]; } //create if doesn't exist.
		self::$timers[$timer]['time'] = microtime(true); //reference point for time passed.
		self::$lastTimer = $timer;
	}
	
	//stop and return profiler timer.
    public static function stopTimer($timer = "user")
    {
		if(zs::isBlank($timer)) { zl::fault("invalid timer sent."); }
    	if(isset(self::$timers[$timer]['time']) && self::$timers[$timer]['time'] != "")
    	{
    		$totalTime = floatval(microtime(true) - self::$timers[$timer]['time']); //stop timer now.
    		self::$timers[$timer]['total'] += $totalTime; //update total for this timer.
		    self::$timers[$timer]['events']++; //update total number of events.
    		self::$timers[$timer]['time'] = ""; //blank out timer.
    		return self::secsToUnits($totalTime); //return text string.
    	}
    	else return("?");
    }
	
	//performance report for debugger
    public static function reportPerf($debugMem = 0)
    {
		if(self::$reportedPerf) { return ""; } else { self::$reportedPerf = true; }
		
		//get memory usage ahead of time.
		$memUsed = memory_get_usage();
		$memPeak = memory_get_peak_usage();

		function reportFormat($name, $time, $percentage = "", $eventCount = "")
	    {
	    	$maxProcessLength = 30;
			if($name != "Timer" && $percentage != "Percent")
			{
				if($name == "total") { $esp = "\n"; } else { $esp = ""; }
				return $esp . str_pad(zstr::shorten($name, $maxProcessLength - 3). ": ", $maxProcessLength) . str_pad(ztime::secsToUnits($time), 12) . str_pad(znum::shortFloat($percentage) . "%", 9) . $eventCount . "\n";
			}
			else //heading
			{ return "<b>" . str_pad($name, $maxProcessLength) . str_pad($time, 12) . str_pad($percentage, 9) . $eventCount . "</b>"; }
	    }
		
		//begin report to screen
	    if(zl_mode != "prod") //do simple mode for prod!!
	    {
			//stop all active timers
    	    foreach(self::$timers as $timer => $value) { self::stopTimer($timer); }
			
			$reportText = "<pre>" . reportFormat("Timer", "Time", "Percent", "Events");
			zarr::sortByKey(self::$timers, "total"); //sort the timers.
	    	
		    //reconfigure timer array into new format for display.
			$timers = array
			(
				"user" => array("time" => 0, "events" => 0, "pct" => 0, "subEvents" => array()),
				"zl" => array("time" => self::$timers['zl_time_total']['total'], "events" => 1, "pct" => 100, "subEvents" => array())
			);
			
			//establish total time first.
			$timers['total']['time'] = self::$timers['zl_time_total']['total'];
			$timers['total']['events'] = 1;
			$timers['total']['pct'] = 100;
			$timers['total']['subEvents'] = [];
			
			foreach(self::$timers as $key => $value)
			{
				if(zs::containsCase($key, "zl_"))
				{
					if($key != "zl_time_total") //already have this one, skip.
					{
						$timers['zl']['time'] += $value['total'];
						$timers['zl']['events'] += $value['events'];
						$timers['zl']['subEvents'][] = array( "name" => $key, "time" => $value['total'], "events" => $value['events'], "pct" => 0 );
					}
					else
					{
						//$timers['zl']['subEvents'][] = array("name" => "zl_time_total", "time" => $value['total'], "events" => $value['events'], "pct" => 0 );
					}
				}
				else //user defined events.
				{
					$timers['user']['time'] += $value['total'];
					$timers['user']['events'] += $value['events'];
					$timers['user']['subEvents'][] = array( "name" => $key, "time" => $value['total'], "events" => $value['events'], "pct" => 0 );
				}
			}
			
			//calculate main and sub percentages
		    foreach($timers as $key => $value)
		    {
				$mainPct = 0;
				$sec = count($value['subEvents']);
				
				if($sec > 0)
				{
					//calculate sub timers.
					for($i = 0; $i < $sec; $i++)
					{
						if($timers[$key]['subEvents'][$i]['time'] <= 0) { $subPct = 0; }
						else { $subPct = ($timers[$key]['subEvents'][$i]['time'] / $timers['total']['time']) * 100; }
						$mainPct += $subPct;
						$timers[$key]['subEvents'][$i]['pct'] = $subPct;
					}
					$timers[$key]['pct'] = $mainPct;
				}
		    }
			
			//zl is always 100%
			$timers['zl']['pct'] = 100;
			$timers['zl']['time'] = self::$timers['zl_time_total']['total'];
			
			//add 'unaccounted for' data.
		    if(!isset($timers['user'])) //we can't figure out the mixture of user/zl time right now.
		    {
				$timers['unaccounted']['events'] = "?";
				$timers['unaccounted']['subEvents'] = [];
				$timers['unaccounted']['time'] = $timers['total']['time'] - $timers['zl']['time'];
				if($timers['unaccounted']['time'] <= 0) { $pct = 0; }
				else { $pct = $timers['total']['time'] / $timers['unaccounted']['time']; }
				$timers['unaccounted']['pct'] = $pct;
			}
			
			//print to screen.
		    foreach($timers as $key => $value)
		    {
				//print any sub-events.
			    if(count($timers[$key]['subEvents']) > 0)
			    {
					$reportText .= "\n" . reportFormat($key, $value['time'], $value['pct'], $value['events']); //head
					foreach($timers[$key]['subEvents'] as $event)
					{ $reportText .= reportFormat("  " . $event['name'], $event['time'], $event['pct'], $event['events']); }
			    }
		    }
			
			$memScript = $memUsed - zl::$initMem;
			
			$reportText .= "\nDB Mem Cache Hit: " . intval(self::$DBMemCacheHits);
			$reportText .= "\nDB Disk Cache Hit:" . intval(self::$DBDiskCacheHits) . "\n";
			$reportText .= "\nScript mem:       " . znum::bytesToUnits($memScript - $debugMem);
			if($debugMem != 0) { $reportText .= "\nDebugger mem:     " . znum::bytesToUnits($debugMem) . "\n"; }
			else { $reportText .= "\n"; }
			$reportText .= "\nPHP init mem:     " . znum::bytesToUnits(zl::$initMem);
			$reportText .= "\nTotal end mem:    " . znum::bytesToUnits($memUsed);
			$reportText .= "\nTotal peak mem:   " . znum::bytesToUnits($memPeak);
			$reportText .= "\n\nPHP Version:      " . PHP_VERSION;
			$reportText .= "\nZL Version:       " . zl_version;
			$reportText .= "\nZL Inside App:    " . zl::$envInsideApp;
			$reportText .= "\nZL Side App:      " . zl::$envSideApp;
			
			return array("report" => $reportText . "</pre>", "time" => $timers['zl']['time']);
	    }
    	else
		{
			//just calculate total
			self::stopTimer("zl_time_total");
			$execTime = self::secsToUnits(self::$timers["zl_time_total"]['total']);
			
			if(zl::$set['debugLog'] || zl::$set['debugLogOnFault'])
			{
				//we may still need to generate this for the debug log, so..
				return ['report' => "<pre>" . $execTime . "</pre>", $execTime];
			}
			else
			{
				echo "\n<!-- Finished in: " . $execTime . " -->\n", "";
				return ['report' => "<pre>" . $execTime . "</pre>", 'time' => $execTime];
			}
		}
    }
}
?>