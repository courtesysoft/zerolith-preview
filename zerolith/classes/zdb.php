<?php
// Zerolith MySQL Library v1.65
// 06/2021 - refactored db3 to static class and added database switching
// 12/2022 - fixed float bug and improved error detection in writeRow::
// 01/2023 - started safe read functions
// 02/2023 - added tickmarks to fields like `fieldname` so that keys with mysql reserved keywords ( there's lots of them ) don't bomb.
// 05/2023 - added in-memory SQL cache.
// 12-2023 - fixed edge case in writeRow where writing a string with leading zeros results in the zeros being removed and being converted to an integer on write

//TODO: Implement automatic handling of SQL keywords like NOW() when sent as a string. writeRow() and other parameterized functions need this.
//TODO: Implement safe read functions, starting with getFieldSafe
//TODO: Implement disk cache; should be able to use json ( built into PHP ), simdjson ( 3x faster json ) igbinary ( extension, smallest data )
//TODO: Implement memory cache size count when debuglevel = 4
//TODO: Experiment with / implement mysqli persistent connections to remove per-instance overhead: https://www.php.net/manual/en/mysqli.persistconns.php

//How gnat does it ( from conversation 07/25/2023 )
//So one way I'm a fan of. Have a unified key/value API for cache that checks these for your SQL query in the following order:
//RAM (for repeated reads, basically)
//Memcached or redis or sqlite.
//Finally, database.
//Can just be 2-3 functions. cache_read() cache_write() cache_update() ...

class zdb
{
	public static $returnErrors = false;    //Return error as FALSE instead of exiting. Be very careful about toggling this frequently!
	private static $db = 1;                 //Default database number
	private static $connections = [];       //The array of available connections.
	private static $debugVoice = ['libraryName' => "zdb", 'micon' => "storage", 'textClass' => "zl_linkDark", 'bgClass' => 'zl_bgLinkLight'];
	
	private static $cachedCall = false;     //internal toggled flag - used to enable the cached versions of calls
	private static $cachedCallUsed = false; //if cached call has been used, unset cached data when manual SQL call made
	private static $SQLCacheUnsafe = [];    //associative array for unsafe SQL cache; [Literal SQL call => SQL output data]
	private static $SQLCacheSafe = [];      //associative array for safe command SQL cache; [JSON representation of call => SQL output data]
	
    public static function __init() { self::connectionOpen(zl::$set['dbDefault']); }
    
	
	// ------- Most Common Calls -------
	
