<?php
include("bd_connection.php");
include("utils.php");
include("contact.php");

error_log("REDCap DET",0);

/*if(!file_exists('data.txt')) {
    touch('data.txt');
}*/

$redcapUrl = $_POST["redcap_url"];
if(!isset($redcapUrl) || !in_array($redcapUrl,array("https://redcap.redetb.org.br/","https://redcap.raras.org.br/"))) {
	exit();
}

$event = '';
if(isset($_POST["redcap_event_name"]))
	$event = $_POST["redcap_event_name"];

$instance = '';
if(isset($_POST["redcap_repeat_instance"]))
	$instance = $_POST["redcap_repeat_instance"];

$dag = '';
if(isset($_POST["redcap_data_access_group"]))
	$dag = $_POST["redcap_data_access_group"];

$pid = $_POST["project_id"];
$project = getRedcapProject($pid,$redcapUrl,$conn);
$projectName = $project["project"];
$projectID = $project["id"];

$recordID = $_POST["record"];
$author = $_POST["username"];
$formName = $_POST["instrument"];

if(!isset($_POST[$formName."_complete"]))
	exit();

$complete = $_POST[$formName."_complete"];

$form = getFormByName($projectID,$formName,$conn);

if($form == -1)
	exit();

$firstForm = $form["first_form"];
$formID = $form["form_id"];

//$prefix = "f".$formID."_";
$today = date("Y-m-d H:i:s");

//GET OPERATION
$sql = "SELECT id FROM form_data WHERE project_id = $projectID AND redcap_record_id = $recordID;";
$result = $conn->query($sql);
$operation = "add";
if($result->num_rows > 0) {
	$operation = "edit";
}

$apiToken = $project["api_token"];
$apiUrl = $project["api_url"];

$data = exportRecordsRedCap($formName,$event,$recordID,null,$apiToken,$apiUrl);
//file_put_contents('data.txt',"$data",FILE_APPEND);
//error_log($data,0);
$arrayData = json_decode($data,true);

//IF IT IS A REPEATABLE FORM, GET DATA FROM THE CORRECT INSTANCE
if($instance != "") {
	foreach($arrayData as $r) {
		if($r["redcap_repeat_instance"] == $instance) {
			$arrayData = array($r);
			break;
		}
	}	
}

//CHECK IF DATA EXISTS OR WAS DELETED
if($arrayData != null && !empty($arrayData)) {
	$semantics = checkSemantics($arrayData[0],$form["id"],$conn);
	$anonymizedData = checkIdentifiers($arrayData[0],$form["id"],$conn);
	$data = json_encode($anonymizedData);
} else {
	$operation = "delete";
	$semantics = '';	
}

//WRITE DATA TO TXT FILE
//file_put_contents('data.txt',"[Proj $projectID - Form $formID - RECORD_ID $recordID - $operation - PARSED] [$today] $data\n",FILE_APPEND);

//WRITE DATA TO MYSQL
$sql = "INSERT INTO form_data (project_id,redcap_record_id,unique_id,first_form,form_id,json_data,parsed_json_data,author,operation,timestamp) 
	VALUES ('$projectID',$recordID,'$recordID',$firstForm,'$formID','$data','$data','$author','$operation','$today');";
//file_put_contents('data.txt',"$sql\n",FILE_APPEND);
$conn->query($sql);
$last_id = $conn->insert_id;

