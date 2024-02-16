<?php
// Zerolith PostgreSQL Library v0.6
// 12/2023 - started as a modified copy of zdb. NOT TESTED

//TODO: Make feature equivalent to zdb

class zdp
{
	public static $returnErrors = false;    //Return error as FALSE instead of exiting. Be very careful about toggling this frequently!
	private static $db = 1;                 //Default database number
	private static $conns = [];             //The array of available connections.
	private static $debugVoice = ['libraryName' => "zdp", 'micon' => "storage", 'textClass' => "zl_linkDark", 'bgClass' => 'zl_bgLinkLight'];
		
    public static function __init() { self::connectionOpen(zl::$set['dbDefault']); }
	
	// ------- Most Common Calls -------
	
	//toggle the active database.
    public static function useDB($useDB = "")
    {
		if(!isset(self::$conns[$useDB])) { self::connectionOpen($useDB); }
		else { self::$db = $useDB; }
	}
	
	//return the mysql equivalent of now()
	public static function now() { return date('Y-m-d G:i:s'); }
	
	//currently a shortcut for writeRow. Speed could be dramatically improved
    public static function writeArray(string $mode, string $table, array $writeArray, $whereArray = "", $failMsg = "", $returnRowsAffected = false)
    {
		$success = true;
		$rowsAffected = 0;
    	foreach($writeArray as $write)
	    {
			$result = self::writeRow($mode, $table, $write, $whereArray, $returnRowsAffected, $failMsg, $returnRowsAffected);
			if($returnRowsAffected) { $rowsAffected += $result; }
			else { if($result === false) { $success = false; } }
		}
		
		if($returnRowsAffected) { return $rowsAffected; } else { return $success; }
    }
    
	public static function writeRow(string $mode, string $table, array $writeArray, $whereArray = [], $failMSG = "Database Error", $returnRowsAffected = false)
	{
	    //sanity/error checks
        $mode = strtoupper($mode);
        if($mode != "INSERT" && $mode != "DELETE" && $mode != "UPDATE")
        { self::fault($failMsg, "zdb: Invalid mode sent to writeRow"); return false; }
	    
		//build first part of command.
        if($mode == "INSERT") { $sql = "INSERT INTO " . $table . " SET "; }
        else if($mode == "UPDATE") { $sql = "UPDATE " . $table . " SET "; }
        else if($mode == "DELETE") { $sql = "DELETE FROM " . $table; }
		
		//special values PG needs
		$pgIter = 1;    //iterator for parameter values
		$pgParams = [];  //sequential array that will be passed to pg_execute()
		
	    //build variable array
        if($mode == "INSERT" || $mode == "UPDATE")
        {
            foreach($writeArray as $key => $value)
            {
                $varList[$key] = &$writeArray[$key];
                $keyList .= "`" . $key . '`=$' . $pgIter . ',';
				$pgIter++;
				$pgParams[] = $value;
            }

            $keyList = trim($keyList, ", "); //clean up..
            $sql .= $keyList;
        }

        //build where data.
        if($mode == "UPDATE" || $mode == "DELETE")
        {
            if(!is_array($whereArray)) //allow insecure manual SQL.
			{
				if($whereArray == "") { self::fault($failMsg, "\nzdb::writeRow() was given an UPDATE or DELETE mode with a blank whereArray. This can result in an unintended update of all rows"); }
				$sql .= " " . $whereArray;
			}
            else
            {
				//safety check
	            if(zs::isBlank($whereArray))
				{
					self::fault($failMsg, "\nzdp::writeRow() was given an UPDATE or DELETE mode with a blank whereArray. This can result in an unintended update of all rows");
				}
				
                $whereString = " WHERE ";
				$multiWhere = false;
                foreach($whereArray as $key => $value)
                {
                    if($multiWhere) { $whereString .= " AND "; }
                    $whereString .= $key . '`=$' . $pgIter . ',';
                    $multiWhere = true;
					$pgIter++;
					$pgParams[] = $value;
                }
                $sql .= $whereString;
            }
        }
	    
	    // Prepare the query
	    if(!pg_prepare(self::$conn[self::$db], "", $sql)) //whoops
		{
			self::fault($failMsg, "\nzdp::writeRow() prepare failure\nSQL was: " . $sql . "\n bindvars:\n" . zs::pr($pgParams) . "\nPGSQL Error: " . zs::pr(pg_last_error(self::$connections[self::$db])) . "\n");
			return false;
	    }
	    
	    // Execute the prepared statement with the values
	    $result = pg_execute(self::$conn[self::$db], "write_query", $pgParams);
	    
	    if(!$result)
		{
	        self::fault($failMsg, "\nzdp::writeRow() execute failure\nSQL was: " . $sql . "\n bindvars:\n" . zs::pr($pgParams) . "\nPGSQL Error: " . zs::pr(pg_last_error(self::$connections[self::$db])) . "\n");
			return false;
	    }
		else
		{
			$rowCount = pg_affected_rows($result);
			//strict: inserts MUST affect a row.
			if($mode == "INSERT" && $rows <= 0) { self::fault($failMsg, "zdp: 0 rows affected during " . $mode . "."); return false; }
			
			self::log($rows);
			if($returnRowsAffected) { return $rows; } else { return true; }
		}
	}
	
