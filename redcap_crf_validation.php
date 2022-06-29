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
$fields = json_decode(exportRedCapMetadataJSON($redboxProject["api_token"],$redboxProject["api_url"]),true);


?>

<!DOCTYPE html>
<html lang="pt-br" class="h-100">

<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>REDbox - Validação de CRFs</title>

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
								<form action="redcap_crf_validation_post.php" method="POST">
									<input type="hidden" value="<?=$_POST["authkey"]?>" name="authkey" />
									<input type="hidden" value="<?=$auth["callback_url"]?>" name="callback_url" />
									<input type="hidden" value="<?=$redcap_url?>" name="redcap_url" />
									<input type="hidden" value="<?=$redboxProject["id"]?>" name="redbox_project_id" />
									<h3 class="text-center">Validação de formulários/CRFs</h3>
									<p class="mt-3"><button type="button" class="btn btn-secondary btn-sm" onclick="window.location.href='<?=$auth["callback_url"]?>';">Voltar</button></p>
									<div class="form-group">
										<label for="form">Formulário/CRF</label>
										<select class="form-control" id="form" name="form" onchange="getQuestions(this);" required>
											<option value="">Selecione...</option>
										<?php
											foreach($forms as $f) {
												echo "<option value='".$f["instrument_name"]."'>".$f["instrument_label"]."</option>";
											}	
										?>
											<option value="geral">Geral</option>
										</select>
									</div>
									<div class="form-group">
										<label for="question">Pergunta</label>
										
										<select class="form-control" id="question" name="question" onchange="" required disabled>
											<option value="geral" selected>Geral</option>
										<?php
											foreach($fields as $f) {
												if($f["field_type"] != "descriptive") {
													$field_label = strip_tags($f["field_label"]);
													if(strlen($field_label) > 120)
														$field_label_truncate = substr($field_label,0,117)."...";
													else
														$field_label_truncate = $field_label;
													
													$field_name = $f["field_name"];
													$field_type = $f["field_type"];
													$form_name = $f["form_name"];
													echo "<option value='$form_name|$field_name|$field_label|$field_type' title='$field_label'>$field_label_truncate</option>";
												}
											}	
										?>
										</select>
									</div>
									<div class="form-group">
										<label for="comments_text">Comentários</label>
										<textarea class="form-control" id="comments_text" name="comments_text" rows="10" required></textarea>
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
		function getQuestions(form) {
			$("#question").attr("disabled",true);
			$('#question').val('geral');
			if(form.value != "" && form.value != "geral") {
				$("#question > option").each(function() {
					const values = this.value.split("|");
					if(values[0] != form.value) {
						$(this).css("display","none");
					} else {
						$(this).css("display","inline");
					}
				});
				
				$("#question").attr("disabled",false);
			}
		}
	</script>
</body>
</html>