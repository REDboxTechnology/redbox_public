<?php
require_once 'PHPExcel/IOFactory.php';
include("../bd_connection.php");
include("../utils.php");

if (isset($_POST['upload_excel'])) {
    //recebe arquivo
    $file_directory = str_replace('\\', '/', getcwd()) . "/uploads/";
    $new_file_name = date("dmY") . time() . $_FILES["result_file"]["name"];

    //recebe nome do arquivo
    $file_name_csv = explode('.', $_FILES["result_file"]["name"]);
    unset($file_name_csv[sizeof($file_name_csv) - 1]);
    $file_name_csv = implode('', $file_name_csv);

    if (move_uploaded_file($_FILES["result_file"]["tmp_name"], $file_directory . $new_file_name)) {
        //leitura do arquivo e armazenamento em array
        $file_type = PHPExcel_IOFactory::identify($file_directory . $new_file_name);
        $objReader = PHPExcel_IOFactory::createReader($file_type);
        $objPHPExcel = $objReader->load($file_directory . $new_file_name);
        $sheet_count = $objPHPExcel->getSheetCount();
        for ($i = 0; $i < $sheet_count; $i++) {
            $kobo_data[] = $objPHPExcel->getSheet($i)->toArray(null, True, True, True);
        }
		
		$check_xls = checkXLSForm($kobo_data);
		if(!empty($check_xls)) {
			header("Location: index.php?t=error&msg=".serialize($check_xls));
			return;
		}
		
        //conversao
        $redcap_data = [];
		$accepted_field_types = ['text','date','datetime','time','integer','decimal','calculate','select_one','select_multiple','note'];		
		$headers_xls = $kobo_data[0]["1"];
		//$form_name = strtolower(str_replace(' ','_',$_POST["form_name"])) . "_" . date("Ymd_Hi");
		$form_name = strtolower(str_replace(' ','_',$_POST["form_name"]));
		
		$redcap_data[1]['A'] = 'field_name';
		$redcap_data[1]['B'] = 'form_name';
		$redcap_data[1]['C'] = 'section_header';
		$redcap_data[1]['D'] = 'field_type';
		$redcap_data[1]['E'] = 'field_label';
		$redcap_data[1]['F'] = 'select_choices_or_calculations';
		$redcap_data[1]['G'] = 'field_note';
		$redcap_data[1]['H'] = 'text_validation_type_or_show_slider_number';		
		$redcap_data[1]['I'] = 'text_validation_min';
		$redcap_data[1]['J'] = 'text_validation_max';
		$redcap_data[1]['K'] = 'identifier';
		$redcap_data[1]['L'] = 'branching_logic';
		$redcap_data[1]['M'] = 'required_field';
		$redcap_data[1]['N'] = 'custom_alignment';
		$redcap_data[1]['O'] = 'question_number';
		$redcap_data[1]['P'] = 'matrix_group_name';
		$redcap_data[1]['Q'] = 'matrix_ranking';
		$redcap_data[1]['R'] = 'field_annotation';
		
		$section_header = "";		
		$branching_logic_section = [];
        foreach ($kobo_data[0] as $key_survey => $line) {
			if($line['A'] == "begin group" || $line['A'] == "end group")
				$line['A'] = str_replace(" ","_",$line['A']); 
		
			$line['A'] = explode(' ', $line['A']);
						
			if ($line['A'][0] == 'begin_group') {
				$s = array_search('label',$headers_xls);
				$section_header = $line[$s];
				$s = array_search('relevant',$headers_xls);
				if($s !== false)
					$branching_logic_section[] = str_replace('"',"'",$line[$s]);
			} else if ($line['A'][0] == 'end_group') {
				array_pop($branching_logic_section);
			}
			
			if ($line['B'] != "" && $line['B'] != '__version__' && $line['B'] != 'info_upload' && $line['B'] != 'info_url_upload' && in_array($line['A'][0],$accepted_field_types)) {
				if ($key_survey != 1) {
					$field_name = trim($line['B']);
                    
                    //field_type
                    $redcap_data[$key_survey]['D'] = $line['A'][0];
                    if ($line['A'][0] == 'select_one') {
                        $redcap_data[$key_survey]['D'] = 'radio';
                    } else if ($line['A'][0] == 'select_multiple') {
                        $redcap_data[$key_survey]['D'] = 'checkbox';
                    } else if ($line['A'][0] == 'note') {
                        $redcap_data[$key_survey]['D'] = 'descriptive';
                    } else if ($line['A'][0] == 'calculate') {
                        $redcap_data[$key_survey]['D'] = 'calc';
                    } else {
						if (strpos($line['B'], 'upload') !== false) {
							$redcap_data[$key_survey]['D'] = 'file';
						} else {
							$redcap_data[$key_survey]['D'] = 'text';
						}						                       
                    }
					
					//field_label
					if ($line['A'][0] == 'calculate') {
						$s = array_search('guidance_hint',$headers_xls);
						if($s !== false)
							$redcap_data[$key_survey]['E'] = str_replace('"',"'",$line[$s]);
						else
							$redcap_data[$key_survey]['E'] = "autogenerated_label";
                    } else {
						$s = array_search('label',$headers_xls);
						$redcap_data[$key_survey]['E'] = "autogenerated_label";
						if($line[$s] != "") {
							$line[$s] = str_replace("$","",$line[$s]);
							$line[$s] = str_replace("{","[",$line[$s]);
							$line[$s] = str_replace("}","]",$line[$s]);
							$line[$s] = str_replace('"',"'",$line[$s]);
							$redcap_data[$key_survey]['E'] = $line[$s];
						}
					}
					
					//text_validation_type_or_show_slider_number
					$redcap_data[$key_survey]['H'] = '';
					if ($line['A'][0] == 'integer') {
						$redcap_data[$key_survey]['H'] = 'integer';
					} else if ($line['A'][0] == 'decimal') {
						$redcap_data[$key_survey]['H'] = 'number';
					} else if ($line['A'][0] == 'date') {
						$redcap_data[$key_survey]['H'] = 'date_dmy';
					} else if ($line['A'][0] == 'datetime') {
						$redcap_data[$key_survey]['H'] = 'datetime_dmy';
					} else if ($line['A'][0] == 'time') {
						$redcap_data[$key_survey]['H'] = 'time';
					}
                    
					//section_header					
					$redcap_data[$key_survey]['C'] = '';
					if($section_header != "") {
						$redcap_data[$key_survey]['C'] = $section_header;
						$section_header = "";
					}
					
					//field_name
					$redcap_data[$key_survey]['A'] = $field_name;
					
					//field_note
					$redcap_data[$key_survey]['G'] = '';
					$s = array_search('hint',$headers_xls);
					if($s !== false)
						$redcap_data[$key_survey]['G'] = str_replace('"',"'",$line[$s]);

                    //required
                    $redcap_data[$key_survey]['L'] = ''; 
                    $redcap_data[$key_survey]['M'] = ''; 
                    if ($line[array_search('required',$headers_xls)] == 'true') {
                        $redcap_data[$key_survey]['M'] = 'y';
                    } 
					
					//branching_logic
					$redcap_data[$key_survey]['L'] = '';
					$s = array_search('relevant',$headers_xls);
					if($s !== false) {
						$branching_logic = $line[$s];
						
						if($branching_logic == "" && sizeof($branching_logic_section) > 0) {
							$branching_logic = end($branching_logic_section);
						}
						
						if (preg_match("/\band\b/i", $branching_logic) && preg_match("/\bor\b/i", $branching_logic)) {
							$branching_logic = '';
						} else if (preg_match("/\band\b/i", $branching_logic)) {
							$aux = explode("and",$branching_logic);
							$bls = array();
							foreach($aux as $bl) {
								$bls[] = checkBranchingLogic(trim($bl),$kobo_data[0]);
							}
							$branching_logic = implode(" and ",$bls);
						} else if (preg_match("/\bor\b/i", $branching_logic)) {
							$aux = explode("or",$branching_logic);
							$bls = array();
							foreach($aux as $bl) {
								$bls[] = checkBranchingLogic(trim($bl),$kobo_data[0]);
							}
							$branching_logic = implode(" or ",$bls);							
						} else {
							$branching_logic = checkBranchingLogic($branching_logic,$kobo_data[0]);
						}						
												
						if($branching_logic != "[]=")
							$redcap_data[$key_survey]['L'] = $branching_logic;
					}						

                    //select_choices_or_calculations
                    if ($line['A'][0] == 'select_one' || $line['A'][0] == 'select_multiple') {
                        $redcap_data[$key_survey]['F'] = '';
                        foreach ($kobo_data[1] as $key_choices => $choices) {
                            if (end($line['A']) == $choices['A']) {
                                $redcap_data[$key_survey]['F'] .= $choices['B'] . ', ' . $choices['C'];
                                if (isset($kobo_data[1][$key_choices + 1])) {
                                    if ($kobo_data[1][$key_choices]['A'] == $kobo_data[1][$key_choices + 1]['A']) {
                                        $redcap_data[$key_survey]['F'] .= ' | ';
                                    }
                                }
                            }
                        }
                    } else {
                        $redcap_data[$key_survey]['F'] = '';
                    }
                                        
					//form_name
					//$redcap_data[$key_survey]['B'] = str_replace(' ', '', preg_replace('/[^A-Za-z0-9\-]/', '', $file_name_csv));
					$redcap_data[$key_survey]['B'] = $form_name;
					
					//'identifier' e 'field_annotation' (metadados, default value)
					$redcap_data[$key_survey]['K'] = '';
					$redcap_data[$key_survey]['R'] = '';
					$metadata = getMetadata($field_name,$conn);
					$semantic = '';
					if($metadata != -1) {
						if($metadata["identifier"] == 1) {
								$redcap_data[$key_survey]['K'] = 'y';
						}		
						if(isset($metadata["semantic_annotation"]) && !empty($metadata["semantic_annotation"])) {
							$semantic = $metadata["semantic_annotation"];					
						}
                    }
					
					$default = '';
					$s = array_search('default',$headers_xls);
					if($s !== false)
						$default = "@DEFAULT='" . $line[$s] . "'";
					
					$redcap_data[$key_survey]['R'] = $default . ' ' . $semantic;
					$redcap_data[$key_survey]['R'] = trim($redcap_data[$key_survey]['R']);
					
					//preenche com string vazia os campos que não existem no kobo                    
					$redcap_data[$key_survey]['I'] = '';
                    $redcap_data[$key_survey]['J'] = '';                    
                    $redcap_data[$key_survey]['N'] = '';
					$redcap_data[$key_survey]['O'] = '';
					$redcap_data[$key_survey]['P'] = '';
					$redcap_data[$key_survey]['Q'] = '';					
                }
            }
        }
		
		//construir o csv			
		$csv_string = '';
		foreach ($redcap_data as $key_redcap => $line_redcap) {
			//if($key_redcap != 1) {
				$csv_string .= '"' . $line_redcap['A'] . '",'
						. '"' . $line_redcap['B'] . '",'
						. '"' . $line_redcap['C'] . '",'
						. '"' . $line_redcap['D'] . '",'
						. '"' . $line_redcap['E'] . '",'
						. '"' . $line_redcap['F'] . '",'
						. '"' . $line_redcap['G'] . '",'
						. '"' . $line_redcap['H'] . '",'
						. '"' . $line_redcap['I'] . '",'
						. '"' . $line_redcap['J'] . '",'
						. '"' . $line_redcap['K'] . '",'
						. '"' . $line_redcap['L'] . '",'
						. '"' . $line_redcap['M'] . '",'
						. '"' . $line_redcap['N'] . '",'
						. '"' . $line_redcap['O'] . '",'
						. '"' . $line_redcap['P'] . '",'
						. '"' . $line_redcap['Q'] . '",'
						. '"' . $line_redcap['R'] . '"' . PHP_EOL;
			//}
		}
		
		$csv_header = '"' . $redcap_data[1]['A'] . '",'
				. '"' . $redcap_data[1]['B'] . '",'
				. '"' . $redcap_data[1]['C'] . '",'
				. '"' . $redcap_data[1]['D'] . '",'
				. '"' . $redcap_data[1]['E'] . '",'
				. '"' . $redcap_data[1]['F'] . '",'
				. '"' . $redcap_data[1]['G'] . '",'
				. '"' . $redcap_data[1]['H'] . '",'
				. '"' . $redcap_data[1]['I'] . '",'
				. '"' . $redcap_data[1]['J'] . '",'
				. '"' . $redcap_data[1]['K'] . '",'
				. '"' . $redcap_data[1]['L'] . '",'
				. '"' . $redcap_data[1]['M'] . '",'
				. '"' . $redcap_data[1]['N'] . '",'
				. '"' . $redcap_data[1]['O'] . '",'
				. '"' . $redcap_data[1]['P'] . '",'
				. '"' . $redcap_data[1]['Q'] . '",'
				. '"' . $redcap_data[1]['R'] . '"';
		
		$output = $_POST["output"];	
		if($output == "output_redcap") {
			//construir json - import metadata
			/*$headers_list = $redcap_data[1];
			$fields_array = array();		
			foreach ($redcap_data as $key_redcap => $line_redcap) {						
				if($key_redcap != 1) {
					$aux_array = array();
					foreach ($headers_list as $key_header => $value_header) {
						$aux_array[] = '"' . trim($value_header) . '":"' . trim($line_redcap[$key_header]) . '"';
					}
					$fields_array[] = '{' . implode(',',$aux_array) . '}';
				}			
			}*/
					
			$apiToken = $_POST["api_token"];	
			$apiUrl =  $_POST["api_url"];
			
			$current_metadata = exportRedCapMetadata($apiToken,$apiUrl);

			/*if($current_metadata == '[]') {
				$current_metadata = '';					
			} else {
				$current_metadata = substr($current_metadata,1,strlen($current_metadata)-2) . ',';
			}*/
			
			if($current_metadata == '') {
				$current_metadata = $csv_header . PHP_EOL . 'record_id,'.$form_name.',,text,"Record ID",,,,,,,,,,,,,' . PHP_EOL;
			}
			
			//$json_metadata = '[' . $current_metadata . implode(',',$fields_array) . ']';	
			$csv_metadata = $current_metadata . $csv_string;	
			$r = importRedCapMetadata($csv_metadata,$apiToken,$apiUrl);
			
			
			/*echo "Resposta API: " . $r . "<br/><br/>";
			echo "<a href='index.php'>Voltar</a>";*/
			
			if(is_integer($r)) {
				$r = 'Importação finalizada com sucesso!';
				header("Location: index.php?t=success&msg=$r");
			} else if($r == '') {
				header("Location: index.php?t=error&msg=Erro ao importar metadados. Verifique a chave e a URL da API");
			} else {
				header("Location: index.php?t=error&msg=$r");
			}
		} else if($output == "output_zip") {	
			$csv_string = $csv_header . $csv_string;
		
			$zip = new ZipArchive();
			$zipFile = "uploads/instrument.zip";
			if ($zip->open($zipFile, ZipArchive::CREATE) === TRUE) {
				$zip->addFromString('instrument.csv', $csv_string);
				$zip->close();
			}

			header('Content-Type: application/zip');
			header("Accept-Ranges: bytes");
			header('Content-Disposition: attachment; filename=instrument.zip');
			header('Content-Length: ' . filesize($zipFile));
			print readfile($zipFile);

			unlink($zipFile);
			unlink($file_directory . $new_file_name);  
		}		
    } else {
		header("Location: index.php?t=error&msg=Erro ao carregar arquivo. Por favor, tente novamente.");
	}
} else {
	header("Location: index.php?t=error&msg=Erro ao carregar arquivo. Por favor, tente novamente.");
}