<?php
require_once 'settings.php';
mysqli_close($databaseSQL);
if (isset($_SESSION['verified'])) { //Person is already logged in
	header('Location: admin.php');
	exit('You are already logged in. Redirecting to member page...'); //Automatically closes MySQL connection and sends to logged in page
}
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
		<h1>Admin Panel Login</h1>
	</div>';
echo '<div class="info">';

if (isset($_POST['login'])) {
	if (password_verify($_POST['password'], $settingsArray['AdminPassword'])) {
		echo "<p>Logged in.</p>";
		$_SESSION['verified'] = true;
		header('Location: admin.php');
		exit('Welcome. Redirecting to member page...'); //Automatically closes MySQL connection and sends to logged in page
	} else {
		echo '<p>Password incorrect.</p>';
	}
} else { // Form generation if submit has not been pressed
	echo '<form action="admin_login.php" method="post">
		Password: <input type="password" name="password" required><br>
		<input type="submit" value="Login" name="login">
		</form>';
}
echo '</div>';
