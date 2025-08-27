<?php
//Zero Karma - Fast, extensive, safe fail2ban-like karma judgement mechanism. (c)2022 Courtesy Software
//Allows for app ban, intermediary punishment, and reward enforcement via hints sent manually and automatically, and a rolling score per IP.

//v0.5 Concept as of 01/29/23 - non working

class zkarma
{
	//affects the rates for judgement
	private static $bigReward = 10;        //karma an IP gets for a "very good" action.
	private static $bigPunishment = 10;    //karma an IP gets for a "very bad" action.
	private static $interfereMultiple = 5; //total negative karma ( multiple of bigPunishment ) before interfering?
	private static $banMultiple = 10;      //total negative karma ( multiple of bigPunishment ) before banning?
	private static $banHours = 1;          //default time for ban. Each additional ban multiplies this time.
	private static $forceMultiplier = 1;   //a multiplier of how long punishments last.
	private static $banMemoryDays = 365;   //cull ban history beyond this length, and write a new compiled record.
	private static $karmaMemoryDays = 30;  //cull karma memory beyond this length.
	
	//judge the user based on their past behavior, and dole out a punishment up front.
	public static function inspect()
	{
		return; //short circuit for now.
		
		$ipAddress = $_SERVER['REMOTE_ADDR'];
		
		//lookup the long bans table.
		zdb::getRow("SELECT * FROM zl_karmaBans WHERE ipAddress ='" . $ipAddress .  "'");
		
		//is the user banned?
		if(isset($bla))
		{
		
		}
		
	}
	
	//occasionally judge the overall picture and adjust the force multiplier accordingly.
	public static function judgeOverall()
	{
	
	}
	
	//good behavior; false = user gave valid input, true = user gave a contribution to the system.
	public static function good($reason, $important = false)
	{
		return; //short circuit for now.
		
		if(!$important) { $level = self::$bigReward; }
		else { $level = 1; } self::alterScore($level, $reason);
	}
	
	//bad behavior; false = user gave invalid input, true = user .
	public static function bad($reason, $important = false)
	{
		return; //short circuit for now.
		
		//add the point.
		if(!$important) { $level = -self::$bigReward; } else { $level = -1; } self::alterScore($level, $reason);
		
		//do we need a ban?
		
			//lookup the short judgement table.
			$karmas = zdb::getRow("SELECT points FROM zl_karma WHERE ipAddress ='" . $_SERVER['REMOTE_ADDR'] .  "'");
			
			//calculate the total karma.
			$karmaTotal = 0;
			foreach($karmas as $karma) { $karmaTotal =+ $karma['points']; }
			
			//make a judgement
			if($karmaTotal > (self::$banMultiple * self::$bigPunishment))
			{
			
			}
			elseif($karmaTotal > (self::$interfereMultiple * self::$bigPunishment))
			{
			
			}
	}
	
	
	//cleans zkarma records.
	public static function cleanRecords()
	{
	
	}
	
	public static function alterScore($direction, $reason)
	{
		$ipAddress = $_SERVER['REMOTE_ADDR'];
		
		//is this deserving of a ban?
	}
	
	//ban a user from the system.
	public static function ban($ipAddress)
	{
		//nuke all short term records.
		
		//write a ban with an expiration date
	}
	
	//unban a user from the system
	public static function unban($ipAddress)
	{
	
	}
	
	//piss off the bot with random delays and strings.
	public static function interfere($ipAddress)
	{
	
	}
	
	//Nope..
	public static function deny($ipAddress)
	{
	
	}
	
	//show warning message to the user.
	public static function showAbuseMsg()
	{
		echo "you bad!";
	}
}
?>