<?php
require_once 'settings.php';
require_once 'PHPMailer/PHPMailer.php';
require_once 'PHPMailer/SMTP.php';
require_once 'PHPMailer/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
if (isset($_SESSION['verified'])) {
	echo '<!DOCTYPE HTML>
	<html>
	<head>
		<meta charset="utf-8">
		<link rel="stylesheet" type="text/css" href="style.css">
		<title>Admin Panel Calendar Blockoffs - College Counseling Booking</title>
		<meta name="author" content="David Frankel">
		<meta name="description" content="This College Counseling Booking site allows Sidwell students to create appointments with their college counselors.">
	</head>
	<body>
		<div class="header">
			<img src="logo.png" class="logo" alt="Logo">
			<h1>Block Off Times</h1>
		</div>
		<style>
		input[type=submit] {
			font-family: serif;
			cursor: pointer;
		}
		input[type=submit].slotBusy {
			background-color: lightgrey;
			color: #971b2f;
		}
		input[type=submit]:hover.slotBusy {
			background-color: lightgrey;
			color: #971b2f;
		}
		input[type=submit].slotBlocked {
			color: grey;
			background-color: lightgrey;
			cursor: default;
		}
		input[type=submit]:hover.slotBlocked {
			color: grey;
			background-color: lightgrey;

		}
		</style>';
	
	if (isset($_POST['blockDay'])) {
		//ADD BETTER VERIFICATION CHECKS FOR ALL THIS
		if ($_POST['daySelected'] > time() and in_array($_POST['counselor'], $settingsArray['Counselors'])) {
			$counselor = mysqli_real_escape_string($databaseSQL, $_POST['counselor']);
			$day = mysqli_real_escape_string($databaseSQL, $_POST['daySelected']);
			$createBlock=  mysqli_prepare($databaseSQL, 'INSERT INTO CCAppBusy (Counselor, UnixTimestamp) VALUES (?,?);');
			mysqli_stmt_bind_param($createBlock, 'ss', $counselor, $startingtime);			
			foreach ($settingsArray['Timeslots'] as $slot) {
				$startingtime = strtotime($slot['start'], $day);
				if(!checkAvailability($startingtime, $counselor)) {
					$deleteCheckResult = mysqli_query($databaseSQL, "SELECT * FROM CCApp WHERE Counselor='$counselor' AND UnixTimestamp='$startingtime';");
					$row = mysqli_fetch_assoc($deleteCheckResult);
					if (mysqli_num_rows($deleteCheckResult) == 1){
						$delete_appt = mysqli_prepare($databaseSQL, 'DELETE FROM CCApp WHERE Counselor=? AND UnixTimestamp=?;');
						mysqli_stmt_bind_param($delete_appt, 'ss', $counselor, $startingtime);
						mysqli_stmt_execute($delete_appt);
						mysqli_stmt_close($delete_appt);
						if ($settingsArray['MailerSettings']['Password'] != '' and $settingsArray['SendConfEmail']){
							try {
								$mail->addAddress($row['Email'], $row['FirstName'].' '.$row['LastName']);
								$mail->isHTML(true);
								$mail->Subject = 'CANCELLED: Your Meeting with '.$row['Counselor'];
								$mail->Body    = "<p>Hi {$row['FirstName']},</p><p></p>
								<p>Thank you for scheduling your college counseling meeting through CCApp. Your appointment with ID ".$row['ApptID']." has been cancelled by an admin.
								</p><p></p><p>Thanks,</p><p>CCApp Creator: David Frankel</p>";
								$mail->AltBody = 'If you see this message, contact CCApp help. Your appointment ID is '.$row['ApptID'].'.';
								$mail->send();

								echo '<p>Message has been sent.</p>';
							} catch (Exception $e) {
								echo '<p>Message could not be sent. Mailer Error: '.$mail->ErrorInfo.'</p>';
							}
						} else {
							echo "<p>Settings prevented a confirmation email from being sent.</p>"; 
						}
					}
				}
				$checkBlockExisting = mysqli_query($databaseSQL, "SELECT * FROM CCAppBusy WHERE Counselor='$counselor' AND UnixTimestamp='$startingtime';");
				$row = mysqli_fetch_assoc($checkBlockExisting); //Only writes if that slot isn't already blocked
				if (mysqli_num_rows($checkBlockExisting) == 0){
					if (!mysqli_stmt_execute($createBlock)) {
						echo '<p>Please try again. An error occured. Something is wrong.</p>';
						exit();
					}
					mysqli_stmt_close($createBlock);
				}

			}
		} else {
			echo "Cannot block off the current day. Select each slot individually if necessary. Or, your college counselor option may have been modified.";
		}
		//Add checks to verify hidden input
		//Add to CCAppBusy DONE
		//NEED TO CHANGE INDEX.PHP AND BOOK.PHP TO ALSO CHECK CCAppBusy DONE
		//Cancel any appointments there already DONE
	}
	if (isset($_POST['blockTime'])) {
		//ADD BETTER VERIFICATION CHECKS FOR ALL THIS
		if ($_POST['timeSelected'] > time() and in_array($_POST['counselor'], $settingsArray['Counselors'])) {
			$counselor = mysqli_real_escape_string($databaseSQL, $_POST['counselor']);
			$startingtime = mysqli_real_escape_string($databaseSQL, $_POST['timeSelected']);
			$create_appt =  mysqli_prepare($databaseSQL, 'INSERT INTO CCAppBusy (Counselor, UnixTimestamp) VALUES (?,?);');
			mysqli_stmt_bind_param($create_appt, 'ss', $counselor, $startingtime);
			if(!checkAvailability($startingtime, $counselor)) {
				$deleteCheckResult = mysqli_query($databaseSQL, "SELECT * FROM CCApp WHERE Counselor='$counselor' AND UnixTimestamp='$startingtime';");
				$row = mysqli_fetch_assoc($deleteCheckResult);
				if (mysqli_num_rows($deleteCheckResult) == 1){
					$delete_appt = mysqli_prepare($databaseSQL, 'DELETE FROM CCApp WHERE Counselor=? AND UnixTimestamp=?;');
					mysqli_stmt_bind_param($delete_appt, 'ss', $counselor, $startingtime);
					mysqli_stmt_execute($delete_appt);
					mysqli_stmt_close($delete_appt);
					if ($settingsArray['MailerSettings']['Password'] != '' and $settingsArray['SendConfEmail']){
						try {
							$mail->addAddress($row['Email'], $row['FirstName'].' '.$row['LastName']);
							$mail->isHTML(true);
							$mail->Subject = 'CANCELLED: Your Meeting with '.$row['Counselor'];
							$mail->Body    = "<p>Hi {$row['FirstName']},</p><p></p>
							<p>Thank you for scheduling your college counseling meeting through CCApp. Your appointment with ID ".$row['ApptID']." has been cancelled by an admin.
							</p><p></p><p>Thanks,</p><p>CCApp Creator: David Frankel</p>";
							$mail->AltBody = 'If you see this message, contact CCApp help. Your appointment ID is '.$row['ApptID'].'.';
							$mail->send();

							echo '<p>Message has been sent.</p>';
						} catch (Exception $e) {
							echo '<p>Message could not be sent. Mailer Error: '.$mail->ErrorInfo.'</p>';
						}
					} else {
						echo "<p>Settings prevented a confirmation email from being sent.</p>"; 
					}
				}
			}
			$checkBlockExisting = mysqli_query($databaseSQL, "SELECT * FROM CCAppBusy WHERE Counselor='$counselor' AND UnixTimestamp='$startingtime';");
			//MAKE THIS BIND PARAM AND STMT
			$row = mysqli_fetch_assoc($checkBlockExisting); //Only writes if that slot isn't already blocked
			if (mysqli_num_rows($checkBlockExisting) == 0){
				if (!mysqli_stmt_execute($create_appt)) {
					echo '<p>Please try again. An error occured. Something is wrong.</p>';
					exit();
				}
			} else {
				//This means that it has already been blocked, and therefore the block should be removed
				$removeBlock = mysqli_prepare($databaseSQL, 'DELETE FROM CCAppBusy WHERE Counselor=? AND UnixTimestamp=?;');
				mysqli_stmt_bind_param($removeBlock, 'ss', $counselor, $startingtime);
				mysqli_stmt_execute($removeBlock);
				mysqli_stmt_close($removeBlock);
			}
			mysqli_stmt_close($create_appt);
		} else {
			echo "Cannot block off the current day. Select each slot individually if necessary. Or, your college counselor option may have been modified.";
		}
	}

	echo '<p>Select an entire day or a single appointment slot. Grey background and maroon text means an appointment has already been made in that time slot. An cancellation email will be sent if you override it. Click on an individual time slot to un-block it.</p>';
	echo '<p><button onclick="window.location.href=\'admin.php\'">Go back</button></p>';
	$current_timestamp = empty($_GET['jumpDate']) ? strtotime('today') : strtotime('today', htmlentities($_GET['jumpDate'])); //If GET exists, returns beginning of day time of jump for. Otherwise, returns current beginning of day time.

	if (date('l', $current_timestamp) == 'Sunday' or date('l', $current_timestamp) == 'Saturday') {
		$current_timestamp = strtotime('Monday');
	}

	$current_counselor = empty($_GET['counselor']) ? $settingsArray['Counselors'][0] : htmlentities($_GET['counselor']); //Defaults to the first college counselor in list if no counselor is selected
	echo '<select onchange="changeCounselor(this)">'; //Creates select options from counselors array in settings
	foreach ($settingsArray['Counselors'] as $counselor) {
		echo '<option '.(($counselor == $current_counselor) ? 'selected' : '').' value="'.$counselor.'">'.$counselor.'</option>'; //Selected currents counselor if found in GET request
	}
	echo '</select>';
	$path = strtok(PHP_SELF, '?').'?jumpDate='.$current_timestamp;
	echo '<script>
	function changeCounselor(element) {
	var value = element.value;
	var path = "'.$path.'&counselor=" + value;
	window.location.href = path;
	}
	</script>'; //Redirects to new GET request when changing college counselor
	
	// No restriction on scheduling needed
	if ($current_timestamp < strtotime('today')) { //Prevents calendar generation if the starting_time is in the past or too far in the future
		echo '<p>This is the past. Nothing you can do now.</p>';
		echo '<a href="'.strtok(PHP_SELF, '?').'?counselor='.$current_counselor.'">Go to today.</a>';
		exit();
	}

	$previous_jump_date = strtotime('-'.$settingsArray['DaysAtATime'].' weekdays', $current_timestamp); //Generates time for previous link by going back the set number of weekdays from the passed time
	if ($previous_jump_date >= strtotime('today')) { //Verifies that $previous_jump_date is valid before displaying back arrow
		echo '<a class="pageButton" href="'.strtok(PHP_SELF, '?').'?jumpDate='.$previous_jump_date.'&counselor='.$current_counselor.'"><div class="arrow prevArrow"></div></a>';
	} else {
		echo '<a class="pageButton"><div class="goneArrow arrow prevArrow"></div></a>';
	}

	echo '<div class="calendarGrid">';
	for ($i=0; $i < $settingsArray['DaysAtATime']; $i++) {
		$iteration_time = strtotime($i.' weekday', $current_timestamp);
		echo '<div class="gridDay grid'.date('D', $iteration_time).'">';
		echo '<div class="gridHeader">';
		echo '<span class="gridHeaderDayName">'.date('D', $iteration_time).'</span>';
		echo '<span class="gridHeaderDate">'.date('m/d/y', $iteration_time).'</span>';
		echo '<form action="admin_index.php?jumpDate='.$current_timestamp.'&counselor='.$current_counselor.'" method="post">
		<input type="hidden" name="daySelected" value="'.$iteration_time.'" required>
		<input type="hidden" name="counselor" value="'.$current_counselor.'" required>
		<input class="calendarDayBlock" type="submit" value="Block Off This Day" name="blockDay">
		</form>';
		echo '</div>';
		foreach ($settingsArray['Timeslots'] as $slot) { //slots stored as addition to epoch time
			$startingtime = strtotime($slot['start'], $iteration_time);
			//Checking database to see if appointment slot is already taken
			$slotIsFree = checkAvailability($startingtime, $current_counselor);
			$slotIsOpen = true;
			if ($startingtime < time() or !checkBlock($startingtime, $current_counselor)) {
				$slotIsOpen = false;
			}
			echo '<form action="admin_index.php?jumpDate='.$current_timestamp.'&counselor='.$current_counselor.'" method="post">
			<input type="hidden" name="timeSelected" value="'.$startingtime.'" required>
			<input type="hidden" name="counselor" value="'.$current_counselor.'" required>
			<input class = "gridTime '.(($slotIsFree and $slotIsOpen)?'slotFree':'slotBusy').' '.($slotIsOpen?'slotOpen':'slotBlocked').'" type="submit" value="'.date('h:i', $startingtime).'" name="blockTime">
			</form>';
			// $redirect_path = 'book.php?time='.$startingtime.'&counselor='.$current_counselor;
			// echo '<div class="gridTime '.(($slotIsFree and $slotIsOpen)?'slotFree':'slotBusy').' '.($slotIsOpen?'slotOpen':'slotBlocked').'" '.($slotIsOpen?('onclick="location=\''.$redirect_path.'\'"'):'').'>';
			// echo '<a id="button-'.$startingtime.'" '.($slotIsOpen?('href="'.$redirect_path.'"'):"").'>'.date('h:i', $startingtime).'</a>';
			// echo '</div>';
		}
		echo '</div>';
	}
	echo '</div>';

	$next_jump_date = strtotime($settingsArray['DaysAtATime'].' weekdays', $current_timestamp); //Equivalent to $iteration_time
	//if ($next_jump_date < strtotime('today '.(($settingsArray['FarAhead'])*$settingsArray['DaysAtATime']).' weekdays')) { //No restriction on advancement
		echo '<a class="pageButton" href="'.strtok(PHP_SELF, '?').'?jumpDate='.$next_jump_date.'&counselor='.$current_counselor.'"><div class="arrow nextArrow"></div></a>';
	//}
	mysqli_close($databaseSQL);
} else {
	header('Location: admin_login.php');
	exit("You are not logged in. Redirecting...");
}
