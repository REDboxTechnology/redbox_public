<?php
include("bd_connection.php");
include("utils.php");
include("contact.php");

if (!file_exists('data.txt')) {
    touch('data.txt');
}

$data = file_get_contents('php://input');
$data = unicode2html($data);

$projectID = getHeader("PROJECT_ID");
$formID = getHeader("FORM_ID");
$form = getFormById($projectID,$formID,$conn);

if($form == -1 || $form == null) {
	sendMail("youraddress@yourdomain.com",null,"[REDbox][ERROR]","[Project ID: $projectID] [Form ID: $formID] Form não encontrado");
	exit();
}

$firstForm = $form["first_form"];
$formName = $form["form_name"];
$prefix = "f".$formID."_";
	
//if(true) { if (true) {
if(isset($_SERVER['PHP_AUTH_USER']) && isset($_SERVER['PHP_AUTH_PW'])) {
	if($_SERVER['PHP_AUTH_USER'] == "tb" && $_SERVER['PHP_AUTH_PW'] == "Tuber#123") {
		try{
			if(strlen($data) > 0) {
				$api = getApi($projectID,$conn);							
				$apiToken = $api["api_token"];
				$apiUrl = $api["api_url"];
							
				//PARSE JSON
				$parsedJsonData = parseJson($data);
				
				//EXTRACT THE UNIQUE ID FROM JSON
				if(isset($parsedJsonData[$form["unique_id_field"]]))
					$uniqueId = trim($parsedJsonData[$form["unique_id_field"]]);
				
				//EXTRACT RESP COLETA FROM JSON, IF ANY
				if(isset($parsedJsonData[$form["author_field"]]))
					$author = trim($parsedJsonData[$form["author_field"]]);
				else
					$author = '';
				
				//CHECK SEMANTIC ANNOTATION
				$semantics = checkSemantics($parsedJsonData,$form["id"],$conn);
						
				//GET RECORD ID
				if($firstForm == 0) {										
					//GET REDCAP RECORD_ID FROM MYSQL
					$recordID = getRecordId($uniqueId,$projectID,$conn);
								
					//IF RECORD_ID DOES NOT EXISTS, FINISH EXECUTION
					if($recordID == -1) {
						throw new Exception('Submissão falhou. Não é o primeiro form (firstForm) e o recordID não foi encontrado.');
					}					
				} else if($firstForm == 1) {
					$recordID = -1;
					if($form["is_repeatable"] == 1) {		
						//GET REDCAP RECORD_ID FROM MYSQL
						$recordID = getRecordId($uniqueId,$projectID,$conn);
					}
					
					if($recordID == -1) {
						//GENERATE NEW REDCAP RECORD_ID
						$recordID = generateNextRecordId($apiToken,$apiUrl);
					}
				}
				
				//VERIFY FILE UPLOAD
				$fileNameList = array();
				$fileTypeList = array();
				$baseFieldName = $prefix."upload";
				$fileCount = substr_count($data,$baseFieldName);
				for($i=1;$i<=$fileCount;$i++) {
					$fieldName = $baseFieldName."_".$i;				
					$fileToken = trim($parsedJsonData[$fieldName]);
					if($fileToken != null) {
						$fileInfo = getUploadedFile($projectID,$fileToken,$conn);
						$fileNameList[] = $fileInfo["file_name"];
						$fileTypeList[] = $fileInfo["file_type"];
						unset($parsedJsonData[$fieldName]);
					}
				}
				
				//VERIFY IF IS A REPEATABLE FORM OR IF IS PART OF AN EVENT AND INPUT INTO REDCAP JSON
				if($form["is_in_event"] == 1) {
					$parsedJsonData['redcap_event_name'] = $parsedJsonData[$prefix."event"];
					unset($parsedJsonData[$prefix.'event']);
				}
				
				if($form["is_repeatable"] == 1) {				
					$event = null;
					if(isset($parsedJsonData['redcap_event_name']))
						$event = $parsedJsonData['redcap_event_name'];
					
					$resp = exportRecordsRedCap($formName,$event,null,null,$apiToken,$apiUrl);
					$lastRecord = array_pop(json_decode($resp));
					$repeatInstance = $lastRecord->redcap_repeat_instance + 1;
					
					$parsedJsonData['redcap_repeat_instrument'] = $formName;
					$parsedJsonData['redcap_repeat_instance'] = $repeatInstance;
				}
				
				//INPUT RECORD_ID INTO REDCAP JSON
				$parsedJsonData['record_id'] = $recordID;
				$parsedJsonData[$formName.'_complete'] = "1"; //incomplete=0, unverified=1, complete=2
				$parsedJsonData = json_encode($parsedJsonData);
				
				//WRITE DATA TO TXT FILE
				$today = date("Y-m-d H:i:s");  
				file_put_contents('data.txt',"[Proj $projectID - Form $formID - RECORD_ID $recordID - ADD - FULL] [$today] $data\n",FILE_APPEND);
				file_put_contents('data.txt',"[Proj $projectID - Form $formID - RECORD_ID $recordID - ADD - PARSED] [$today] $parsedJsonData\n",FILE_APPEND);
				
				//CALL REDCAP API			
				$count = importRecordsRedCap("[".$parsedJsonData."]",$apiToken,$apiUrl);		

				//UPLOAD FILES TO REDCAP, IF ANY
				$ipfs = array();
				for($i=0;$i<sizeof($fileNameList);$i++) {
					$fieldName = $baseFieldName."_".($i+1);
					file_put_contents('data.txt',"IMPORTING FILE - RECORD_ID $recordID - $fieldName - ".$fileNameList[$i]."\n",FILE_APPEND);
					$r = importFileRedCap($fieldName,$recordID,$fileNameList[$i],$fileTypeList[$i],$apiToken,$apiUrl);	
					if(!empty($r)) {
						file_put_contents('data.txt',"$r\n",FILE_APPEND);		
						$ipfs[] = "/var/www/html/tb/kobo_redcap/kobo_files_upload/uploads/".$fieldName;
					}
				}
				
				//WRITE DATA TO MYSQL
				$sql = "INSERT INTO form_data (project_id,redcap_record_id,unique_id,first_form,form_id,json_data,parsed_json_data,author,operation,timestamp,semantics) VALUES ('$projectID',$recordID,'$uniqueId',$firstForm,'$formID','$data','$parsedJsonData','$author','add','$today','$semantics');";
				file_put_contents('data.txt',"$sql\n",FILE_APPEND);
				$conn->query($sql);
				$last_id = $conn->insert_id;
				
				//WRITE TO KALEIDO BLOCKCHAIN
				if($form["kaleido_enabled"] == 1) {
					$kldCredentials = getKldCredentials($projectID,$conn);			
					if($kldCredentials != null) {
						$ipfs_resp = array();
						if(isset($kldCredentials["kld_ipfs_api_endpoint"]) && !empty($kldCredentials["kld_ipfs_api_endpoint"])) {							
							foreach($ipfs as $file) {
								$ipfs_resp[] = json_decode(ipfsAdd($file,$kldCredentials,$conn),true);
							}
						}
						if(!empty($ipfs_resp)) {
							$d = json_decode($parsedJsonData,true);
							$d["files_ipfs"] = $ipfs_resp;
							$parsedJsonData = json_encode($d);
						}
						writeToBlockchain($parsedJsonData,$last_id,'add',$projectID,$formName,$recordID,$author,$semantics,$kldCredentials,$conn);
					}				
				} 
				
				//SEND NOTIFICATION TO ADMIN
				if($form["enable_notification"] == 1) {
					sendMail($form["email_notification"],null,"[REDbox][INFO] Form preenchido","[Project ID: $projectID] [Form: ".$form["description"]."] - Dados submetidos");					
				}
							
				//SEND CONFIRMATION EMAIL TO RESPONDENT
				if ($form["respondent_confirmation"] == 1) { 
					$campoEmail = $form["respondent_email_field"];
					$emailResp = json_decode($parsedJsonData)->$campoEmail;
					$formDesc = $form["description"];
					sendMail($emailResp,$form["email_notification"],utf8_decode("[REDbox] $formDesc - Confirmação de preenchimento"),"Prezado(a),<br/><br/>Confirmamos o recebimento do formulário.<br/><br/><strong>Equipe de TI<br/>Rede nAcional de doenças raRAS (RARAS)</strong>");
				}
			}
		} catch(Exception $e) {
			//$e->getMessage();
			sendMail("youraddress@yourdomain.com",null,"[REDbox][ERROR]","[Project ID: $projectID] [Form ID: $formID] ".$e->getMessage());
		}
	}
} else {
	exit();
}
?>
