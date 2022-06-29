<?php

include("../bd_connection.php");
include("../utils.php");

//var_dump($_FILES['file']);

$projectID = $_POST["project"];
$fileName = $_FILES['file']['name'];
$fileType = $_FILES['file']['type'];
$fileSize = $_FILES['file']['size'];

$uploadDir = '/var/www/html/tb/kobo_redcap/kobo_files_upload/uploads/';
$dest = $uploadDir . basename($_FILES['file']['name']);

//echo $dest."<br/>";

if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
    $token = randomString(32);
	$sql = "INSERT INTO form_uploads (project_id,token,file_name,file_type,file_size) VALUES ($projectID,'$token','$fileName','$fileType',$fileSize);";
	$result = $conn->query($sql);
	
	if($result)
		header("Location: index.php?t=$token");
	else
		header("Location: index.php?t=error");
} else {
    echo "Erro!<br/>";
	print_r($_FILES["file"]["error"]);
}

?>