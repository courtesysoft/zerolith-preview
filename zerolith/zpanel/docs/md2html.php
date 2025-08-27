<?php
//ZL doc displayer, DS 08/29/2024
require "../../zl_init.php";
require "../zpanelConfig.php";
require zl::$site['pathZerolithClasses'] . "/3p/parsedown.php";

//get the input; filename without the .md
extract(zfilter::array("md", "stringSafe"));

//form the title
$titleArray = explode("_", $md); //remove the ##_ part
array_shift($titleArray);
$title = str_replace("_", " ", ucfirst(implode("_", $titleArray)));

//output ze file
zpage::start("ZL Docs - " . $title);
echo Parsedown::instance()->text(file_get_contents($md . ".md"));