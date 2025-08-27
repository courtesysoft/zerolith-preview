<?php
//incomplete - DS 02/04/2024
exit;

require "zpanelConfig.php"; //load zpanel settings modification
$imageTender = new imageTender("/var/www/files");
$imageTender->processBatch("/var/www/files");
?>