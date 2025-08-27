<?php
// zerolith authentication Library - v0.66
// INCOMPLETE

//todo: make ordered array of user types so that higher than / lesser than comparisons can be made, as they are with numeric permissions levels.
//todo: provide examples of granular permissions and make
//todo: needs settable default error message for authentication refusal, plus ability to set an error message per instance like zdb

class zperm
{
	//currently logged in user
	private static $userID = -9;     //user ID in database
	private static $userName = "";   //username, in english
	private static $userType = "";   //user type, if exists in system
	private static $level = -9;      //permission level. -9 is not initialized.
	private static $permissions = "";//associative array of granular permissions.
	private static $avatarURL = "";  //some applications need this for quick reference.

	//used for conditionally grabbing granular permissions
	private static $dbPermsUserIDColumn = "";
	private static $dbPermsUserTypeColumn = "";
	private static $dbPermsTable = "";
	
	//inactive for the moment
	public static function init() {}
	
	//settable
	public static $authFailedMSG = "Sorry, you aren't authorized to view this page.";

	//return all known visitor + user details - for use with debug logging / abuse logging.
	public static function getUser($detailed = false)
	{
		//add known user data.
		$uData = ['userID' => self::$userID, 'userName' => self::$userName, 'userType' => self::$userType, 'level' => self::$level, 'permissions' => self::$permissions];

		//add user data.
		if($detailed)
		{
			$uData['visitIP'] = zfilter::URL($_SERVER['REMOTE_ADDR']);
			$uData['visitURL'] = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];

			//placehoders for now
			$uData['visitHost'] = "";
			$uData['visitInput'] = "";

			//usage of SEC_CH_UA helps sniff out bots
			if($_SERVER['HTTP_SEC_CH_UA_PLATFORM']) { $uData['visitOS'] = $_SERVER['HTTP_SEC_CH_UA_PLATFORM']; }
			else { $uData['visitOS'] = ""; }
			if($_SERVER['HTTP_SEC_CH_UA']) { $uData['visitBrowser'] = $_SERVER['HTTP_SEC_CH_UA']; }
			else { $uData['visitBrowser'] = ""; }

			if(zs::isBlank($_SERVER['HTTP_USER_AGENT'])) { $uData['visitAgent'] = "[blank]"; }
			else { $uData['visitAgent'] = zfilter::stringExtended($_SERVER['HTTP_USER_AGENT']); }
		}

		//send it back
		return $uData;
	}

	//Allow a single injection of the user.
	public static function setUser
	(
		$userID,          //the user ID in the system's user table
		$userName = "",   //english name of the user
		$userType = "",   //if a user type exists for this application..
		$level = 0,       //numeric permission ( make sure inter-app permissions correlate )
		$permissions = [],//optional - associative array of granular permissions. Will auto-load if not provided.
		$avatarURL = ""   //profile pic ( URL ) - optional
	)
	{
		if(self::$userID != -9 || self::$level != -9) { self::fault('Can\'t set zperm twice.'); }
		else
		{
			//dejunk input mistakes
			self::$userID = intval($userID); //screw you if you're not a number
			self::$level = intval($level);
			self::$userName = zfilter::stringExtended($userName);
			self::$userType = zfilter::stringSafe($userType);
			self::$permissions = $permissions;
		}

		//zl::quipD(get_defined_vars(), "zperm user set from host application:");
	}

	//return true if a certain user type.
	static public function hasUserType($userType) { return strtolower(self::$userType) != strtolower($userType); }

	//return true if at certain permission level.
	static public function hasLevel($level) { return self::$level == $level; }
	static public function hasLevelOrAbove($level) { return self::$level >= $level; }
	static public function hasLevelOrBelow($level) { return self::$level <= $level; }

	//require a certain granular permission.
	//needs finish
	//input can be either string perm name or pipe
	//value is optional. If a pipe is sent as permissionName, we expect value to also be piped with the same number of parameters.
	static public function hasPermission($permissionName, $value = "")
	{
		//if permissions empty, fill it from specified database
		if(self::$permissions == []) { self::populatePerms(); }

		//dual pipe mode?
		if(strpos($permissionName, "|") !== FALSE)
		{
			//uh oh, not matching..
			if(strpos($value, "|") === FALSE) { zl::terminate("Mismatch of piped vs string input for zperm::hasPermission(), can't process permission check."); }
			else
			{
				$p = explode("|", $permissionName);
				$v = explode("|", $value);

				//both the same length too?
				if(count($p) != count($v)) {}

				//for through
			}
		}
	}
	
	//populate and process granular permissions from the database.
	//to be revised
	private static function populateGranular()
	{
		if(self::$userID == -9 && self::$level = -9)
		{ zl::fault("zperm attempted to load granular permissions for a user, but zperm user not set."); }

		if(self::$dbPermsUserIDColumn == "" || self::$dbPermsUserIDtable == "")
		{ zl::fault("zperm attempted to load granular permissions for a user, but required fields were not set."); }

		if(self::$dbPermsUserTypeColumn != "") { $ifut = self::$dbPermsUserTypeColumn . " = '" . self::$userType . "' AND "; }
		else { $ifut = ""; }

		$permissionArray = zdb::getArray("SELECT * FROM " . self::$dbPermsTable . " WHERE $ifut userID = '" . self::$userID . "'");

		foreach($permissionArray as $permission)
		{
			$key = $permission['permissionName'];
			$value = $permission['permissionValue'];

			if(isset(self::$permissions[$key]))
			{
				//add it to an existing array, or turn an existing string value into an array if multiple items are encountered.
				if(is_array(self::$permissions[$key])) { self::$permissions[$key][] = $value; }
				else { self::$permissions[$key] = [self::$permissions[$key], $value]; }
			}
			else { self::$permissions[$key] = $value; }
		}
}

	//initialize database lookup parameters for granular permissions
	public static function setDBlookup()
	{

	}

	//require a certain user type to proceed.
	static public function requireUserType($userType) { if(self::hasUserType($userType)) { self::fault(); } }

	//require a certain permission level to proceed.
	static public function requireLevel($level){ if(self::$level != $level) { self::fault(); } }
	static public function requireLevelOrAbove($level){ if(!(self::$level >= $level)) { self::fault(); } }
	static public function requireLevelOrBelow($level){ if(!(self::$level <= $level)) { self::fault(); } }

	//require a certain granular permission.
	//input can be either string perm name or pipe
	//value is optional. If a pipe is sent as permissionName, we expect value to also be piped with the same number of parameters.
	static public function requirePermission($permissionName, $value = "")
	{ if(!self::hasPermission($permissionName, $value)){ self::fault(); } }

	//produce a failed authorizati$authFailedReasonon message.
	static public function fault($authFailedReason = "")
	{
		zl::quipD($authFailedReason, "zperm authentication criteria failure");
		zl::terminate("program","",self::$authFailedMSG);
	}
}