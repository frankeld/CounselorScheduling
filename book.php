<?php
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require_once 'settings.php';
require_once 'crypto_random/random.php'; //Workaround since PHP5 doesn't have good random_int
echo '<!DOCTYPE HTML>
<html>
<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>Booking - College Counseling Booking</title>
	<meta name="author" content="David Frankel">
	<meta name="description" content="This College Counseling Booking site allows Sidwell students to create appointments with their college counselors.">
</head>
<body>
	<div class="header">
		<img src="logo.png" class="logo" alt="Logo">
		<h1>Book Your Appointment!</h1>
	</div>';
$name_err = $time_err = $email_err = $other_err ="";
echo '<div class="info">';
if (isset($_POST['book'])) {
	if (fields_exist()) {
		$firstName = clean_input($_POST['first']);
		$lastName = clean_input($_POST['last']);
		$email = clean_input($_POST['email']);
		$time = clean_input($_POST['time']);
		$counselor = clean_input($_POST['counselor']);

		//Start of verification process
		$valid = true;
		if (!preg_match("/^[a-zA-Z ]*$/", $firstName) or !preg_match("/^[a-zA-Z ]*$/", $lastName)) {
			$name_err .= "Only letters and white space allowed.";
			$valid = false;
		}
		$email_domain = explode('@', $email);
		if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
			$email_err .= "Invalid email format.";
			$valid = false;
		} elseif (strtolower(array_pop($email_domain)) != "sidwell.edu")  {
			$email_err .= "You must use your school email account";
			$valid = false;
		}

		if ($time < time() or $time > strtotime('today '.($settingsArray['FarAhead']*$settingsArray['DaysAtATime']).' weekdays')) { //Should probably recheck calendar for availability here
			$time_err .= "Something went wrong with the time you submitted. It is no longer available. Please try again with a different time. If you keep encountering this error, please submit a bug report.";
			$valid = false;
		} elseif (!(checkAvailability($time, $counselor) and checkBlock($time, $counselor))) {
			$time_err .= "That slot has already been reserved.";
			$valid = false;
		}

		if (!in_array($counselor, $settingsArray['Counselors'])) { //Makes sure counselor exists
			$other_err .= "That is not a valid college counselor. Something went wrong. Try again or contact the help desk.";
			$valid = false;
		}

		$time_limit_before = strtotime('-1 month', $time);
		$time_limit_after = strtotime('+1 month', $time);
		$result = mysqli_query($databaseSQL, "SELECT * FROM CCApp WHERE Email='{$email}';");
		while ($row = mysqli_fetch_assoc($result)) {
			if (($row['UnixTimestamp'] > $time_limit_before and $row['UnixTimestamp'] < $time) or ($row['UnixTimestamp'] < $time_limit_after and $row['UnixTimestamp'] > $time)) { //Check that no appointments a month ahead of behind
				$other_err .= "You cannot schedule more than one appointment a month. Please contact help if you think this message is in error. You can contact the admin to schedule manually around this restriction.";
				$valid = false;
				break;
			}
		}
		mysqli_free_result($result);


		if ($valid) { //errors are modified here. passed by reference. also checks to make sure appointment is still avaliable
			$appt_id = generate_id();

			$starting_timezone = date_default_timezone_get();
			date_default_timezone_set('UTC'); //This is necessary because all time in iCS must be sent in UTC
			$event = "BEGIN:VCALENDAR\nVERSION:2.0\nMETHOD:REQUEST\nBEGIN:VEVENT\nDTSTART:".date("Ymd\THis\Z",$time)."\nDTEND:".date("Ymd\THis\Z",$time+45*60)."\nLOCATION:"."US 130"."\nTRANSP: OPAQUE\nSEQUENCE:0\nUID:\nDTSTAMP:".date("Ymd\THis\Z")."\nSUMMARY:"."Meeting with $firstName $lastName and $counselor"."\nDESCRIPTION:"."$counselor has a meeting with $firstName {$lastName}. Created with CCApp."."\nPRIORITY:1\nCLASS:PUBLIC\nBEGIN:VALARM\nTRIGGER:-PT10080M\nACTION:DISPLAY\nDESCRIPTION:Reminder\nEND:VALARM\nBEGIN:VALARM\nDESCRIPTION:REMINDER\nTRIGGER;RELATED=START:-P1D\nACTION:DISPLAY\nEND:VALARM\nEND:VEVENT\nEND:VCALENDAR\n"; //Creates event
			date_default_timezone_set($starting_timezone); //Reverts to general program functioning timezone
			if ($settingsArray['MailerSettings']['Password'] != '' and $settingsArray['SendConfEmail']){
				try {
					$mail->addAddress($email, $firstName.' '.$lastName);
					$mail->addStringAttachment($event,'ics/'.$appt_id.'.ics');
					$mail->isHTML(true);
					$mail->Subject = 'Your Meeting with '.$counselor;
					$mail->Body    = "<p>Hi {$firstName},</p><p></p>
					<p>Thank you for scheduling your college counseling meeting through CCApp. An iCS with your appointment information has been attached to this email. Your appointment ID is {$appt_id}. To cancel your appointment, go <a href=\"/change.php\" target=\"_blank\">here</a>.
					</p><p></p><p>Thanks,</p><p>CCApp Creator: David Frankel</p>";
					$mail->AltBody = 'If you see this message, contact CCApp help. Your appointment ID is '.$appt_id.'.';
					$mail->send();

					echo '<p>Message has been sent.</p>';
				} catch (Exception $e) {
					echo '<p>Message could not be sent. Mailer Error: '.$mail->ErrorInfo.'</p>';
					exit();
				}
			} else {
				echo "<p>Settings prevented a confirmation email from being sent.</p>";
			}
			//Add to database letter because email should be sent first? How to make sure that if one fails the other doesn't still exist?
			//Add to database
			$create_appt =  mysqli_prepare($databaseSQL, "INSERT INTO CCApp (ApptID, Counselor, FirstName, LastName, UnixTimestamp, Email) VALUES (?,?,?,?,?,?);");
			mysqli_stmt_bind_param($create_appt, 'ssssis', $appt_id, $counselor, $firstName, $lastName, $time, $email);
			if (!mysqli_stmt_execute($create_appt)) {
				echo '<p>Please try again. An error occured. Your confirmation is invalid.</p>';
				exit();
			}
			mysqli_stmt_close($create_appt);

			//send confirmation email and Jackie email
			//CHECK TO MAKE SURE ONLY ONE APPT A WEEK
			echo "<h1>Thank your for scheduling an appointment. PLEASE READ THE INFORMATION BELOW.</h1>";
			echo '<p>Your appointment ID is <b>'.$appt_id.'</b>. You will need the ID to cancel or change to appointment. Do not share this ID, as anyone you share it with will be able to access your appointment. You should be expecting a confirmation email shortly.</p>';
			echo "</div>";
			exit();
		} else {
			echo "<p>Something went wrong with the information you entered. Data is invalid. Please try again.</p>";
			//Doesn't exit so form is still generated
		}
	} else {
		echo "<p>Something went wrong with the information you entered. Please try again.</p>";
		//Doesn't exit so form is still generated
	}
}
if (empty($_GET['time'])) {
	echo "<p>You didn't select a time from the calendar.</p>";
	echo '<a href="index.php?counselor='.clean_input($_GET['counselor']).'">Go to the calendar.</a>';
	exit();
} elseif ($_GET['time'] < time()) { //check database calendar as well?
	echo "<p>This time is no longer available.</p>";
	echo '<a href="index.php?counselor='.clean_input($_GET['counselor']).'">Go back to the calendar.</a>';
	exit();
} elseif (empty($_GET['counselor'])) {
	echo "<p>Something went wrong. You did not select a college counselor. If this error continues, contact the help desk.</p>";
	echo '<a href="index.php?counselor='.clean_input($_GET['counselor']).'">Go back to the calendar.</a>';
	exit();
}

