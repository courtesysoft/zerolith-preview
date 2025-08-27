<?php
//zCurl, efficient, fast, and easy to use extensive cURL library created by Courtesy Software on 11/20/21
//multiCurl async loop originated from rollingcurl by Josh Fraser (www.joshfraser.com)
//simpleMulti async loop originated from paraCurl - https://gist.github.com/nimmneun/10d7e2b65ff2ab1c0414
//
//v0.95 - 11/27/21 - Implemented all methods
//v1.20 - 02/02/23 - Code shorten, consistent input/output on all methods, consistent option application, curl error reason
//v1.25 - 04/03/23 - Allow bare string to be sent as postData, php 8.x compatibility

//Issue 05/02/23: using execute with a $maxConnections below 3 results in execute() falling back to the multiCurlSimple method, which has fixed postData/headers/curlOptions/method; async loop needs work

//11/27/2023 - profiling error - multiple usage of zcurl::curl() results in total events not increasing and total time being counted



//for v1.3:
//needs: make curldata consistently filtered and include curlError
//needs: Below 3 maxConnections, use multiCurlSimple and convert $this->requests array format to multiCurlSimple input format
//needs: when exec called, return array when singleCurl is used for consistency
//needs: Way to pipe arrays of arrays into multiCurlSimple to remove the <3 concurrent connection functionality penalty

//General usage:
//$url = "https://whatevers.com"
//$method = "GET" - or POST, PUT, PATCH, DELETE
//$fullOutput = true returns an associative array, false just returns HTML
//$postData = will take a string or associative array -"raw=some raw stuff" or ['raw' => 'yup']
//$headerData = takes an associative array: ['Content-Type' => 'application/x-www-form-urlencoded']
//$extraOptionData = array version of how curl likes it typed: [CURLOPT_WHATEVER => "value"]

class zcurl
{
	public static $debugVoice = ['libraryName' => 'zcurl', 'micon' => 'sync_alt', 'textClass' => 'zl_orange10', 'bgClass' => 'zl_bgOrange1'];
	
	public static $unsafe = false;                            //Toggle this to disable SSL Cert checks.
	public static $goodHTTPCodes = [200, 301, 400, 429, 403]; //HTTP codes indicating a successful transfer, for error logging.
	public static $maxConnections = 3;                        //Max simultaneous connections for asynchronous curl methods
	
	//for instantiated class calls
	private $multiTimeout = 30;     //timeout for multiCurl
	private $requests = [];         //buffer for instantiated requests
	private $copyOptions = false;   //speed hack for multiCurl - reuse compiled cURL options from first query
	private $clean = true;          //marker that is used to reset
	
	//default curl options; is copied into new connections.
	public static $baseOptions =
	[
		CURLOPT_CONNECTTIMEOUT => 5,            //Time to complete initial handshakes, etc.
		CURLOPT_TIMEOUT        => 45,           //Total amount of time allowed for a CURL call.
		CURLOPT_RETURNTRANSFER => 1,            //Don't print stuff to screen!
		CURLOPT_FOLLOWLOCATION => 1,            //Will follow a 301
		CURLOPT_MAXREDIRS => 3,                 //After this point, we assume the remote server is f*cking with us
		CURLOPT_TCP_FASTOPEN => 1,              //Speed hack: Attempt to use fastopen ( saves some TCP overhead )
		CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4  //Speed hack: Only resolve IPv4
	];
	
	
	//Basic single curl call with no processing option.
	public static function curl($url, $method = "GET", $fullOutput = false, $postData = [], $headerData = [], $extraOptionData = [])
	{
		ztime::startTimer("zl_zcurl_curl");
		$CS = curl_init($url);
		$options = self::compileOptions($url, $method, $fullOutput, $postData, $headerData, $extraOptionData);
		curl_setopt_array($CS, $options);
		
		//return the transfer as a string
		$html = curl_exec($CS);
		$curlData = curl_getinfo($CS);
		$curlError = curl_error($CS);
		
		if($fullOutput)
		{
			//Separate response header & body.
			$results['headers'] = substr($html, 0, $curlData['header_size']);
		    $results['html'] = substr($html, $curlData['header_size']);
			$results['curlData'] = $curlData;
			curl_close($CS);
			self::log($results['html'], $results['curlData'], $curlError);
			return $results;
		}
		else
		{
			curl_close($CS);
			self::log($html, $curlData, $curlError);
			return $html;
		}
	}
	
