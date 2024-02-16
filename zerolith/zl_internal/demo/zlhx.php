<?php
//HTMX in ZL proof of concept - server side
//v1.1 - 06/12/2023 - Now with comments.
require "../../zl_init.php";
//require "cm_init.php";

zl::setDebugLevel(4);   //debug level for regular script

zl::routeZLHX(); //manual run in case we have auto turned off; won't hurt anything

//fun HTMX tricks that aren't used in this demo:
//Manually run an ajax call and then when the transfer is complete, execute the following function asynchronously.
//htmx.ajax('GET', hxURL, '.' + dialogInnerName).then(() => { animateDiv(dialogName); });

//--------- regular script section ---------
zl::setOutFormat("page");
zl::$page['wrap'] = true;
zpage::start("htmx test");

?>
<script> //can't press the button until all fields are filled
function checkIfCanPress()
{
	var hxv = htmx.values(htmx.find("#carMaker")); //get all variables in the form with the ID #carMaker
	htmx.find("#Gimme").disabled = !(hxv.make != '' && hxv.model != '' && hxv.trans != ''); //set disabled unless all fields have something in them.
}
</script>

<div class="zl_w100p">
	<div class="zl_w50p zl_left">
		<!-- because we have a target setup, when a button is pressed inside this form, the entire form's contents will be sent in the htmx request. We don't need an hx-include! -->
		<form hx-target="#car" hx-get="?hxfunc=makeCar" id="carMaker">
			<div><label>Make: </label><?=zlhx::makeSB()?></div><br>
			<div><label>Model: </label><?=zlhx::modelSB()?></div><br>
			<div><label>Trans: </label><?=zlhx::tranSB()?></div><br>
			<?=zui::buttonSubmit("Gimme Car", "directions_car", "", "","ID='Gimme' disabled")?>
		</form>
		<br>
		<!-- We can use hx-include to grab input from outside the form and send it through the button. -->
		<?=zui::buttonSubmit("Gimme Broken Car", "", "", "", 'hx-target="#car" hx-get="?hxfunc=makeCarBroken" hx-include="#makeSB,#modelSB,#transSB"')?><br><br>
		
		<!-- We can also use hx-include to get the entire form's variables. What a cheat! :) -->
		<?=zui::buttonSubmit("Gimme Broken Car 2", "", "", "", 'hx-target="#car" hx-get="?hxfunc=makeCarBroken" hx-include="#carMaker"')?><br><br>
		
		<!-- In these cases, we are sending a raw GET string as the request and not including form input. -->
		<?=zui::buttonSubmit("GimmeError", "pest_control", "", "", 'hx-target="#car" hx-get="?hxfunc=makeCarError"')?><br><br>
		<?=zui::buttonSubmit("GimmeTimeout", "pest_control", "", "", 'hx-target="#car" hx-get="?hxfunc=makeCarTimeout"')?><br><br>
		<?=zui::buttonSubmit("GimmePHPerror", "pest_control", "", "", 'hx-target="#car" hx-get="?hxfunc=makeCarPHPerror"')?><br><br>
		<?=zui::buttonSubmit("GimmeDBerror", "storage", "", "", 'hx-target="#car" hx-get="?hxfunc=makeCarDBerror"')?><br><br>
		</form>
	</div>
	<div class="zl_w50p zl_right" id ="car">no car yet</div>
</div>
<?php

//this is a magic class that, if present, will be auto routed via zl::routeZLHX if ?hxfunc=[the function name] is sent to your script.
class zlhx
{
	static function zlhxInit() { zl::setDebugLevel(1); } //(optional) will run automatically via zl::routeHX if present
	
	//car making functions
	
	static function makeCarError() { http_response_code(500); } //simulate an error
	static function makeCarTimeout() { sleep(6); } //simulate a timeout
	static function makeCarPHPerror() { whoopsThisFunctionDoesntExist(); } //simulate a php error
	
	static function makeCarDBerror() //simulate a database library failure
	{
		$wa = ["id" => "","1workType" => "whatever", "ratePay" => 99.99];
		zdb::writeRow("insert", "r111ates", $wa, ["ID" => ""]);
		echo "Well, this is awkward, the database call should have failed!";
	}
	
	static function makeCar()
	{
		extract(zfilter::array("make|models|trans", "stringSafe")); //form input was submitted from htmx here
		echo "Your $trans $make $models has arrived, sir: <br>" . zui::miconR("directions_car", "", "","style='font-size:100px !important;'") . "</div>";
	}
	
	static function makeCarBroken()
	{
		sleep(2);
		extract(zfilter::array("make|models|trans", "stringSafe")); //form input was submitted from htmx here
		echo "Your $trans $make $models has arrived, sir: <br>" . zui::miconR("directions_car", "", "","style='font-size:100px !important; transform: rotate(180deg) scaleX(-1); color:red;'") . "</div>";
	}
	
	//select boxes output.
	
	static function makeSB() //make of car selectbox
	{
		//doesn't need to take any input
		zui::selectBox("make", "", ["Audi","Toyota","BMW"], "","",'ID="makeSB" hx-get="?hxfunc=modelSB" hx-target="#modelWrap"  hx-swap="outerHTML"' . ' onchange="checkIfCanPress();"');
	}
	
	static function modelSB() //model selectbox
	{
		usleep(500);
		extract(zfilter::array("make", "stringSafe")); //form input was submitted from htmx here
		
		//prep work
		if($make == "Audi") { $SBarray = ["A1","A2","A3","A4"]; }
		else if($make == "BMW") { $SBarray = ["325i","330i","530i","740i"]; }
		else if($make == "Toyota") { $SBarray = ["Prius","Landcruiser","Tacoma","Yaris"]; }
		else { $SBarray = ["" => "Select a make"]; }
		
		//output
		?><span id="modelWrap"><?php
		zui::selectBox("models", "", $SBarray, "","",'ID="modelSB" hx-get="?hxfunc=tranSB" hx-target="#tranWrap" hx-swap="outerHTML"' . ' onchange="checkIfCanPress();"');
		?>
		<!-- hackish way to force transmission selectbox to refresh to blank; prob a better way to do it -->
		<script>
		if(document.readyState == "complete"){ htmx.ajax('GET', '?hxfunc=tranSB', '#tranWrap'); checkIfCanPress(); }
		</script>
		</span>
		<?php
	}
	
	static function tranSB() //transmission selectbox
	{
		extract(zfilter::array("models", "stringSafe")); //get from form input
		
		usleep(100000);
		//prep work
		if($models == ""){ $SBarray = ["" => "Select a Model"]; }
		else { if(rand(0,1) == 0){ $SBarray = ["Automatic","Manual"]; } else { $SBarray = ["CVT", "Manual"]; } }
		
		//output
		?><span id="tranWrap"><?php
		zui::selectBox("trans", "", $SBarray, "","",'ID="transSB" onchange="checkIfCanPress();"');?>
		</span>
		<?php
	}
}
?>