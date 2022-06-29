<?php
include("http_requester.php");

function vars($k1,$k2,$file) {
	$m = json_decode(file_get_contents($file),true);
	if(isset($m[$k1][$k2]))
		return $m[$k1][$k2];
	else
		return "???";
}

function checkXLSForm($kobo_data) {	
	$alerts = array();
	$id_found = false;
	foreach ($kobo_data[0] as $key_survey => $line) {		
		//error - check if 'select multiple' questions starts with 'checkbox_' (field's name)		
		if (explode(" ",$line['A'])[0] == 'select_multiple') {
			if(substr($line['B'],0,9) !== "checkbox_") {
				$alerts["errors"][$line['B']] = "O nome do campo deve iniciar com o prefixo 'checkbox_'";
			}
		}			
		
		//error - check fields' names for accentuation and special characters (only letters, numbers and _)
		if ($line['B'] != NULL && preg_match('/^[\w]+$/', $line['B']) == 0) {
			if(!isset($alerts["errors"][$line['B']]))
				$alerts["errors"][$line['B']] = "O nome do campo deve conter apenas letras, numeros e '_', sem acentuacao.";
			else
				$alerts["errors"][$line['B']] .= " e conter apenas letras, numeros e '_', sem acentuacao.";
		}
		
		//error - field name does not start with letter or number
		$first_char = substr($line['B'],0,1);
		if ($line['B'] != '__version__' && $first_char != "" && preg_match('/([A-Za-z0-9])/', $first_char) != 1) {
			if(!isset($alerts["errors"][$line['B']]))
				$alerts["errors"][$line['B']] = "O nome do campo deve comecar com uma letra ou numero.";
			else
				$alerts["errors"][$line['B']] .= " e deve comecar com uma letra ou numero.";		
		}
		
		//warning - check 'fx_id'
		if (preg_match('/(f[A-Za-z0-9]+_id)/', $line['B']) == 1) {
			$id_found = true;
		}
	}
	foreach ($kobo_data[1] as $key_survey => $line) {
		//error - check choices for accentuation and special characters (only letters, numbers and _)				
		if ($line['A'] != "" && preg_match('/^[\w]+$/', trim($line['B'])) == 0) {
			$alerts["errors"][$line['A']] = $line['B']. " ($key_survey) => Os valores das opcoes (choices) devem conter apenas letras, numeros e '_', sem acentuacao.";
		}		
	}
	
	/*if(!$id_found)
		$alerts["warning"]['id'] = "Nenhum identificador encontrado. Em um projeto com varios formularios no REDCap, defina um campo identificador em cada formulario";*/
	
	return $alerts;
}

function getHeader($header) {
	$headerValue = "";
	foreach (apache_request_headers() as $h => $value) {
		if($h == $header) {
			$headerValue = $value;
			break;
		}		
	}
	return $headerValue;
}

function getMetadata($fieldName,$conn) {
	$sql = "SELECT identifier,semantic_annotation FROM form_metadata WHERE field_name = '$fieldName';";
	$result = $conn->query($sql);
		
	if(!$result || $result->num_rows == 0) {	
		return -1;
	}
	
	return $result->fetch_assoc();
}

function getSemanticsMetadata($formID,$conn) {
	$sql = "SELECT field_name,semantic_annotation FROM form_metadata WHERE redcap_form_id = '$formID';";
	$result = $conn->query($sql);
		
	if(!$result || $result->num_rows == 0) {	
		return -1;
	}
	
	return $result->fetch_all(MYSQLI_ASSOC);
}

function getIdentifierMetadata($formID,$conn) {
	$sql = "SELECT field_name,identifier FROM form_metadata WHERE redcap_form_id = '$formID';";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0) {	
		return -1;
	}
	
	return $result->fetch_all(MYSQLI_ASSOC);
}

function getRedcapProject($pid,$redcapUrl,$conn) {
	$sql = "SELECT id,api_token,api_url,database_name,project FROM redcap_projects WHERE redcap_pid = $pid AND api_url LIKE '$redcapUrl%';";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0) {
		return -1;
	}
	
	return $result->fetch_assoc();
}

function getRedcapProjectByPk($id,$conn) {
	$sql = "SELECT id,api_token,api_url,project AS project_name,export_csv_data,api_token_deidentified FROM redcap_projects WHERE id = $id;";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0) {
		return -1;
	}
	
	return $result->fetch_assoc();
}

function getApi($projectID,$conn) {
	$sql = "SELECT api_token, api_url FROM redcap_projects WHERE id = $projectID;";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0) {
		return -1;
	}
	
	return $result->fetch_assoc();
}

function getFormByPk($formID,$conn) {
	$sql = "SELECT id,form_id,form_name,first_form,enable_notification,email_notification,description,is_repeatable,is_in_event,unique_id_field,kaleido_enabled,respondent_confirmation,respondent_email_field,author_field,autolock,autolock_admin_only,autolock_unlock_non_complete,autolock_autovalidate,autolock_unlock_duplicate,check_duplicity,check_duplicity_fields,force_unverified FROM redcap_forms WHERE id = $formID;";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0) {
		return -1;
	}
	
	return $result->fetch_assoc();
}

function getFormById($projectID,$formID,$conn) {
	$sql = "SELECT id,form_id,form_name,first_form,enable_notification,email_notification,description,is_repeatable,is_in_event,unique_id_field,kaleido_enabled,respondent_confirmation,respondent_email_field,author_field,autolock,autolock_admin_only,autolock_unlock_non_complete,autolock_autovalidate,autolock_unlock_duplicate,check_duplicity,check_duplicity_fields,force_unverified FROM redcap_forms WHERE project_id = $projectID AND form_id = '$formID';";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0) {
		return -1;
	}
	
	return $result->fetch_assoc();
}

function getFormByName($projectID,$formName,$conn) {
	$sql = "SELECT id,form_id,form_name,first_form,enable_notification,email_notification,description,is_repeatable,is_in_event,unique_id_field,kaleido_enabled,respondent_confirmation,respondent_email_field,author_field,autolock,autolock_admin_only,autolock_unlock_non_complete,autolock_autovalidate,autolock_unlock_duplicate,check_duplicity,check_duplicity_fields,force_unverified FROM redcap_forms WHERE project_id = $projectID AND form_name = '$formName';";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0) {
		return -1;
	}
	
	return $result->fetch_assoc();
}

function getRecordId($uniqueId,$projectID,$conn) {
	$sql = "SELECT redcap_record_id FROM form_data WHERE unique_id = '$uniqueId' AND first_form = 1 AND project_id = $projectID ORDER BY id DESC LIMIT 1;";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0) {
		return -1;
	}
	
	$row = $result->fetch_assoc();
	return $row['redcap_record_id'];
}

