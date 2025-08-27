<?php
// Zerolith Email Library
// v0.4  - 12/24/2022 semi-functional - only mail() works.
// v1.40 - 01/20/2023 completed new design with consolidated tables.
// v1.50 - needs final testing - incomplete

//Todo: Backport better debugging and dev email forwarding from other library
//Todo: Make PHPmailer debug only run on failure
//Todo: New email queue runner
//Todo: Timing data has problems, fix

class zmail
{
	public static $debug = true;   //enable this for phpmailer debug output when there are errors.
	public static $header = ['html' => '<table width="100%" cellpadding="5"><tr><td><font face="Verdana, Arial">', 'text' => ''];
	public static $footer = ['html' => '</font></td></tr></table>', 'text' => ''];
	public static $dbMail = "zl_mail"; //email log table name
	public static $dbUnsubscribed = "zl_mailUnsubscribed"; //email unsubscribed table name
	private static $debugVoice = ['libraryName' => "zmail", 'micon' => "mail", 'textClass' => "zl_orange10", 'bgClass' => "zl_bgOrange1"];
	
	public static function init()
	{
		require(zl::$site['pathZerolithClasses'] . "3p/PHPMailer/PHPMailer.php");
		require(zl::$site['pathZerolithClasses'] . "3p/PHPMailer/SMTP.php");
		require(zl::$site['pathZerolithClasses'] . "3p/PHPMailer/Exception.php");
	}
	
	//shortcuts for sending emails to users specified in config file
	public static function sendToOwner($subject, $message, $category = "owner") 
	{ return self::send(zl::$site['emailOwner'], 0, $subject, $message, $category); }
	public static function sendToSupport($subject, $message, $category = "support") 
	{ return self::send(zl::$site['emailSupport'], 0, $subject, $message, $category); }
	public static function sendToDebug($subject, $message, $category = "debug") 
	{ return self::send(zl::$site['emailDebug'], 0, $subject, $message, $category); }
	
	//Sends an email immediately; reusing the array function.
	//'to' can contain a piped list of multiple email addresses.
	//example:: sendLater('bob@gmail.com', 1234, 'your order is ready, 'bla bla' 'orders', 'system@yeehaw.com')
	public static function send($to, $toUserID, $subject, $message, $category, $from = "", $replyto = "", $attachmentPath = "")
	{
		$result = self::sendArray([get_defined_vars()]); //straight pipe it
		return ($result['success'] == 1); 
	}

    //Queues an individual email, for use with large batches of emails where mail sending waits are unacceptable.
	//Processed by runMailQueue.php via cron.
	//same syntax as above
    public static function sendLater($to, $toUserID, $subject, $message, $category, $from = "", $replyto = "", $attachmentPath = "")
    {
		ztime::startTimer("zl_mailQueue");
        $toFiltered = self::filterEmailAddresses($to);

		if(zs::isBlank($from)) { $from = zl::$site['emailSupport']; } //default
		if(zs::isBlank($replyto)) { $replyto = $from; }

		//form write array from input
		$WA = compact("to","toUserID","subject","message","category","from","replyto","attachmentPath");

        //malformed email?
        if(strlen($toFiltered) < 6 || $subject == "" || $message == "") //need a better check here with filter_var($email, FILTER_VALIDATE_EMAIL)
        {
			$WA['status'] = "failed";
			$WA['timeSent'] = zdb::now();
			$WA['resultData'] = "[malformed][at queue]";
        }
        else //good email
        {
			$WA['status'] = "queued";
			$WA['resultData'] = "[queued]";
        }

		$success = zdb::writeRow("INSERT", self::$dbMail, $WA); //success state is the write state
		if($WA['status'] == "failed") { $success = false; } //except if we had an error wih malformation

	    //debug and return
		if(zl::$set['debugLevel'] > 2) { self::log($WA, $success, "zl_emailQueue", 1); }
		else { self::log($WA['resultData'], $success, "zl_emailQueue", 1); }
		return $success;
    }