	//Send a raw SQL statement on the database.
    public static function writeSQL($SQL, $failMsg = "Database Error", $returnRowsAffected = false)
    {
    	ztime::startTimer("zl_dbp_write");
    	
        if(!pg_query(self::$conns[self::$db], $SQL)){ self::fault($failMsg, pg_last_error(self::$conns[self::$db])); return false; }
        else
		{
			$rows = pg_affected_rows(self::$conns[self::$db]);
        	if($returnRowsAffected == true) { self::log($rows); return $rows; }
            else { self::log($rows); return true; }
        }
    }
	
	// Return a string from a single column SQL request.
	// Behavior: Halt program on database access error, halt program if output is blank and faultOnBlank = true
    public static function getField($SQL, $failMsg = "Database Error", $faultOnBlank = false)
    {
    	ztime::startTimer("zl_dbp_read");
    	
        if(($mq = pg_query(self::$conns[self::$db], $SQL)) === false)
		{ self::fault($failMsg, pg_last_error(self::$conns[self::$db])); return false; }

        if($data = pg_fetch_assoc($mq) === false)
		{ self::fault($failMsg, pg_last_error(self::$conns[self::$db])); return false; }
		if($data === null)
		{
			if($faultOnBlank) { self::fault($failMsg, "ZL: fault on blank"); return false; }
			$value = "";
		}
		else { $value = zarr::first($data); }

        pg_free_result($mq);
        if(zl::$set['debugLevel'] > 2 || zl::$set['debugLog'] || zl::$set['debugLogOnFault'] ) { self::log($value); } else { self::log(); }
		
		//Conditional update of cache - in rare case that we are making an uncached call now but later make a cached call to the same query
		if(!self::$cachedCall && self::$cachedCallUsed && isset(self::$SQLCacheUnsafe[$SQL]))
		{ self::$SQLCacheUnsafe[$SQL] = $value; }
		
		self::$cachedCall = false; //turn off marker immediately
        return $value;
    }
	
    // Return the first (only!) row array of the query specified.
	// Behavior: Halt program on database access error, halt program if output is blank and faultOnBlank = true
    public static function getRow($SQL, $failMsg = "Database Error", $faultOnBlank = false)
    {
		ztime::startTimer("zl_dbp_read");
        if(!($mq = pg_query(self::$conns[self::$db], $SQL)))
		{ self::fault($failMsg, pg_last_error(self::$conns[self::$db])); return false; }

        // return the row as an array...
        if($rData = pg_fetch_assoc($mq) === false)
		{ self::fault($failMsg, pg_last_error(self::$conns[self::$db])); return false; }
		if($rData === null)
		{
			if($faultOnBlank) { self::fault($failMsg, "ZL: fault on blank"); return false; }
			$rData = [];
		}
        pg_free_result($mq);

		if(zl::$set['debugLevel'] > 2 || zl::$set['debugLog'] || zl::$set['debugLogOnFault']) { self::log($rData); } else { self::log(); }
  
		//Conditional update of cache - in rare case that we are making an uncached call now but later make a cached call to the same query
		if(!self::$cachedCall && self::$cachedCallUsed && isset(self::$SQLCacheUnsafe[$SQL]))
		{ self::$SQLCacheUnsafe[$SQL] = $rData; }
		
		self::$cachedCall = false; //turn off marker immediately
		return $rData;
    }
	