function getUploadedFile($projectID,$fileToken,$conn) {
	$sql = "SELECT file_name,file_type FROM form_uploads WHERE project_id = $projectID AND token = '$fileToken';";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0) {
		return -1;
	}
	
	$row = $result->fetch_assoc();
	return $row;
}

function checkForFilesFields($data) {
	$fields = array();
	foreach($data as $key => $value) {	
		if($value == "[document]") {
			$fields[] = $key;
		}
	}
	return $fields;
}

function parseJson($jsonData) {
	$data = json_decode($jsonData, true);
	
	$headersToRemove = ["info_url_upload","info_upload","username","_notes","_bamboo_dataset_id","_tags","_xform_id_string","meta/instanceID","start","end","_geolocation","_status","__version__","_validation_status","_uuid","formhub/uuid","_id","_attachments","_submitted_by","_submission_time"];	
	foreach ($headersToRemove as $h) { //remove unnecessary headers
		unset($data[$h]);
	}	
	
	foreach($data as $key => $value) {			
		if (strpos($key, '/') !== false) { //remove groups from variable name
			$aux = explode('/',$key);
			$new_key_index = sizeof($aux)-1;
			$data[$aux[$new_key_index]] = $value;
			unset($data[$key]);
			$key = $aux[$new_key_index];
		}
		
		if (strpos($key, 'checkbox_') !== false) { //checkboxes
			$aux = explode(" ",$value);
			foreach($aux as $v) {
				$fieldLabel = $key . "___" . strtolower($v);
				$data[$fieldLabel] = '1';
			}
			unset($data[$key]);
		}

		if (preg_match('/^([0-9]{2}:[0-9]{2}:00.000-00:00)$/', $value) == 1) { //time
			$data[$key] = substr($value,0,5);
		} else if (preg_match('/^([0-9]{4}-[0-9]{2}-[0-9]{2}T[0-9]{2}:[0-9]{2}:00.000-00:00)$/', $value) == 1) { //datetime
			$data[$key] = substr($value,0,10) . " " . substr($value,11,5); 
		}
	}
	
	foreach($data as $key => $value) {
		unset($data[$key]);
		$data[strtolower($key)] = $value;
	}
	
	return $data;
}

function generateNextRecordId($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'generateNextRecordName'
	);	
	return callAPI($data,'nextRecordId',$apiUrl);
}

function importRecordsRedCap($jsonData,$apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'record',
		'format' => 'json',
		'type' => 'flat',
		'overwriteBehavior' => 'normal',
		'forceAutoNumber' => 'false',
		'data' => $jsonData,
		'dateFormat' => 'YMD',
		'returnContent' => 'count',
		'returnFormat' => 'json'
	);	

	$r = json_decode(callAPI($data,'importRecords',$apiUrl),true);	
	
	if(isset($r["count"]))
		return $r["count"];
	
	return $r["error"];
}

function exportRecordsRedCap($formName,$event,$recordID,$filterLogic,$apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'record',
		'format' => 'json',
		'type' => 'flat',
		'fields' => array('record_id'),
		'rawOrLabel' => 'raw',
		'rawOrLabelHeaders' => 'raw',
		'exportCheckboxLabel' => 'false',
		'exportSurveyFields' => 'false',
		'exportDataAccessGroups' => 'false',
		'returnFormat' => 'json'
	);
	
	if($formName != null)
		$data["forms"] = array($formName);
		
	if($event != null)
		$data["events"] = array($event);
	
	if($filterLogic != null)
		$data["filterLogic"] = $filterLogic;
		
	if($recordID != null)
		$data["records"] = array($recordID);
	
	return callAPI($data,'exportRecords',$apiUrl);
}

function exportFullRecordsRedCap($formName,$event,$recordID,$filterLogic,$apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'record',
		'format' => 'json',
		'type' => 'flat',
		'rawOrLabel' => 'raw',
		'rawOrLabelHeaders' => 'raw',
		'exportCheckboxLabel' => 'false',
		'exportSurveyFields' => 'false',
		'exportDataAccessGroups' => 'false',
		'returnFormat' => 'json'
	);
	
	if($formName != null)
		$data["forms"] = array($formName);
		
	if($event != null)
		$data["events"] = array($event);
	
	if($filterLogic != null)
		$data["filterLogic"] = $filterLogic;
		
	if($recordID != null)
		$data["records"] = array($recordID);
	
	return callAPI($data,'exportRecords',$apiUrl);
}

function exportRecordsFieldsRedCap($formName,$events,$recordID,$filterLogic,$fields,$apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'record',
		'format' => 'json',
		'type' => 'flat',
		'fields' => $fields,
		'rawOrLabel' => 'raw',
		'rawOrLabelHeaders' => 'raw',
		'exportCheckboxLabel' => 'false',
		'exportSurveyFields' => 'false',
		'exportDataAccessGroups' => 'false',
		'returnFormat' => 'json'
	);
	
	if($formName != null)
		$data["forms"] = array($formName);
		
	if($events != null)
		$data["events"] = $events;
	
	if($filterLogic != null)
		$data["filterLogic"] = $filterLogic;
		
	if($recordID != null)
		$data["records"] = array($recordID);
	
	return callAPI($data,'exportRecords',$apiUrl);
}

function exportRecordRedCapByRecordIdAndInstanceAndEvent($formName,$recordId,$instance,$unique_event_name,$fields,$filterLogic,$apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'record',
		'format' => 'json',
		'type' => 'flat',
		'csvDelimiter' => '',
		'fields' => $fields,
		//'forms' => array($formName),
		'records' => array($recordId),
		'events' => array($unique_event_name),
		'rawOrLabel' => 'raw',
		'rawOrLabelHeaders' => 'raw',
		'exportCheckboxLabel' => 'false',
		'exportSurveyFields' => 'false',
		'exportDataAccessGroups' => 'false',
		'returnFormat' => 'json'
	);
	
	if($formName != null)
		$data["forms"] = array($formName);
	
	if($filterLogic != null)
		$data["filterLogic"] = $filterLogic;

	$result = json_decode(callAPI($data,'exportRecords',$apiUrl),true);		
	//echo "$recordId | $formName | $instance | ".sizeof($result)."\n";
	//var_dump($result);
	
	if(sizeof($result) == 1) {
		return $result[0];
	}
	
	foreach($result as $r) {		
		if(isset($r["redcap_repeat_instance"]) && !empty($r["redcap_repeat_instance"])) {
			if(intval($r["redcap_repeat_instance"]) == $instance)
				return $r;							
		}
	}
}

