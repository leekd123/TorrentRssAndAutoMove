<?php

$con = mysqli_connect($db_info->host, $db_info->id, $db_info->pw) or die(mysqli_error($con));
mysqli_select_db($con, $db_info->name) or die(mysqli_error($con));
	
$settings = (object)[];
$settings->path = (object)[];
$settings->url = (object)[];

$query = "SELECT * FROM target WHERE is_use=1 ";
$result = mysqli_query($con, $query) or die(mysqli_error($con));

$targets = array();
while ( $row = mysqli_fetch_assoc($result) )
{
    $target = (object)[
    	"id"=> $row["id"],
    	"category" => $row["category"],
    	"season" => $row["season"],
    	"season_folder" => $row["season_folder"],
    	"ep_calc" => $row["ep_calc"],
    	"name" => $row["name"],
    	"new_name" => $row["new_name"] == "" ? $row["name"] : $row["new_name"],
    	"is_bind" => $row["is_bind"],
    	"is_hold" => $row["is_hold"],
    	"is_use" => $row["is_use"],
	];
    array_push($targets, $target);
}

$query = "SELECT * FROM setting ";
$result = mysqli_query($con, $query) or die(mysqli_error($con));

while ( $row = mysqli_fetch_assoc($result) )
{
	switch($row["type"]) {
	    case "path" :
        	switch($row["name"]) {
        		case "source": $settings->path->source = $row["val"]; break;
        		case "destination": $settings->path->destination = $row["val"]; break;
        		case "complete": $settings->path->complete = $row["val"]; break;
        		case "fake": $settings->path->fake = $row["val"]; break;
        		case "overlap": $settings->path->overlap = $row["val"]; break;
        		case "hold": $settings->path->hold = $row["val"]; break;
        		case "log": $settings->path->log = $row["val"]; break;
        		default : break;
        	}
	        break;   
	    case "url" :
        	switch($row["name"]) {
        		case "sms": $settings->url->sms = $row["val"]; break;
        		default : break;
        	}
	        break;   
		default : break;
	}
}
$settings->target = $targets;

mysqli_free_result($result);
mysqli_close($con);

?>
