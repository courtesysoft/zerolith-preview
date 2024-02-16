<?php
//incomplete - DS 02/04/2024
exit;
$bench = new benchmark();

$bench->crawl("https://yeah-like-whatever.com", 20);
$bench->cleanCrawl();
$bench->crawlReport();

?>