function exportRedcapEventByName($eventName,$isUniqueEventName,$apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'event',
		'format' => 'json',
		'returnFormat' => 'json'
	);

	$result = json_decode(callAPI($data,'exportEvents',$apiUrl),true);		
	
	if(isset($result["error"])) {
		$resp = array();
		$resp["unique_event_name"] = "event_1_arm_1";
		$resp["event_name"] = "Event 1";
		return $resp;
	} else {	
		foreach($result as $r) {
			$varName = "event_name";
			if($isUniqueEventName)
				$varName = "unique_event_name";
			
			if(isset($r[$varName]) && !empty($r[$varName])) {
				if($r[$varName] == $eventName)
					return $r;							
			}
		}	
	}
	return null;
}

function exportRedcapVersion($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'version'
	);

	return callAPI($data,'exportVersion',$apiUrl);		
}

function exportProjectInfo($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'project',
		'format' => 'json',
		'returnFormat' => 'json'
	);

	return callAPI($data,'exportProjectInfo',$apiUrl);		
}

function exportProjectArms($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'arm',
		'format' => 'json',
		'returnFormat' => 'json'
	);

	return callAPI($data,'exportProjectArms',$apiUrl);		
}

function exportFormEventMapping($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'formEventMapping',
		'format' => 'json',
		'returnFormat' => 'json'
	);

	return callAPI($data,'exportFormEventMapping',$apiUrl);		
}

function exportInstruments($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'instrument',
		'format' => 'json',
		'returnFormat' => 'json'
	);

	return callAPI($data,'exportInstruments',$apiUrl);		
}

function exportEvents($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'event',
		'format' => 'json',
		'returnFormat' => 'json'
	);

	return callAPI($data,'exportEvents',$apiUrl);		
}

function exportRepeatingFormsEvents($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'repeatingFormsEvents',
		'format' => 'json',
		'returnFormat' => 'json'
	);

	return callAPI($data,'exportRepeatingFormsEvents',$apiUrl);		
}

function exportDAGs($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'dag',
		'format' => 'json',
		'returnFormat' => 'json'
	);

	return callAPI($data,'exportDAGs',$apiUrl);		
}

function exportFullDatasetCSV($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'record',
		'action' => 'export',
		'format' => 'csv',
		'type' => 'flat',
		'csvDelimiter' => '',
		'rawOrLabel' => 'raw',
		'rawOrLabelHeaders' => 'raw',
		'exportCheckboxLabel' => 'false',
		'exportSurveyFields' => 'false',
		'exportDataAccessGroups' => 'true',
		'returnFormat' => 'json'
	);

	return callAPI($data,'exportFullDatasetCSV',$apiUrl);		
}

function exportFileRedCap($fieldName,$recordID,$event,$apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'file',
		'action' => 'export',
		'record' => $recordID,
		'field' => $fieldName,
		'event' => $event,
		'returnFormat' => 'json'
	);
		
	$f = callAPIExportFile($data,$apiUrl);
	
	$fileName = "tmp/".str_replace('"','',$f["file_name"]);
	$fp = fopen($fileName, 'w+');
	fwrite($fp,$f["body"]);
	fclose($fp);
	return $fileName;
}

function importFileRedCap($fieldName,$recordID,$fileName,$fileType,$apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'file',
		'action' => 'import',
		'record' => $recordID,
		'field' => $fieldName,
		'event' => '',
		'returnFormat' => 'json'
	);
	$data['file'] = (function_exists('curl_file_create') ? curl_file_create('/var/www/html/tb/kobo_redcap/kobo_files_upload/uploads/'.$fileName, $fileType, $fileName) : "@/var/www/html/tb/kobo_redcap/kobo_files_upload/uploads/".$fileName);
	return callAPI($data,'importFile',$apiUrl);
}

function exportRedCapMetadata($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'metadata',
		'format' => 'csv',
		'returnFormat' => 'json'
	);
	return callAPI($data,'exportMetadata',$apiUrl);
}

function exportRedCapMetadataJSON($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'metadata',
		'format' => 'json',
		'returnFormat' => 'json'
	);
	return callAPI($data,'exportMetadata',$apiUrl);
}

function exportRedCapFormMetadata($formName,$apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'metadata',
		'format' => 'json',
		'returnFormat' => 'json',
		'forms' => array($formName)
	);
	return callAPI($data,'exportMetadata',$apiUrl);
}

function exportRedCapFormOrFieldMetadata($forms,$fields,$apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'metadata',
		'format' => 'json',
		'returnFormat' => 'json'
	);
	
	if($forms != null)
		$data["forms"] = $forms;
	
	if($fields != null)
		$data["fields"] = $fields;
	
	return callAPI($data,'exportMetadata',$apiUrl);
}

function importRedCapMetadata($metadata,$apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'metadata',
		'format' => 'csv',
		'data' => $metadata,
		'returnFormat' => 'json'
	);
	return callAPI($data,'importMetadata',$apiUrl);
}

function exportRedCapProjectUsers($apiToken,$apiUrl) {
	$data = array(
		'token' => $apiToken,
		'content' => 'user',
		'format' => 'json',
		'returnFormat' => 'json'
	);
	return callAPI($data,'exportUsers',$apiUrl);
}


function callAPI($data,$operation,$url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
	
	if($operation == "importFile")
		curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
	else
		curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
	
	$output = curl_exec($ch);
	curl_close($ch);
	return $output;
}

function callAPIExportFile($data,$url) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_VERBOSE, 0);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	curl_setopt($ch, CURLOPT_AUTOREFERER, true);
	curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_FRESH_CONNECT, 1);
	curl_setopt($ch, CURLOPT_HEADER, 1);	
	curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data, '', '&'));
	
	$output = curl_exec($ch);
		
	$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $header_string = substr($output, 0, $header_size);
	$body = substr($output, $header_size);
   
    $header_rows = explode(PHP_EOL, $header_string);
    $header_rows = array_filter($header_rows, "trim");
	$i=0; $j=0;
    foreach((array)$header_rows as $hr){
        $colonpos = strpos($hr, ':');
        $key = $colonpos !== false ? substr($hr, 0, $colonpos) : (int)$i++;
        $headers[$key] = $colonpos !== false ? trim(substr($hr, $colonpos+1)) : $hr;
    }
    foreach((array)$headers as $key => $val){
        $vals = explode(';', $val);
        if(count($vals) >= 2){
            unset($headers[$key]);
            foreach($vals as $vk => $vv){
                $equalpos = strpos($vv, '=');
                $vkey = $equalpos !== false ? trim(substr($vv, 0, $equalpos)) : (int)$j++;
                $headers[$key][$vkey] = $equalpos !== false ? trim(substr($vv, $equalpos+1)) : $vv;
            }
        }
    }
    //print_r($headers);		
	curl_close($ch);		
	
	$result["body"] = $body;
	$result["file_name"] = $headers["Content-Type"]["name"];
	return $result;
}

