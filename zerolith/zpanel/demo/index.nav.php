<?php
require "../zpanelConfig.php"; //load zpanel settings modification

//populate the nav with all the files in /demo
$exclusions = ['index.php', 'index.nav.php']; //no showy!
foreach(glob("*.php") as $nav) { if(!in_array($nav, $exclusions)) { zpage::addNavItem($nav, $nav, "save"); } }
zl::$page['logoLink'] = "/zerolith/zpanel/index.php"; //monkeypatch for icon