	//toggle the active database.
    public static function useDB($useDB = "")
    {
		if(!isset(self::$connections[$useDB])) { self::connectionOpen($useDB); }
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
    
    // Parameterized UPDATE and INSERT function for keyed(associative) arrays.
    // $mode = INSERT, UPDATE, DELETE
    // $writeRow is a associative array that contains all of the fields that need to be written. It can only be blank when the DELETE command is issued.
    // $whereArray is an associative array containing a list of WHERE = conditions; you can name the values like %hamster, which results in a WHERE variable LIKE '%hamster'
    // whereArray can also be a standard SQL string for situations like when you need a WHERE IN(), etc operations; but this is discouraged as it could be insecure.
	// for SQL keywords like NULL, use PHP NULL instead of "null".
	// for SQL keywords like curdate(), now(), output a formatted timestamp using zdb::now() into that field
    public static function writeRow(string $mode, string $table, array $writeArray, $whereArray = [], $failMsg = "Database Error", $returnRowsAffected = false)
    {
    	ztime::startTimer("zl_db_write");
        $sqlTypes = ""; $keyList = ""; $varList = []; //initialize things we need later.
		
        //sanity/error checks
        $mode = strtoupper($mode);
        if($mode != "INSERT" && $mode != "DELETE" && $mode != "UPDATE")
        { self::fault($failMsg, "zdb: Invalid mode sent to writeRow"); return false; }

        if($table == "") { self::fault($failMsg, "zdb: Blank Table sent to writeRow"); return false; }
        if($mode == "INSERT" || $mode == "UPDATE") //blank writeArray sent? bail..
        {
            if(zs::isBlank($writeArray)) { self::fault($failMsg, "zdb: Blank writeArray sent to writeRow"); return false; }
            if(is_array($writeArray) && !zarr::isAssociative($writeArray)) { self::fault($failMsg, "zdb: Non-associative array in writeArray"); return false; }
        }

        if($mode == "UPDATE" || $mode == "DELETE") //blank whereArray sent? bail..
        {
             if(zs::isBlank($whereArray)) { self::fault($failMsg, "zdb: Blank whereArray sent to writeRow"); return false; }
             if(is_array($whereArray) && !zarr::isAssociative($whereArray)) { self::fault($failMsg, "zdb: non-associative whereArray in whereArray"); return false; }
        }
		
        //build first part of command.
        if($mode == "INSERT") { $sql = "INSERT INTO " . $table . " SET "; }
        else if($mode == "UPDATE") { $sql = "UPDATE " . $table . " SET "; }
        else if($mode == "DELETE") { $sql = "DELETE FROM " . $table; }

        //build variable array
        if($mode == "INSERT" || $mode == "UPDATE")
        {
            foreach($writeArray as $key => $value) //iterate and get key and value as seperate variables..
            {
                $varList[$key] = &$writeArray[$key];
                $keyList .= "`" . $key . "`=?, ";
				$isn = is_numeric($value); //for speed
				
                if($isn && is_float($value + 0)) { $sqlTypes .= 'd'; } //+ 0 is a hack to force conversion of a string to number so we can check if it's actually a float. PHP requires this kledge.
				else if($isn)
				{
					//this prevents an edge case where  01234 internally converts to an int (1234) when it should be a string (01234).
					if(is_string($value) && substr($value, 0, 1) == "0" && strlen($value >= 1)) { $sqlTypes .= "s"; }
					else { $sqlTypes .= "i"; }
				}
				else { $sqlTypes .= "s"; }
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
				
                $whereString = " WHERE "; $multiWhere = false;
                foreach($whereArray as $key => $value)
                {
					//test and see if this can be removed
	                $isn = is_numeric($value); //for speed
	                
					if($isn && is_float($value + 0)) { $sqlTypes .= 'd'; }  //+ 0 is a hack to force conversion of a string to number so we can check if it's actually a float. PHP requires this kledge.
                    else if($isn)
					{
						//this prevents an edge case where  01234 internally converts to an int (1234) when it should be a string (01234).
						if(is_string($value) && substr($value, 0, 1) == "0" && strlen($value >= 1)) { $sqlTypes .= "s"; }
						else { $sqlTypes .= "i"; }
					}
					else { $sqlTypes .= 's'; }
					
                    if($multiWhere) { $whereString .= " AND "; }
                    $whereString .= $key . " = ?";
                    if($mode == "DELETE") { $varList[$key] = &$whereArray[$key]; } //there's no writeArray here, and stmt_bind likes the names to match.
                    else { $varList["where_" . $key] = &$whereArray[$key]; } //the where_ prevents conflict if you are using same variable names for where & write. For other modes, it seems to be okay with this :)
                    $multiWhere = true;
                }
                $sql .= $whereString;
            }
        }
		
        //prep work
        $mp = mysqli_prepare(self::$connections[self::$db], $sql);
		
		//handle incorrect binding.
        if($mp === 1)
        {
			$stmtError = "zdb: The sql parameters could not be bound.\n";
        	$stmtError .= "keylist: \n" .  zs::pr($keyList) . "\n";
            $stmtError .= "\nvarlist: \n" . zs::pr($varList) . "\n";
            $stmtError .= "\nsqlTypes:\n" . zs::pr($sqlTypes) . "\n";
            $stmtError .= "\nsql:\n" . $sql . "\n\n";
            $stmtError .= "\nsql_stmt:\n" . zs::pr($mp) . "\n";
			self::fault($failMsg, $stmtError); return false;
        }
	    
        if($mode == "DELETE" && !is_array($whereArray) ) {  } //do nothing
        else
        {
        	//supports php 8.0 --v
        	//suppress error visually and fail explicitly
	        if(@!call_user_func_array('mysqli_stmt_bind_param', array_merge(array($mp, $sqlTypes), array_values($varList))))
	        {
				self::fault($failMsg, "\nzdb::writeRow() Binding failure\nSQL was: " . $sql . "\nSQL bindvars:\n" . zs::pr($varList) . "\nMySQL Error: " . zs::pr(mysqli_error(self::$connections[self::$db])) . "\n"); return false;
	        }
        }
		
		// v-- faulting line
        if(mysqli_stmt_execute($mp))
        {
			if(($rows = mysqli_stmt_affected_rows($mp)) == -1)
			{ self::fault($failMsg, "zdb: failed to update any rows during " . $mode . "."); return false; }
			
			//strict: inserts MUST affect a row.
			if($mode == "INSERT" && $rows == 0)
			{ self::fault($failMsg, "zdb: 0 rows affected during " . $mode . "."); return false; }
			
			mysqli_stmt_close($mp);
			self::log($rows);
			if($returnRowsAffected) { return $rows; } else { return true; }
        }
        else
        {
            self::fault($failMsg, "\nzdb::writeRow() Statement Execute failure\nSQL was: " . $sql . "\nSQL bindvars:\n" . zs::pr($varList) . "\nMySQL Error: " . zs::pr(mysqli_error(self::$connections[self::$db])) . "\n"); return false;
        }
    }
	
	//Send a raw SQL statement on the database.
    public static function writeSQL($SQL, $failMsg = "Database Error", $returnRowsAffected = false)
    {
    	ztime::startTimer("zl_db_write");
    	
        if(!mysqli_query(self::$connections[self::$db], $SQL)){ self::fault($failMsg, mysqli_error(self::$connections[self::$db])); return false; }
        else
		{
			$rows = mysqli_affected_rows(self::$connections[self::$db]);
        	if($returnRowsAffected == true) { self::log($rows); return $rows; }
            else { self::log($rows); return true; }
        }
    }
	
	// untested --v
	// Return a string from a single column SQL request, but with SQL injection prevention
	// Behavior: Halt program on database access error, halt program if output is blank and faultOnBlank = true
    public static function getFieldSafe($SQL, $valuesInOrder = [], $failMsg = "Database Error")
    {
		ztime::startTimer("zl_db_read"); $sqlTypes = "";
		
		//unequal ? and valuesInOrder sent?
		if(substr_count($SQL, "?") != count($valuesInOrder)) { self::fault("unequal number of values to ? sent!"); return false; }
  
		//formulate types string
		foreach($valuesInOrder as $value)
        { if(is_float($value)) { $sqlTypes .= 'd'; } else if(is_numeric($value)) { $sqlTypes .= 'i'; } else { $sqlTypes .= 's'; } }
		
		$mp = mysqli_prepare(self::$connections[self::$db], $SQL);
        if($mp === 1) //handle incorrect binding.
        {
			$stmtError = "zdb: The sql parameters could not be bound.\n";
            $stmtError .= "\nsqlTypes:\n" . zs::pr($sqlTypes) . "\n" . "\nsql:\n" . $SQL . "\n\n";
        	self::fault($failMsg, $stmtError); return false;
        }
		
		//binding parameters
		if(@!call_user_func_array('mysqli_stmt_bind_param', array_merge(array($mp, $sqlTypes), array_values($valuesInOrder))))
        {
			self::fault($failMsg, "\nzdb::getFieldSafe() Binding failure\nSQL was: " . $SQL . "\nSQL bindvars:\n" . zs::pr($valuesInOrder) . "\nMySQL Error: " . zs::pr(mysqli_error(self::$connections[self::$db])) . "\n"); return false;
        }
		
		if($mp->execute())
		{
			$result = $mp->get_result();
			$rows = zarr::first($result->fetch_assoc());
			mysqli_free_result($mp);
			self::log($rows);
			return $rows;
		}
		else
		{
			self::fault($failMsg, "\nzdb::getFieldSafe() Statement Execute failure\nSQL was: " . $SQL . "\nSQL bindvars:\n" . zs::pr($valuesInOrder) . "\nMySQL Error: " . zs::pr(mysqli_error(self::$connections[self::$db])) . "\n"); return false;
		}
    }
	
	// Return a string from a single column SQL request.
	// Behavior: Halt program on database access error, halt program if output is blank and faultOnBlank = true
    public static function getField($SQL, $failMsg = "Database Error", $faultOnBlank = false)
    {
    	ztime::startTimer("zl_db_read");
    	
        if(($mq = mysqli_query(self::$connections[self::$db], $SQL)) === false)
		{ self::fault($failMsg, mysqli_error(self::$connections[self::$db])); return false; }

        if(($data = mysqli_fetch_array($mq, MYSQLI_ASSOC)) === false)
		{ self::fault($failMsg, mysqli_error(self::$connections[self::$db])); return false; }
		if($data === null)
		{
			if($faultOnBlank) { self::fault($failMsg, "ZL: fault on blank"); return false; }
			$value = "";
		}
		else { $value = zarr::first($data); }

        mysqli_free_result($mq);
        if(zl::$set['debugLevel'] > 2 || zl::$set['debugLog'] || zl::$set['debugLogOnFault'] ) { self::log($value); }
		else { self::log(); }
		
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
		ztime::startTimer("zl_db_read");
        if(!($mq = mysqli_query(self::$connections[self::$db], $SQL)))
		{ self::fault($failMsg, mysqli_error(self::$connections[self::$db])); return false; }

        // return the row as an array...
        if(($rData = mysqli_fetch_array($mq, MYSQLI_ASSOC)) === false)
		{ self::fault($failMsg, mysqli_error(self::$connections[self::$db])); return false; }
		if($rData === null)
		{
			if($faultOnBlank) { self::fault($failMsg, "ZL: fault on blank"); return false; }
			$rData = [];
		}
        mysqli_free_result($mq);

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
    	ztime::startTimer("zl_db_read");
        $outArray = [];
        if(!($mq = mysqli_query(self::$connections[self::$db], $SQL)) ) { self::fault($failMsg, mysqli_error(self::$connections[self::$db])); return false; }

        while($arrayItem = mysqli_fetch_array($mq, MYSQLI_ASSOC))
        {
			if($arrayItem === false) { self::fault($failMsg, mysqli_error(self::$connections[self::$db])); return false; }
			$outArray[] = $arrayItem;
		}
        mysqli_free_result($mq); //free memory immediately
		
		if($faultOnBlank) { if(zs::isBlank($outArray)) { self::fault($failMsg, "ZL: fault on blank"); return false; } }
		
		if(zl::$set['debugLevel'] > 2 || zl::$set['debugLog'] || zl::$set['debugLogOnFault']) { self::log($outArray); } else { self::log(); }
		
		//Conditional update of cache - in rare case that we are making an uncached call now but later make a cached call to the same query
		if(!self::$cachedCall && self::$cachedCallUsed && isset(self::$SQLCacheUnsafe[$SQL]))
		{ self::$SQLCacheUnsafe[$SQL] = $outArray; }
		
		self::$cachedCall = false; //turn off marker immediately
        return $outArray;
    }
	
	//in-memory cache variants of regular database commands.
	public static function getFieldMem($SQL, $failMsg = "Database Error", $faultOnBlank = false)
	{ return self::memCacheOrSQL($SQL, $failMsg, $faultOnBlank, "getField"); }
	
	public static function getRowMem($SQL, $failMsg = "Database Error", $faultOnBlank = false)
	{ return self::memCacheOrSQL($SQL, $failMsg, $faultOnBlank, "getRow"); }
	
	public static function getArrayMem($SQL, $failMsg = "Database Error", $faultOnBlank = false)
	{ return self::memCacheOrSQL($SQL, $failMsg, $faultOnBlank, "getArray"); }
	
	//basic features for manual management of memory cache
	public static function memCacheInvalidate($SQL) { unset(self::$SQLCacheUnsafe[$SQL]); }
	public static function memCacheWrite($SQL, $data) { self::$SQLCacheUnsafe[$SQL] = $data; }
	
	//wrapper function for memory cache variant calls
	private static function memCacheOrSQL($SQL, $failMsg = "Database Error", $faultOnBlank = false, $funcName = "")
	{
		self::$cachedCallUsed = true; //always flag this
		if(isset(self::$SQLCacheUnsafe[$SQL])) //attempt to read from RAM
		{
			ztime::$DBMemCacheHits++; //for later debugger bragging
			return self::$SQLCacheUnsafe[$SQL]; //yee haw!
		}
		else //go the slow route
		{
			self::$cachedCall = true;
			self::$SQLCacheUnsafe[$SQL] = self::$funcName($SQL, $failMsg, $faultOnBlank); //update the cache
			return self::$SQLCacheUnsafe[$SQL]; //return the promised data
		}
	}
	
	
	// ------- Less Common Calls -------
	
	// Return a bool value based on whether a table exists or not.
    public static function tableExists($table)
    {
    	ztime::stopWatch("zl_db_read");
		$msr = mysqli_query(self::$connections[self::$db], "SHOW TABLES LIKE '" . $table . "'");
		if($msr->num_rows == 0) { $success = false; } else { $success = true; }
		self::log($table . " existence check", $success);
    	return $success;
    }
	
    //Return the count of rows in a table.
    public static function getCount(string $table, $whereSQL = "")
    {
    	ztime::startTimer("zl_db_read");
    	if($whereSQL == "") { $whereSQL = "WHERE 1"; }
		
        $SQL = "SELECT count(*) AS c FROM $table $whereSQL"; //count(*) is an optimization according to planetscale.com;
        if(!($mq = mysqli_query(self::$connections[self::$db], $SQL)) )
		{ self::fault("Database Error", mysqli_error(self::$connections[self::$db])); return false; }
		
        if(($data = mysqli_fetch_array($mq, MYSQLI_ASSOC)) == false)
		{ self::fault("Database Error", mysqli_error(self::$connections[self::$db])); return false; }
		
		if(zl::$set['debugLevel'] > 2 || zl::$set['debugLog'] || zl::$set['debugLogOnFault'])
		{ self::log($data['c']); } else { self::log(); }
        mysqli_free_result($mq);
		
        return $data['c'];
    }
	
	//return an array of values from an ENUM field.
	public static function getFieldEnums($table, $field, $failMsg = "Database Error")
	{
		$dataRow = self::getRow("SHOW COLUMNS FROM " . $table . " LIKE '" . $field . "'", $failMsg, true);
		preg_match_all("/'(.*?)'/", $dataRow['Type'], $enum_array ); //extract the values. the values are enclosed in single quotes and separated by commas.
		return($enum_array[1]);
	}

    // Return an associative array or string listing the columns in a table/field.
	// mode: simple or complex.
    public static function getTableValues($table, $mode = "simple", $failMsg = "Database Error" )
    {
		$dataArray = self::getArray("SHOW COLUMNS FROM " . $table, $failMsg, true);

        if($mode == "simple") //returns a simple flat array of field names.
        {
            $temp = [];
            foreach($dataArray as $data) { $temp[] = $data['Field']; }
            sort($temp);
			if(zl::$set['debugLevel'] > 2 || zl::$set['debugLog'] || zl::$set['debugLogOnFault']) { self::log($temp); } else { self::log(); }
            return $temp;
        }
        elseif($mode == "complex") //returns a 2 part array. 0 = fieldName, 1 = fieldDescription
        {
            $temp = [];
            foreach($dataArray as $data)
            {
                $data['Type'] = str_replace(" unsigned", "", $data['Type']);
                $data['Type'] = str_replace("'", "", $data['Type']);
                if( zs::contains($data['Type'], "har(") ) { $data['Type'] = "text"; }
                if( zs::contains($data['Type'], "ext(") ) { $data['Type'] = "text"; }
                if( zs::contains($data['Type'], "nt(") ) { $data['Type'] = "number"; }

                $temp[] = array($data['Field'], $data['Field'] . " - " . $data['Type']);
            }
            sort($temp);
            if(zl::$set['debugLevel'] > 2 || zl::$set['debugLog'] || zl::$set['debugLogOnFault']) { self::log($temp); } else { self::log(); }
            return $temp;
        }
        else { self::fault($failMsg, "zdb: incorrect mode passed. Table: " . $table ." mode: " . $mode); return false; }
    }

    // Shortcut to get the last inserted id.
    public static function getLastInsertID(string $table, $extraSQL = "")
    { return intval(self::getField("SELECT Last_Insert_ID() FROM $table $extraSQL LIMIT 1")); }

    //buffer a SQL chunk to be executed later
    public static function bufferSQL($bufChunk, $originator = "undefined")
    { return (self::writeRow("INSERT", "zl_SQLBuffer", ["SQLData" => $bufChunk, "originator" => $originator] , [], "Could not buffer SQL chunk", true)); }

    //buffer an array of SQL chunks to be executed later
    public static function bufferSQLarray($sqlArray, $originator = "undefined", $bufMaximum = 50)
    {
        $bufCount = 0; $bufferedChunks = 0; $bufferedChunksSuccessful = 0;
        $bufChunk = "";

        foreach($sqlArray as $sqlEntry)
        {
            if($bufCount == $bufMaximum)
            {
                //write SQL chunk
                $bufferedChunksSuccessful += self::bufferSQL($bufChunk, $originator);
                $bufferedChunks++;
                $bufCount = 0; $bufChunk = "";
            }
            $bufChunk .= $sqlEntry;
            $bufCount++;
        }

        //write SQL chunk remnant if it exists.
        if($bufChunk != "")
        {
            $bufferedChunksSuccessful += self::bufferSQL($bufChunk, $originator);
            $bufferedChunks++;
        }
        if($bufferedChunks == $bufferedChunksSuccessful) { $result = true; } else { $result = false; }
        return array($result, $bufferedChunks, $bufferedChunksSuccessful);
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
    private static function fault($userMsg = "Database error", $MySQLError = "")
    {
		self::$cachedCall = false; //turn cache call marker off
		self::log(false,false, ["MySQLError" => $MySQLError, "userMsg" => $userMsg]);
		
		//if return errors is on, don't die; the next command in the library will return false.
		if(self::$returnErrors) { zl::faultSoft($userMsg); return false; } else { zl::fault($userMsg); }
	}
	
	//open a database connection if not present.
    private static function connectionOpen($useDB = 1)
    {
    	ztime::startTimer("zl_db_connect");
        if($useDB == 1)
        {
            self::$connections[1] = mysqli_connect(zl::$set['dbHost'], zl::$set['dbUser'], zl::$set['dbPass'], zl::$set['dbName'])
            or self::fault("Unable to connect to MySQL server 1.", mysqli_error(self::$connections[1]));
        }
        else //must be database two!
        {
        	self::$connections[2] = mysqli_connect(zl::$set['dbHost2'], zl::$set['dbUser2'], zl::$set['dbPass2'], zl::$set['dbName2'])
            or self::fault("Unable to connect to MySQL server 2.", mysqli_error(self::$connections[2]));
        }
        
		self::$db = $useDB;
		ztime::stopTimer("zl_db_connect"); //don't bother logging this - only measure.
    }
}
zdb::__init();
?>