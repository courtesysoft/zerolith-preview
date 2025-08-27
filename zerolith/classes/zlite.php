<?php
// Zerolith SQLite library - v0.55
// This library uses the SQLite3 class in PHP instead of PDO
//
// 20250121 - started work based on zdb.php
// 08/01/2025 - fixes; create database, save to safe path, filter filename and don't accept full path - DS
// status: beta
// class dependencies: SQLite3, zl, ztime, zs, zarr

class zlite
{
    private static $connection = null;
	private static $debugVoice = ['libraryName' => "zlite", 'micon' => "storage", 'textClass' => "zl_linkDark", 'bgClass' => 'zl_bgLinkLight'];
	public  static $returnErrors = false;    //Return error as FALSE instead of exiting. Be very careful about toggling this frequently!

	// setting construct to private will disallow new class instantiation
	private function __construct() 
	{
		// empty
	}

    public static function init(string $sqliteFilename = ""): bool
    {
		$sqliteFilename = zl::$site['pathZerolithData'] . "sqlites/" . zfilter::stringSafe($sqliteFilename); //no funny business
		if(!file_exists($sqliteFilename)) 
		{
			//try to create it
			try { $db = new SQLite3($sqliteFilename); } 
			catch (Exception $e) { zl::fault("zlite: error creating sqlite database: " . $e->getMessage()); }			
		}
		
        if (self::$connection === null) { return self::connectionOpen($sqliteFilename); }
		return true; // a connection had already been made
    }

	// pass SQL to be executed directly
	// this helps with testing but should not be used otherwise
	public static function exec(string $SQL)
	{
		return self::$connection->exec($SQL);
	}

	// opens a connection to a SQLite database
	// by defaults this connect to the db filename specified in zl_config.php
	// call this again if you want to connect to another db
    private static function connectionOpen(string $sqliteFilename=""): bool
    {   
		if (empty($sqliteFilename)) {
			// defaults to using config value if there is no db filename specified
			$sqliteFilename = zl::$set['dbSQLiteDefaultFilename'];
		}
        if (!file_exists($sqliteFilename)) {
            self::fault("zlite: File $sqliteFilename does not exist.");
			return false;
        }

		if (self::$connection) {
			// close existing connection, if already exists
			self::$connection->close();
		}

		ztime::startTimer("zlite_db_connect");
		// passing the flag SQLITE3_OPEN_READWRITE will override the default flag 
		// (which is SQLITE3_OPEN_READWRITE | SQLITE3_OPEN_CREATE)
		// by doing this, we disallow temporary on-disk database creation -
		// that would happen if SQLite3 is instantiated by passing an empty string.
		try {
			self::$connection = new SQLite3($sqliteFilename, SQLITE3_OPEN_READWRITE);
		} catch (Exception $e) {
			self::fault("zlite: Unable to connect to SQLite database: ".$e->getMessage());
			return false;
		}
		ztime::stopTimer("zlite_db_connect");
		return true;
    }

	private static function handleGet(string $type, string $SQL, array $options = []) 
	{
		$defaults = [
			'failMsg' => "Database Error",
			'faultOnBlank' => false
		];
		$options = array_merge($defaults, $options);

		if (!self::$connection) {
			self::fault($options['failMsg'], "zlite: There is no active SQLite connection");
			return false;
		}

		if (!in_array($type, ['getArray', 'getRow', 'getField'], true)) {
			self::fault($options['failMsg'], "zlite: Invalid type passed to handleGet(): $type");
			return false;
		}

		ztime::startTimer("zlite_db_read");
		
		$stmt = self::$connection->prepare($SQL);
		if (!$stmt) {
			self::fault($options['failMsg'], "zlite: Statement preparation failed: ". self::$connection->lastErrorMsg());
			return false;
		}

		$result = $stmt->execute();
		if (!$result) {
			self::fault($options['failMsg'], "zlite: Statement execution failed: ". self::$connection->lastErrorMsg());
			return false;
		}

		$row = $result->fetchArray(SQLITE3_ASSOC);
		$returnVal = [];
		if (!$row) { // empty result
			if ($options['faultOnBlank']) {
				self::fault($options['failMsg'], "zlite: Fault on blank");
				return false;
			}
			// sets the default value to return on empty result
			// on getArray and getRow, this would be empty array (unchanged)
			// on getField, this would be empty string
			if ($type === 'getField') {
				$returnVal = '';
			} elseif ($type === 'getRow' || $type === 'getArray') {
				$returnVal = [];
			}
		} else { // has result
			if ($type === 'getField') {
				// on an associative array like:
				// $arr = ['banana' => 'yellow', 'apple' => 'red'];
				// echo reset($arr); // will print out 'yellow'
				$returnVal = reset($row);
			} elseif ($type === 'getRow') {
				$returnVal = $row;
			} elseif ($type === 'getArray') {
				$returnVal = [$row];
				while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
					$returnVal[] = $row;
				}
			}
		}

