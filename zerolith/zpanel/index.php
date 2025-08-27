<?php
//homepage for zpanel - incomplete
require("../zl_init.php"); //Load framework. Creates the zl object.
require "zpanelConfig.php"; //load zpanel settings modification

zpage::start("Zpanel Home");
?>Welcome to Zerolith!<br>

<div class = "zl_cols">
<div class = "col">
	<h3>Features</h3>
	<a href="install.php">ZL Installer</a><br>
	<a href="demo/index.php">Demo Gallery</a><br>
	<br>
	<a href="bugLog.php">Bug Log</a><br>
	<a href="mailLog.php">Mail Log</a><br>
	<a href="test/index.php">ZTest</a><br>
	
</div>
<div class = "col">
	<h3>Documentation</h3>
	<?php
	$docs = glob("docs/*.md");
	foreach($docs as $doc)
	{
        $sanitizedName = str_replace(['.md', "docs/"], '',$doc);
        $titleArray = explode("_", $sanitizedName); //remove the ##_ part
		array_shift($titleArray);
        $title = str_replace("_", " ", ucfirst(implode("_", $titleArray)));
        ?><a href="docs/md2html.php?md=<?=$sanitizedName?>"><?=$title?></a><br><?php
    }
	?>
</div>
</div>