function randomString($n) { 
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
    $randomString = ''; 
  
    for ($i = 0; $i < $n; $i++) { 
        $index = rand(0, strlen($characters) - 1); 
        $randomString .= $characters[$index]; 
    } 
    return strtoupper($randomString); 
} 

function getKldCredentials($projectID,$conn) {
	$sql = "SELECT kld_user, kld_passwd, kld_from, kld_api_url, kld_api_key, kld_ipfs_api_endpoint, kld_smart_contract_address FROM redcap_projects WHERE id = $projectID;";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0) {
		return -1;
	}
	
	$row = $result->fetch_assoc();
	return $row;
}

function writeToBlockchain($data,$formDataID,$operation,$projectID,$formName,$recordID,$author,$semantics,$kldCredentials,$conn) {	
	$http = new HTTPRequester();
	
	if(!empty($kldCredentials["kld_smart_contract_address"]))
		$url = $kldCredentials["kld_api_url"]."/".$kldCredentials["kld_smart_contract_address"]."/store";
	else
		$url = $kldCredentials["kld_api_url"]."/store";
	
	$params["author"] = $author;
	$params["data_to_store"] = $data;
	$params["form_name"] = $formName;
	$params["operation"] = $operation;
	$params["project_id"] = $projectID;
	$params["redcap_record_id"] = $recordID;
	$params["data_hash"] = hash('sha256',$data);
	$params["data_semantic_annotation"] = $semantics;

	$params["kld-from"] = $kldCredentials["kld_from"];	
	$params["kld-sync"] = "true";	
	
	$tx = $http->HTTPPost($url,$params,'basic',$kldCredentials["kld_user"].":".$kldCredentials["kld_passwd"]);	
	$jsonTx = json_decode($tx);
		
	//WRITE DATA TO MYSQL
	$sql = "INSERT INTO kld_transactions (`project_id`, `form_data_id`, `redcap_record_id`, `transaction_hash`, `block_hash`, `block_number`, `from`, `to`, `time_received`, `time_elapsed`, `raw_transaction`) 
		VALUES ($projectID,$formDataID,$recordID,'".$jsonTx->transactionHash."','".$jsonTx->blockHash."',".$jsonTx->blockNumber.",'".$jsonTx->from."','".$jsonTx->to."','".$jsonTx->headers->timeReceived."',".$jsonTx->headers->timeElapsed.",'$tx');";
	//file_put_contents('data.txt',"$sql\n",FILE_APPEND);
	$conn->query($sql);
		
	return $tx;
}

function ipfsAdd($file,$kldCredentials,$conn) {
	$cmd = 'curl --form "path=@'.$file.'" https://'.$kldCredentials["kld_user"].':'.$kldCredentials["kld_passwd"].'@'.$kldCredentials["kld_ipfs_api_endpoint"].'/v0/add';
	//echo $cmd."<br>";
	return shell_exec($cmd);	
}

function ipfsCat($fileHash,$kldCredentials,$conn) {
	$cmd = 'curl -X POST https://'.$kldCredentials["kld_user"].':'.$kldCredentials["kld_passwd"].'@'.$kldCredentials["kld_ipfs_api_endpoint"].'/v0/cat/'.$fileHash.' -o /var/www/html/tb/kobo_redcap/'.$fileHash.'.mp4';
	//echo $cmd."<br>";
	shell_exec($cmd);	
}

function checkIdentifiers($json,$formID,$conn) {
	$metadata = getIdentifierMetadata($formID,$conn);	
	if($metadata != -1) {
		$keys = array_keys($json);
		foreach($keys as $key) {
			foreach ($metadata as $m) {
				if($key == $m["field_name"]) {				
					unset($json[$key]);
				}
			}
		}			
	}
	$r = json_encode($json,JSON_UNESCAPED_SLASHES);
	return $r;
}

function checkSemantics($json,$formID,$conn) {
	$metadata = getSemanticsMetadata($formID,$conn);
	$semantics = array();
	$keys = array_keys($json);
	foreach($keys as $key) {
		if($metadata != -1) {
			foreach ($metadata as $m) {
				if($key == $m["field_name"] && isset($m["semantic_annotation"]) && !empty($m["semantic_annotation"])) {
					$semantics[$key] = $m["semantic_annotation"];
				}
			}
		}
	}
	$s = json_encode($semantics,JSON_UNESCAPED_SLASHES);
	return $s;
}

function checkFieldTypeByName($data,$field_name) {
	$type = '';
	foreach ($data as $key_survey => $line) {
		if ($line['B'] == $field_name) {
			$type = explode(" ",$line['A'])[0];
			breaK;
		}
	}
	return $type;
}

function locking($recordID,$event,$instrument,$instance,$apiUrl,$apiToken,$action) {
	$params = "token=$apiToken&returnFormat=json&record=$recordID&instrument=$instrument";
	
	if($event != null && $event != '')
		$params .= "&event=$event";
	
	if($instance != null && $instance != '')
		$params .= "&instance=$instance";
	
	$cmd = 'curl -d "'.$params.'" "'.$apiUrl.'?NOAUTH&type=module&prefix=locking_api&page='.$action.'"';
	//echo $cmd;
	return shell_exec($cmd);
	//return $cmd;
}

function unlockFormBatch($projectId,$eventId,$instrument,$instance,$redcapInstance) {
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox"))) {
		$conn = connectExternalBd(getExternalDb($redcapInstance));
		if(is_null($conn))
			return null;
	}	
	
	$conn->select_db($redcapInstance);
	
	$sql = "UPDATE redcap_locking_data SET record = CONCAT('_',record) WHERE project_id = $projectId AND form_name = '$instrument' AND instance = $instance AND event_id = $eventId;";
	$conn->query($sql);
	
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox")))
		$conn->close();
	else
		$conn->select_db("projetos_tuberculose");
}

function changeFormStatusBatch($newStatus,$projectId,$eventId,$instrument,$instance,$redcapInstance) {
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox"))) {
		$conn = connectExternalBd(getExternalDb($redcapInstance));
		if(is_null($conn))
			return null;
	}	
	
	$conn->select_db($redcapInstance);
	$fieldName = $instrument."_complete";
	
	$sql = "UPDATE redcap_data SET value = $newStatus WHERE project_id = $projectId AND field_name = '$fieldName' AND instance = $instance AND event_id = $eventId;";
	$conn->query($sql);
	
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox")))
		$conn->close();
	else
		$conn->select_db("projetos_tuberculose");
}

