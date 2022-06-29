<!doctype html>
<html lang="pt-br" class="h-100">
  <head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>XLSForm -> REDCap Conversor</title>
	<link rel="shortcut icon" href="../assets/images/favicon.ico" type="image/x-icon">

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
		<h1 class="mt-5">Convert XLSForm to REDCap CSV</h1>
		
		<?php
			include("../bd_connection.php");
			$sql = "SELECT id,project FROM redcap_projects ORDER BY project ASC;";
			$result = $conn->query($sql);
		?>
		
		<form action="conversor.php" method="POST" enctype="multipart/form-data">
			<div class="form-group mt-4">
				<label for="result_file" class="sr-only">Choose the XLSForm file</label>
				<input type="file" name="result_file" class="form-control-file" id="result_file" required>
			</div>			
			<div class="form-group mt-4">
				<label for="form_name">Form name (only letters and underscore)</label>
				<input type="text" name="form_name" class="form-control" id="form_name" required>
			</div>
			<div class="form-group mt-4">
				<label for="">Do you want to download a ZIP file to manually import it or want to send the form directly to REDCap?</label>
				
				<label for="output_zip">Generate ZIP file</label>
				<input type="radio" name="output" id="output_zip" value="output_zip" onclick="document.getElementById('api_url').required = false; document.getElementById('api_token').required = false; document.getElementById('api_div').style.display = 'none';">
				
				<label for="output_redcap" style="margin-left:25px;">Import to REDCap (disabled)</label>
				<input type="radio" name="output" id="output_redcap" value="output_redcap" onclick="document.getElementById('api_url').required = true; document.getElementById('api_token').required = true; document.getElementById('api_div').style.display = 'block';" disabled>
			</div>
			<div id="api_div" style="display:none;">
				<div class="form-group mt-4">
					<label for="api_token">API Token</label>
					<input type="text" name="api_token" class="form-control" id="api_token">
				</div>
				<div class="form-group mt-4">
					<label for="api_url">API URL</label>
					<input type="text" name="api_url" class="form-control" id="api_url">
				</div>
			</div>
			<button type="submit" name="upload_excel" class="btn btn-block btn-sm btn-secondary mb-2" onclick="clearAlerts();">Go</button>
		</form>
		
		<?php 
			if(isset($_GET["t"])) {
				$t = $_GET["t"];
				if($t == "error") {
					$json = json_encode(unserialize($_GET["msg"]),JSON_PRETTY_PRINT);
		?>
					<div class="alert alert-danger mt-4" role="alert" id="div-danger">							
						<?="<pre style='white-space: break-spaces;'>$json</pre>"?>
					</div>	
		<?php 	} else if($t == "success") { ?>
					<div class="alert alert-success mt-4" role="alert" id="div-success">
						<?=$_GET["msg"]?>
					</div>	
		<?php	}
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
		function clearAlerts() {
			//document.getElementById("div-danger").innerHTML = "";
			//document.getElementById("div-success").innerHTML = "";
			document.getElementById("div-danger").style.display = "none";
			document.getElementById("div-success").style.display = "none";
		}
	</script>
  </body>
</html>	
