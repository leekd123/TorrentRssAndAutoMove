<?php

require_once "config/config.php";
require_once "target.php";

$site = $_GET["s"];
$sitehost = $path[$site];
$localhostpath = $localhost."/site/".$site;

$bo_table_list = array(
	"torrentwal" => array(""),
);


?>