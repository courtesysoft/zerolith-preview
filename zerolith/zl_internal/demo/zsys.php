<?php
require "../../zl_init.php";
zpage::start("zsys system library demo");
zui::printTable([zsys::getMemUsed()]);
zui::printTable(zsys::getDiskSpace());
zui::quip(zsys::getCpuUsedPct() . "%", "cpu use % across all cores");