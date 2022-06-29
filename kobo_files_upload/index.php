<!doctype html>
<html lang="pt-br" class="h-100">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Upload de arquivos - Kobotoolbox</title>

    <!-- Bootstrap core CSS -->
	<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">

    <style>
		.container {
			width: auto;
			max-width: 680px;
			padding: 0 15px;
		}

		.footer {
			background-color: #f5f5f5;
		}
		
		.bd-placeholder-img {
			font-size: 1.125rem;
			text-anchor: middle;
			-webkit-user-select: none;
			-moz-user-select: none;
			-ms-user-select: none;
			user-select: none;
		}	

		@media (min-width: 768px) {
			.bd-placeholder-img-lg {
			  font-size: 3.5rem;
			}
		}
    </style>
  </head>
  <body class="d-flex flex-column h-100">
	<main role="main" class="flex-shrink-0">
	  <div class="container">
		<h1 class="mt-5">Upload de arquivo - Kobotoolbox</h1>
		
		<?php
			include("../bd_connection.php");
			$sql = "SELECT id,project FROM redcap_projects ORDER BY project ASC;";
			$result = $conn->query($sql);
		?>
		
		<form action="process_upload.php" method="POST" enctype="multipart/form-data">
			<div class="input-group mb-3 mt-3">
				<div class="input-group-prepend">
					<label class="input-group-text" for="project">Projeto</label>
				</div>
				<select class="custom-select" id="project" name="project" required>
					<option value="" selected>Selecione</option>
					<?php
						while($row = $result->fetch_assoc()) {
							extract($row);
							echo "<option value='$id'>$project</option>";
						}
					?>
				</select>
			</div>
		<div class="form-group mt-4">
			<label for="file" class="sr-only">Escolher arquivo</label>
			<input type="file" name="file" class="form-control-file" id="file" required>
		</div>
		<button type="submit" class="btn btn-block btn-sm btn-secondary mb-2">Enviar</button>
		</form>
		
		<?php 
			if(isset($_GET["t"])) {
				$t = $_GET["t"];
				if($t == "error") {
		?>
					<div class="alert alert-danger mt-4" role="alert">
						Erro ao carregar arquivo. Por favor, tente novamente.
					</div>
		<?php 	} else { ?>
					<h2 class="mt-5">Código de identificação do arquivo</h2>
					<small>Copie o código abaixo e retorne para o formulário </small>
					<div class="input-group">
					  <input type="text" class="form-control" readonly id="token" value="<?=$t?>">
					  <div class="input-group-append">
						<button class="btn btn-outline-primary" type="button" id="btnCopiar" onclick="copyClipboard()">Copiar</button>
					  </div>
					</div>		
		<?php 	} 
			}
		?>
	  </div>
	</main>

	<footer class="footer mt-auto py-3">
	  <div class="container text-center">
		<span class="text-muted">Laboratório de Informática em Saúde - FMRP - USP</span>
	  </div>
	</footer>
	
	<script type="text/javascript">
		function copyClipboard() {
		  var copyText = document.getElementById("token");
		  copyText.select();
		  copyText.setSelectionRange(0, 99999);
		  document.execCommand("copy");
		  
		  var btnCopiar = document.getElementById("btnCopiar");
		  btnCopiar.innerHTML = "Copiado!";
		}
	</script>
</body>
</html>
