<?php

	require_once('../config.php');
	require_once('../locallib.php');
	require_once('sl_authlib.php');

	$sloodleerrors = array();

	$sloodleinstallurl = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];

	print_header('Enter your Second Life Avatar Details', '', '', '', false, '', '', false, '');
	print_heading('Sloodle User to SL Avatar authentication page');

//first verify that user is not a guest
if (isguest()) {
    error(get_string('noguestauthentication', 'sloodle'), $referrer);
    exit;
}

if (!isloggedin()) {
    error(get_string('not logged in', 'sloodle'), $referrer);
    exit;
}

//print_header();

$userid = $USER->id;
$sloodleuser = sloodle_get_sloodle_user_for_moodle_user($USER);

$isnewuser = ($sloodleuser == null);
if (!$isnewuser) {
  echo '<p>Current data on record:<br>Avatar Name ' .$sloodleuser->avname;
  echo '<br> Avatar UUID (key) ' .$sloodleuser->uuid;
  echo '<br> Authentication Table ID (for debug only): ' .$sloodleuser->id;
} 

echo '<form name="input" action="sl_loginzone_manual_update.php" method="get">';
echo 'Second Life avatar name: <input type="text" name="avname">';
echo ' Second Life avatar UUID (key): <input type="text" name="uuid">';
echo ' <input type="submit" value="Submit">';
echo ' </form>';

//$options = array(0 => "avname", 1 => "uuid");
//print_single_button('/mod/sloodle/login/sl_loginzone_manual_entry.php', $options, 'Submit', 'get', '_self');

print_footer();

	exit;


?>
