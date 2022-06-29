<?php
include("bd_connection.php");
include("utils.php");

$sql = "SELECT * FROM projetos_tuberculose.vw_redcap_validation_issues WHERE is_solved = 0 ORDER BY issue_date ASC;";
$result_issues = $conn->query($sql);
$issues = $result_issues->fetch_all(MYSQLI_ASSOC);

foreach($issues as $i) {
	$issue_id = $i["issue_id"];
	
	$project = getRedcapProjectByPk($i["project_id"],$conn);
	$api_token = $project["api_token"];
	$api_url = $project["api_url"];
	
	//$record = exportRecordRedCapByRecordIdAndInstanceAndEvent($i["form_name"],$i["redcap_record_id"],$i["form_instance_number"],$i["unique_event_name"],$api_token,$api_url);	
	$sql = "SELECT value FROM ".$i["redcap_instance"].".redcap_data WHERE project_id = ".$i["redcap_pid"]." AND event_id = ".$i["event_id"]." 
				AND record = '".$i["redcap_record_id"]."' AND (instance = ".$i["form_instance_number"]." OR instance IS NULL) 
				AND field_name = '".$i["field_name"]."';";				
	$result_record = $conn->query($sql);	
	
	/*if(!$result_record || $result_record->num_rows != 1) {
		echo $sql."\n";
		$sql = "DELETE FROM projetos_tuberculose.redcap_validation_issues WHERE id = $issue_id;";
		echo $sql."\n";
		//$conn->query($sql);
		continue;
	}	*/
	
	if(!$result_record || $result_record->num_rows != 1)
		$field_value = "";
	else
		$field_value = $result_record->fetch_assoc()["value"];	
	
	//$field_value = $record[$i["field_name"]];
	$other_value = $i["value_to_compare"];
	
	if($i["compare_other_field"] == 1) {
		$other_value = getFieldValueFromRedcap($i["redcap_pid"],$i["value_to_compare"],$i["redcap_record_id"],$i["form_instance_number"],$i["event_id"],$i["redcap_instance"],$conn);
	} else if($i["compare_function"] == 1) {					
		$other_value = getValueFromFunction($i["value_to_compare"],$i["redcap_pid"],$i["form_name"],$i["event_id"],$i["redcap_record_id"],$i["form_instance_number"],$i["redcap_instance"],$conn);
	} else if(!is_null($i["get_value_from_function"])) {					
		$field_value = getValueFromFunction($i["get_value_from_function"],$i["redcap_pid"],$i["form_name"],$i["event_id"],$i["redcap_record_id"],$i["form_instance_number"],$i["redcap_instance"],$conn);		
	}
		
	if(compareValues($field_value,$other_value,$i["comparator"],$i["is_regex"])) {
		$sql = "UPDATE projetos_tuberculose.redcap_validation_issues SET is_solved = 1, issue_date_solved = CURRENT_TIMESTAMP(), solved_by = 'system' WHERE id = $issue_id;";
		$conn->query($sql);
		echo $sql."\n";		
	}
}
?>