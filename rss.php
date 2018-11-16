<?php

require_once "settings.php";

$param = $_SERVER['QUERY_STRING'];
$url = "$localhost/rss.site.php?$param";

$xml = simplexml_load_file($url);
$itemarr = $xml->channel->item;

for ($i = 0; $i < count($itemarr); $i++) {
	$is_target = false;
	foreach($settings->target as $target){
		if($target->name == "") continue;
		if(!$target->is_use) continue;

		$tmp_title = str_replace(" ", "", $itemarr[$i]->title);
		$tmp_target = str_replace(" ", "", $target->name);

		if(preg_match("/$tmp_target/", $tmp_title)) {
			$is_target = true;
			break;
		}
	}

	if(!$is_target){
		unset ($itemarr[$i--]);
	}
}

echo $xml->asXML();

?>