echo '<form action="book.php?time='.htmlspecialchars($_GET['time']).'&counselor='.htmlspecialchars($_GET['counselor']).'" method="post"">
		<span class="error">'.$other_err.'</span><br>
		First Name: <input type="text" name="first" required><br>
		Last Name: <input type="text" name="last" required><br>
		<span class="error">'.$name_err.'</span>
		Email: <input type="email" name="email" required><br>
		<span class="error">'.$email_err.'</span>
		<input type="hidden" name="time" value="'.htmlspecialchars($_GET['time']).'" required>
		<input type="hidden" name="counselor" value="'.htmlspecialchars($_GET['counselor']).'" required>
		<span class="error">'.$time_err.'</span>
		<input type="submit" value="Book" name="book">
		</form>'; //htmlspecialchars should not be needed.
echo '<p>Once you click submit, please wait. It may take a few seconds to submit. Do not refresh the page or click submit again, as you could be locked out.</p>';
echo '</div>';
function fields_exist() {
	if (empty($_POST['time']) or empty($_POST['last']) or empty($_POST['first']) or empty($_POST['email'])) {
		return false;
	}
	return true;
}

function clean_input($input){
	$input = trim($input);
	$input = stripslashes($input);
	$input = htmlspecialchars($input);
	return $input;
}