function checkBranchingLogic($branching_logic,$kobo_data) {
	if($branching_logic == '')
		return '';
	
	if (strpos($branching_logic, 'not(selected') !== false) {
		$str = substr($branching_logic,13);
		$str = substr($str,0,strlen($str)-2);
		$aux = explode(",",$str);
		$branching_logic = trim($aux[0]) . "!=" . trim($aux[1]);
	} else if (strpos($branching_logic, 'selected') !== false) {
		$str = substr($branching_logic,9);
		$str = substr($str,0,strlen($str)-1);
		$aux = explode(",",$str);
		$branching_logic = trim($aux[0]) . "=" . trim($aux[1]);
	} else if (strpos($branching_logic, 'not') !== false) {
		//$branching_logic = str_replace("not","",$branching_logic);
		return '';
	}
								
	$branching_logic = str_replace('$', '', $branching_logic);
	$branching_logic = str_replace('{', '', $branching_logic);
	$branching_logic = str_replace('}', '', $branching_logic);
	
	$op = "=";
	if (strpos($branching_logic, '!=') !== false)
		$op = "!=";
	 
	$aux = explode($op,$branching_logic);
	if(checkFieldTypeByName($kobo_data,$aux[0]) == 'select_multiple') {							
		$branching_logic = "[".strtolower($aux[0])."(".trim(str_replace('\'', '', $aux[1])).")]$op'1'";
	} else {
		$branching_logic = "[".strtolower($aux[0])."]$op".$aux[1];
	}
	return $branching_logic;
}

function unicode2html($str){
    // Set the locale to something that's UTF-8 capable
    setlocale(LC_ALL, 'en_US.UTF-8');
    // Convert the codepoints to entities
    $str = preg_replace("/u([0-9a-fA-F]{4})/", "&#x\\1;", $str);
    // Convert the entities to a UTF-8 string
    return str_replace("\\","",iconv("UTF-8", "ISO-8859-1//TRANSLIT", $str));
}

function compareValues($value1,$value2,$comparator,$is_regex) {
	$mdc = array('NI','INV','UNK','NASK','ASKU','NAV','MSK','NA','NAVU','NP','QS','TRC','UNC','DER','PINF','NNF','OTH');
	if(in_array($value1,$mdc) || in_array($value2,$mdc))
		return true;
	
	if($is_regex == 0) {
		if($value2 == 'null')
			$value2 = '';
		
		if(is_integer($value1)) {
			$value1 = intval($value1);
			$value2 = intval($value2);
		} else if(strtotime($value1) !== false){ 
			$value1 = strtotime($value1);
			$value2 = strtotime($value2);
		}
		
		switch ($comparator) {
			case ">":
				if($value2 != '' && $value2 != null && $value1 != '' && $value1 != null && $value1 > $value2)
					return true;
				break;
			case "<":
				if($value2 != '' && $value2 != null && $value1 != '' && $value1 != null && $value1 < $value2)
					return true;
				break;
			case ">=":
				if($value2 != '' && $value2 != null && $value1 != '' && $value1 != null && $value1 >= $value2)
					return true;
				break;
			case "<=":
				if($value2 != '' && $value2 != null && $value1 != '' && $value1 != null && $value1 <= $value2)
					return true;
				break;
			case "==":
				if($value1 == $value2)
					return true;
				break;
			case "!=":
				if($value1 != $value2)
					return true;
				break;
			default:
				return false;
		}
	} else if ($is_regex == 1) {
		if($value1 == '' || $value1 == null) { //DO NOT TEST EMPTY FIELDS
			return true;
		}
		
		//echo $comparator." ".$value1."\n";
		if(preg_match($comparator,$value1,$matches,PREG_OFFSET_CAPTURE,0) == 1)
			return true;	
	}
	
	return false;
}

function checkDuplicity($projectId,$fieldName,$recordId,$value,$redcapInstance,$conn) {
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox"))) {
		$conn = connectExternalBd(getExternalDb($redcapInstance));
		if(is_null($conn))
			return null;
	}		
	
	$conn->select_db($redcapInstance);
	
	$sql = "SELECT record FROM redcap_data 
		WHERE project_id = $projectId AND field_name = '$fieldName' AND record <> '$recordId' AND value = '$value'
			AND value NOT IN ('NI','INV','UNK','NASK','ASKU','NAV','MSK','NA','NAVU','NP','QS','TRC','UNC','DER','PINF','NNF','OTH')";	
	$result = $conn->query($sql);
		
	if(!$result || $result->num_rows == 0) {	
		return null;
	}
	
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox")))
		$conn->close();
	else
		$conn->select_db("projetos_tuberculose");
	
	return $result->fetch_all(MYSQLI_ASSOC);
}

function getFieldValueFromRedcap($projectId,$fieldName,$recordId,$formInstance,$eventId,$redcapInstance,$conn) {
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox"))) {
		$conn = connectExternalBd(getExternalDb($redcapInstance));
		if(is_null($conn))
			return null;
	}	
	
	$conn->select_db($redcapInstance);
	
	$sql = "SELECT value FROM redcap_data WHERE project_id = $projectId AND field_name = '$fieldName' AND record = '$recordId' AND instance = $formInstance AND event_id = $eventId";	
	$result = $conn->query($sql);
		
	if(!$result || $result->num_rows == 0) {	
		return null;
	}
	
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox")))
		$conn->close();
	else
		$conn->select_db("projetos_tuberculose");
	
	return $result->fetch_all(MYSQLI_ASSOC);
}

function getDataFromRedcapForm($projectId,$formName,$recordId,$formInstance,$redcapInstance,$conn) {
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox"))) {
		$conn = connectExternalBd(getExternalDb($redcapInstance));
		if(is_null($conn))
			return null;
	}	
	
	$conn->select_db($redcapInstance);
	
	$sql = "SELECT D.*, M.form_name FROM redcap_sbgm.redcap_data D
				INNER JOIN redcap_sbgm.redcap_metadata M ON D.field_name = M.field_name AND D.project_id = M.project_id
				WHERE D.project_id = $projectId AND M.form_name = '$formName' AND D.record = '$recordId'";	
				
	if($formInstance != null) {
		$sql .= " AND instance = $formInstance";
	} else {
		$sql .= " AND instance IS NULL";
	}
				
	$result = $conn->query($sql);
		
	if(!$result || $result->num_rows == 0) {	
		return null;
	}
	
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox")))
		$conn->close();
	else
		$conn->select_db("projetos_tuberculose");
	
	return $result->fetch_all(MYSQLI_ASSOC);
}

