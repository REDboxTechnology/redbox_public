<?php
include("bd_connection.php");
include("utils.php");

$response = array();

$pid = $_GET["redcap_pid"];
$redcap_url = $_GET["redcap_url"];

$project = getRedcapProject($pid,$redcap_url,$conn);

if($project == -1) {
	$response["status"] = "404";
	$response["message"] = "Not found";
	echo json_encode($response);
	exit();
}

$api_token = $project["api_token"];
$api_url = $project["api_url"];

$v = exportRedcapVersion($api_token,$api_url);
$response["status"] = "200";
$response["message"] = $v;
echo json_encode($response);

?>