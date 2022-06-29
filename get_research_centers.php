<?php
include("bd_connection.php");
include("utils.php");

if(!isset($_SERVER["HTTP_REFERER"]) || strpos($_SERVER["HTTP_REFERER"], "yourdomain.com") === false 
		|| !isset($_POST["project_id"]) || empty($_POST["project_id"])) {
	$response["status"] = "401";
	$response["message"] = "Unauthorized " . $_SERVER["HTTP_REFERER"];
	echo json_encode($response);
	return;
}

$pid = $_POST["project_id"];
$redcap_url = $_POST["redcap_url"];
$api = getRedcapProject($pid,$redcap_url,$conn);

if($api == null) {
	$response["status"] = "404";
	$response["message"] = "Project not found";
	echo json_encode($response);
	return;
}

$project_id = $api["id"];
$sql = "SELECT custom_center_field FROM vw_redcap_visits WHERE project_id = $project_id LIMIT 1;";
$result = $conn->query($sql);
$custom_center_field = $result->fetch_assoc()["custom_center_field"];

$centros = array();
if($custom_center_field == "record-dag-name") {
	$dags = json_decode(exportDAGs($api["api_token"],$api["api_url"]),true);
	foreach ($dags as $dag) {		
		$c = array();
		$c["cod"] = $dag["unique_group_name"];
		$c["nome"] = $dag["data_access_group_name"];
		array_push($centros,$c);	
	}	
} else {
	$fields = array($custom_center_field);
	$m = json_decode(exportRedCapFormOrFieldMetadata(null,$fields,$api["api_token"],$api["api_url"]),true);

	if(sizeof($m) != 1) {
		$response["status"] = "404";
		$response["message"] = "Metadata not found";
		echo json_encode($response);
		return;
	}
		
	$aux = explode(" | ",$m[0]["select_choices_or_calculations"]);	
	foreach ($aux as $centro) {
		$aux2 = explode(",",$centro);
		$c = array();
		$c["cod"] = trim($aux2[0]);
		$c["nome"] = trim($aux2[1]);
		array_push($centros,$c);	
	}	
}

$response["status"] = "200";
$response["message"] = $centros;
echo json_encode($response);
?>