    // Returns an array of all of the rows of data.
	// Behavior: Halt program on database access error, halt program if output is blank and faultOnBlank = true
    public static function getArray($SQL, $failMsg = "Database Error", $faultOnBlank = false)
    {
    	ztime::startTimer("zl_dbp_read");
        $outArray = [];
        if(!($mq = pg_query(self::$conns[self::$db], $SQL)) ) { self::fault($failMsg, pg_last_error(self::$conns[self::$db])); return false; }

        while($arrayItem = pg_fetch_assoc($mq))
        {
			if($arrayItem === false) { self::fault($failMsg, pg_last_error(self::$conns[self::$db])); return false; }
			$outArray[] = $arrayItem;
		}
        pg_free_result($mq); //free memory immediately
		
		if($faultOnBlank) { if(zs::isBlank($outArray)) { self::fault($failMsg, "ZL: fault on blank"); return false; } }
		
		if(zl::$set['debugLevel'] > 2 || zl::$set['debugLog'] || zl::$set['debugLogOnFault']) { self::log($outArray); } else { self::log(); }
		
		//Conditional update of cache - in rare case that we are making an uncached call now but later make a cached call to the same query
		if(!self::$cachedCall && self::$cachedCallUsed && isset(self::$SQLCacheUnsafe[$SQL]))
		{ self::$SQLCacheUnsafe[$SQL] = $outArray; }
		
		self::$cachedCall = false; //turn off marker immediately
        return $outArray;
    }
	
	// ------- Less Common Calls -------
	
	// Return a bool value based on whether a table exists or not.
    public static function tableExists($table)
    {
    	ztime::stopWatch("zl_dbp_read");
		$msr = pg_query(self::$conns[self::$db], "SHOW TABLES LIKE '" . $table . "'");
		if($msr->num_rows == 0) { $success = false; } else { $success = true; }
		self::log($table . " existence check", $success);
    	return $success;
    }
	
    //Return the count of rows in a table.
    public static function getCount(string $table, $whereSQL = "")
    {
    	ztime::startTimer("zl_dbp_read");
    	if($whereSQL == "") { $whereSQL = "WHERE 1"; }
		
        $SQL = "SELECT count(*) AS c FROM $table $whereSQL"; //count(*) is an optimization according to planetscale.com; strangely enough
        if(!($mq = pg_query(self::$conns[self::$db], $SQL)) ){ self::fault("Database Error", pg_last_error(self::$conns[self::$db])); return false; }
		
        if($data = pg_fetch_assoc($mq) == false) { self::fault("Database Error", pg_last_error(self::$conns[self::$db])); return false; }
		
		if(zl::$set['debugLevel'] > 2 || zl::$set['debugLog'] || zl::$set['debugLogOnFault']) { self::log($data['c']); } else { self::log(); }
        pg_free_result($mq);
		
        return $data['c'];
    }
	
	// ------- Internal Class Functions -------
	
	//log class success/fail states to the debug console
	private static function log($out = "", $success = true, $faultData = "", string $timerToStop = "")
	{
		self::$cachedCall = false; //turn cache call marker off
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
	
	//write automatic error to debug console, then commit seppuku.
    private static function fault($userMsg = "PG Database error", $PGSQLerror = "")
    {
		self::$cachedCall = false; //turn cache call marker off
		self::log(false,false, ["PGSQLerror" => $PGSQLerror, "userMsg" => $userMsg]);
		
		//if return errors is on, don't die; the next command in the library will return false.
		if(self::$returnErrors) { zl::faultSoft($userMsg); return false; } else { zl::fault($userMsg); }
	}
	
	//open a database connection if not present.
    private static function connectionOpen($useDB = 1)
    {
    	ztime::startTimer("zl_dbp_connect");
        if($useDB == 1)
        {
            self::$conns[1] = pg_connect("host=" . zl::$set['dbpHost'] . " dbname=" . zl::$set['dbpName'] . " user=" . zl::$set['dbpUser'] . " password=". zl::$set['dbpPass'])
            or self::fault("Unable to connect to PostgreSQL server 1.", pg_last_error(self::$conns[1]));
        }
        else //must be database two!
        {
        	self::$conns[2] = pg_connect("host=" . zl::$set['dbpHost2'] . " dbname=" . zl::$set['dbpName2'] . " user=" . zl::$set['dbpUser2'] . " password=". zl::$set['dbpPass2'])
            or self::fault("Unable to connect to PostgreSQL server 2.", pg_last_error(self::$conns[2]));
        }
        
		self::$db = $useDB;
		ztime::stopTimer("zl_dbp_connect"); //don't bother logging this - only measure.
    }
}
zdp::__init();
?>