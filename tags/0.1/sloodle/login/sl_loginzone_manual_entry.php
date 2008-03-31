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

$newAvname = optional_param('avname', 'Name', PARAM_RAW);
$newUUID = optional_param('uuid', 'Key', PARAM_RAW);

$isnewuser = ($sloodleuser == null);
if (!$isnewuser) {
  echo '<p>Current data on record:<br>Avatar Name ' .$sloodleuser->avname;
  echo '<br> Avatar UUID (key) ' .$sloodleuser->uuid;
  echo '<br> Authentication Table ID (for debug only): ' .$sloodleuser->id;

  // If we've got here without putting new values in the avname and key fields,
  // then use the values from the database
  if ($newAvname === 'Name') {
	$newAvname = $sloodleuser->avname;
  }
  if ($newUUID === 'Key') {
     $newUUID = $sloodleuser->uuid;
  }
} 

// Create the form to update the sl avatar details
// Put default values in - use values that have been submitted
// TO DO should probably change this so that if values have been submitted
// as input to this page, the user will not be able to edit them!
echo '<form name="input" action="sl_loginzone_manual_update.php" method="get">';
echo '<input type="hidden" name="avname" '; 
if ($newAvname !== NULL) {
  echo 'value="' .$newAvname .'"';
} 
echo '>';
echo 'New Data:<br>Second Life name: ' .$newAvname .'<br>UUID (Key): ' .$newUUID .'<br>'; 
echo '<input type="hidden" name="uuid" ';
if ($newUUID !== NULL) {
  echo 'value="' .$newUUID .'"';
}
echo '>';
echo ' <input type="submit" value="Submit" >';
echo ' </form>';

//$options = array(0 => "avname", 1 => "uuid");
//print_single_button('/mod/sloodle/login/sl_loginzone_manual_entry.php', $options, 'Submit', 'get', '_self');

print_footer();

	exit;


?>
