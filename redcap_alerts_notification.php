<?php
include("utils.php");
include("bd_connection.php");
include("contact.php");

$sql = "SELECT id AS alert_dag_id, dag_id, project_id, category_id, redcap_name, name, recipients FROM projetos_tuberculose.redcap_alerts_dag;";
$result = $conn->query($sql);
$alerts_dag = $result->fetch_all(MYSQLI_ASSOC);

foreach($alerts_dag as $dag) {
	extract($dag);
	
	$where_category = "";
	if(isset($category_id) && $category_id != null)
		$where_category = "AND category_id = $category_id";
	
	$sql = "SELECT project_name,center_name,recipients,short_description,DATE_FORMAT(deadline, '%d/%m/%Y') AS deadline,deadline_expired,record FROM projetos_tuberculose.vw_redcap_alerts_log WHERE dag_id = $dag_id AND sent = 0 $where_category ORDER BY dag_id, short_description, record;";
	$result = $conn->query($sql);
	$alerts = $result->fetch_all(MYSQLI_ASSOC);

	if($result->num_rows > 0) {
		$project_name = utf8_decode($alerts[0]["project_name"]);
		$center_name = utf8_decode($alerts[0]["center_name"]);
		$recipients = $alerts[0]["recipients"];
		
		$msg = "<html>";
		$msg .= "<head></head>";
		$msg .= "<body style='font-size:15px;'>";
		$msg .= "<p><strong>ALERTAS - PREENCHIMENTOS PENDENTES - REDCap</strong></p>";
		$msg .= "<p><strong>Centro:</strong> $center_name</p>";
		$msg .= "<ul>";
		foreach($alerts as $a) {
			$msg .= "<li>PID ".$a["record"]." - ".utf8_decode($a["short_description"]." - até ".$a["deadline"]);
			if($a["deadline_expired"] == 1)
				$msg .= " - <span style='color:red;'>PRAZO EXPIRADO</span>";
			$msg .= "</li>";
		}
		$msg .= "</ul>";
		$msg .= "<p><strong>Equipe de TI e Gerenciamento de Dados<br/>Projeto $project_name</strong></p>";
		$msg .= "</body></html>";
		$subject = "[$project_name] Alertas de Preenchimentos Pendentes - REDCap - $center_name";
		//echo $msg."<br>";
		//$recipients = "viniciuslima@usp.br";
		if(sendMail($recipients,null,$subject,$msg)) {
			$sql = "UPDATE redcap_alerts_log SET sent = 1, sent_timestamp = NOW() WHERE alert_dag_id = $alert_dag_id $where_category AND sent = 0;";
			echo $sql."\n";
			$conn->query($sql);
		}
	}
}
?>