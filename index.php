<?php
require_once 'settings.php';
echo '<head>
	<meta charset="utf-8">
	<link rel="stylesheet" type="text/css" href="style.css">
	<title>Calendar - College Counseling Booking</title>
	<meta name="author" content="David Frankel">
	<meta name="description" content="This College Counseling Booking site allows Sidwell students to create appointments with their college counselors."></head>';
echo '<body>
	<div class="header">
		<img src="logo.png" class="logo" alt="Logo">
		<h1>Book Your Appointment</h1>
	</div>';
echo '<p>First, select your college counselor using the dropdown on the right. Then, select an available slot. Use the next and previous button to move between dates. To edit your appointment, click <a href="change.php">here</a>.</p><br/>';

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

if ($current_timestamp > strtotime('today '.($settingsArray['FarAhead']*$settingsArray['DaysAtATime']).' weekdays') or $current_timestamp < strtotime('today')) { //Prevents calendar generation if the starting_time is in the past or too far in the future
	echo '<p>You cannot schedule more than '.$settingsArray['FarAhead']*$settingsArray['DaysAtATime'].' weekdays ahead.</p>';
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
	echo '</div>';
	foreach ($settingsArray['Timeslots'] as $slot) { //slots stored as addition to epoch time
		$startingtime = strtotime($slot['start'], $iteration_time);
		//Checking database to see if appointment slot is already taken
		$slotIsFree = false;
		if (checkAvailability($startingtime, $current_counselor) and checkBlock($startingtime, $current_counselor)){
			$slotIsFree = true;
		}
		if ($startingtime < time()) {
			$slotIsFree = false;
		}
		//TODO: Check Outlook calendar for availability and change class if not free
		$redirect_path = 'book.php?time='.$startingtime.'&counselor='.$current_counselor;
		echo '<div class="gridTime '.($slotIsFree?'slotFree':'slotBusy').'" '.($slotIsFree?('onclick="location=\''.$redirect_path.'\'"'):'').'>';
		echo '<a id="button-'.$startingtime.'" '.($slotIsFree?('href="'.$redirect_path.'"'):"").'>'.date('h:i', $startingtime).'</a>';
		echo '</div>';
	}
	echo '</div>';
}

echo '</div>';

$next_jump_date = strtotime($settingsArray['DaysAtATime'].' weekdays', $current_timestamp); //Equivalent to $iteration_time
if ($next_jump_date < strtotime('today '.(($settingsArray['FarAhead'])*$settingsArray['DaysAtATime']).' weekdays')) {
	echo '<a class="pageButton" href="'.strtok(PHP_SELF, '?').'?jumpDate='.$next_jump_date.'&counselor='.$current_counselor.'"><div class="arrow nextArrow"></div></a>';
} else {
	echo '<a class="pageButton"><div class="nextArrow arrow goneArrow"></div></a>';
}
mysqli_close($databaseSQL);