    //takes a direct database feed from runEmail and sends emails out as a group - fast!
	//new version also takes a single email from mail()
    public static function sendArray($emailArray) //default
    {
		//track emails to return back to the queue
		$result = ['success' => 0, 'fail' => 0];
		$PM = self::getPmail(); //get initialized phpmailer object
	    
        foreach($emailArray as $email)
        {
			ztime::startTimer("zl_mail"); //timer will be stopped by writeMailStatus()

	        //came from the database but not marked as 'sending'; possible desync in emailQueue sending, handle it
			if(isset($email['ID']) && $email['status'] == "sending")
			{
				zl::fault("Queued email was sent to sendArray but didn't have 'sending' status; bombing to prevent unintended emails from being sent from the queue.");
			}
			
			//immediately fail on this one.
			if($email['subject'] == "" || $email['message'] == "")
			{
				$email['status'] = "failed"; $email['resultData'] = "[malformed][subject/msg]";
				self::writeMailStatus($email);
				$result['fail']++;
				continue;
			}
			
			//verify multiple mail addresses if they exist.
            $emailAddrs = explode("|", $email['to']);
			$hasValidAddress = false;

            foreach($emailAddrs as $emailAddr)
            {
				//any reasons for it being invalid..?
				$lastReason = "";
				if(!filter_var($emailAddr, FILTER_VALIDATE_EMAIL))       { $lastReason = "[malformed][emailaddress]"; }
				else if(zs::containsCase($emailAddr, "###"))             { $lastReason = "[3bounces]"; }
				else if(in_array($emailAddr, zl::$set['mailBlackList'])) { $lastReason = "[blacklist]"; }
				else
				{
					$hasValidAddress = true;
					$filteredAddress = str_replace(["#", "*"], "", $emailAddr);
					$PM->addAddress($filteredAddress, $filteredAddress); //strip out any existing # before sending.
				}
            }
			
			//if no addresses were valid.. then this email totally failed.
			if(!$hasValidAddress)
			{
				//Clear things and stop processing this email immediately
                $PM->clearAllRecipients(); $PM->clearReplyTos();

				$email['status'] = "failed"; $email['resultData'] = $lastReason;
				self::writeMailStatus($email);
				$result['fail']++;
				continue;
			}
			
			//ok, let's actually do it!
	        
	        //auto-set these values if missing.
	        zs::ifBlankFill($email['from'], zl::$site['emailSupport']);
			zs::ifBlankFill($email['replyto'], $email['from']);
            $PM->setFrom($email['from'], zl::$site['name']);
            $PM->addReplyTo($email['replyto'], $email['replyto']);

			if($email['attachmentPath'] != "")
			{
				$string =@file_get_contents($email['attachmentPath']);
				$PM->AddStringAttachment($string, $email['attachmentPath']);
			}
			$PM->Subject = $email['subject'];

            if(strpos($email['message'], "<") !== FALSE) //world's fastest and worst HTML detection
            {
				$PM->ContentType = 'text/html'; $PM->IsHTML(true);
				$PM->Body = self::$header['html'] . $email['message'] . self::$footer['html'];
            }
            else
            {
                $PM->ContentType = 'text/plain'; $PM->IsHTML(false);
				$PM->Body = self::$header['text'] . $email['message'] . self::$footer['text'];
            }
			
			//finally send the dang thing
			if(zl::$set['mailOn'])
			{
				ob_start(); //start the PHPmailer error output capture
				if(!$PM->send())
				{
					sleep(1); //retry!
					if(!$PM->send())
					{
						//fill the error data we're about to write.
						$email['resultData'] = "[fail]<br><br>PHPmailer data:<br>" . ob_get_clean();
						$email['status'] = "failed";
					}
				}
			}
			else //mail was off so let's leave a note.
			{ $email['resultData'] = "[success] zmail turned off; email marked as successful."; }
			
			if(isset($email['status']) && $email['status'] == "failed") //fail
			{ $result['fail']++; }
			else //success
			{
				$result['success']++;
				$email['status'] = "sent";
				$email['resultData'] = "[success]";
			}
			
			//Clear all addresses and such for next loop
            $PM->clearAllRecipients(); $PM->clearReplyTos();

			//write status of the email and stop the timer
			if(zl::$set['mailOn']) { self::writeMailStatus($email, ob_get_clean()); }
			else { self::writeMailStatus($email, "[Mail was turned off - not sent]"); }
        }

		//we done; return results array
	    ob_start(); $PM->smtpClose(); ob_get_clean();
        return $result;
    }

	//------ for mostly internal use  ------

	//log class success/fail states to the debug console
	//set to private once mailWrap.php() no longer needs this
	public static function log($out = "", $success = true, $timerToStop = "zl_mail", $backtraceOffset = 2)
	{
		if(!zl::$set['debugger']) { return; } //if debugger is absolutely off, forget accumulating this data
		$debugObject = self::$debugVoice; //add the data output from the library
		$debugObject['callData'] = debug_backtrace(0,3)[$backtraceOffset];
		$debugObject['out'] = $out; //any output of the function
		$debugObject['success'] = $success;
		$debugObject['time'] = ztime::stopTimer($timerToStop);
		zl::deBuffer($debugObject); //out to the debug buffer.
	}

	//return a standard phpmailer object
	//not efficient, but effectively fully refreshes the class
	private static function getPmail()
	{
		$PM = new PHPMailer\PHPMailer\PHPMailer(true);
		$PM->isSMTP();

		if(self::$debug && zl::$set['debugLevel'] > 3) { $PM->SMTPDebug = zl::$set['debugLevel'] -2; } else { $PM->SMTPDebug = 0; }

		$PM->Username      = zl::$set['mailUser'];      $PM->Password = zl::$set['mailPass'];
		$PM->Host          = zl::$set['mailHost'];      $PM->Port     = zl::$set['mailPort'];
		$PM->SMTPSecure    = zl::$set['mailSecurity'];  $PM->SMTPAuth = zl::$set['mailSMTPAuth'];
		$PM->SMTPKeepAlive = zl::$set['mailKeepalive']; return $PM;
	}