//WRITE DATA TO BLOCKCHAIN		
if($form["kaleido_enabled"] == 1) {
	$kldCredentials = getKldCredentials($projectID,$conn);			
	if($kldCredentials != null) {
		if(isset($kldCredentials["kld_ipfs_api_endpoint"]) && !empty($kldCredentials["kld_ipfs_api_endpoint"]) && $operation != "delete") {
			//WRITE FILES TO IPFS
			$filesFields = checkForFilesFields($arrayData[0]);		
			$ipfs = array();
			foreach($filesFields as $f) {
				$exportedFile = exportFileRedCap($f,$recordID,$event,$apiToken,$apiUrl);
				$ipfs[] = json_decode(ipfsAdd($exportedFile,$kldCredentials,$conn),true);
			}		
			
			if(!empty($ipfs)) {
				$d = json_decode($anonymizedData,true);
				$d["files_ipfs"] = $ipfs;
				$data = json_encode($d);
			}		
		}
		//WRITE TX AND BLOCKS
		writeToBlockchain($data,$last_id,$operation,$projectID,$formName." (".$event.")",$recordID,$author,$semantics,$kldCredentials,$conn);
	}		
}	

$isREDCapAdmin = isREDCapAdmin($author,$project["database_name"],$conn);

if($isREDCapAdmin)
	error_log("eh admin",0);
else
	error_log("nao eh admin",0);

//AUTOLOCK
$locked = intval(json_decode(locking($recordID,$event,$formName,$instance,$apiUrl,$apiToken,"status"),true)[0]["locked"]);
if($form["autolock"] == 1) {	
	if($locked == 0) {
		$lock = false;
		if((($form["autolock_admin_only"] == 1 && $isREDCapAdmin) || ($form["autolock_admin_only"] == 0 && $form["autolock_autovalidate"] == 0)) && $complete == 2) {
			$lock = true;
		} else if($form["autolock_autovalidate"] == 1 && ($complete == 1 || $complete == 2)) {					
			error_log("iniciar autovalidacao",0);
			$eventId = getEventIdByEventName($pid,$event,$project["database_name"],$apiToken,$apiUrl,$conn);			
			if(!checkOpenQueries($formName,$pid,$recordID,$instance,$eventId,$project["database_name"],$conn)) {
				if(autovalidate($formName,$arrayData[0],$pid,$apiToken,$apiUrl,$project["database_name"],'det',$conn) == 1) {
					$lock = true;
					error_log("registro eh valido",0);
					if($complete == 1) {		
						$arrayData[0][$formName."_complete"] = "2";
					}					
				} else {
					$arrayData[0][$formName."_complete"] = "0";
				}					
			} else {
				$arrayData[0][$formName."_complete"] = "0";
			}	 
			if(intval($arrayData[0][$formName."_complete"]) != $complete) {
				error_log("alterando status do registro",0);
				importRecordsRedCap(json_encode($arrayData),$apiToken,$apiUrl);	
			}
		}
		if($lock) {
			error_log("fazendo lock",0);
			$locked = 1;
			locking($recordID,$event,$formName,$instance,$apiUrl,$apiToken,"lock");
		}
	} else if($locked == 1 && $complete != 2 && $form["autolock_unlock_non_complete"] == 1) {
		error_log("fazendo unlock",0);
		locking($recordID,$event,$formName,$instance,$apiUrl,$apiToken,"unlock");		
	}
}

//FORCE UNVERIFIED STATUS FOR NON ADMINS
if($form["force_unverified"] == 1 && $complete == 2 && !$isREDCapAdmin && $locked == 0) {	
	error_log("force_unverified",0);
	$arrayData[0][$formName."_complete"] = "1";
	$count = importRecordsRedCap(json_encode($arrayData),$apiToken,$apiUrl);
}

