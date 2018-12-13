<?php
require_once 'settings.php';
mysqli_close($databaseSQL);
unset($_SESSION['verified']);
header('Location: admin.php'); //Redirects users
exit("Logged out and redirecting to login page."); //End script with message to truly end page