		if(zl::$set['debugLevel'] > 2 || zl::$set['debugLog'] || zl::$set['debugLogOnFault']) { 
			self::log($returnVal); 
		} else { 
			self::log();
		}
		return $returnVal;
	}

	// run a SELECT SQL query and return only the first row as an associative array
	// returns the result as an associative array on success and an empty array on error
	// Behavior: Halt program on database access error, halt program if output is blank and faultOnBlank = true
	public static function getRow(string $SQL, string $failMsg = "Database Error", bool $faultOnBlank = false): array
	{
		$options = [
			'failMsg' => $failMsg,
			'faultOnBlank' => $faultOnBlank
		];
		return self::handleGet('getRow', $SQL, $options);
	}

	// fetch a string from a single column SQL request.
	// returns the first cell of the first row of the result or an empty string on error
	// Behavior: Halt program on database access error, halt program if output is blank and faultOnBlank = true
    public static function getField(string $SQL, string $failMsg = "Database Error", bool $faultOnBlank = false): string
	{
		$options = [
			'failMsg' => $failMsg,
			'faultOnBlank' => $faultOnBlank
		];
		return self::handleGet('getField', $SQL, $options);
	}

	// like getRow, but this will return all the rows of the result
	// returns an array containing one array per row of result, or an empty array
	// [
	//     ['name' => 'apple', 'color' => 'red'],
	//     ['name' => 'banana', 'color' => 'yellow'],
	//     ...
	// ]
    public static function getArray(string $SQL, string $failMsg = "Database Error", bool $faultOnBlank = false): array
	{
		$options = [
			'failMsg' => $failMsg,
			'faultOnBlank' => $faultOnBlank
		];
		return self::handleGet('getArray', $SQL, $options);
	}

	public static function getLastInsertID(): int
	{
		return self::$connection->lastInsertRowID();
	}

	private static function handleWrite(string $type, string $SQL, array $options = [])
	{
		$defaults = [
			'failMsg' => "Database Error",
			'returnRowsAffected' => false,
			// these will be passed when called by writeRow()
			'queryMode' => '',
			'writeArray' => [],
			'whereArray' => []
		];
		$options = array_merge($defaults, $options);

		if (!self::$connection) {
			self::fault($options['failMsg'], "zlite: There is no active SQLite connection");
			return false;
		}

		ztime::startTimer("zlite_db_write");

		$stmt = self::$connection->prepare($SQL);
		if (!$stmt) {
			self::fault($options['failMsg'], "zlite: Statement preparation failed: ". self::$connection->lastErrorMsg());
			return false;
		}

		// if called by writeRow(), we try to bind writeArray and whereArray
		if ($type === 'writeRow') {
			$mode = $options['queryMode'];
			if (in_array($mode, ['INSERT', 'UPDATE'])) {
				foreach ($options['writeArray'] as $column => $value) {
					$stmt->bindValue(":$column", $value, self::getValueType($value));
				}
			}
			if (in_array($mode, ['UPDATE', 'DELETE'])) {
				foreach ($options['whereArray'] as $column => $value) {
					// we prefix the binding name with 'where_' to prevent conflict 
					// with the bindings for writeArray
					$stmt->bindValue(":where_$column", $value, self::getValueType($value));
				}
			}
		}

		$result = $stmt->execute();
		if (!$result) {
			self::fault($options['failMsg'], "zlite: Statement execution failed: ". self::$connection->lastErrorMsg());
			return false;
		}

		$countAffected = self::$connection->changes();
		self::log($countAffected);
		if ($options['returnRowsAffected']) {
			return $countAffected;
		} else {
			return true;
		}
	}

    public static function writeRow(string $mode, string $table, array $writeArray = [], $whereArray = [], string $failMsg = "Database Error", bool $returnRowsAffected = false)
	{
		$mode = strtoupper($mode);
		if (!in_array($mode, ['INSERT', 'UPDATE', 'DELETE'], true)) {
			self::fault($failMsg, "zlite: Invalid mode passed to writeRow(): $mode");
			return false;
		}

        if (empty(trim($table))) {
			self::fault($failMsg, "zlite: Blank table sent to writeRow()"); 
			return false; 
		}

		// INSERT and UPDATE query must have a valid $writeArray passed
		if ($mode === 'INSERT' || $mode === 'UPDATE') {
            if (zs::isBlank($writeArray)) {
				self::fault($failMsg, "zlite: writeRow() error, $mode query cannot have a blank writeArray"); 
				return false;
			}
            if (is_array($writeArray) && !zarr::isAssociative($writeArray)) { 
				self::fault($failMsg, "zlite: Non-associative array in writeArray"); 
				return false; 
			}
		}

		// UPDATE and DELETE query must have a valid $whereArray passed
		if ($mode === 'UPDATE' || $mode === 'DELETE') {
             if (zs::isBlank($whereArray)) {
				self::fault($failMsg, "zlite: writeRow() error, $mode query cannot have a blank whereArray"); 
				return false; 
			}
             if (is_array($whereArray) && !zarr::isAssociative($whereArray)) {
				self::fault($failMsg, "zlite: Non-associative array in whereArray"); 
				return false; 
			}
		}

		// build SQL with placeholders
		// for example, by passing in:
		//   $table      = 'myTable'
		//   $writeArray = ['course' => 'BIO', 'enrolled' => 'Y']
		//   $whereArray = ['course' => 'MAT']
		// the query that will be created would look like:
		// for INSERT: 
		//   INSERT INTO myTable (course, enrolled) VALUES (:course, :enrolled)
		// for UPDATE: 
		//   UPDATE myTable SET (course = :course, enrolled = :enrolled) WHERE course = :where_course
		// for DELETE:
		//   DELETE FROM myTable WHERE course = :where_course
		// 
		// the placeholders then will be bound later on with bindValue() to the corresponding values
		switch ($mode) {
			case 'INSERT':
				$columns = implode(', ', array_keys($writeArray));
				$placeholders = ':' . implode(', :', array_keys($writeArray));
				$SQL = "INSERT INTO $table ($columns) VALUES ($placeholders)";
				break;

			case 'UPDATE':
				$set = implode(', ', array_map(fn($col) => "$col = :$col", array_keys($writeArray)));
				$where = implode(' AND ', array_map(fn($col) => "$col = :where_$col", array_keys($whereArray)));
				$SQL = "UPDATE $table SET $set WHERE $where";
				break;

			case 'DELETE':
				$where = implode(' AND ', array_map(fn($col) => "$col = :where_$col", array_keys($whereArray)));
				$SQL = "DELETE FROM $table WHERE $where";
				break;
		}

		$options = [
			'failMsg' => $failMsg,
			'returnRowsAffected' => $returnRowsAffected,
			'queryMode' => $mode,
			'writeArray' => $writeArray,
			'whereArray' => $whereArray
		];
		return self::handleWrite('writeRow', $SQL, $options);
	}

    public static function writeSQL(string $SQL, string $failMsg = "Database Error", bool $returnRowsAffected = false)
	{
		$options = [
			'failMsg' => $failMsg,
			'returnRowsAffected' => $returnRowsAffected
		];
		return self::handleWrite('writeSQL', $SQL, $options);
	}


	// --- internal class functions ---

	// helper method for parameter type binding
	private static function getValueType($value): int {
		if (is_int($value)) return SQLITE3_INTEGER;
		if (is_float($value)) return SQLITE3_FLOAT;
		if (is_null($value)) return SQLITE3_NULL;
		if (is_resource($value) && get_resource_type($value) === 'stream') return SQLITE3_BLOB;
		return SQLITE3_TEXT;
	}

	//log class success/fail states to the debug console
	private static function log
	(
		$out = "", // can be anything (string/array/bool/empty/etc.)
		bool $success = true, 
		$faultData = "", // only ever used by self::fault
		string $timerToStop = ""
	)
	{
		if(!zl::$set['debugger']) { return; } //if debugger is absolutely off, forget accumulating this data
		$debugObject = self::$debugVoice; //add the data output from the library
		if($success) { $debugObject['callData'] = debug_backtrace(0,3)[2]; }
		else { $debugObject['callData'] = debug_backtrace(0,3)[2]; }
		$debugObject['out'] = $out; //any output of the function
		$debugObject['faultData'] = $faultData;
		$debugObject['success'] = $success;
		
		//timer calculation - stop the last one, or manually specified one?
		if($timerToStop == "") { $debugObject['time'] = ztime::stopLastTimer(); }
		else { $debugObject['time'] = ztime::stopTimer($timerToStop); }
		
		zl::deBuffer($debugObject); //out to the debug buffer.
	}


    private static function fault(string $userMsg = "Database error", string $SQLiteError = "")
    {
		self::log(false, false, ["userMsg" => $userMsg, "SQLiteError" => $SQLiteError]);
		
		// if return errors is on, don't die; the next command in the library will return false.
		if(self::$returnErrors) 
		{ 
			zl::faultSoft($userMsg, $SQLiteError); 
			return false;
		}
		else
		{
			zl::fault($userMsg, $SQLiteError . "    " . print_r(debug_backtrace(0,2)[1], true));
		}
	}
}
?>