//CHECK FOR RECORDS DUPLICITY
if($form["check_duplicity"] == 1) {			
	$targetFields = explode(",",$form["check_duplicity_fields"]);
	$duplicates = array();
	
	foreach($targetFields as $field) {
		$field = trim($field);
		if(isset($arrayData[0][$field]))
			$records = checkDuplicity($pid,$field,$recordID,$arrayData[0][$field],$project["database_name"],$conn);		
		
		if(isset($records) && !empty($records) && !is_null($records)) {
			foreach($records as $r) {
				if(!in_array($r["record"],$duplicates))
					$duplicates[] = $r["record"];
			}
		}				
	}

	if(!empty($duplicates)) {	
		//GET RESPONDANT EMAIL
		$users = json_decode(exportRedCapProjectUsers($apiToken,$apiUrl),true);
		$userMail = "";
		foreach($users as $u) {
			if($u["username"] == $author) {
				$userMail = $u["email"];
				break;
			}				
		}
		
		//CHECK DAG ENABLED
		$duplicatesInternal = array();
		$duplicatesExternal = array();
		if (strpos($recordID, '-') !== false) {			
			$centerID = trim(explode("-",$recordID)[0]);
			foreach($duplicates as $d) {
				$d = trim($d);
				$duplicateCenterID = explode("-",$d)[0];
				if($centerID == $duplicateCenterID) {
					$duplicatesInternal[] = $d;
				} else {
					$duplicatesExternal[] = $d;
				}
			}						
		} else {
			$duplicatesInternal = $duplicates;
		}
		
		//STORE DATA MYSQL
		foreach($duplicates as $d) {
			$sql = "INSERT INTO redcap_duplicates (project_id,record_id,record_id_duplicate,username) VALUES ($projectID,'$recordID','$d','$author')";
			$conn->query($sql);
		}
		
		//SEND NOTIFICATIONS
		$formDesc = $form["description"];
		$emailNotification = $form["email_notification"];
		//$emailNotification = "viniciuscosta90@gmail.com";
		if(!empty($duplicatesExternal)) {
			$duplicatesStringExternal = implode(", ",$duplicatesExternal);
			sendMail($emailNotification,null,utf8_decode("[REDbox] $projectName - $formDesc - Duplicidade de registro EXTERNO"),"Olá,<br/><br/>Foi identificado um registro duplicado entre centros - <strong>nº $recordID</strong>.<br/><strong>Registros duplicados:</strong> $duplicatesStringExternal<br/><br/><strong>Equipe de TI e Gerenciamento de Dados</strong>");
		}				
		if(!empty($duplicatesInternal)) {
			if($locked == 1 && $form["autolock_unlock_duplicate"] == 1) {	
				locking($recordID,$event,$formName,$instance,$apiUrl,$apiToken,"unlock");
				$arrayData[0][$formName."_complete"] = "0";
				$count = importRecordsRedCap(json_encode($arrayData),$apiToken,$apiUrl);
			}			
			$duplicatesString = implode(", ",$duplicatesInternal);		
			sendMail($userMail,$emailNotification,utf8_decode("[REDbox] $projectName - $formDesc - Duplicidade de registro"),"Prezado(a),<br/><br/>Foi identificado um registro duplicado no seu centro - <strong>nº $recordID</strong>.<br/><strong>Registros duplicados:</strong> $duplicatesString<br/><br/><strong>Equipe de TI e Gerenciamento de Dados</strong>");
		}
	}
}