	//return a named status based on the email address's format, any #/* codes.
	//* = unsubscribed.
	//### = invalid ( has bounced before )
	public static function judgeEmailAddress($email, $emailPurpose = "system", $unsubscribedArray = [])
	{
		if(count($unsubscribedArray) == 0) { $unsubscribedArray = self::getUnsubscribedList(); }

		if($emailPurpose != "system") { if(in_array(str_replace(["#","*"], "", $email), $unsubscribedArray))
		{ return "unsubscribed"; } }

		if(zs::isBlank($email)){ return "blank"; }
		if(zs::containsCase($email, "###") || !filter_var($email, FILTER_VALIDATE_EMAIL)) { return "invalid"; }
		return "valid";
	}

	//blank out blacklisted or invalid emails from 'to', for use with sendLater
    public static function filterEmailAddresses($emailString)
    {
    	$emailString = trim($emailString);
        if($emailString == "") { return ""; }

        if(!zs::containsCase($emailString, "|")) //single email without pipe.
        {
            if(in_array($emailString, zl::$set['mailBlackList'])) { return ""; } else { return($emailString); }
        }
        else //piped.
        {
        	$emailAddrs = explode("|", $emailString);
        	$eacount = count($emailAddrs);
			for($i = 0; $i < $eacount; $i++)
	        {
	            if(zs::containsCase($emailAddrs[$i], "###")|| in_array($emailAddrs[$i], zl::$set['mailBlackList']))
				{ $emailAddrs[$i] = ""; }
	        }
	        $emailString = implode("|", $emailAddrs);
            return $emailString;
        }
    }

	//either updates ( for queue ) or inserts a new log of a sent email using a standardized $email array
	private static function writeMailStatus($email, $phpMailerDebugOutput = "")
	{
		$email['timeSent'] = zdb::now(); //stamp it!
		if($email['status'] != "sent")
		{
			$email['resultData'] .= "\n" . $phpMailerDebugOutput; //append phpmailer debug info if we had a fail
			$success = false;
		}
		else { $success = true; }

		if(zl::$set['debugLevel'] > 2) { self::log($phpMailerDebugOutput, $success, "zl_mail"); }
		else { self::log($email['resultData'], $success, "zl_mail"); }

		if(isset($email['ID'])) //came from the database
		{
			$WA = ['status' => $email['status'], 'resultData' => $email['resultData'], 'timeSent' => $email['timeSent']];
			zdb::writeRow("UPDATE", self::$dbMail, $WA, ["ID" => $email['ID']]);
		}
		else //sent straight via mail()
		{
			//if(!in_array($email['to'], zl::$set['emailIgnorelist'])) { zdb::writeRow("INSERT", self::$dbMail, $email); }
			zdb::writeRow("INSERT", self::$dbMail, $email);
		}
	}


	//------ higher level & other functions -------


	//return a flat list of unsubscribed users, pom specific
	public static function getUnsubscribedList()
	{ return array_column(zdb::getArray("SELECT * FROM " . self::$dbUnsubscribed), "address"); }

	//not used
	//extract emails from customer raw db array output containing user data
	public static function extractEmailsFromUsers($userArray)
	{
		$alreadyUsedArray = [];
		$resultCount = array("invalid" => 0, "duplicate" => 0, "unsubscribed" => 0, "blank" => 0, "valid" => 0);

		$unsubscribedArray = self::getUnsubscribedList();

		//fail if wrong data type sent.
		if(!isset($userArray[0]['email'])) { return []; }
		$emailArray = [];

		for($i = 0; $i < count($userArray); $i++)
		{
			if(isset($alreadyUsedArray[$userArray[$i]['email']])) { $resultCount['duplicate']++; }
			else
			{
				$result1 = self::judgeEmailAddress($userArray[$i]['email'], "spam", $unsubscribedArray);
				if($result1 == "valid")
				{
					$alreadyUsedArray[$userArray[$i]['email']] = $userArray[$i]['email'];
					$emailArray[] = ["email" => $userArray[$i]['email'],"first" => $userArray[$i]['first'], "last" => $userArray[$i]['last'], "username" => $userArray[$i]['username']];
					$resultCount['valid']++;
				}
				else { $resultCount[$result1]++; }
			}

			if(isset($alreadyUsedArray[$userArray[$i]['email2']])) { $resultCount['duplicate']++; }
			else
			{
				$result2 = self::judgeEmailAddress($userArray[$i]['email2'], "spam", $unsubscribedArray);
				if($result2 == "valid")
				{
					$alreadyUsedArray[$userArray[$i]['email2']] = $userArray[$i]['email2'];
					$emailArray[] = ["email" => $userArray[$i]['email2'],"first" => $userArray[$i]['first'], "last" => $userArray[$i]['last'], "username" => $userArray[$i]['username']];
					$resultCount['valid']++;
				}
				else { $resultCount[$result2]++; }
			}
		}

		//compile counts
		$resultCount['totalInspected'] = 0;
		foreach($resultCount as $resultCountItem){ $resultCount['totalInspected'] += $resultCountItem; }

		//return array
		return ["emailArray" => $emailArray, "resultTotals" => $resultCount];
	}
}

zmail::init(); //initialize and load phpmailer
?>