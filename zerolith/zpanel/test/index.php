<?php
//__________                  .__  .__  __  .__
//\____    /___________  ____ |  | |__|/  |_|  |__
//  /     // __ \_  __ \/  _ \|  | |  \   __\  |  \
// /     /\  ___/|  | \(  <_> )  |_|  ||  | |   Y  \
///_______ \___  >__|   \____/|____/__||__| |___|  /
//        \/   \/                                \/
//
//	Zerolith Test Suite
//
//	All-in-one browser, functional and unit tests with beautiful output.
//
//	Your Project tests
//		Location ➡️ project/tests
//	Zerolith tests
//		Location ➡️ project/zerolith/zpanel/test/tests
//		Example  ➡️ project/zerolith/zpanel/test/examples
//

//DOES NOT STORE DATA IN SAFE DIRECTORY, DO NOT USE YET 08/21/2025 - DS

class ztest]\[p]1234457`109287disabled
{
	public static $testsProject = ['../../../ztests', '']; // Add your project specific test folder(s) here!
	public static $testsZerolith = ['./tests']; // Zerolith specific tests.
	public static $config = '../../../zl_config.php'; // Your project Zerolith config location.

	public static $testUrl = 'http://127.0.0.1';
	public static $testResults = [];
	public static $testNumber = 0;
	public static $testLoaded = [];

	public static $web = true; // Turn web browser tests on or off globally.
	public static $webImageSave = './web'; // Base directory on server to save images.
	public static $webImageView = '/web'; // Base URL on server to view images.
	public static $webSizeX = '1920';
	public static $webSizeY = '1080';
	public static $webDriver = 'http://127.0.0.1:9515'; // WebDriver host address.
	public static $webAuth = []; // Stores HTTP Authentication for domains. See self::webAuth()
	public static $webSession = 0; // Will be set if web browser session is active.

	public static $curlCookies = './web/cookies.txt';

	// Unit / logic tests.
	public static function test($input, $rule, $goal='', $name='')
	{
		self::$testNumber++;
		$success = false;
		$message = '';

		try // Safely catch any fatal PHP errors and still continue.
		{
			// Ensure input doesn't cause display side effects.
			$inputClean = strval($input);
			if(strlen($inputClean) > 50) { $inputClean = htmlentities(substr($inputClean, 0, 100).' ... '); } else { htmlentities($inputClean); }
			$goalClean = strval($goal);
			if(strlen($goalClean) > 50) { $goalClean = htmlentities(substr($goalClean, 0, 100).' ... '); } else { htmlentities($goalClean); }

			switch($rule) // Do we have a test for this $rule ?
			{
				// Requires $goal
				case "==":          if($input == $goal) { $success = true; $message = "$inputClean equal to $goalClean"; } else $message = "$inputClean not equal to $goalClean"; break;
				case "===":         if($input === $goal) { $success = true; $message = "$inputClean same type and equal to $goalClean"; } else $message = "$inputClean not same type or not equal to $goalClean"; break;
				case "~==":         if(strcasecmp($input, $goal)) { $success = true; $message = "$inputClean equal to (insensitive) $goalClean"; }  else $message = "$inputClean not equal to (insensitive) $goalClean"; break;
				case "!=":          if($input != $goal) { $success = true; $message = "$inputClean not equal to $goalClean"; } else $message = "$inputClean equal to $goalClean"; break;

				case "has":         if(strrpos($input, $goal) !== false) { $success = true; $message = "$inputClean contains $goalClean"; } else $message = "$inputClean does not contain $goalClean"; break;
				case "!has":        if(strrpos($input, $goal) === false) { $success = true; $message = "$inputClean does not contain $goalClean"; }  else $message = "$inputClean contains $goalClean"; break;
				case "~has":        if(stripos($input, $goal) !== false) { $success = true; $message = "$inputClean contains (insensitive) $goalClean"; } else $message = "$inputClean does not contain (insensitive) $goalClean"; break;
				case "~!has":       if(stripos($input, $goal) === false) { $success = true; $message = "$inputClean does not contain (insensitive) $goalClean"; } else $message = "$inputClean contains (insensitive) $goalClean"; break;

				case "atStart":     if(strrpos($input, $goal) === 0) { $success = true; $message = "$inputClean at the start of $goalClean"; } else $message = "$inputClean not at the start of $goalClean"; break;
				case "!atStart":    if(strrpos($input, $goal) !== 0) { $success = true; $message = "$inputClean not at the start of $goalClean"; } else $message = "$inputClean at the start of $goalClean"; break;
				case "iatEnd":      if(strrpos($input, $goal, strlen($input)-strlen($goal)) === 0) { $success = true; $message = "$inputClean at the end of $goalClean"; } else $message = "$inputClean not at the end of $goalClean"; break;
				case "!atEnd":      if(strrpos($input, $goal, strlen($input)-strlen($goal)) !== 0) { $success = true; $message = "$inputClean not at the end of $goalClean"; } else $message = "$inputClean at the end of $goalClean"; break;

				// Does NOT require $goal
				case ">0":          if( (is_string($input) && strlen($input) > 0) || (is_array($input) && count($input) > 0) || (is_numeric($input) && $input > 0) ) { $success = true; $message = "$inputClean greater than 0"; } else $message = "$inputClean not greater than 0"; break;
				case "<0":          if( (is_string($input) && strlen($input) < 0) || (is_array($input) && count($input) < 0) || (is_numeric($input) && $input < 0) ) { $success = true; $message = "$inputClean less than 0"; } else $message = "$inputClean not less than 0"; break;
				case ">=0":         if( (is_string($input) && strlen($input) >= 0) || (is_array($input) && count($input) >= 0) || (is_numeric($input) && $input >= 0) ) { $success = true; $message = "$inputClean greater than 0 or equal to 0"; } else $message = "$inputClean not greater than or equal to 0"; break;
				case "<=0":         if( (is_string($input) && strlen($input) <= 0) || (is_array($input) && count($input) <= 0) || (is_numeric($input) && $input <= 0) ) { $success = true; $message = "$inputClean less than 0 or equal to 0"; } else $message = "$inputClean not less than or equal to 0"; break;
				case "true":        if($input) { $success = true; $message = "$inputClean is true"; }  else $message = "$inputClean is not true"; break;
				case "false":       if(!$input) { $success = true; $message = "$inputClean is false"; } else $message = "$inputClean is not false"; break;

				// Type checks.
				case "array":       if(is_array($input)) { $success = true; $message = "$inputClean is an array"; } else $message = "$inputClean is not an array"; break;
				case "!array":      if(!is_array($input)) { $success = true; $message = "$inputClean is not an array"; } else $message = "$inputClean is an array"; break;

				case "numeric":     if(is_numeric($input)) { $success = true; $message = "$inputClean is numeric"; } else $message = "$inputClean is not numeric"; break;
				case "!numeric":    if(!is_numeric($input)) { $success = true; $message = "$inputClean is not numeric"; } else $message = "$inputClean is numeric"; break;

				case "float":       if(is_float($input)) { $success = true; $message = "$inputClean is a float"; } else $message = "$inputClean is not a float"; break;
				case "!float":      if(!is_float($input)) { $success = true; $message = "$inputClean is not a float"; } else $message = "$inputClean is a float"; break;

				case "string":      if(is_string($input)) { $success = true; $message = "$inputClean is a string"; } else $message = "$inputClean is not a string"; break;
				case "!string":     if(!is_string($input)) { $success = true; $message = "$inputClean is not a string"; } else $message = "$inputClean is a string"; break;

				case "bool":        if(is_bool($input)) { $success = true; $message = "$inputClean is a bool"; } else $message = "$inputClean is not a bool"; break;
				case "!bool":       if(!is_bool($input)) { $success = true; $message = "$inputClean is not a bool"; } else $message = "$inputClean is a bool"; break;

				// Arrays
				case "hasKey":      if(is_array($input) && array_key_exists($goal, $input)) { $success = true; $message = "$inputClean contains key $goalClean"; } else $message = "$inputClean does not contain key $goalClean"; break;
				case "!hasKey":     if(is_array($input) && !array_key_exists($goal, $input)) { $success = true; $message = "$inputClean does not contain key $goalClean"; } else $message = "$inputClean contains key $goalClean"; break;
				case "hasValue":    if(is_array($input) && in_array($goal, $input, true)) { $success = true; $message = "$inputClean contains value $goalClean"; } else $message = "$inputClean does not contain value $goalClean"; break;
				case "!hasValue":   if(is_array($input) && !in_array($goal, $input, true)) { $success = true; $message = "$inputClean does not contain value $goalClean"; } else $message = "$inputClean contains value $goalClean"; break;

				// No known $rule? test() shorthand is being used. Fall back to simple equality check.
				default:
					$goal = $rule;
					// Ensure input doesn't cause display side effects.
					$goalClean = strval($goal);
					if(strlen($goalClean) > 50) { $goalClean = htmlentities(substr($goalClean, 0, 100).' ( ... ) '); } else { htmlentities($goalClean); }
					$rule = '==';
					if($input == $goal) { $success = true; $message = "$inputClean equal to $goalClean"; } else $message = "$inputClean not equal to $goalClean";
				break;
			}
		}
		catch (Throwable $e)
		{
			$success = false;
			$message = $e->getMessage();
		}

		// Store result for later use.
		$result = [];
		$result['input'] = $input;
		$result['rule'] = $rule;
		$result['goal'] = $goal;
		$result['name'] = $name;
		$result['success'] = $success;
		$result['message'] = $message;
		// Test metadata
		$trace = debug_backtrace(0,2)[0];
		$result['function'] = $trace['function'];
		$result['line'] = $trace['line'];
		$result['file'] = $trace['file'];

		// Write to results
		if(empty($name))
			$name = 'Test '.self::$testNumber;

		self::$testResults[$name] = $result;

		$name = ' | '.$name;

		if($success) echo "<div>✅ <span style='display: inline-block; text-align:right; min-width:8ch; margin-right:1ch;'>Line ".$result['line']."</span> $name</div>";
		else echo "<div>🔴 <span style='display: inline-block; text-align:right; min-width:8ch; margin-right:1ch;'>Line ".$result['line']."</span> $name ⚠️ <span style='color:#dd2222;'>$message</span></div>";
	}

	// Gracefully start web browser session if not already started.
	public static function webStart()
	{
		if(!self::$web) return false; // Web browser tests are off.
		if(!empty(self::$webSession)) return true; // Web browser tests already started!

		// Check requirements: Chromium, ChromeDriver
		$dirChromeDriver = `bash --login -c 'which chromedriver'`;
		$dirChromium = `bash --login -c 'which chromium'`;
		if(empty($dirChromeDriver) || empty($dirChromium)) {
			$trace = debug_backtrace(0,1)[0];
			?>⚠️ Skipping: Chromium or Chromedriver not installed. (<?=@$trace['function']?>() in <?=@$trace['file']?>:<?=@$trace['line']?>)<br /><?php
			return [false, 'Skipping: Chromium or Chromedriver not installed. Install both using: sudo apt install chromium-chromedriver'];
		}

		// Reference: https://developer.chrome.com/articles/new-headless/
		`killall chromedriver; killall chromium; sleep 1`; // Cleanup any previous run.
		`[ "$(whoami)" = "www-data" ] && killall chrome`; // TODO: Might be needed for snap version. ):
		//shell_exec(sprintf('%s > /dev/null 2>&1 &', ""));$dirChromeDriver --whitelisted-ips --allowed-origins='*'
		passthru("bash --login -c '$dirChromeDriver --whitelisted-ips --allowed-origins=\"*\" --port=9515 > /dev/null 2>&1 &'  > /dev/null 2>&1 &"); // Begin new.

		// Create browser session. Retry on fail.
		$sessionId = null;
		$timeout = time()+10; // Timeout after 10 seconds.
		while(!$sessionId && $timeout > time()) {
			$webSize = '1920,1080';
			if(!empty(intval(self::$webSizeX)) && !empty(intval(self::$webSizeY))) { $webSize = intval(self::$webSizeX).','.intval(self::$webSizeY); }
			$result = self::curl(self::$webDriver.'/session', 'POST', [], '{"capabilities": {"acceptInsecureCerts": true, "alwaysMatch": {"goog:chromeOptions": { "args": ["--incognito", "--headless", "--disable-gpu", "--window-size='.$webSize.'"] }}}}');
			//var_dump($result);
			if($result[1] != 200) { usleep(500000); continue; } // Retry after 0.5 seconds.
			//var_dump($result);
			$result = json_decode($result[0],true);
			$sessionId = $result['value']['sessionId'];
		}
		if(empty($sessionId)) {
			$trace = debug_backtrace(0,1)[0];
			?>⚠️ Skipping: Chromedriver timed out. (<?=@$trace['function']?>() in <?=@$trace['file']?>:<?=@$trace['line']?>)<br /><?php
			exit();
		}

		// Ready to use browser!
		self::$webSession = $sessionId;
		return true;
	}

	// Explicitly end web browser session.
	public static function webEnd()
	{
		if(!self::$web) return; // Web tests are off.
		if(empty(self::$webSession)) { // We don't have the session, just finish early.
			`killall chromedriver; killall chromium`;
			return;
		}
		self::curl(self::$webDriver.'/session/'.self::$webSession, 'DELETE');
		self::curl(self::$webDriver.'/sessions');
		`killall chromedriver; killall chromium`;
	}

	// Per domain, add username / password HTTP Authorization. No negative consequences of running more than once.
	public static function webAuth($url='', $username='', $password='')
	{
		if(!$error = self::webStart()) return $error; // Continue only if web browser tests are on, with no issues.

		$url = self::urlParse($url);
		$auth = urlencode($username).':'.urlencode($password).'@'; // Encode auth.
		self::$webAuth[$url['domain']] = $auth; // Store this auth.
		self::curl(self::$webDriver.'/session/'.self::$webSession.'/url', 'POST', [], '{"url": "'.$url['protocol'].'://'.$auth.$url['domain'].'"}'); // Activate auth in web browser.
	}

	// Per domain, get formatted "username:password@" HTTP Authorization.
	public static function webAuthGet($url='')
	{
		$domain = @self::urlParse($url)['domain'];
		if(isset(self::$webAuth[$domain])) return self::$webAuth[$domain];
		return ''; // Authorization not found.
	}

	// Web Browser: Go to URL.
	public static function webGo($url='')
	{
		if(!$error = self::webStart()) return $error; // Continue only if web browser tests are on, with no issues.

		$auth = self::webAuthGet($url); // Can probably remove in future because Chromium saves the auths.
		//$url = self::urlParse($url); // '.$url['protocol'].'://'.$auth.$url['domain'].$url['path'].'
		$result = self::curl(self::$webDriver.'/session/'.self::$webSession.'/url', 'POST', [], '{"url": "'.$url.'"}');
		?>🧭 Navigated to <strong><?=$url?></strong><br /><?php
	}

	// Web Browser: Select and retun an element id using CSS selectors. Mostly used internally by webClick() and webScreenshotSelect().
	public static function webSelect($selector='')
	{
		if(!$error = self::webStart()) return $error; // Continue only if web browser tests are on, with no issues.
		$selector = str_ireplace("\\", "\\\\", $selector); // Convert \ to \\
		$selector = str_ireplace('"', '\\"', $selector); // Convert " to \\"

		$result = self::curl(self::$webDriver.'/session/'.self::$webSession.'/element', 'POST', [], '{"using": "css selector", "value":"'.$selector.'"}');
		$result = @json_decode($result[0], true)['value'];
		return $result ?? '';
	}

	// Web Browser: Click an element using result from webSelect()
	public static function webClick($selector='')
	{
		if(!$error = self::webStart()) return $error; // Continue only if web browser tests are on, with no issues.
		if(!$element = self::webSelect($selector)) return false; // Invalid element.

		$result = self::curl(self::$webDriver.'/session/'.self::$webSession.'/element/'.reset($element).'/click', 'POST', [], '{"script":""}');
		?>🖱 Clicked <strong><?= stripslashes($selector) ?></strong><br /><?php
	}

	// Web Browser: Accept alert() choice pop up / popups
	public static function webAlertAccept()
	{
		if(!$error = self::webStart()) return $error; // Continue only if web browser tests are on, with no issues.

		$result = self::curl(self::$webDriver.'/session/'.self::$webSession.'/alert/accept', 'POST', [], '{"script":""}');
		?>🖱 Accepted Popup<br /><?php
	}

	// Web Browser: Run Script.
	public static function webScript($script='')
	{
		if(!$error = self::webStart()) return $error; // Continue only if web browser tests are on, with no issues.
		$script = str_ireplace("\\", "\\\\", $script); // Convert \ to \\
		$script = str_ireplace('"', '\\"', $script); // Convert " to \\"

		$result = self::curl(self::$webDriver.'/session/'.self::$webSession.'/execute/sync', 'POST', [], '{"script":"'.$script.'", "args":[]}');
		?>🪄 Ran Scripted Action<br /><?php
	}

	// Web Browser: Get page output / source code.
	public static function webOutput($url='')
	{
		if(!$error = self::webStart()) return $error; // Continue only if web browser tests are on, with no issues.

		if(!empty($url)) self::webGo($url);
		$result = self::curl(self::$webDriver.'/session/'.self::$webSession.'/screenshot'); // Cleanest way to ensure Chrome has loaded, or you'll get data race.
		$result = self::curl(self::$webDriver.'/session/'.self::$webSession.'/source');
		$result = @json_decode($result[0], true)['value'];
		return $result ?? '';
	}

	// Web Browser: Get stats of all active sessions.
	public static function webStats()
	{
		if(!$error = self::webStart()) return $error; // Continue only if web browser tests are on, with no issues.
		$result = self::curl(self::$webDriver.'/sessions');
	}

	// Web Browser: Take screenshot.
	// If $url is not specified, screenshot will be taken of current session.
	public static function webScreenshot($url='', $imageName='')
	{
		if(!$error = self::webStart()) return $error; // Continue only if web browser tests are on, with no issues.
		if(!empty($url)) self::webGo($url);

		$result = self::curl(self::$webDriver.'/session/'.self::$webSession.'/screenshot');
		$result = @json_decode($result[0], true)['value'];

		$image = imagecreatefromstring(base64_decode($result));
		$trace = debug_backtrace(0,2)[0];
		if(empty($imageName)) $imageName = basename($trace['file']).':'.str_pad($trace['line'], 5, '0', STR_PAD_LEFT).':'.date("Y_m_d_H_i_s").':'.self::$webSession.'.jpg';
		if($image !== false) {
			imagejpeg($image, self::$webImageSave.'/'.$imageName);
			imagedestroy($image);
		}
		?>📸 Screenshot <a style="text-decoration: none;" href="<?= self::$webImageSave.'/'.$imageName ?>"
			onclick="event.preventDefault()
			var img = document.createElement('img')
			img.src = this.getAttribute('href')
			this.parentNode.replaceChild(img, this)
			">👁️</a><br />
		<?php
	}

	// Web Browser: Take screenshot of element using CSS selector.
	public static function webScreenshotSelect($selector='')
	{
		if(!$error = self::webStart()) return $error; // Continue only if web browser tests are on, with no issues.
		if(!$element = self::webSelect($selector)) return false; // Invalid element.

		$result = self::curl(self::$webDriver.'/session/'.self::$webSession.'/element/'.reset($element).'/screenshot');
		$result = @json_decode($result[0], true)['value'];

		$image = imagecreatefromstring(base64_decode($result));
		$trace = debug_backtrace(0,2)[0];
		if(empty($imageName)) $imageName = basename($trace['file']).':'.str_pad($trace['line'], 5, '0', STR_PAD_LEFT).':'.date("Y_m_d_H_i_s").':'.self::$webSession.'.jpg';
		if($image !== false) {
			imagejpeg($image, self::$webImageSave.'/'.$imageName);
			imagedestroy($image);
		}
		?>📸 Screenshot of <strong><?= stripslashes($selector) ?></strong> <a style="text-decoration: none;" href="<?= self::$webImageSave.'/'.$imageName ?>"
			 onclick="event.preventDefault()
			 var img = document.createElement('img')
			 img.src = this.getAttribute('href')
			 this.parentNode.replaceChild(img, this)
			 ">👁️</a><br />
		<?php
	}

	// Run full Test Suite automatically.
	public static function run()
	{
		@mkdir(self::$webImageSave, 0764, true);
		$url = self::urlParse(self::$testUrl);
		$only = urldecode($_GET['only'] ?? ''); // Running selected test only?
		?>
		<html>
		<head>
			<style>
				* { margin: 0; padding: 0; box-sizing: border-box; color: #111; }
				html { font-size: 10px; }
				body { margin-bottom: 40px; font-size: 1.4rem; }
				html, h2 { background: hsl(198, 10%, 50%, 0.5); font-family:system-ui; color: #555; text-shadow: 0px 0px 2px #00000033; }
				h1 { display: grid; grid: 1fr / 1fr fit-content(10%); color: #666; font-size: 1.8rem; padding: 20px 20px; border-radius: 8px; padding: 20px 20px; margin: 10px; font-weight: normal; background: hsl(198, 10%, 98%, 0.98); }
				h1 a { text-decoration: none; }
				h1 a.button { background: hsl(198, 10%, 75%, 0.4); border-radius: 8px; cursor: pointer; padding: 6px 10px; margin-left: 10px; white-space: nowrap; }
				h2 { margin: 10px; font-size: 1.4rem; background: hsl(198, 10%, 98%, 0.5); border-radius: 8px; padding: 6px 10px; }
				ul { list-style-type: none; margin: 10px 20px; }
				.indent { margin: 0 30px 0 30px; }
				.test_running { font-size: 1.4rem; background: hsl(100, 0%, 90%, 0.5); border-radius: 8px; padding: 4px 8px; margin: 4px 0; }
				.test_output { font-size: 1.5rem; padding: 4px 8px; margin: 0 30px 0 30px; }
				.test_output>img { display:inline-block; max-width: 80%; margin: 4px; border-radius: 4px; }
				.test_output:empty { display:none; }
				.test_failed { font-size: 1.4rem; background: hsl(0, 70%, 90%, 0.5); border-radius: 8px; padding: 4px 8px; margin: 4px 0; }
				.test_passed { font-size: 1.4rem; background: hsl(100, 70%, 90%, 0.5); border-radius: 8px; padding: 4px 8px; margin: 4px 0; }
				.test_loading img { margin: 0 auto; max-width: 100px; display: block; }
				.test_gallery img { max-width: 200px; border-radius: 10px; cursor: pointer; border: 2px solid #999999AA; }
				.test_gallery img:hover { border: 2px solid #DDDDDDAA; }
				.test_gallery>div { display: flex; flex-wrap:wrap; justify-content: center; align-items: center; }
				.test_gallery>div>a { margin: 10px; }
				.test_gallery .group { display:flex; flex-wrap:wrap; justify-content: center; align-items: center; gap: 10px; padding: 10px; background: #80808045; margin: 10px; border-radius: 10px; }
				.test_gallery .label { color: hsl(0 0% 20% / 100%); background: hsl(198, 10%, 98%, 0.98); width: fit-content; font-size: 1.4rem; text-align:center; font-weight: bold;  margin: 0 auto 20px auto; padding: 0.6rem 1.2rem; border-radius: 8px; }
			</style>
		</head>
		<body>
			<h1><div>🔬 <a href="/zerolith/zpanel/test">Zerolith Test</a> Results for <a href="<?= $url['url'] ?>">🌐 <?= $url['domain'] ?></a> on 🕒 <?=gmdate("Y-m-d H:i")?> UTC</div><div><a class="button" href="/zerolith/zpanel/test">🔃 Reload</a></div></h1>
			<div class="test_loading"><img src="/zerolith/zpanel/test/loading.svg" /></div>
			<div style="display: grid; align-content: center; grid:  1fr fit-content / 1fr 1fr 1fr">
				<div style="grid-column:3/4; grid-row:2/3">
					<?php
					$testsZerolith = array_filter(self::$testsZerolith);
					if($testsZerolith) {
						?><h2>⚙️ Zerolith Tests</h2><?php
						foreach ($testsZerolith as $folder) { ?>
							<ul class="indent">
								<li>📂 <?=$folder?></li>
								<?php
								$target = rtrim($folder, '/').'/';
								$tests = glob($target.'/{,*/}*.php', GLOB_BRACE);
								?>
								<ul>
									<?php
									foreach ($tests as $test) {
										$test = str_replace('//', '/', $test);
										$testName = substr($test, strlen($folder)+1);
										$skip = (strpos($testName, '_') === 0 && $only !== $test); // Skip tests starting with "_" unless selected to run.
										?>
										<li><?php if ($only && $only === $test) { echo "🎯"; } elseif ($skip) { echo "🏳️"; } else { echo "🧪"; } ?> <a href="?only=<?=urlencode($test)?>"><?=$testName?></a></li>
										<?php
										if($skip) continue;
										self::$testLoaded[] = $test;
									}
									?>
								</ul>
							</ul>
						<?php }
					}
					?>
					<br />
					<?php
					$testsProject = array_filter(self::$testsProject);
					if($testsProject) {
						?><h2>🌠 Project Tests</h2><?php
						foreach ($testsProject as $folder) { ?>
							<ul class="indent">
								<li>📂 <?=$folder?></li>
								<?php
								$target = rtrim($folder, '/').'/';
								$tests = glob($target.'/{,*/}*.php', GLOB_BRACE);
								?>
								<ul>
									<?php
									foreach ($tests as $test) {
										$test = str_replace('//', '/', $test);
										$testName = substr($test, strlen($folder)+1);
										$skip = (strpos($testName, '_') === 0 && $only !== $test); // Skip tests starting with "_" unless selected to run.
										?>
										<li><?php if ($only && $only === $test) { echo "🎯"; } elseif ($skip) { echo "🏳️"; } else { echo "🧪"; } ?> <a href="?only=<?=urlencode($test)?>"><?=$testName?></a></li>
										<?php
										if($skip) continue;
										self::$testLoaded[] = $test;
									}
									?>
								</ul>
							</ul>
						<?php }
					}
					?>
					<br />
					<?php
					if($only) {
						?><h2>🎯 Running selected test only</h2>
						<ul class="indent">
							<li>↩️ <a href="?all">Go Back</a></li>
						</ul>
						<br />
						<?php
					}
					?>
				</div>
				<div style="grid-column:1/3; grid-row:2/3;">
					<h2>⚙️ Test Log</h2>
					<div class="indent">
					<?php $time_benchmark = microtime(true); ?>
					<?php foreach (self::$testLoaded as $k => $v) {
						if (!$only || ($only && $only === $v)) {
							?>
							<div class="test_running">▶️ Running: <a href="?only=<?=urlencode($v)?>"><?=$v?></a> &nbsp;&nbsp;&nbsp;</div>
							<div class="test_output"><?include($v);?></div>
							<?php
						}
					}
					?>
					</div>
				</div>
				<script>document.querySelector('.test_loading')?.remove()</script>
				<div style="grid-column:1/4; grid-row: 1/2">
					<?php [$success, $fail] = self::stats(); $total = $success + $fail; ?>
					<h2>📋 Test Summary </h2>
					<div class="indent">
						<div class="test_passed">✅ <?= $success.'/'.$total ?> tests have passed</div>
						<div class="test_failed">🔴 <?= $fail.'/'.$total ?> tests have failed</div>
					</div>
					<div class="indent">
						<div class="test_running">⏱ Tests finished in <?= round((microtime(true) - $time_benchmark), 3) ?> seconds</div>
					</div>
				</div>
			</div>
			<?php
			$images = glob(self::$webImageSave.'/*.jpg', GLOB_BRACE);
			if(!empty($images)) {
			?>
				<div class="test_gallery">
					<h2>🖼️ Test Gallery</h2>
					<div class="indent">
						<?php
						// Generate gallery groups.
						$group = '';
						$groupLast = '';
						$groupEnd = false;

						$images = glob(self::$webImageSave.'/*.jpg', GLOB_BRACE);
						foreach ($images as $image) {
							if(strpos($image,self::$webSession)) { // Only images that were part of the current session.
								$group = substr($image, strrpos($image, '/')); // Remove path.
								$group = substr($group, 1, strpos($group, ':')-1); // Remove extras.
								$group = substr($group, 0, strpos($group, '.')); // Remove extension.
								if ($group != $groupLast) {
									// New group. Add previous group label only if it exists.
									if (!empty($groupLast)) { ?> </div><div class="label">📸 <?=$groupLast?></div></div> <?php }
									?><div><div class="group"><?php
									$groupLast = $group;
								}
								?><a href="<?=$image?>" target="_blank"><img src="<?=$image?>"/></a><?php
							}
						}
						// Final group label only if it exists.
						if($groupLast) { ?> </div><div class="label">📸 <?=$groupLast?></div></div> <?php } ?>
					</div>
				</div>
			<?php } ?>
			<br /><br />
			</body>
		</html><?php

		// Close web browser.
		self::webEnd();
		// Clean up old screenshots.
		foreach (glob(self::$webImageSave.'/*.jpg', GLOB_BRACE) as $image) {
			if (is_file($image) && time() - filemtime($image) > 86400) { // 86400 = 60 * 60 * 24 = 1 day
				unlink($image);
			}
		}
	}

	// Stats of test results.
	public static function stats()
	{
		$fail = $success = 0;
		//go through the results
		foreach(self::$testResults as $name => $result) {
			if($result['success']) { $success++; } else { $fail++; }
		}

		return [$success, $fail];
	}

	// Alternative to self::run() for test files that can run independently.
	public static function results($html=true, $echo=true)
	{
		[$success, $fail] = self::stats();
		$total = $success + $fail;
		$output = '';

		?>
		<h2>📋 Test Summary </h2>
		<div class="indent">
			<div class="test_passed">✅ <?= $success.'/'.$total ?> tests have passed</div>
			<div class="test_failed">🔴 <?= $fail.'/'.$total ?> tests have failed</div>
		</div>
		<?php
	}

	// Test using curl. Used internally by self::web* functions.
	public static function curl($url, $method='GET', $payload=[], $json='', $cookie=[], $username='', $password='', $timeoutConnect=5, $timeoutMax=10, $debug=1)
	{
		$curl = curl_init();
		$headers = [];
		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1); // Return content, do not print.

		// Cookies.
		curl_setopt($curl, CURLOPT_COOKIEJAR, self::$curlCookies);
		curl_setopt($curl, CURLOPT_COOKIEFILE, self::$curlCookies);
		curl_setopt($curl, CURLOPT_COOKIE, self::$curlCookies);
		curl_setopt($curl, CURLOPT_COOKIESESSION, 1);

		// Payload.
		if($method == 'DELETE')
		{
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'DELETE');
		}
		if($method == 'POST' || !empty($post) || !empty($json))
		{
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');

			if(!empty($post)) curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
			if(!empty($json))
			{
				curl_setopt($curl, CURLOPT_POSTFIELDS, $json);
				$headers[] = 'Content-Type: application/json';
				#$headers[] = ['Content-Length:'. strlen($json)];
			}
		}
		// Authorization.
		if(!empty($username) && !empty($username)) {
			$headers[] = 'Authorization: Basic '.base64_encode(trim($username).':'.trim($password));
		}
		curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
		// Timeouts.
		curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, $timeoutConnect);
		curl_setopt($curl, CURLOPT_TIMEOUT, $timeoutMax);
		// Security.
		curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1); // Follow redirects.
		curl_setopt($curl, CURLOPT_MAXREDIRS, 5);      // Redirect loop protection.
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0); // Certs don't have to be valid for testing.
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
		// Meta.
		curl_setopt($curl, CURLOPT_USERAGENT, 'Zerolith Test (zl) Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/118.0.0.0 Safari/537.36');
		// Debug.
		if($debug) curl_setopt($curl, CURLOPT_VERBOSE, 1);
		if($debug) curl_setopt($curl, CURLINFO_HEADER_OUT, 1);
		// Run.
		$result = trim(curl_exec($curl));

		// Error? Return error and 500.
		if (curl_errno($curl)) {
			$error = curl_error($curl);
			curl_close($curl);
			return ['Curl error: '.$error, 500];
		}

		// Success. Return result and HTTP code.
		$code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
		curl_close($curl);
		return [$result, $code];
	}

	// Internal. Similar to url_parse(), but doesn't fail if protocol is missing.
	public static function urlParse($url='')
	{
		$url = rtrim(trim($url), '/'); // Remove whitespace, remove trailing slash.
		$protocol = explode('://', $url); // Find protocol.
		$protocol = (count($protocol) > 1) ? $protocol[0] : 'http';
		$domain = explode('://', $url); // Find domain.
		$domain = (count($domain) > 1) ? $domain[1] : $domain[0];
		$domain = explode('/', $domain);
		$domain = (count($domain) > 1) ? $domain[0] : $domain[0];
		$path = explode('://', $url); // Find path.
		$path = (count($path) > 1) ? $path[1] : $path[0];
		$path = explode('/', $path);
		$path = (count($path) > 1) ? '/'.$path[1] : '/';
		return ['url' => $url, 'protocol' => $protocol, 'domain' => $domain, 'path' => $path];
	}
}

ob_implicit_flush(true);
define('zl_terminate_ignore', true);
include(ztest::$config); // Your project Zerolith config (ex: "../../../zl_config.php")
if (!defined('zl_mode') || zl_mode != 'dev') {  header('Location: /'); die(); }
if(isset($zl_site['URLbase'])) ztest::$testUrl = $zl_site['URLbase'];

// Run tests!
ztest::run();
