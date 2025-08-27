<?
	//handle amazon bounces - unsubscribes from mailing list. Completed on 03/31/16 - DS
	//update 2021
	
	exit();
	//01/21/2023 - need to finish zerolith version - DS
	
	ini_set('always_populate_raw_post_data', '1');
	$superRawInput = file_get_contents("php://input");
	
	//inject test data here.
	$rawInput = json_decode(iconv(mb_detect_encoding($superRawInput, mb_detect_order(), true), "UTF-8", $superRawInput));

	//show any interpretation errors to sending client
	if(is_empty($rawInput)) { echo "provided no input?"; exit; }
	echo "Message recieved. Errors: [" . json_last_error_msg() . "]<br>";

	if(!isset($rawInput->Message) || !contains($rawInput->TopicArn, "your_bounces") ) //prevent fraud by verifying topic ARN.
	{ mailQueue(email_debug, "debug", "amazon bounce - possibly fraudulent", "Raw input: " . $superRawInput, email_support, email_support); exit; }
	
	$messageInput = $rawInput->Message;
	$bounceObject = json_decode(str_replace(array("]", "["),"",$messageInput)); //this is nested JSON, cut a level!
	
	//form bounce array that our system can handle.
	$responseData = array("type" => $bounceObject->notificationType);
	
	if($responseData['type'] == "Bounce")
	{
		$responseData['subType'] = $bounceObject->bounce->bounceType;
		$responseData['reason'] = $bounceObject->bounce->bouncedRecipients->diagnosticCode;
		$responseData['emailAddress'] = $bounceObject->bounce->bouncedRecipients->emailAddress;
		$responseData['reporter'] = $bounceObject->bounce->reportingMTA;
	}
	elseif($responseData['type'] == "Complaint")
	{
		$responseData['subType'] = $bounceObject->complaint->complaintFeedbackType;
		$responseData['reason'] = "User clicked the angry button.";
		$responseData['emailAddress'] = $bounceObject->complaint->complainedRecipients->emailAddress;
		$responseData['reporter'] = $bounceObject->complaint->userAgent;
	}
	else //dunno how to deal with other data.
	{
		mailQueue(email_debug, "debug", "amazon bounce [not understood]", "Partially Interpreted Response: " . print_r($bounceObject, true) . "\n\nRaw Input:\n\n" . $superRawInput, email_support, email_support); exit;
	}
	
	//not enough data in the response?
	if($responseData['emailAddress'] == "" || $responseData['type'] == "")
	{
		mailQueue(email_debug, "debug", "amazon bounce [not understood]", "Interpreted Response: " . print_r($responseData, true) . "\n\nRaw Input:\n\n" . $superRawInput, email_support, email_support); exit;
	}
	
	//respond to response
	if($responseData['type'] == "Complaint")
	{
		//on unsubscribe list yet? if not, add them...
		if(count(getCount("emailUnsubscribed", "address = '" . $responseData['emailAddress'] . "'")) == 0)
		{
			dbExecute("INSERT INTO emailUnsubscribed (address) VALUES ('" . $responseData['emailAddress'] . "')");
			$actionTaken = "Complaint: Added " . $responseData['emailAddress'] . " to unsubscribe list\n";
		}
	}
	elseif($responseData['type'] == "Bounce")
	{
		//if permanent, add 3 ### to indicate address busted.
		if($responseData['subType'] == "Permanent") { $severity = "###"; } else { $severity = "#"; }
		
		$rowCount = 0;
		$rowCount += dbExecute("UPDATE customer SET email = '" . $responseData['emailAddress'] . $severity . "' WHERE email LIKE '%" . $responseData['emailAddress'] . "%'" , "Y");
		$rowCount += dbExecute("UPDATE customer SET email2 = '" . $responseData['emailAddress'] . $severity . "' WHERE email2 LIKE '%" . $responseData['emailAddress'] . "%'", "Y");
		$actionTaken = "Bounce: Added " . $severity . " to " . $rowCount . " customers with address: " . $responseData['emailAddress'] . "\n";
	}
	
	//email me with results..
	//mailQueue(email_debug, "debug", "amazon bounce [understood]", $actionTaken . "\n\nInterpreted Response:\n\n" . print_r($responseData, true) . "\n\nRaw Input:\n\n" . $superRawInput . "\n\n", email_support, email_support);	
?>