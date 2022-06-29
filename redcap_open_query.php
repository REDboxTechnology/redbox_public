<?php
include("bd_connection.php");
include("utils.php");

$data = file_get_contents('php://input');
$data = json_decode($data,true);

$code = $data["code"];
if(!isset($code) || $code != "yourpassword") {
	$response["status"] = "401";
	$response["message"] = "Unauthorized";
	echo json_encode($response);
	return;
}

$response = array();

$pid = $data["redcap_pid"];
$recordId = $data["record_id"];
$fieldName = $data["field_name"];
$instance = $data["instance"];
$userId = $data["user_id"];
$comment = $data["comment"];
$redcapInstance = $data["redcap_instance"];

$assignedUserId = "";

openQuery($pid,$recordId,$fieldName,$instance,$userId,$assignedUserId,$comment,$redcapInstance,$conn);

$response["status"] = "200";
$response["message"] = "OK";
echo json_encode($response);
?>