function random_str($length, $keyspace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ') {
	$str = '';
	$max = mb_strlen($keyspace, '8bit') - 1;
	for ($i = 0; $i < $length; ++$i) {
		$str .= $keyspace[random_int(0, $max)];
	}
	return $str;
	//Thanks: https://stackoverflow.com/questions/4356289/php-random-string-generator/31107425#31107425
}

function verify_fields($first_name, $last_name, $email, $time, $counselor, &$name_err, &$email_err, &$time_err, &$other_err) {
	$valid = true;
	global $settingsArray;
	if (!preg_match("/^[a-zA-Z ]*$/", $first_name) or !preg_match("/^[a-zA-Z ]*$/", $last_name)) {
		$name_err .= "Only letters and white space allowed.";
		$valid = false;
	}
	$email_domain = explode('@', $email);
	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$email_err .= "Invalid email format.";
		$valid = false;
	} elseif (strtolower(array_pop($email_domain)) != "sidwell.edu")  {
		$email_err .= "You must use your school email account";
		$valid = false;
	}

	if ($time < time() or $time > strtotime('today '.($settingsArray['FarAhead']*$settingsArray['DaysAtATime']).' weekdays')) { //Should probably recheck calendar for availability here
		$time_err .= "Something went wrong with the time you submitted. It is no longer available. Please try again with a different time. If you keep encountering this error, please submit a bug report.";
		$valid = false;
	} elseif (!(checkAvailability($time, $counselor) and checkBlock($time, $counselor))) {
		$time_err .= "That slot has already been reserved.";
		$valid = false;
	}

	if (!in_array($counselor, $settingsArray['Counselors'])) { //Makes sure counselor exists
		$other_err .= "That is not a valid college counselor. Something went wrong. Try again or contact the help desk.";
		$valid = false;
	}



	// if () {
	// 	$other_err .= "You cannot schedule more than one appointment a month. Please contact help if you think this message is in error. You can contact the admin to schedule manually around this restriction.";
	// 	$valid = false;
	// }

	return $valid;
}

function generate_id(){
	global $settingsArray;
	$apptID = random_str($settingsArray['ApptIDLength']);
	$i = 0;
	//Checks to make sure ID is unique, but is statistically very likely to be unique
	global $databaseSQL;
	$result = mysqli_query($databaseSQL, "SELECT * FROM CCApp WHERE ApptID='{$apptID}';");
	if ($result === FALSE) {
		echo "Error 19999. Please report immediately.";
	}
	while (mysqli_num_rows($result) != 0) {
		$apptID = random_str($settingsArray['ApptIDLength']);
		echo "Starting 9999.";
		$i++;
		if ($i > 30) {
			echo "Error 9999. Please report immediately.";
			exit();
		}
	}

	return $apptID;
}
mysqli_close($databaseSQL);