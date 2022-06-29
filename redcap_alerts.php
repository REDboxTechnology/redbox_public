<?php
include("utils.php");
include("bd_connection.php");
include("contact.php");

$sql = "SELECT * FROM projetos_tuberculose.vw_redcap_alerts WHERE enabled = 1;";
$result = $conn->query($sql);
$alerts = $result->fetch_all(MYSQLI_ASSOC);

foreach($alerts as $a) {
	extract($a);
	
	if($project_id == 13) {
		$sql = "CALL `projetos_tuberculose`.`redcap_sbgm_update_dag_alerts`(13,29);";
		$conn->query($sql);
	}
	
	$project = getRedcapProjectByPk($project_id,$conn);
	$apiToken = $project["api_token"];
	$apiUrl = $project["api_url"];

	echo "ALERTA ".$project["project_name"]." - $short_description\n";
	$data = exportRecordsFieldsRedCap(null,$event_name,null,$filter_logic,array("record_id","$ref_field"),$apiToken,$apiUrl);
	$arrayData = json_decode($data,true);							
	//error_log(json_encode($arrayData),0);
			
	/*foreach($arrayData as $r) {
		if($r["redcap_repeat_instance"] == $instance) {
			$arrayData = array($r);
			break;
		}
	}*/			
	
	//error_log(json_encode($arrayData),0);			
	$today = date_create();
	date_time_set($today, 00, 00);
	foreach($arrayData as $d) {
		if(isset($d[$ref_field]) && !empty($d[$ref_field])) {		
			$startDate = date_add(date_create($d[$ref_field]), date_interval_create_from_date_string($days_offset.' days'));
			$limitDate = date_add($startDate, date_interval_create_from_date_string($days.' days'));
			$diff = (array) date_diff($today, $limitDate);
			//echo print_r($diff,1);

			$alert = false;
			$expired = 0;
			
			if($today > $startDate) {
				$mod = $diff["days"]%$days_frequency;
				if($mod == 0) {
					$alert = true;
					if($today > $limitDate) { //expired
						$expired = 1;
					}
				}
			}
			
			if($alert) {
				$dag_id = 0;
				$pos = strpos($d["record_id"],"-");
				if($pos !== false)
					$dag_id = substr($d["record_id"],0,$pos);
				
				$sql = "SELECT id FROM projetos_tuberculose.redcap_alerts_dag WHERE dag_id = $dag_id AND (category_id = $category_id OR category_id IS NULL) LIMIT 1;";
				$result = $conn->query($sql);
				$alert_dag_id = $result->fetch_assoc()["id"];

				$sql = "INSERT INTO projetos_tuberculose.redcap_alerts_log (alert_dag_id,alert_id,record,deadline,deadline_expired,category_id) VALUES ($alert_dag_id,$alert_id,'".$d["record_id"]."','".$limitDate->format('Y-m-d')."',$expired,$category_id);";
				$conn->query($sql);
				echo $sql."\n";
			}
		}
	}
}
echo "FINALIZOU EXECUÇÃO SCRIPT ALERTAS\n";
?>