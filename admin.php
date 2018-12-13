<?php
require_once 'settings.php';
echo '<!DOCTYPE HTML>
<html>
<head>
<meta charset="utf-8">
<link rel="stylesheet" type="text/css" href="style.css">
<title>Admin Panel - College Counseling Booking</title>
<meta name="author" content="David Frankel">
<meta name="description" content="This College Counseling Booking site allows Sidwell students to create appointments with their college counselors.">
</head>
<body>
<div class="header">
<img src="logo.png" class="logo" alt="Logo">
<h1>Admin Panel</h1>
</div>';
echo '<div class="info">';
if (isset($_SESSION['verified'])) {
	if (isset($_POST['addCounselor'])) {
		$settingsArray['Counselors'][] = $_POST['counselor'];
		$updatedData = mysqli_real_escape_string($databaseSQL, serialize($settingsArray['Counselors']));
		$updateDataCommand = "UPDATE CCAppSettings SET Data='{$updatedData}' WHERE Name='Counselors';";
		$writeToTable = mysqli_query($databaseSQL, $updateDataCommand);
		echo '<p class="taskDone">Counselor added.</p>';
	} elseif (isset($_POST['removeCounselor'])) {
		unset($settingsArray['Counselors'][array_search($_POST['counselor'], $settingsArray['Counselors'])]);
		$settingsArray['Counselors'] = array_values($settingsArray['Counselors']);
		$updatedData = mysqli_real_escape_string($databaseSQL, serialize($settingsArray['Counselors']));
		$updateDataCommand = "UPDATE CCAppSettings SET Data='{$updatedData}' WHERE Name='Counselors';";
		$writeToTable = mysqli_query($databaseSQL, $updateDataCommand);
		echo '<p class="taskDone">Counselor removed.</p>';
	} elseif (isset($_POST['cancelAppointment'])){
		$ApptID = mysqli_real_escape_string($databaseSQL, $_POST['apptID']);
		$deleteCheckResult = mysqli_query($databaseSQL, "SELECT * FROM CCApp WHERE ApptID='$ApptID';");
		if (mysqli_num_rows($deleteCheckResult) == 1){
			$row = mysqli_fetch_assoc($deleteCheckResult);
			$deleteCommand = mysqli_query($databaseSQL, "DELETE FROM CCApp WHERE ApptID='$ApptID';");
			if ($settingsArray['MailerSettings']['Password'] != '' and $settingsArray['SendConfEmail']){
				try {
					$mail->addAddress($row['Email'], $row['FirstName'].' '.$row['LastName']);
					$mail->isHTML(true);
					$mail->Subject = 'CANCELLED: Your Meeting with '.$row['Counselor'];
					$mail->Body    = '<p>Hi '.$row['FirstName'].',</p><p></p>
					<p>Thank you for scheduling your college counseling meeting through CCApp. Your appointment with ID '.$row['ApptID'].' has been canceled by an admin.
					</p><p></p><p>Thanks,</p><p>CCApp Creator: David Frankel</p>';
					$mail->AltBody = 'If you see this message, contact CCApp help. Your appointment ID is '.$row['ApptID'].'.';
					$mail->send();

					echo '<p>Message has been sent.</p>';
				} catch (Exception $e) {
					echo '<p>Message could not be sent. Mailer Error: '.$mail->ErrorInfo.'</p>';
				}
			} else {
				echo "<p>Settings prevented a confirmation email from being sent.</p>"; 
			}
		} else {
			echo "<p>Couldn't find the appointment.</p>"; 
		}
		echo '<p class="taskDone">The appointment had been canceled.</p>';
	} elseif (isset($_POST['toggleEmails'])){
		$settingsArray['SendConfEmail'] = !$settingsArray['SendConfEmail'];
		$updatedData = mysqli_real_escape_string($databaseSQL, serialize($settingsArray['SendConfEmail']));
		$updateDataCommand = "UPDATE CCAppSettings SET Data='{$updatedData}' WHERE Name='SendConfEmail';";
		$writeToTable = mysqli_query($databaseSQL, $updateDataCommand);
		//header('Location: '.PHP_SELF); //Prevents resubmitting POST
		echo '<p class="taskDone">Toggled.</p>';
	}

	echo "<p>Welcome to the admin page. If you need to logout, there is a button at the bottom of the page. Be very careful with these settings, as there is no undo.</p>";
	echo '<br/>';
	echo '<br/>';
	echo "<p>Enter an appointment ID to cancel the appointment. We will automatically send an email to the person who booked the appointment.</p>";
	echo '<form action="admin.php" method="post">
	Appointment ID: <input type="text" name="apptID" required><br>
	<input type="submit" value="Cancel Appointment" name="cancelAppointment">
	</form>';
	echo '<br/>';
	echo '<br/>';

	echo "<p>Enter a college counselor to remove them as an option. It will not delete their appointments from the database.</p>";
	echo '<form action="admin.php" method="post">
	Name: <input type="text" name="counselor" required><br>
	<input type="submit" value="Remove Counselor" name="removeCounselor">
	</form>';
	echo '<br/>';
	echo '<br/>';	
	echo "<p>Hired a new college counselor? Add them here.</p>";
	echo '<form action="admin.php" method="post">
	Name: <input type="text" name="counselor" required><br>
	<input type="submit" value="Add Counselor" name="addCounselor">
	</form>';
	echo '<br/>';
	echo '<br/>';
	echo '<p>Use this toggle to turn confirmation emails on or off. Reminder emails are not yet available.</p>';
	echo '<form action="admin.php" method="post">
	<input type="submit" value="'.($settingsArray['SendConfEmail'] ? 'Turn off confirmation emails' : 'Turn on confirmation emails').'" name="toggleEmails">
	</form>';
	echo '<br/>';
	echo '<br/>';
	//Didn't have time to do this.

// 		echo "<p>Use this to delete a college counselor's previous appointments or all appointments. Will send email to future appointments.</p>";
// 		echo '<form action="admin.php" method="post">
// 		Name: <input type="text" name="counselor" required><br>
// 		<input type="submit" value="All Previous Appointments" name="previousAppointments">
// 		<input type="submit" value="All Appointments" name="allAppointments">
// 		</form>';

	echo "<p>Use this to block off calendar times. Select a range of times. Can begin and end at the same spot. Will cancel appointments made in these time-slots and send an email.</p>";
	echo '<button onclick="window.location.href=\'admin_index.php\'">Block off times.</button>';
	echo '<br/>';
	echo '<br/>';
	echo '<p><button onclick="window.location.href=\'admin_logout.php\'">Logout.</button></p>';
	echo '</div>';

} else {
	header('Location: admin_login.php');
	exit("You are not logged in. Redirecting...");
}