function getValueFromFunction($fn,$projectId,$instrument,$eventId,$recordId,$instance,$redcapInstance,$conn) {
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox"))) {
		$conn = connectExternalBd(getExternalDb($redcapInstance));
		if(is_null($conn))
			return null;
	}	
	
	$conn->select_db($redcapInstance);
	$fieldName = $instrument."_complete";
	
	/*if($fn == "form_instances_count") {
		$sql = "SELECT ifnull(MAX(instance),1) AS result FROM redcap_data WHERE project_id = $projectId AND field_name = '$fieldName' AND event_id = $eventId AND record = '$recordId';";				
	} else*/ if($fn == "complete_by_non_admin") {
		$instanceWhere = "AND instance = $instance";
		if($instance == 1) { $instanceWhere = "AND (instance IS NULL OR instance = 1)"; }
		
		$sql = "SELECT * FROM redcap_data WHERE field_name = '$fieldName' 
			AND record = '$recordId' AND project_id = $projectId AND event_id = $eventId AND value = 2 $instanceWhere";
			
		$result = $conn->query($sql);	
		if(!$result || $result->num_rows == 0) {
			//echo $recordId." - 0\n";
			return 0;
		}
		
		$sql = "SELECT count(*) as result
			FROM redcap_sbgm.redcap_log_event4 update_log
			join redcap_sbgm.redcap_user_rights user_rights on update_log.user = user_rights.username and user_rights.project_id = '$projectId' 
			join redcap_sbgm.redcap_data red_data on red_data.record = update_log.pk 
			left join redcap_sbgm.redcap_locking_data lock_data on red_data.record = lock_data.record and (red_data.instance = lock_data.instance or (red_data.instance is null and lock_data.instance = '1'))
			where 
			(
			(
			update_log.description = 'Create record' 
			and (
			(update_log.data_values like '%".$fieldName." = \'2\'%' and red_data.field_name = '$fieldName') 
			) 
			) or (
			update_log.description = 'Update record'
			and (
			(update_log.data_values like '%".$fieldName." = \'2\'%' and red_data.field_name = '$fieldName') 
			) 
			)
			)
			and red_data.value = '2'
			and user_rights.role_id != '9'
			and update_log.project_id = '$projectId' 
			and lock_data.record is null
			and update_log.pk = '$recordId'
			and update_log.event_id = $eventId
			group by update_log.log_event_id;";
		//echo $sql."\n";
	} else if($fn == "locked_not_complete") {
		$sql = "SELECT count(*) as result
					FROM redcap_data red_data 
					join redcap_locking_data lock_data on red_data.record = lock_data.record and (red_data.instance = lock_data.instance or (red_data.instance is null and lock_data.instance = '1'))
					where lock_data.form_name = '$instrument' and red_data.field_name = '$fieldName' and red_data.value != '2' 
					and lock_data.project_id = $projectId and lock_data.event_id = $eventId and lock_data.record = '$recordId' and lock_data.instance = $instance;";
		//echo $sql."<br/>";;
	} else {
		return -1;
	}
	
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0)
		return 0;
	
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox")))
		$conn->close();
	else
		$conn->select_db("projetos_tuberculose");
	
	//echo $recordId." - ".$result->fetch_assoc()["result"]."\n";
	return $result->fetch_assoc()["result"]; //must return a number
}

function openQuery($projectId,$recordId,$fieldName,$instance,$userId,$assignedUserId,$comment,$redcapInstance,$conn) {
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox"))) {
		$conn = connectExternalBd(getExternalDb($redcapInstance));
		if(is_null($conn))
			return null;
	}	
	
	$conn->select_db($redcapInstance);
	
	$sql = "INSERT INTO redcap_data_quality_resolutions
				(ts, user_id, response_requested, response, comment, current_query_status, upload_doc_id, field_comment_edited) 
					VALUES (NOW(), $userId, 1, NULL, '$comment', 'OPEN', NULL, 0);";
	$conn->query($sql);
	echo $sql."<br>";
	
	$sql = "INSERT INTO redcap_data_quality_status 
				(rule_id, pd_rule_id, non_rule, project_id, record, event_id, field_name, repeat_instrument, instance, status, exclude, query_status, assigned_user_id) 
					VALUES  (NULL, NULL, 1, $projectId, '$recordId', 41, '$fieldName', NULL, $instance, NULL, 0, 'OPEN', $assignedUserId);";
	$conn->query($sql);
	echo $sql."<br>";
	
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox")))
		$conn->close();
	else
		$conn->select_db("projetos_tuberculose");
}

function getEventIdByEventName($projectId,$uniqueEventName,$redcapInstance,$apiToken,$apiUrl,$conn) {
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox"))) {
		$conn = connectExternalBd(getExternalDb($redcapInstance));
		if(is_null($conn))
			return null;
	}	
	
	$eventName = "Event 1";
	$armNum = 1;
		
	if($uniqueEventName != "event_1_arm_1" && $uniqueEventName != "") {
		$event = exportRedcapEventByName($uniqueEventName,true,$apiToken,$apiUrl);		
		if(isset($event) && !empty($event)) {
			$eventName = $event["event_name"];
			$armNum = $event["arm_num"];
		}
	}
		
	$conn->select_db($redcapInstance);
	$sql = "SELECT M.event_id FROM redcap_events_metadata M INNER JOIN redcap_events_arms A ON M.arm_id = A.arm_id WHERE A.project_id = $projectId AND A.arm_num = $armNum AND descrip = '$eventName'";
	$result = $conn->query($sql);
	
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox")))
		$conn->close();
	else
		$conn->select_db("projetos_tuberculose");
	
	return $result->fetch_assoc()["event_id"];	
}

function connectExternalBd($externalDb) {
	$conn = new mysqli($externalDb["host"],$externalDb["user"],$externalDb["password"],"",$externalDb["port"]);
	if ($conn->connect_error) {
		die("Falha na conexao: " . $conn->connect_error);
		return null;
	}
	return $conn;
}

function getExternalDb($dbName) {	
	$sql = "SELECT * FROM redcap_external_databases WHERE db_name = '$dbName';";	
	$result = $conn->query($sql);
		
	if(!$result || $result->num_rows != 1) {	
		return null;
	}

	return $result->fetch_assoc();
}

function isREDCapAdmin($username,$redcapInstance,$conn) {
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox"))) {
		$conn = connectExternalBd(getExternalDb($redcapInstance));
		if(is_null($conn))
			return null;
	}	
	$conn->select_db($redcapInstance);
	
	$sql = "SELECT username FROM redcap_user_information WHERE super_user = 1 AND username = '$username';";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows != 1) {
		return false;
	}
	
	return true;
}

