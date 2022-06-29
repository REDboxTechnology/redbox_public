<?php
include("utils.php");
include("bd_connection.php");
include("contact.php");

$sql = "SELECT id AS project_id,project AS project_name,redcap_pid,database_name FROM projetos_tuberculose.redcap_projects WHERE alert_queries = 1;";
$result = $conn->query($sql);
$projects = $result->fetch_all(MYSQLI_ASSOC);

foreach($projects as $p) {
	extract($p);
	$sql = "SELECT R.group_id,
				(SELECT group_name FROM $database_name.redcap_data_access_groups G WHERE G.group_id = R.group_id) as group_name,
				GROUP_CONCAT(U.user_email SEPARATOR ',') as users_mail
			FROM $database_name.redcap_user_rights R
				INNER JOIN $database_name.redcap_user_information U ON U.username = R.username
			WHERE R.project_id = $redcap_pid AND R.group_id IS NOT NULL
			GROUP BY R.group_id;";
	$result = $conn->query($sql);
	$alerts_dag = $result->fetch_all(MYSQLI_ASSOC);
	
	foreach($alerts_dag as $dag) {
		extract($dag);
		
		$sql = "SELECT record,field_name,instance,query_status FROM $database_name.redcap_data_quality_status WHERE query_status = 'OPEN' AND project_id = $redcap_pid AND record LIKE '$group_id-%';";
		$result = $conn->query($sql);
		$queries = $result->fetch_all(MYSQLI_ASSOC);
		$queries_count = $result->num_rows;
		
		if($queries_count > 0) {
			$project_name = utf8_decode($project_name);
			$group_name = utf8_decode($group_name);
			$recipients = $users_mail;
			
			$msg = "<html>";
			$msg .= "<head></head>";
			$msg .= "<body style='font-size:15px;'>";
			$msg .= "<p><strong>ALERTAS - QUERIES PENDENTES - REDCap</strong></p>";
			//$msg .= "<p>$recipients</p>";
			$msg .= "<p><strong>Centro:</strong> $group_name</p>";
			$msg .= utf8_decode("<table><thead><tr><th>Record ID</th><th>Campo/variável</th><th>Instância</th><th>Status</th></tr></thead><tbody>");
			foreach($queries as $q) {
				extract($q);
				$msg .= "<tr>";
					$msg .= "<td>$record</td>";
					$msg .= "<td>$field_name</td>";
					$msg .= "<td>$instance</td>";
					$msg .= "<td>$query_status</td>";
				$msg .= "</tr>";
			}
			$msg .= "</tbody></table>";
			$msg .= "<p><strong>Equipe de TI e Gerenciamento de Dados<br/>Projeto $project_name</strong></p>";
			$msg .= "</body></html>";
			$subject = "[$project_name] Alertas de Queries Pendentes - REDCap";
			//echo $msg."<br><br>";
			$recipients = "viniciuslima@usp.br";
			if(sendMail($recipients,null,$subject,$msg)) {
				$sql = "INSERT INTO projetos_tuberculose.redcap_alerts_queries_log (project_id,dag_id,queries_count) VALUES ($project_id,$group_id,$queries_count);";
				//error_log($sql,0);
				$conn->query($sql);
			}
		}
	}
}
?>