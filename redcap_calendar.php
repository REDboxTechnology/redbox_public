<?php
require_once("bd_connection.php");
require_once("utils.php");
$server = $_SERVER["HTTP_ORIGIN"];
$center = "";
if($server != "https://redbox.technology") {	
	if(!isset($_POST["authkey"])) { exit(); }	
	$http = new HTTPRequester();
	$params = array();
	$params["authkey"] = $_POST["authkey"];
	$params["format"] = "json";
	$auth = json_decode($http->HTTPPost("$server/api/", $params, null, null),true);	
	$pid = $auth["project_id"];		
} else {
	if(!isset($_POST["pid"]) || !isset($_POST["redcap_url"]) || !isset($_POST["center"])) { exit(); }	
	$pid = $_POST["pid"];	
	$server = $_POST["redcap_url"];	
	$center = $_POST["center"];	
}

if($center != "") {
	$api = getRedcapProject($pid,$server,$conn);
	$project_id = $api["id"];
	
	$sql = "SELECT * FROM vw_redcap_visits WHERE project_id = $project_id ORDER BY `order` ASC;";
	$result = $conn->query($sql);
	$visits = $result->fetch_all(MYSQLI_ASSOC);												
	
	$refDateField = $visits[0]["reference_date_field"];		
	$centerFilter = " and [".$visits[0]["custom_center_field"]."] = '".$center."'";
	
	if(isset($visits[0]["custom_center_field_event"]))
		$centerFilter = " and [".$visits[0]["custom_center_field_event"]."][".$visits[0]["custom_center_field"]."] = '".$center."'";
	
	$filter = "[".$visits[0]["reference_date_field_event"]."][$refDateField] != '' $centerFilter";
	$records = json_decode(exportRecordsFieldsRedCap(null,null,null,$filter,array("record_id","$refDateField"),$api["api_token"],$api["api_url"]),true);	
	//echo $filter."<br/>";
	//var_dump($records); exit();
}	
?>

