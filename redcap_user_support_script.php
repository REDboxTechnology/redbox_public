<?php
include("utils.php");
include("bd_connection.php");
include("contact.php");

$sql = "SELECT S.*, P.api_url, P.api_token, P.database_name, P.project
			FROM projetos_tuberculose.redcap_user_support S
				INNER JOIN projetos_tuberculose.redcap_projects P ON S.project_id = P.id
			WHERE complete = 0;";
$result = $conn->query($sql);
$requests = $result->fetch_all(MYSQLI_ASSOC);

foreach($requests as $req) {
	extract($req);
	if($action == "unlock") {
		$locked = intval(json_decode(locking($record_id,$event_unique_name,$form_name,$instance,$api_url,$api_token,"status"),true)[0]["locked"]);
		if($locked == 1) {
			locking($record_id,$event_unique_name,$form_name,$instance,$api_url,$api_token,"unlock");
			$record = json_decode(exportRecordsRedCap($form_name,$event_unique_name,$record_id,null,$api_token,$api_url),true)[$instance-1];
			$record[$form_name."_complete"] = "0";
			$record = array($record);			
			importRecordsRedCap(json_encode($record),$api_token,$api_url);
		}
		$sql = "UPDATE projetos_tuberculose.redcap_user_support SET complete=1 WHERE id = $id;";
	} else if($action == "delete" && $support_team_alert_sent == 0) {
		$emailNotification = "gd@redbox.technology";
		if($database_name == "redcap_sbgm")
			$emailNotification = "suporte@raras.org.br";
						
		if($form_name == "todos") {
			$formDesc = "($form_name)";
		} else {
			$form = getFormByName($project_id,$form_name,$conn);
			$formDesc = "(".$form["description"]." - instância nº $instance)";
		}
		
		sendMail($emailNotification,null,utf8_decode("[REDbox] $project - Exclusão de registro"),"Olá,<br/><br/>Foi solicitada a exclusão do registro <strong>nº $record_id</strong> $formDesc.<br/><strong>Solicitante:</strong> $username<br/><strong>Motivo:</strong> $reason<br/><br/><strong>Equipe de TI e Gerenciamento de Dados</strong>");
		$sql = "UPDATE projetos_tuberculose.redcap_user_support SET support_team_alert_sent=1 WHERE id = $id;";
	}	
	$conn->query($sql);
}
?>