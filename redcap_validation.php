<?php
include("bd_connection.php");
include("utils.php");

$http = new HTTPRequester();

$sql = "SELECT id as project_id, database_name as redcap_instance, redcap_pid, api_token, api_url FROM projetos_tuberculose.redcap_projects;";
$result_projects = $conn->query($sql);
$projects = $result_projects->fetch_all(MYSQLI_ASSOC);

foreach($projects as $p) {
	extract($p);
	$sql = "SELECT id as form_id, form_name FROM projetos_tuberculose.redcap_forms WHERE project_id = $project_id AND check_data_quality = 1;";
	$result_forms = $conn->query($sql);
	
	if(!$result_forms || $result_forms->num_rows == 0)
		continue;
	
	$sql = "SELECT * FROM projetos_tuberculose.vw_redcap_validation_rules WHERE enabled = 1 AND project_id = $project_id;";
	$result_validation_rules = $conn->query($sql);
	//echo $sql."\n";
	
	$validation_rules = $result_validation_rules->fetch_all(MYSQLI_ASSOC);
	
	$forms = $result_forms->fetch_all(MYSQLI_ASSOC);
	foreach($forms as $f) {
		$form_id = $f["form_id"];
		$form_name = $f["form_name"];
		
		echo "FORM ID: ".$form_id."\n";
				
		$filter = "[".$form_name."_complete] = '1' or [".$form_name."_complete] = '2'";
		//$filter = null;
		$records = json_decode(exportRecordsRedCap($form_name,null,null,$filter,$api_token,$api_url),true);	
			
		foreach($records as $r) {					
			$record_id = $r["record_id"];
			
			$instance = 1;
			if(isset($r["redcap_repeat_instance"]) && !empty($r["redcap_repeat_instance"]))
				$instance = $r["redcap_repeat_instance"];							
			
			$unique_event_name = "event_1_arm_1";
			if(isset($r["redcap_event_name"]) && !empty($r["redcap_event_name"])) {
				$unique_event_name = $r["redcap_event_name"];					
			}
			
			$event_id = getEventIdByEventName($redcap_pid,$unique_event_name,$redcap_instance,$api_token,$api_url,$conn);
			
			foreach($validation_rules as $rule) {					
				if(!isset($r[$rule["field_name"]]) || $rule["form_id"] != $form_id)
					continue;
				
				$field_value = $r[$rule["field_name"]];
				$other_value = $rule["value_to_compare"];
				
				if($rule["compare_other_field"] == 1) {
					$other_value = getFieldValueFromRedcap($redcap_pid,$rule["value_to_compare"],$record_id,$instance,$event_id,$redcap_instance,$conn);
					if(is_null($other_value))
						continue;
				} else if($rule["compare_function"] == 1) {					
					$other_value = getValueFromFunction($rule["value_to_compare"],$redcap_pid,$form_name,$event_id,$record_id,$instance,$redcap_instance,$conn);
				} else if(!is_null($rule["get_value_from_function"])) {
					$field_value = getValueFromFunction($rule["get_value_from_function"],$redcap_pid,$form_name,$event_id,$record_id,$instance,$redcap_instance,$conn);
					//echo $rule_id." ".$project_id." ".$form_name." ".$record_id." ".$field_value."\n";
				}
				
				$rule_id = $rule["rule_id"];
				if(!compareValues($field_value,$other_value,$rule["comparator"],$rule["is_regex"])) {
					$sql = "SELECT id FROM projetos_tuberculose.redcap_validation_issues 
								WHERE validation_rule_id = $rule_id AND redcap_record_id = '$record_id' 
									AND form_instance_number = $instance AND event_id = $event_id AND is_solved = 0;";
					//echo $sql."<br/>";
					$result_issues = $conn->query($sql);
					if($result_issues->num_rows == 0) {				
						$sql = "INSERT INTO projetos_tuberculose.redcap_validation_issues (validation_rule_id,redcap_record_id,form_instance_number,event_id,unique_event_name) VALUES ($rule_id,'$record_id',$instance,$event_id,'$unique_event_name');";
						$conn->query($sql);
						echo $sql."\n";
					}
				}									
			}
		}		
	}
}
?>