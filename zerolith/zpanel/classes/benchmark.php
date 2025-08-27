<?php
class benchmark
{
	var $performanceTime;
	var $crawlTime;
	var $hammerTime;
	var $firstURL;
	var $firstTime = true;
	var $URLArray = [];
	var $URLseen = [];
	var $goodHTTPCodes = [200, 301, 400, 429, 403];
	
	function __construct() {}
	
	function reset() {}
	
	function cleanCrawl()
	{
		array_multisort(array_column($this->URLArray, 'valid'), SORT_ASC, $this->URLArray);
	}
	
	//hammer the URLs with repeated requests.
	function hammer() {}
	
	//get initial URLs
	function crawl($url, $depth = 5, $fromURL = "")
	{
		//mark initial URL
		if($this->firstTime)
		{
			$this->firstTime = false;
			$this->firstURL = $url;
		}
		
		//prevent URLs from getting hit again.
		if(isset($this->URLseen[$url]) || $depth === 0)
		{
			return;
		}
		$this->URLseen[$url] = true;
		
		//zfilter out incorrect URLs.
		if($this->contains($url, "#") || $this->contains($url, "mailto:"))
		{
			return;
		}
		
		$curlResponse = $this->curlGet($url);
		$resultTime = $curlResponse['data']['total_time'] - $curlResponse['data']['namelookup_time'];
		
		if(in_array($curlResponse['data']['http_code'], $this->goodHTTPCodes) && $curlResponse['data']['size_download'] != 0)
		{
			$valid = true;
		}
		else
		{
			$valid = false;
		}
		
		$this->URLArray[] = ["url" => $url, "fromurl" => $fromURL, "time" => $resultTime, "valid" => "true", "bytes" => $curlResponse['data']['size_download'], "HTTPcode" => $curlResponse['data']['http_code'], "valid" => $valid];
		if(!$valid)
		{
			return;
		} //don't bother parsing the URL if it is invalid.
		
		//don't go further into the foreign site.
		if(!$this->contains($url, $this->firstURL))
		{
			return;
		}
		
		$dom = new DOMDocument('1.0');
		@$dom->loadHTML($curlResponse['string']);
		$anchors = $dom->getElementsByTagName('a');
		
		foreach($anchors as $element)
		{
			$href = $element->getAttribute('href');
			if(0 !== strpos($href, 'http'))
			{
				$path = '/' . ltrim($href, '/');
				if(extension_loaded('http'))
				{
					$href = http_build_url($url, ['path' => $path]);
				}
				else
				{
					$parts = parse_url($url);
					$href = $parts['scheme'] . '://';
					if(isset($parts['user']) && isset($parts['pass']))
					{
						$href .= $parts['user'] . ':' . $parts['pass'] . '@';
					}
					$href .= $parts['host'];
					if(isset($parts['port']))
					{
						$href .= ':' . $parts['port'];
					}
					@$href .= dirname($parts['path'], 1) . $path;
				}
			}
			
			$this->crawl($href, $depth - 1, $url);
		}
	}
	
	function crawlReport()
	{
		//establish vars
		$totalRequests = count($this->URLArray);
		$totalCrawlTime = 0;
		?>
		<table class="zl_table">
		<tr>
			<th>URL</th>
			<th>Referrer</th>
			<th>Time</th>
			<th>Byes</th>
			<th>Code</th>
		</tr><?php
		
		foreach($this->URLArray as $URLData)
		{
			$totalCrawlTime = $totalCrawlTime + $URLData['time'];
			
			if(!$URLData['valid'])
			{
				$bgcolor = ' style="background-color:pink !important"';
			}
			else
			{
				$bgcolor = "";
			}
			echo "<tr" . $bgcolor . '><td><a href="' . $URLData['url'] . '" target="_blank">' . $this->shorten($URLData['url'], 75) . '</a></td><td><a href="' . $URLData['fromurl'] . '" target="_blank">' . $this->shorten($URLData['fromurl'], 50) . "</a></td><td>" . $this->convertSecsToUnits($URLData['time']) . "</td><td>" . $URLData['bytes'] . "</td><td>" . $URLData['HTTPcode'] . "</td></tr>\n";
		}
		?></table><?php
		
		echo "<br>Total URLs: " . $totalRequests;
		echo "<br>Total crawl ztime: " . $this->convertSecsToUnits($totalCrawlTime);
		echo "<br>Reqs/sec: " . sprintf("%0.1f", floatval($totalRequests / $totalCrawlTime));
	}
	
	private function startPerformance() { $this->performanceTime = microtime(true); } //start performance test.
	
	private function stopPerformance() //stop performance test.
	{
		return floatval(microtime(true) - $this->performanceTime);
		$this->startPerformance();
	}
	
	public function convertSecsToUnits($numSeconds)
	{
		if($numSeconds >= 86400)
		{
			return sprintf("%0.1f", floatval($numSeconds / 86400)) . " days";
		}
		else if($numSeconds >= 3600)
		{
			return sprintf("%0.1f", floatval($numSeconds / 3600)) . " hours";
		}
		else if($numSeconds >= 60)
		{
			return intval($numSeconds / 60) . " minutes";
		}
		else
		{
			return sprintf("%0.3f", floatval($numSeconds)) . " seconds";
		}
	}
	
	private function contains($haystack, $needle) //replacement for bare strpos..
	{
		if(strpos(strtolower($haystack), strtolower($needle)) !== false)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
	
	//recieve data from Canvas.
	private function curlGet($URL)
	{
		//send information over to canvas server.
		$C = curl_init($URL);
		
		curl_setopt($C, CURLOPT_POST, 0);
		curl_setopt($C, CURLOPT_SSL_VERIFYHOST, 0);
		curl_setopt($C, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($C, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($C, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($C, CURLOPT_CONNECTTIMEOUT, 3);
		curl_setopt($C, CURLOPT_TIMEOUT, 10); //timeout in seconds
		
		$cOutput = trim(curl_exec($C), " ");
		$cData = curl_getinfo($C);
		curl_close($C);
		return ["string" => $cOutput, "data" => $cData];
	}
	
	private function shorten($textString, $limit = 0)
	{
		if($limit < 4)
		{
			return $textString;
		}
		if(strlen($textString) > $limit)
		{
			$textString = substr($textString, 0, ($limit - 3)) . "...";
		}
		return $textString;
	}
}

?>