<!doctype html>
<html lang="pt-br" class="h-100">
  <head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>REDbox - Calendário</title>
	<link rel="shortcut icon" href="assets/images/favicon.ico" type="image/x-icon">

	<!-- Bootstrap core CSS -->
	<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">

	<!-- jQuery -->
	<script type="text/javascript" charset="utf8" src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
	
	<!-- Datatables -->
    <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.10.10/js/jquery.dataTables.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.10.10/css/jquery.dataTables.css">
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.7.0/js/dataTables.buttons.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.html5.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/buttons/1.7.0/js/buttons.print.min.js"></script>
	<script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>	
	<script type="text/javascript" charset="utf8" src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>		
	
	<script type="text/javascript">
		$(document).ready( function () {
			$('#visitsTable').DataTable({
				dom: 'Bfrtip',
				buttons: [
					'csv', 
					'excel', 
					{
						extend: 'print',
						text: 'Imprimir'
					}
				],
				"language": {
					"url": "//cdn.datatables.net/plug-ins/1.10.19/i18n/Portuguese-Brasil.json"
				},
				columnDefs: [
					{
						targets: "_all",
						className: "dt-body-center"
					}
				]
			});
		});
		
		function carregaCentros(projectId,selected) {									
			if(projectId == "")
				return;
			
			var centers = document.getElementById("center");
			centers.value = "";
			centers.disabled = true;
			$.ajax({
				url: 'get_research_centers.php',
				method: 'POST',
				dataType: 'json',
				data: { project_id: projectId, redcap_url: '<?=$server?>' },				
				complete: function (xhr) {
					var response = xhr.responseJSON;
					//console.log(response);
					if (response.message.length	 > 0) {												
						var options = document.querySelectorAll('#center option');
						options.forEach(o => o.remove());

						var option = document.createElement("option");
						option.text = "Selecione um centro";
						option.value = "";
						centers.add(option);
						
						if(response.status == "200") {
							for (var i = 0; i < response.message.length; i++) {
								var NOME = response.message[i].nome;
								if(NOME != "-") {
									var CODIGO = response.message[i].cod;							
									option = document.createElement("option");
									option.text = NOME;
									option.value = CODIGO;
									
									if(selected != null && selected == CODIGO)
										option.selected = true;
									
									centers.add(option);
								}
							}
						}
						centers.disabled = false;
					}
				}
			});
		}
	</script>
	
	<style>
		th, td { text-align:center; }
		.footer { background-color: #f5f5f5; }
	</style>
  </head>
  <body class="d-flex flex-column h-100">
	<main role="main" class="flex-shrink-0">
	  <div class="container" style="max-width: 99%;">		
		<h2 class="mt-5">Calendário</h2>		
		<form action="redcap_calendar.php" onsubmit="document.getElementById('btnSubmit').disabled=true;" method="POST">
			<input type="hidden" name="pid" value="<?=$pid?>"/>
			<input type="hidden" name="redcap_url" value="<?=$server?>"/>
			<div class="form-group row mt-3">                
				<div class="col-md-3">
					<label for="center" class="sr-only">Centro</label>
					<select class="form-control" id="center" name="center" disabled="disabled" required>
						<option value="">Carregando centros...</option>
					</select>					
				</div>
				<div class="col-md-1">
					<button type="submit" id="btnSubmit" class="btn btn-primary btn-block">Enviar</button>
				</div>
			</div>
						
			<script type="text/javascript">carregaCentros(<?=$pid?>,'<?=$center?>');</script>								
		</form>
		
<?php if(isset($visits)) { ?>
		<div class="row mt-4 mb-4">
			<div class="col-md-12" style="overflow-x: scroll;">
				<table id="visitsTable" class="table table-hover table-bordered">
					<thead>
						<tr>
							<th>Participante</th>
							<th>Data Referência</th>
						<?php
							$contatos = array();
							foreach($visits as $w) {
								$contatos[$w["reference_done_field_event"]] = json_decode(exportRecordsFieldsRedCap(null,$w["reference_done_field_event"],null,null,array("record_id",$w["reference_done_field"]),$api["api_token"],$api["api_url"]),true);
								echo "<th>".$w["window_name"]."</th>";
							}
						?>
						</tr>
					</thead>
					<tbody>
					<?php 
						$today = strtotime(date("d-m-Y"));	
						foreach($records as $r) {
							if($r["redcap_event_name"] == $visits[0]["reference_date_field_event"]) {
								$aux = explode("-",substr($r[$refDateField],0,10));
								$refDate = $aux[2]."-".$aux[1]."-".$aux[0];																			
																					
								echo "<tr>";
									echo "<td>".$r["record_id"]."</td>";
									echo "<td>".$refDate."</td>";								
									foreach($visits as $w) {
										$visitDate = date("d-m-Y", strtotime($r[$refDateField]." + ".$w["days"]." days"));
										$visitDateBefore = strtotime(date("d-m-Y", strtotime($visitDate." - ".$w["days_before"]." days")));
										$visitDateAfter = strtotime(date("d-m-Y", strtotime($visitDate." + ".$w["days_after"]." days")));
										
										$tdColor = "none";
										if(($today > $visitDateAfter) || ($today >= $visitDateBefore && $today <= $visitDateAfter)) {																				
											$visitDone = false;
											foreach($contatos[$w["reference_done_field_event"]] as $c) {											
												if($c["record_id"] == $r["record_id"] && $c["redcap_event_name"] == $w["reference_done_field_event"] && $c[$w["reference_done_field"]] != '') {
													if($c[$w["reference_done_field"]] == $w["reference_done_field_value"]
														|| ($w["reference_done_field_value"] == "NOT_NULL" && isset($c[$w["reference_done_field"]]) 
															&& !empty($c[$w["reference_done_field"]]) && $c[$w["reference_done_field"]] != "")) {
														$visitDone = true;
														break;
													}
												}
											}
																																						
											if($visitDone) {
												$tdColor = "lightgreen";
											} else {
												if($today > $visitDateAfter)
													$tdColor = "lightcoral";
												else if($today >= $visitDateBefore && $today <= $visitDateAfter)
													$tdColor = "lightyellow";
											}
										}
										echo "<td style='background:$tdColor;'>".$visitDate."</td>";
									}															
								echo "</tr>";
							}
						} 
					?>
					</tbody>
				</table>
			</div>
		</div>
<?php } ?>		
	  </div>
	</main>

	<footer class="footer mt-auto py-3">
	  <div class="container text-center">
		<span class="text-muted"><a href="https://redbox.technology" target="_blank"><img src="assets/images/redbox_logoh.png" width="200"/></a></span>
	  </div>
	</footer>
	
  </body>
</html>	