/************************* TEMP SOLUTIONS *************************/
//RARAS --> IMPORTAR DADOS DO PROJETO RETROSPECTIVO
if($pid == 29 && $redcapUrl == "https://redcap.raras.org.br/" && $complete == 1 && $formName == "importao_estudo_retrospectivo" && $arrayData[0]['importacao_retrospect'] == "0") {	
	error_log("IMPORTACAO RARAS RETROSPECTIVO: ".$arrayData[0]['record_id_retrospectivo'],0);
	if(isset($arrayData[0]['record_id_retrospectivo']) && !empty($arrayData[0]['record_id_retrospectivo'])) {
		$recordRetrospec = json_decode(exportFullRecordsRedCap(null,null,$arrayData[0]['record_id_retrospectivo'],null,"BE22E902B96DC573D8393BF8EB5FAAF5","https://redcap.raras.org.br/api/"),true);		
		if(isset($recordRetrospec) && !empty($recordRetrospec)) {
			$notImportFields = array("recorrencia_familiar","resp_preench_tto","data_preench_tto","resp_preench_diag","data_preench_diag","resp_preench_identificacao","data_preench_identificacao","idade","tipo_identificador","identificador","sexo","internacao_previa","qtd_internacoes","dta_internacao_previa","cid_internacao_previa","ocorrencia_obito","necropsia_realizada","dta_obito","cid_obito","terminologia", "data_consulta_revisada");
			for($i=0;$i<sizeof($recordRetrospec);$i++) {
				$recordRetrospec[$i]["record_id"] = $recordID;			
				foreach($notImportFields as $f) {
					unset($recordRetrospec[$i][$f]);
				}				
			}
			//file_put_contents('data.txt',json_encode($recordRetrospec),FILE_APPEND);
			$resp = importRecordsRedCap(json_encode($recordRetrospec),"645FD37FAFCF55F40ACD1EB428D664B8","https://redcap.raras.org.br/api/");
			error_log("RESULT IMPORTACAO RARAS RETROSPECTIVO: ".$resp,0);
			$sql = "UPDATE redcap_sbgm.redcap_data SET value = '0' WHERE project_id = 29 AND field_name LIKE '%_complete' AND field_name <> 'importao_estudo_retrospectivo_complete' AND record = '$recordID';";
			$conn->query($sql);
			$sql = "UPDATE redcap_sbgm.redcap_data SET value = '1' WHERE project_id = 29 AND field_name = 'importacao_retrospect' AND record = '$recordID';";
			$conn->query($sql);				
		}
	}
	$sql = "UPDATE redcap_sbgm.redcap_data SET value = '2' WHERE project_id = 29 AND field_name = 'importao_estudo_retrospectivo_complete' AND record = '$recordID';";
	$conn->query($sql);
	locking($recordID,$event,$formName,$instance,$apiUrl,$apiToken,"lock");
} //FESIMA/IAL
else if($pid == 36 && $redcapUrl == "https://redcap.redetb.org.br/" && ($complete == 1 || $complete == 2)) {	
	if($formName == "roteiro_para_visita_tcnica_lab_de_tb_bac_trm_e_cul" || $formName == "roteiro_para_visita_tcnica_lab_de_tb_baciloscopia") {
		if($instance == "") $instance = 1;
		$formMetadata = json_decode(exportRedCapFormMetadata($formName,$apiToken,$apiUrl),true);
		$record = json_decode(exportFullRecordsRedCap($formName,$event,$recordID,null,$apiToken,$apiUrl),true)[$instance-1];
		
		$matrixList = array();
		foreach($formMetadata as $m) {	
			if($m["matrix_group_name"] != "" && !in_array($m["matrix_group_name"],$matrixList)) {
				$matrixList[$m["matrix_group_name"]][] = $m["field_name"];
			}
		}

		foreach($matrixList as $matrix => $matrixFields) {
			$count_A = 0;
			$count_AP = 0;
			$count_NA = 0;
			foreach($matrixFields as $field) {
				switch ($record[$field]) {
					case 'A':
						$count_A++;
						break;
					case 'AP':
						$count_AP++;
						break;
					case 'NA':
						$count_NA++;
						break;
				}
			}
			
			$matrixList[$matrix]["count_A"] = $count_A;
			$matrixList[$matrix]["count_AP"] = $count_AP;
			$matrixList[$matrix]["count_NA"] = $count_NA;
			
			if($matrix != "ix_req_documentacao" && $matrix != "x_req_doc_equip_40"
				&& $matrix != "xi_req_doc_ind_gerais" && $matrix != "xii_req_doc_indicadores"
				&& $matrix != "viii_req_documentacao_41" && $matrix != "ix_req_doc_equip_41"
				&& $matrix != "x_req_doc_ind_gerais_41" && $matrix != "xi_req_doc_indicadores_41") {
				$matrixList[$matrix]["percent_A"] = round(($count_A/($count_A+$count_AP+$count_NA))*100,2);
				$matrixList[$matrix]["percent_AP_NA"] = round((($count_AP+$count_NA)/($count_A+$count_AP+$count_NA))*100,2);
			}
		}

		if($formName == "roteiro_para_visita_tcnica_lab_de_tb_bac_trm_e_cul") {
			$record["i_infraestrutura_atendidos"] = $matrixList["i_req_infraestrutura"]["percent_A"];
			$record["i_infraestrutura_ap_e_na"] = $matrixList["i_req_infraestrutura"]["percent_AP_NA"];
			$record["ii_biosseguranca_atendidos"] = $matrixList["ii_req_biosseg"]["percent_A"];
			$record["ii_biosseguranca_ap_e_na"] = $matrixList["ii_req_biosseg"]["percent_AP_NA"];
			$record["iii_colet_transp_atendidos"] = $matrixList["iii_req_coleta_transp_amostras"]["percent_A"];
			$record["iii_colet_transp_ap_e_na"] = $matrixList["iii_req_coleta_transp_amostras"]["percent_AP_NA"];
			$record["iv_equip_atendidos"] = $matrixList["iv_req_equipamentos"]["percent_A"];
			$record["iv_equip_ap_e_na"] = $matrixList["iv_req_equipamentos"]["percent_AP_NA"];
			$record["v_insumos_atendidos"] = $matrixList["v_req_insumos"]["percent_A"];
			$record["v_insumos_ap_e_na"] = $matrixList["v_req_insumos"]["percent_AP_NA"];
			$record["vi_rh_atendidos"] = $matrixList["vi_req_rh"]["percent_A"];
			$record["vi_rh_ap_e_na"] = $matrixList["vi_req_rh"]["percent_AP_NA"];
			$record["vii_prat_tec_esp_atendidos"] = $matrixList["vii_req_prat_tec_esp"]["percent_A"];
			$record["vii_prat_tec_esp_ap_e_na"] = $matrixList["vii_req_prat_tec_esp"]["percent_AP_NA"];
			$record["viii_flux_tra_si_atendidos"] = $matrixList["viii_req_flux_trab_si"]["percent_A"];
			$record["viii_flux_tra_si_ap_e_na"] = $matrixList["viii_req_flux_trab_si"]["percent_AP_NA"];
			
			$count_A = $matrixList["ix_req_documentacao"]["count_A"] + $matrixList["x_req_doc_equip_40"]["count_A"];
			$count_AP = $matrixList["ix_req_documentacao"]["count_AP"] + $matrixList["x_req_doc_equip_40"]["count_AP"];
			$count_NA = $matrixList["ix_req_documentacao"]["count_NA"] + $matrixList["x_req_doc_equip_40"]["count_NA"];
			$record["ix_x_doc_atendidos"] = round(($count_A/($count_A+$count_AP+$count_NA))*100,2);
			$record["ix_x_doc_ap_e_na"] = round((($count_AP+$count_NA)/($count_A+$count_AP+$count_NA))*100,2);
			
			$count_A = $matrixList["xi_req_doc_ind_gerais"]["count_A"] + $matrixList["xii_req_doc_indicadores"]["count_A"];
			$count_AP = $matrixList["xi_req_doc_ind_gerais"]["count_AP"] + $matrixList["xii_req_doc_indicadores"]["count_AP"];
			$count_NA = $matrixList["xi_req_doc_ind_gerais"]["count_NA"] + $matrixList["xii_req_doc_indicadores"]["count_NA"];
			$record["xi_xii_indicad_atendidos"] = round(($count_A/($count_A+$count_AP+$count_NA))*100,2);
			$record["xi_xii_indicad_ap_e_na"] = round((($count_AP+$count_NA)/($count_A+$count_AP+$count_NA))*100,2);
			
			$record["preench_finalizado"] = "1";
		}
		
		if($formName == "roteiro_para_visita_tcnica_lab_de_tb_baciloscopia") {
			$record["i_infraestrutura_atendidos_41"] = $matrixList["i_req_infraestrutura_41"]["percent_A"];
			$record["i_infraestrutura_ap_e_na_41"] = $matrixList["i_req_infraestrutura_41"]["percent_AP_NA"];
			$record["ii_biosseguranca_atendidos_41"] = $matrixList["ii_req_biosseg_41"]["percent_A"];
			$record["ii_biosseguranca_ap_e_na_41"] = $matrixList["ii_req_biosseg_41"]["percent_AP_NA"];
			$record["iii_colet_transp_atendidos_41"] = $matrixList["iii_req_coleta_transp_amostras_41"]["percent_A"];
			$record["iii_colet_transp_ap_e_na_41"] = $matrixList["iii_req_coleta_transp_amostras_41"]["percent_AP_NA"];
			$record["iv_insumos_atendidos_41"] = $matrixList["iv_req_insumos_41"]["percent_A"];
			$record["iv_insumos_ap_e_na_41"] = $matrixList["iv_req_insumos_41"]["percent_AP_NA"];
			$record["v_rh_atendidos_41"] = $matrixList["v_req_rh_41"]["percent_A"];
			$record["v_rh_ap_e_na_41"] = $matrixList["v_req_rh_41"]["percent_AP_NA"];
			$record["vi_prat_tec_esp_atendidos_41"] = $matrixList["vi_req_prat_tec_esp_41"]["percent_A"];
			$record["vi_prat_tec_esp_ap_e_na_41"] = $matrixList["vi_req_prat_tec_esp_41"]["percent_AP_NA"];
			$record["vii_flux_trab_si_atendidos_41"] = $matrixList["vii_req_flux_trab_si_41"]["percent_A"];
			$record["vii_flux_trab_si_ap_e_na_41"] = $matrixList["vii_req_flux_trab_si_41"]["percent_AP_NA"];
			
			$count_A = $matrixList["viii_req_documentacao_41"]["count_A"] + $matrixList["ix_req_doc_equip_41"]["count_A"];
			$count_AP = $matrixList["viii_req_documentacao_41"]["count_AP"] + $matrixList["ix_req_doc_equip_41"]["count_AP"];
			$count_NA = $matrixList["viii_req_documentacao_41"]["count_NA"] + $matrixList["ix_req_doc_equip_41"]["count_NA"];
			$record["viii_ix_doc_atendidos_41"] = round(($count_A/($count_A+$count_AP+$count_NA))*100,2);
			$record["viii_ix_doc_ap_e_na_41"] = round((($count_AP+$count_NA)/($count_A+$count_AP+$count_NA))*100,2);
			
			$count_A = $matrixList["x_req_doc_ind_gerais_41"]["count_A"] + $matrixList["xi_req_doc_indicadores_41"]["count_A"];
			$count_AP = $matrixList["x_req_doc_ind_gerais_41"]["count_AP"] + $matrixList["xi_req_doc_indicadores_41"]["count_AP"];
			$count_NA = $matrixList["x_req_doc_ind_gerais_41"]["count_NA"] + $matrixList["xi_req_doc_indicadores_41"]["count_NA"];
			$record["x_xi_ind_gerais_atendidos_41"] = round(($count_A/($count_A+$count_AP+$count_NA))*100,2);
			$record["x_xi_ind_gerais_ap_e_na_41"] = round((($count_AP+$count_NA)/($count_A+$count_AP+$count_NA))*100,2);
			
			$record["preench_finalizado_41"] = "1";
		}
		
		$record["record_id"] = $recordID;
		$record['redcap_repeat_instance'] = $instance;
		$record['redcap_repeat_instrument'] = $formName;
		$record = array($record);

		$jsonData = json_encode($record);
		importRecordsRedCap($jsonData,$apiToken,$apiUrl);
	}
}
?>