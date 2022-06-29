<?php
echo "INICIOU A EXECUÇÃO DO SCRIPT DE VALIDAÇÃO GERAL\n";
include("utils.php");
include("bd_connection.php");

$sql = "SELECT id as project_id, project, api_token as apiToken, api_url as apiUrl, database_name as redcapInstance, redcap_pid as pid
			FROM projetos_tuberculose.redcap_projects 
			WHERE id IN (SELECT DISTINCT project_id FROM projetos_tuberculose.redcap_forms WHERE autolock_autovalidate = 1)
				AND id NOT IN (4,11,14,16);";
				
$sql = "SELECT id as project_id, project, api_token as apiToken, api_url as apiUrl, database_name as redcapInstance, redcap_pid as pid
			FROM projetos_tuberculose.redcap_projects 
			WHERE id = 13;";
$result_projects = $conn->query($sql);
$projectsToValidate = $result_projects->fetch_all(MYSQLI_ASSOC);

foreach($projectsToValidate as $ptv) {
	extract($ptv);
	echo "Checando PROJECT ID $project_id - $project\n";

	$sql = "CALL `$redcapInstance`.`get_locking_and_complete_status_by_project`($project_id,0,0,1,100);";	
	echo $sql."\n";
	$result_forms = $conn->query($sql);	
	$formsToValidate = $result_forms->fetch_all(MYSQLI_ASSOC);
	$conn->close();
	$conn = new mysqli($servername, $username, $password, $dbname, 3306);
	
	foreach($formsToValidate as $ftv) {
		extract($ftv);
		echo "Checando RECORD ID $record - $form_name\n";
		if(!checkOpenQueries($form_name,$pid,$record,$instance,$event_id,$redcapInstance,$conn)) {			
			$uniqueEventName = exportRedcapEventByName($event_name,false,$apiToken,$apiUrl)["unique_event_name"];			
			echo "EVENTO: $event_name > $uniqueEventName \n";
			
			$data = exportRecordsRedCap($form_name,$uniqueEventName,$record,null,$apiToken,$apiUrl);
			$arrayData = json_decode($data,true);							
			//error_log(json_encode($arrayData),0);
					
			foreach($arrayData as $r) {
				if(isset($r["redcap_repeat_instance"]) && $r["redcap_repeat_instance"] == $instance) {
					$arrayData = array($r);
					break;
				}
			}			
			
			//error_log(json_encode($arrayData),0);			
			if(autovalidate($form_name,$arrayData[0],$pid,$apiToken,$apiUrl,$redcapInstance,'script_geral',$conn) == 1) {				
				if($value == 1) {				
					$arrayData[0][$form_name."_complete"] = "2";
				}									
			} else {
				$arrayData[0][$form_name."_complete"] = "0";
			}
		} else {
			$arrayData[0][$form_name."_complete"] = "0";
		}	
		if(intval($arrayData[0][$form_name."_complete"]) != $value) {
			error_log("alterando status do registro",0);
			importRecordsRedCap(json_encode($arrayData),$apiToken,$apiUrl);
			if(intval($arrayData[0][$form_name."_complete"]) == 2)
				locking($record,$uniqueEventName,$form_name,$instance,$apiUrl,$apiToken,"lock");
		}
	}	
}
echo "FINALIZOU A EXECUÇÃO DO SCRIPT DE VALIDAÇÃO GERAL\n";
?>