function autovalidate($formName,$formData,$pid,$apiToken,$apiUrl,$redcapInstance,$source,$conn) {	
	$mdc = array('NI','INV','UNK','NASK','ASKU','NAV','MSK','NA','NAVU','NP','QS','TRC','UNC','DER','PINF','NNF','OTH');
	$recordId = $formData["record_id"];
	
	$event = "event_1_arm_1";
	if(isset($formData["redcap_event_name"]) && $formData["redcap_event_name"] != "")
		$event = $formData["redcap_event_name"];	
	
	$instance = 1;
	if(isset($formData["redcap_repeat_instance"]) && $formData["redcap_repeat_instance"] != "")
		$instance = $formData["redcap_repeat_instance"];
	
	$reqFieldsValid = true;
	$rangesValid = true;
	$typesValid = true;
	$customValid = true;
	
	$failReason = "NULL";	
	error_log("extraindo metadata - INSTANCE $instance",0);
	$fields = json_decode(exportRedCapFormMetadata($formName,$apiToken,$apiUrl),true);
	
	foreach($fields as $f) {
		error_log("CAMPO: ".$f["field_name"],0);
		if((!isset($formData[$f["field_name"]]) && $f["field_type"] != "checkbox") || $f["field_name"] == "record_id")
			continue;
		
		//error_log(json_encode($formData),0);
	
		if($f["field_type"] != "checkbox")
			$fieldValue = $formData[$f["field_name"]];		
		
		error_log("verificando branching logic",0);
		$validBranchingLogic = false;
		$branchingLogicTest = array();
		if($f["branching_logic"] != "") {
			$branchingLogicTest = json_decode(exportRecordsFieldsRedCap($formName,array($event),$recordId,$f["branching_logic"],array("record_id"),$apiToken,$apiUrl),true);					
			if(sizeof($branchingLogicTest) > 1) {				
				foreach($branchingLogicTest as $item) {
					if(isset($item["redcap_repeat_instance"]) && $item["redcap_repeat_instance"] == $instance) {
						$validBranchingLogic = true;
						break;
					}
				}
			} else if(sizeof($branchingLogicTest) == 1) {
				$validBranchingLogic = true;
			}
		} else {
			$validBranchingLogic = true;
		}
		
		if($validBranchingLogic) { //field is visible OR no branching logic			
			//REQUIRED
			error_log("verificando required",0);
			if($f["required_field"] == "y") {
				if($f["field_type"] == "checkbox") {
					$checkboxValid = false;
					foreach($formData as $k => $v) {
						error_log("verificado checkbox - ".$k." - ".$f["field_name"],0);
						if (strpos($k, $f["field_name"]) === 0 && $v == "1") {
							error_log("checkbox encontrado - ".$k." - ".$f["field_name"]." - valor = ".$v,0);
							$checkboxValid = true;
							break;
						}
					}
				}
				
				if(($f["field_type"] == "checkbox" && !$checkboxValid) || ($f["field_type"] != "checkbox" && $fieldValue == "")) {					
					$failReason = "'blank_required_field'";					
					$reqFieldsValid = false;
					break;
				}
			}		
			
			//TYPE
			error_log("verificando type - ".$f["text_validation_type_or_show_slider_number"],0);
			if($f["text_validation_type_or_show_slider_number"] != "" && $f["text_validation_type_or_show_slider_number"] != "file" && $fieldValue != "" && !in_array($fieldValue,$mdc)) {
				$filter = "";
				$isDate = false;
								
				if(substr($f["text_validation_type_or_show_slider_number"],0,16) == "datetime_seconds") {
					$filter = "Y-m-d H:i:s";
					$isDate = true;
				} else if(substr($f["text_validation_type_or_show_slider_number"],0,8) == "datetime") {
					$filter = "Y-m-d H:i";
					$isDate = true;
				} else if(substr($f["text_validation_type_or_show_slider_number"],0,4) == "date") {
					$filter = "Y-m-d";
					$isDate = true;
				} else if($f["text_validation_type_or_show_slider_number"] == "time") {
					$filter = "H:i";
					$isDate = true;
				} else if($f["text_validation_type_or_show_slider_number"] == "email") {
					$filter = FILTER_VALIDATE_EMAIL;
				} else if($f["text_validation_type_or_show_slider_number"] == "integer") {
					$filter = FILTER_VALIDATE_INT;
					//if($fieldValue != "0") $fieldValue = ltrim($fieldValue,'0');
				} else if($f["text_validation_type_or_show_slider_number"] == "number") {
					$filter = FILTER_VALIDATE_FLOAT;
				}
								
				if (($filter != "" && $filter != FILTER_VALIDATE_INT && (($isDate && !validateDate($fieldValue,$filter)) || (!$isDate && !filter_var($fieldValue,$filter)))) 
						|| ($filter == FILTER_VALIDATE_INT && !is_numeric($fieldValue))) {
					//error_log($f["field_name"]." - ".substr($f["text_validation_type_or_show_slider_number"],0,16)." - $filter - $fieldValue",0);
					$failReason = "'invalid_type'";
					$typesValid = false;
					break;
				} 
			}
			
			//MIN
			error_log("verificando min",0);
			if($f["text_validation_min"] != "" && $fieldValue != "" && !in_array($fieldValue,$mdc)) {
				if($fieldValue < $f["text_validation_min"]) {
					$failReason = "'min_value'";
					$rangesValid = false;
					break;
				}
			}
			
			//MAX
			error_log("verificando max",0);
			if($f["text_validation_max"] != "" && $fieldValue != "" && !in_array($fieldValue,$mdc)) {
				if($fieldValue > $f["text_validation_max"]) {
					$failReason = "'max_value'";
					$rangesValid = false;
					break;
				}
			}
			
			//CUSTOM RULES
			error_log("verificando custom rules",0);
			$sql = "SELECT comparator, value_to_compare, is_regex, compare_other_field FROM projetos_tuberculose.vw_redcap_validation_rules 
						WHERE enabled = 1 AND redcap_instance = '$redcapInstance'
							AND form_name = '$formName' AND field_name = '".$f["field_name"]."' AND (compare_other_field = 1 OR is_regex = 1);";
			$result = $conn->query($sql);
			if($result && $result->num_rows > 0) {
				$rules = $result->fetch_all(MYSQLI_ASSOC);
				$fielsToExtract = array();
				foreach($rules as $r) {
					$fielsToExtract[] = $r["value_to_compare"];
				}
				$fieldsToCompare = exportRecordRedCapByRecordIdAndInstanceAndEvent(null,$recordId,$instance,$event,$fielsToExtract,null,$apiToken,$apiUrl);
				foreach($rules as $r) {
					if($r["compare_other_field"] == 1) {
						switch ($r["comparator"]) {
							case ">":
								if($fieldValue != null && $fieldValue != '' && $fieldsToCompare[$r["value_to_compare"]] != null && $fieldsToCompare[$r["value_to_compare"]] != '') {
									if($fieldValue > $fieldsToCompare[$r["value_to_compare"]]) {
										//continue;
									} else {
										$customValid = false;
										break;
									}
								}
								break;
							case "<":
								if($fieldValue != null && $fieldValue != '' && $fieldsToCompare[$r["value_to_compare"]] != null && $fieldsToCompare[$r["value_to_compare"]] != '') {
									if($fieldValue < $fieldsToCompare[$r["value_to_compare"]]) {
										//continue;
									} else {
										$customValid = false;
										break;
									}
								}
								break;
							case ">=":
								if($fieldValue != null && $fieldValue != '' && $fieldsToCompare[$r["value_to_compare"]] != null && $fieldsToCompare[$r["value_to_compare"]] != '') {
									if($fieldValue >= $fieldsToCompare[$r["value_to_compare"]]) {
										//continue;
									} else {
										$customValid = false;
										break;
									}
								}
								break;
							case "<=":
								if($fieldValue != null && $fieldValue != '' && $fieldsToCompare[$r["value_to_compare"]] != null && $fieldsToCompare[$r["value_to_compare"]] != '') {
									if($fieldValue <= $fieldsToCompare[$r["value_to_compare"]]) {
										//continue;
									} else {
										$customValid = false;
										break;
									}
								}
								break;
							case "==":
								if($fieldValue == $fieldsToCompare[$r["value_to_compare"]]) {
									//continue;
								} else {
									$customValid = false;
									break;
								}
								break;
							case "!=":
								if($fieldValue != $fieldsToCompare[$r["value_to_compare"]]) {
									//continue;
								} else {
									$customValid = false;
									break;
								}
								break;
							default:
								$customValid = false;
						}	
					} else if($r["is_regex"] == 1) {
						if(preg_match($r["comparator"],$fieldValue,$matches,PREG_OFFSET_CAPTURE,0) == 0) {
							$customValid = false;							
						}
					}
					if(!$customValid) {						
						if($r["is_regex"] == 1)
							$failReason = "'custom_rule_".$f["field_name"]."_REGEX'";				
						else
							$failReason = "'custom_rule_".$f["field_name"]."_".$r["comparator"]."_".$r["value_to_compare"]."'";				
							
						error_log($failReason,0);
						break;
					}					
				}
				if(!$customValid)
					break;
			}
		}
	}

	$success = 0;
	if($reqFieldsValid && $rangesValid && $typesValid && $customValid) {		
		$failField = "NULL";	
		$success = 1;	
		$sql = "UPDATE projetos_tuberculose.redcap_autovalidation_log SET checked = 1 
			WHERE record_id = '$recordId' AND pid = $pid AND form_name = '$formName'
				AND event = '$event' AND instance = '$instance' AND redcap_instance = '$redcapInstance'
				AND success = 0 AND checked = 0;";		
		//error_log($sql,0);
		$conn->query($sql);
	} else {
		$failField = "'".$f["field_name"]."'";
		$sql = "DELETE FROM projetos_tuberculose.redcap_autovalidation_log 
				WHERE record_id = '$recordId' AND pid = $pid AND form_name = '$formName'
					AND event = '$event' AND instance = '$instance' AND redcap_instance = '$redcapInstance'
					AND success = 0 AND checked = 0 AND fail_field = $failField;";
		//error_log($sql,0);
		$conn->query($sql);
	}
	error_log("inserindo no log - success = $success" ,0);
	$sql = "INSERT INTO projetos_tuberculose.redcap_autovalidation_log (record_id,pid,form_name,event,instance,redcap_instance,success,fail_reason,checked,fail_field,source) 
				VALUES ('$recordId',$pid,'$formName','$event',$instance,'$redcapInstance',$success,$failReason,$success,$failField,'$source');";			
	//error_log($sql,0);
	$conn->query($sql);
	error_log("finalizando funcao",0);
	return $success;
}