	//Basic asynchronous curl call where all options are the same.
	public static function multiCurlSimple($urls = [], $method = "GET", $fullOutput = false, $processType = "none", $postData = [], $headerData = [], $extraOptionData = [])
	{
		ztime::startTimer("zl_zcurl_multiSimple");
		
		//compile a cached copy we will reuse + mutate the URL for speed
		$options = self::compileOptions($urls[0], $method, $fullOutput, $postData, $headerData, $extraOptionData);
		
	    $multiHandle = curl_multi_init();
		$handles = []; //Array with curl handles.
		$open = null; //Curl open/not open flag
		
	    foreach ($urls as $key => $url) //Create curl handles and add the global options.
		{
			$handles[$key] = curl_init($url);
			$options[CURLOPT_URL] = $url;
			curl_setopt_array($handles[$key], $options);
		}
	    $curls = $handles; //need this to keep track
	
	    //Asynchronous - Perform the requests requests & dynamically re-fill available slots.
	    while(0 < $open || 0 < count($curls))
	    {
	        if($open < self::$maxConnections && 0 < count($curls)) { curl_multi_add_handle($multiHandle, array_shift($curls)); }
	        curl_multi_exec($multiHandle, $open);
	    }
		
		//Synchronous - Extract downloaded data from curl handles.
		$results = [];
	    foreach ($handles as $key => $handle)
		{
			if(!$fullOutput)
			{
				$results[$key] = self::convertData(curl_multi_getcontent($handle), $processType);
				$curlData = curl_getinfo($handle);
				self::log($results[$key], $curlData, curl_error($handle));
			}
			else
			{
				$results[$key]['curlData'] = curl_getinfo($handle);
				$html = curl_multi_getcontent($handle);
	            $results[$key]['headers'] = substr($html, 0, $results[$key]['curlData']['header_size']);
	            $results[$key]['html'] = self::convertData(substr($html, $results[$key]['curlData']['header_size']), $processType);
				self::log($results[$key]['html'], $results[$key]['curlData'], curl_error($handle));
			}
	        curl_multi_remove_handle($multiHandle, $handle);
	    }
	    curl_multi_close($multiHandle);
		ztime::stopTimer("zl_zcurl_multiSimple");
		return $results;
	}
	
	//log class success/fail states to the zl debugger
	private static function log($out, array $curlData, $curlError = "")
	{
		if(!zl::$set['debugger']) { return; } //if debugger is absolutely off, forget accumulating this data
		$debugObject = self::$debugVoice; //add the voice of the library
		$debugObject['data'] = self::processCurlData($curlData);
		$debugObject['data']['curlError'] = $curlError;
		if($debugObject['data']['success']) { $debugObject['callData'] = debug_backtrace(0,2)[1]; }
		else { $debugObject['callData'] = debug_backtrace(0,2)[1]; }
		
		$debugObject['success'] = $debugObject['data']['success'];
		$debugObject['time'] = ztime::secsToUnits($debugObject['data']['total_time']); //receives data from CURL itself.
		
		if(zl::$set['debugLevel'] > 2 || zl::$set['debugLog'] || zl::$set['debugLogOnFault']) //short output or not?..
		{ $debugObject['out'] = $out; }
		else
		{
			$debugObject['data'] = $debugObject['data']['url'];
			
			//censor the input of the call
			if($debugObject['callData']['function'] == "curl") //censor the third parameter ( postData )
			{
				if(isset($debugObject['callData']['args'][3])) { $debugObject['callData']['args'][3] = "[postData is censored at this debug level]"; }
			}
		}
		
		zl::deBuffer($debugObject); //out to the debug buffer.
	}
	
	//process the curlData from curlinfo; only include necessary fields.
	private static function processCurlData($curlData)
	{
		//compute success/fail from curlData
		if(in_array($curlData['http_code'], self::$goodHTTPCodes) && $curlData['size_download'] != 0)
	    { $success = true; } else { $success = false; }
		
		//compile and return
		return
		[
			"url" => $curlData['url'],
			"http_code" => $curlData['http_code'],
			"size_download" => $curlData['size_download'],
			"total_time" => $curlData['total_time'],
			"redirect_count" => $curlData['redirect_count'],
			"redirect_url" => $curlData['redirect_url'],
			"success" => $success,
			"unsafe" => self::$unsafe
		];
	}
	
