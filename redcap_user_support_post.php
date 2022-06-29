<?php
if($_SERVER["HTTP_ORIGIN"] != "https://redbox.technology" || !isset($_POST["authkey"])) {
	exit();
}

require_once("bd_connection.php");
require_once("utils.php");

$http = new HTTPRequester();
$params = array();
$params["authkey"] = $_POST["authkey"];
$params["format"] = "json";

$auth = array();
$auth = json_decode($http->HTTPPost($_POST["redcap_url"]."/api/", $params, null, null), true);

if(empty($auth)) {
	echo "Acesso negado";
	exit();
}

$redboxProjectId = $_POST["redbox_project_id"];
$username = $auth["username"];
$recordId = $_POST["recordId"];
$form = explode("|",$_POST["form"]);
$eventUniqueName = $form[0];
$formName = $form[1];
$action = $_POST["action"];
$reason = $_POST["reason"];

$instance = 1;
if(isset($_POST["instance"]))
	$instance = $_POST["instance"];

$sql = "INSERT INTO projetos_tuberculose.redcap_user_support (project_id,username,record_id,event_unique_name,form_name,instance,action,reason) 
			VALUES ($redboxProjectId,'$username','$recordId','$eventUniqueName','$formName',$instance,'$action','$reason')";
$result = $conn->query($sql);
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
				<div id="conteudo" class="collapse show" aria-labelledby="conteudoHead" data-parent="#accordion">
					<div class="card-body">
						<div class="row mt-3">
							<div class="col-md-12 mx-auto">
							<?php if($result) { ?>
								<div class="alert alert-success" role="alert">
									O seu pedido foi registrado e será processado em breve!
								</div>
							<?php } else { ?>
								<div class="alert alert-danger" role="alert">
									Um erro ocorreu ao registrar o seu pedido.<br/>
									Por favor, tente novamente ou entre em contato com a equipe do projeto.
								</div>
							<?php } ?>
								<form action="redcap_user_support.php" method="POST">
									<input type="hidden" value="<?=$_POST["redcap_url"]?>" name="redcap_url" />
									<input type="hidden" value="<?=$_POST["authkey"]?>" name="authkey" />
									<button type="button" class="btn btn-secondary btn-sm" onclick="window.location.href='<?=$auth["callback_url"]?>';">Voltar para o REDCap</button>
									<button type="submit" class="btn btn-primary btn-sm ml-3">Nova solicitação</button>
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
</body>
</html>