function checkOpenQueries($formName,$pid,$recordId,$instance,$eventId,$redcapInstance,$conn) {	
	if(!in_array($redcapInstance,array("redcap","redcap_sbgm","redcap_redbox"))) {
		$conn = connectExternalBd(getExternalDb($redcapInstance));
		if(is_null($conn))
			return null;
	}	
	$conn->select_db($redcapInstance);
	
	if($instance == "") 
		$instance = 1;
	
	$sql = "SELECT status_id FROM redcap_data_quality_status WHERE project_id = $pid AND record = '$recordId' AND instance = $instance AND query_status = 'OPEN' AND event_id = $eventId AND field_name IN (SELECT field_name FROM redcap_metadata WHERE project_id = $pid AND form_name = '$formName');";
	$result = $conn->query($sql);
	
	if(!$result || $result->num_rows == 0) {
		return false;
	}
	
	return true;
}

function validateDate($date,$pattern) {
	$dt = DateTime::createFromFormat($pattern,$date);
	return $dt !== false && !array_sum($dt::getLastErrors());
}

function recordsDivergence($project_id,$record_id_1,$record_id_2,$conn) {
	$project = getRedcapProjectByPk($project_id,$conn);
	$apiToken = $project["api_token"];
	$apiUrl = $project["api_url"];

	$sql = "SELECT form_name, description FROM projetos_tuberculose.redcap_forms WHERE project_id = $project_id;";
	$result_forms = $conn->query($sql);
	$forms = $result_forms->fetch_all(MYSQLI_ASSOC);
	
	$divArray = array();
	$count = 0;
	$excludedFields = array("record_id","data_preench_identificacao","resp_preench_identificacao","id_interno","data_preench_diag","resp_preench_diag","note_id_diag","data_preench_tto","resp_preench_tto","note_id_tto");
	foreach($forms as $f) {
		$data_1 = json_decode(exportRecordsRedCap($f["form_name"],null,$record_id_1,null,$apiToken,$apiUrl),true);
		$data_2 = json_decode(exportRecordsRedCap($f["form_name"],null,$record_id_2,null,$apiToken,$apiUrl),true);
		for($i=0;$i<sizeof($data_1);$i++) {
			$form_instance = $i+1;
			if(isset($data_1[$i]["redcap_repeat_instance"]) && $data_1[$i]["redcap_repeat_instance"] != "")
				$form_instance = $data_1[$i]["redcap_repeat_instance"];
			
			$divArray[$count]["form_name"] = $f["description"]." #$form_instance";
			foreach($data_1[$i] as $d1_key => $d1) {
				if($d1 != $data_2[$i][$d1_key] && !in_array($d1_key,$excludedFields)) {
					$divArray[$count][$record_id_1][$d1_key] = $d1;
					$divArray[$count][$record_id_2][$d1_key] = $data_2[$i][$d1_key];
				}
			}
		}
		$count++;
	}	
	return $divArray;
}
?>