	//universal function for compiling cURL options
	public static function compileOptions($url, $method = "GET", $fullOutput = false, $postData = [], $headerData = [], $extraOptionData = [])
	{
		$method = strtoupper($method); //just in case!
		$options = self::$baseOptions; //start with the base options
		$options[CURLOPT_URL] = $url;  //apply the request URL
		
		//in development mode, insert HTTP passwords.
		if(zl_mode == "dev" && !zs::isBlank(zl::$site['curlPasswords']))
		{
			//zl::quipD(zl::$site['curlPasswords']);
			$host = strtolower(parse_url($url)['host']);
			if(isset(zl::$site['curlPasswords'][$host]))
			{
				zl::quipD("Used the defined HTTP password for " . zl::$site['curlPasswords'][$host],"zcurl HTTP password");
				$options[CURLOPT_USERPWD] = zl::$site['curlPasswords'][$host];
			}
		}
		
		//header output?
		if($fullOutput) { $options[CURLOPT_HEADER] = 1; } else { $options[CURLOPT_HEADER] = 0; }
		
		//set method
		if($method == "POST") { $options[CURLOPT_POST] = 1; }
		elseif(in_array($method, ['GET','DELETE','PUT', 'PATCH'])) { $options[CURLOPT_CUSTOMREQUEST] = $method; }
		else { zl::fault("unrecognized HTTP method sent to zCurl"); }
		
		//POST data?
		if(!zs::isBlank($postData)) { $options[CURLOPT_POSTFIELDS] = $postData; }
		
		//unsafe ( ignore SSL ) option
		if(self::$unsafe) { $options[CURLOPT_SSL_VERIFYHOST] = 0; $options[CURLOPT_SSL_VERIFYPEER] = 0; $options[CURLOPT_SSL_VERIFYSTATUS] = 0; }
		
		//overlay extra options if they exist
		if($extraOptionData != []) { $options = array_merge($extraOptionData, $options); }
		
		//send headers
		if($headerData != [])
		{
			$curlHeaders = [];
			foreach($headerData as $key => $value) { $curlHeaders[] = $key . ": " . $value; }
			$options[CURLOPT_HTTPHEADER] = $curlHeaders;
		}
		
		return $options;
	}
	
	//converts data to the desired format for process
	private static function convertData($textString, $processType = "none")
	{
		if($processType == "deJson" || $processType == "deAPipe" || $processType == "dePipe")
		{ return zarr::$processType($textString); } else { return $textString; }
	}
	
	
	/* Instantiated class land */
	
	
	//add a request to the queue
	function addRequest($url, $method = "GET", $postData = [], $headerData = [], $extraOptionData = [])
	{
		//reset the queue first ( if unclean )
		if(!$this->clean) { $this->requests = []; $this->clean = true; }
		
		$this->requests[] = ["url" => $url, "method" => $method, "postData" => $postData, "headerData" => $headerData, "extraOptionData" => $extraOptionData];
	}
	
	//Execute the queue
	//decides which method would be optimal for accuracy and speed.
	//multiCurl is fastest for large jobs but can fail with ones that are below maxConnections.
	//singleCurl is used as a fallback for small jobs.
	//data processing types: none, deJson, dePipe, deAPipe
    public function execute($processType = "none", $fullOutput = false)
    {
		$reqCount = count($this->requests);
		if($reqCount < 1) { zl::quipD("zcurl library says no requests have been sent; returning blank array"); return []; } //say what?
		else if($reqCount == 1) { return $this->singleCurl($processType, $fullOutput); } //do single.
		else if($reqCount < self::$maxConnections) //do them as singles for safety; speed is less important.
		{
			$returnMulti = [];
			foreach($this->requests as $nevermind) { $returnMulti[] = $this->singleCurl($processType, $fullOutput); }
			return $returnMulti;
		}
		else
		{
			if(self::$maxConnections < 3) //multiCurl cannot handle < 3 concurrent connections; use multiCurlSimple
			{
				zl::quipD('Forcing conversion of multiCurl to multiCurlSimple because we are below 3 $maxConnections; be warned that the postData/method/headerData/extraOptionData is fixed to the first request`s values in this mode and this may give inaccurate results',"zcurl::execute()");
				//from: ["url" => $url, "method" => $method, "postData" => $postData, "headerData" => $headerData, "extraOptionData" => $extraOptionData];
				//to:   public static function multiCurlSimple($urls = [], $method = "GET", $fullOutput = false, $processType = "none", $postData = [], $headerData = [], $extraOptionData = [])
				
				$urls = [];
				foreach($this->requests as $request) { $urls[] = $request['url']; }
				$FR = $this->requests[0]; //copy all options to the 
				$this->clean = false;
				return self::multiCurlSimple($urls, $FR['method'], $fullOutput, $processType, $FR['postData'], $FR['headerData'], $FR['extraOptionData']);
			}
			else //big job? let multiCurl rip!!
			{
				return $this->multiCurl($processType, $fullOutput);
			}
		}
    }
	
    //Performs a single curl GET request from the queue
    private function singleCurl($processType, $fullOutput = false)
    {
        $ch = curl_init();
        $request = array_shift($this->requests); //removes a unit from array - to be processed.
        curl_setopt_array($ch, self::compileOptions($request['url'], $request['method'], $fullOutput, $request['postData'], $request['headerData'], $request['extraOptionData']));
		
		$html = curl_exec($ch);
		$curlData = curl_getinfo($ch);
		$curlError = curl_error($ch);
		
		if($fullOutput)
		{
			$headers = substr($html, 0, $curlData['header_size']);
	        $html = self::convertData(substr($html, $curlData['header_size']), $processType);
			self::log($html, $curlData, $curlError);
			return ["curlData" => $curlData, "html" => $html, "headers" => $headers];
		}
		else
		{
			$html = self::convertData($html, $processType);
			self::log($html, $curlData, $curlError);
			return $html;
		}
    }
	
