<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* Exception class. */
require 'PHPMailer/src/Exception.php';

/* The main PHPMailer class. */
require 'PHPMailer/src/PHPMailer.php';

/* SMTP class, needed if you want to use SMTP. */
require 'PHPMailer/src/SMTP.php';

function sendMail($to,$cc,$subject,$message) {
	$mail = new PHPMailer(TRUE);
	$mail->setLanguage('pt');
	$mail->isSMTP();
	$mail->isHTML(true);
	  
	if(strpos($to, ',') !== false) {
		$recipients = explode(',',$to);
		foreach($recipients as $r) {
			$mail->addAddress($r);
		}
	} else {
		$mail->addAddress($to);
	}
	
	$mail->setFrom("youraddress@yourdomain.com","From Name");
	
	$mail->ClearReplyTos();    
	if(isset($cc) && !is_null($cc)) {
		$mail->addCC($cc);
		$mail->addReplyTo($cc,$cc);
	} else {
		$mail->addReplyTo("youraddress@yourdomain.com","From Name");
	}
	
	$mail->Subject = $subject;
	$mail->Body = $message;

	$mail->isSMTP();
	$mail->Host = 'smtp.gmail.com';
	$mail->Port = 465;
	$mail->SMTPAuth = true;
	$mail->SMTPSecure = 'ssl';
		
	$mail->Username = 'youraddress@yourdomain.com';
	$mail->Password = 'yourpassword';
		   
	if($mail->send()) {
		return true;
	}
}
?>