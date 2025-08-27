<?php

require "../../zl_init.php";
require "index.nav.php"; //navigation menu
zpage::start("ZUI Custom Select Component Demo");

zui::selectBox_new("select1", "middle", ["first","middle","last","invalid|disabled"], "I'm a selectbox."); ?><br><br><?php
zui::selectBox_new("select2", "2", ["0" => "first", "2" => "middle", "1" => "last"], "I'm a selectbox."); ?><br><br><?php
zui::selectBox_new("select3", "2", [["ID" => "0", "name" => "first"],["ID" => "2", "name" => "middle"],["ID" => "1", "name" => "last"]], "I'm a selectbox."); ?><br><br><?php
zui::selectBox_new("select3", "2", [
  ["ID" => "0", "name" => "first", "avatar" => "https://randomfox.ca/images/61.jpg"],
  ["ID" => "2", "name" => "middle", "avatar" => "https://randomfox.ca/images/84.jpg"],
  ["ID" => "1", "name" => "last", "avatar" => "https://randomfox.ca/images/76.jpg"]
], "I'm a selectbox."); ?><br><br><?php
