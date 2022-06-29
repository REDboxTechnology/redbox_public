<?php
if (!isset($_POST["authkey"])) {
	echo "Acesso externo negado.";
	exit();
}

$redcap_url = $_SERVER["HTTP_ORIGIN"];
if($redcap_url == "https://redbox.technology")
	$redcap_url = $_POST["redcap_url"];

require_once("bd_connection.php");
require_once("utils.php");

$http = new HTTPRequester();
$params = array();
$params["authkey"] = $_POST["authkey"];
$params["format"] = "json";

$auth = array();
$auth = json_decode($http->HTTPPost($redcap_url."/api/", $params, null, null), true);

if(empty($auth)) {
	echo "Acesso negado";
	exit();
}

$redboxProject = getRedcapProject($auth["project_id"],$redcap_url,$conn);
if($redboxProject == -1) {
	echo "Projeto não registrado no REDbox.";
	exit();
}

$projectInfo = json_decode(exportProjectInfo($redboxProject["api_token"],$redboxProject["api_url"]),true);

$forms = json_decode(exportInstruments($redboxProject["api_token"],$redboxProject["api_url"]),true);
$repeatingFormsEvents = json_decode(exportRepeatingFormsEvents($redboxProject["api_token"],$redboxProject["api_url"]),true);
	
$filterLogic = null;
if($auth["data_access_group_name"] != "") {
	$filterLogic = "[record-dag-id] = '".$auth["data_access_group_id"]."'";
}

$sql = "SELECT not_allowed_forms,event_unique_name_records_filter FROM projetos_tuberculose.redcap_user_support_config WHERE project_id = ".$redboxProject["id"].";";
$result = $conn->query($sql);
$config = $result->fetch_assoc();

$records = json_decode(exportRecordsFieldsRedCap(null,array($config["event_unique_name_records_filter"]),null,$filterLogic,array("record_id"),$redboxProject["api_token"],$redboxProject["api_url"]),true);
?>

<!DOCTYPE html>
<html lang="pt-br" class="h-100">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>REDbox - Solicitação de desbloqueio e exclusão de registros</title>

	<link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">

	<!-- jQuery -->
	<script src="https://code.jquery.com/jquery-3.6.0.min.js" integrity="sha256-/xUj+3OJU5yExlq6GSYGSHk7tPXikynS7ogEvDej/m4=" crossorigin="anonymous"></script>

	<!-- Bootstrap -->
	<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.3.0/font/bootstrap-icons.css">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script>
	<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script>
</head>

<body class="d-flex flex-column h-100">
	<main role="main" class="flex-shrink-0">
		<div class="mt-4" id="accordion">
			<div class="card" style="width:50%; margin:0 auto;">
				<div class="btn text-left card-header" id="conteudoHead" data-target="#conteudo" aria-expanded="true" aria-controls="conteudo">
					<h5 class="mb-0">
						<?=$projectInfo["project_title"]?>
					</h5>
				</div>
				<div id="conteudo" class="collapse show" aria-labelledby="conteudoHead" data-parent="#accordion">
					<div class="card-body">
						<div class="row mt-3">
							<div class="col-md-12 mx-auto">
								<form action="redcap_user_support_post.php" method="POST">
									<input type="hidden" value="<?=$_POST["authkey"]?>" name="authkey" />
									<input type="hidden" value="<?=$auth["callback_url"]?>" name="callback_url" />
									<input type="hidden" value="<?=$redcap_url?>" name="redcap_url" />
									<input type="hidden" value="<?=$redboxProject["id"]?>" name="redbox_project_id" />
									<h3 class="text-center">Solicitar desbloqueio ou exclusão de registro</h3>
									<p class="mt-3"><button type="button" class="btn btn-secondary btn-sm" onclick="window.location.href='<?=$auth["callback_url"]?>';">Voltar</button></p>
									<div class="form-group">
										<label for="action">Ação</label>
										<select class="form-control" id="action" name="action" onchange="processAction(this);" required>
											<option value="">Selecione...</option>
											<option value="unlock">Desbloquear</option>
											<option value="delete">Excluir</option>
										</select>
									</div>
									<div class="form-group">
										<label for="recordId">Record ID</label>
										<select class="form-control" id="recordId" name="recordId" required>
											<option value="">Selecione...</option>
										<?php
										foreach($records as $r) {
											echo "<option value='".$r["record_id"]."'>".$r["record_id"]."</option>";
										}											
										?>
										</select>
									</div>
									<div class="form-group">
										<label for="form">Formulário</label>
										<select class="form-control" id="form" name="form" onchange="formInstance(this);" disabled required>
											<option value="">Selecione...</option>
										<?php
										if($projectInfo["is_longitudinal"] == 0) {
											foreach($forms as $f) {
												if(strpos($config["not_allowed_forms"],$f["instrument_name"]) === false)
													echo "<option value='event_1_arm_1|".$f["instrument_name"]."'>".$f["instrument_label"]."</option>";
											}	
										} else {
											$formEventMapping = json_decode(exportFormEventMapping($redboxProject["api_token"],$redboxProject["api_url"]),true);
											$events = json_decode(exportEvents($redboxProject["api_token"],$redboxProject["api_url"]),true);
											foreach($formEventMapping as $map) {
												foreach($forms as $f) {
													if($f["instrument_name"] == $map["form"]) {
														$formName = $f["instrument_label"];
														break;
													}
												}
												foreach($events as $e) {
													if($e["unique_event_name"] == $map["unique_event_name"]) {
														$eventName = $e["event_name"];
														break;
													}
												}
												if(strpos($config["not_allowed_forms"],$map["form"]) === false)
													echo "<option value='".$map["unique_event_name"]."|".$map["form"]."'>".$eventName." > ".$formName."</option>";
											}
											
										}
										?>
										</select>
									</div>
									<div class="form-group">
										<label for="instance">Instância/Nº do formulário</label>
										<input type="number" class="form-control" id="instance" name="instance" value="1" min="1" disabled required />
										<small>Se o formulário for repetitivo, informe o nº da instância. Caso contrário, mantenha o valor "1"</small>
									</div>
									<div class="form-group">
										<label for="reason">Justificativa/Motivo</label>
										<textarea class="form-control" id="reason" name="reason" rows="5" required></textarea>
									</div>									
									<button type="submit" class="btn btn-primary btn-lg btn-block">Enviar</button>									
								</form>	
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</main>
	<footer class="footer mt-auto py-3">
	  <div class="container text-center">
		<span class="text-muted"><a href="https://redbox.technology" target="_blank"><img src="assets/images/redbox_logoh.png" width="200"/></a></span>
	  </div>
	</footer>
	
	<script type="text/javascript">
		function processAction(e) {
			$("#form").attr("disabled",true);
			
			if($("#form option:last-child").val() == "todos|todos")
				$("#form option:last-child").remove();
			
			if(e.value == "delete" || e.value == "unlock") {
				if(e.value == "delete")
					$("#form").append("<option value='todos|todos'>Todos os formulários (registro completo)</option>");
				
				$("#form").attr("disabled",false);
			}
		}
		function formInstance(form) {
			$("#instance").attr("disabled",true);
			$("#instance").attr("required",false);
			if(form.value != "todos|todos") {
				$("#instance").attr("disabled",false);
				$("#instance").attr("required",true);
			}
		}
	</script>
</body>
</html>