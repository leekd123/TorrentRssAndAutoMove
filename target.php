<?php

if($setting_type == "db")
	require_once "target.db.php";
elseif($setting_type == "json")
	require_once "target.json.php";
?>
