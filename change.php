<?php
require_once 'settings.php';
echo '<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>Edit - College Counseling Booking</title>
	<meta name="author" content="David Frankel">
	<meta name="description" content="This College Counseling Booking site allows Sidwell students to create appointments with their college counselors.">
</head>
<body>
	<div class="header">
		<img src="logo.png" class="logo" alt="Logo">
		<h1>Edit Your Appointment</h1>
	</div>';
$email_err = $id_err = "";
echo '<div class="info">';
if (isset($_POST['cancel'])) {
	//Do these checks need to be more thorough
	$apptID = clean_input($_POST['apptID']);
	$counselor = clean_input($_POST['counselor']);
	$time = clean_input($_POST['time']);

	//DELETE FROM OUTLOOK CALENDAR

	$delete_appt = mysqli_prepare($databaseSQL, 'DELETE FROM CCApp WHERE ApptID=? AND Counselor=? AND UnixTimestamp=?;');
	mysqli_stmt_bind_param($delete_appt, 'sss', $apptID, $counselor, $time);

	if (mysqli_stmt_execute($delete_appt) and mysqli_stmt_affected_rows($delete_appt) == 1){ //Executes and checks for delete success and writes out the result to the user
		echo "<p>Your appointment was deleted. <a href=\"index.php\">Go to home.</a></p>";
	} else {
		echo "<p>Something went wrong. Please contact help immediately.</p>";
	}

	mysqli_stmt_close($delete_appt);
	exit();
}

if (isset($_POST['edit'])) {
	$apptID = clean_input($_POST['apptID']);
	$email = clean_input($_POST['email']);
	//ADD VERIFY FIELDS THING
	$create_appt = mysqli_prepare($databaseSQL, "SELECT * FROM CCApp WHERE ApptID=? AND Email=?;");
	mysqli_stmt_bind_param($create_appt, 'ss', $apptID, $email);
	mysqli_stmt_execute($create_appt); //System of prepared execution prevents SQL Injection
	$result = mysqli_stmt_get_result($create_appt);
	if ($row = mysqli_fetch_array($result)) {
		echo "<p>{$row["FirstName"]}, you have a 45 minute appointment scheduled for ".date("F j, Y \a\\t g:i a",  $row["UnixTimestamp"])." with {$row["Counselor"]}.</p>";			
		if ($row['UnixTimestamp'] > time()){
			echo "<p>Would you like to cancel your appointment?</p>";
			echo '<form action="'.PHP_SELF.'" method="post" onSubmit="return confirm(\'Are you sure you wish to delete your appointment?\');">
			<input type="hidden" name="apptID" value="'.$row["ApptID"].'">
			<input type="hidden" name="counselor" value="'.$row["Counselor"].'">
			<input type="hidden" name="time" value="'.$row["UnixTimestamp"].'">
			<input type="submit" value="Cancel my appointment" name="cancel">
			</form>'; //htmlspecialchars should not be needed.
		}
		else {
			echo '<p>The appointment time had already passed. You cannot cancel it.</p>';
		}
		echo "<p>In order to reschedule, just cancel this appointment and book a new appointment <a href=\"index.php?counselor={$row["Counselor"]}\">here</a>.</p>";


		//Delete from calendar
		//Delete from MySQL?
		//Email Jackie to let her know
	} else {
		echo '<p class="error">Try again. No results found.</p>';
	}

	//Closes and frees various things
	mysqli_stmt_free_result($create_appt);
	mysqli_stmt_close($create_appt);
	exit();
}

echo '<form action="'.PHP_SELF.'" method="post">
		Email: <input type="email" name="email" required><br>
		<span class="error">'.$email_err.'</span>
		Appointment ID: <input name="apptID" required>
		<span class="error">'.$id_err.'</span>
		<input type="submit" value="Edit" name="edit">
		</form>'; //htmlspecialchars should not be needed.
echo '</div>';
echo '<button onclick="window.location.href=\'index.php\'">Go home</button>';
function clean_input($input){
	$input = trim($input);
	$input = stripslashes($input);
	$input = htmlspecialchars($input);
	return $input;
}
mysqli_close($databaseSQL);
echo '</div>';