    //Performs multiple curl requests from the queue
    private function multiCurl($processType, $fullOutput = false)
    {
        $masterCurl = curl_multi_init();
		$requestMap = [];
		
		//voodoo optimization? needs perf testing
	    curl_multi_setopt($masterCurl, CURLMOPT_PIPELINING, CURLPIPE_MULTIPLEX);
	    curl_multi_setopt($masterCurl, CURLMOPT_MAX_HOST_CONNECTIONS, self::$maxConnections); //rate limit for the big loop.
	    
        //Start the first batch of requests
        for ($i = 0; $i < self::$maxConnections; $i++)
        {
        
			//fill cache if this option is on.
			if($i == 0 && $this->copyOptions)
			{
				$options = self::compileOptions($this->requests[$i]['url'], $this->requests[$i]['method'], $fullOutput, $this->requests[$i]['postData'], $this->requests[$i]['headerData'], $this->requests[$i]['extraOptionData']);
			}
			
			//set options or use cache
			if($this->copyOptions) { $options['url'] = $this->requests['url']; } //use cache
			else
			{
				//build it from scratch
				$options = self::compileOptions($this->requests[$i]['url'], $this->requests[$i]['method'], $fullOutput, $this->requests[$i]['postData'], $this->requests[$i]['headerData'], $this->requests[$i]['extraOptionData']);
			}
			
			//let's start.
			$ch = curl_init();
            curl_setopt_array($ch, $options);
            curl_multi_add_handle($masterCurl, $ch);
			
			//php 8.0+ kludge way to get a unique ID out of the curl handle
			ob_start(); var_dump($ch); $handleID = preg_replace("/[^0-9]/", "", ob_get_clean());
			
			$requestMap[$handleID] = $i;
        }
		
		//Asynchronous land
		$resultArray = []; //array to return back into synchronous land
        do
        {
            while(($execrun = curl_multi_exec($masterCurl, $running)) == CURLM_CALL_MULTI_PERFORM)
            if($execrun != CURLM_OK) { break; }
            while($done = curl_multi_info_read($masterCurl)) // a request was just completed -- find out which one
            {
				//Start a new request ( it's important to do this before removing the old one )
                if($i < count($this->requests) && isset($this->requests[$i]) && $i < count($this->requests))
                {
                    $ch = curl_init();
					
					//set options or use cache
					if($this->copyOptions) { $options['url'] = $this->requests['url']; }
					else { $options = self::compileOptions($this->requests[$i]['url'], $this->requests[$i]['method'], $fullOutput, $this->requests[$i]['postData'], $this->requests[$i]['headerData'], $this->requests[$i]['extraOptionData']); }
					
					curl_setopt_array($ch, $options);
                    curl_multi_add_handle($masterCurl, $ch);
					
					//php 8.0+ kludge way to get a unique ID out of the curl handle :(
					ob_start(); var_dump($ch); $handleID = preg_replace("/[^0-9]/", "", ob_get_clean());
					$requestMap[$handleID] = $i;
                    $i++;
                }
				
				//php 8.0+ kludge way to get a unique ID out of the curl handle
				ob_start(); var_dump($done['handle']); $idLink = preg_replace("/[^0-9]/", "", ob_get_clean());
				
				$curlData = curl_getinfo($done['handle']);
				if($fullOutput)
				{
					$html = curl_multi_getcontent($done['handle']);
					$resultArray[$requestMap[$idLink]] = array
					(
						"headers" => substr($html, 0, $curlData['header_size']),
			            "html" => self::convertData(substr($html, $curlData['header_size']), $processType),
						"curlData" => $curlData
					);
					self::log($resultArray[$requestMap[$idLink]]['html'], $curlData, curl_error($done['handle']));
				}
				else
				{
					$html = self::convertData(curl_multi_getcontent($done['handle']), $processType);
					$resultArray[$requestMap[$idLink]] = $html;
					self::log($html, $curlData, curl_error($done['handle']));
				}
				
				curl_multi_remove_handle($masterCurl, $done['handle']); //cleanup completed curl handle.
            }

            //Block for data in / output; error handling is done by curl_multi_exec
            if($running) { curl_multi_select($masterCurl, $this->multiTimeout); }

        } while ($running);
		
		//Synchronous land - time to finish
        curl_multi_close($masterCurl);
		$this->clean = false; //mark for cleanup of URL array.
        if(count($resultArray) > 0) { return $resultArray; } else { return []; }
    }
}