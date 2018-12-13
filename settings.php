<?php
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
//PHP Verison is Current PHP version: 5.5.36
define("PHP_SELF", htmlspecialchars($_SERVER["PHP_SELF"]));
session_start();
// Set the database access information as constants:
define('DB_HOST', '');
define('DB_USER', '');
define('DB_PASSWORD', '');
define('DB_NAME', '');

$databaseSQL = mysqli_connect (DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
if (!$databaseSQL) { //Triggered if databaseSQL is null and shows error
	trigger_error('Could not connect to MySQL: '.mysqli_connect_error());
}



$settingsResult = mysqli_query($databaseSQL, "SELECT * FROM CCAppSettings;"); //Get Store Inventory results class from database
$settingsArray = array();
while ($row = mysqli_fetch_assoc($settingsResult)) {
	$settingsArray[$row['Name']] = unserialize(stripslashes($row['Data'])); //Must strip slashes from mysqli_real_escape_string when added
	//Creates an array with an primary key as the index
}

if ($settingsArray['VisibleErrors']){
	//Optional error printing code
	error_reporting(-1);
	ini_set('display_errors', 'On');
}

if ($settingsArray['MailerSettings']['Password'] != '' and $settingsArray['SendConfEmail']) {
	$mail = new PHPMailer(true);
	try {
		//Server settings
		$mail->SMTPDebug = $settingsArray['MailerSettings']['DebugLevel'];// 2 enables verbose debug output
		$mail->isSMTP();
		$mail->Host = $settingsArray['MailerSettings']['Host'];
		$mail->SMTPAuth = true;
		$mail->Username = $settingsArray['MailerSettings']['Username'];
		$mail->Password = $settingsArray['MailerSettings']['Password']; //Pasword cleared for my own security
		$mail->SMTPSecure = 'tls';
		$mail->Port = 587;
		$mail->setFrom($settingsArray['MailerSettings']['Username'], $settingsArray['MailerSettings']['ReturnName']);
	} catch (Exception $e) {
		echo '<p>Message could not be sent. Mailer Error: '.$mail->ErrorInfo.'</p>';
		exit();
	}
}

function checkAvailability ($start_time, $inputed_counselor){
	global $databaseSQL;
	$check_appt = mysqli_prepare($databaseSQL, "SELECT * FROM CCApp WHERE UnixTimestamp=? AND Counselor=?;");
	mysqli_stmt_bind_param($check_appt, 'ss', $start_time, $inputed_counselor);
	mysqli_stmt_execute($check_appt); //System of prepared execution prevents SQL Injection.
	$result = mysqli_stmt_get_result($check_appt);
	$returned_row_count = mysqli_num_rows($result);
	if ($result === false) {
		echo "An error occured. Please contact help.";
		exit();
	}
	$isFree = true;
	if ($returned_row_count !== 0) {
		$isFree = false;
	}
	mysqli_stmt_free_result($check_appt);
	mysqli_stmt_close($check_appt);

	return $isFree;
}
function checkBlock ($start_time, $inputed_counselor){
	global $databaseSQL;
	$check_appt = mysqli_prepare($databaseSQL, "SELECT * FROM CCAppBusy WHERE UnixTimestamp=? AND Counselor=?;");
	mysqli_stmt_bind_param($check_appt, 'ss', $start_time, $inputed_counselor);
	mysqli_stmt_execute($check_appt); //System of prepared execution prevents SQL Injection.
	$result = mysqli_stmt_get_result($check_appt);
	$returned_row_count = mysqli_num_rows($result);
	if ($result === false) {
		echo "An error occured. Please contact help.";
		exit();
	}
	$isFree = true;
	if ($returned_row_count !== 0) {
		$isFree = false;
	}
	mysqli_stmt_free_result($check_appt);
	mysqli_stmt_close($check_appt);
	return $isFree;
}