<?php

	require_once('../config.php');
	require_once('../locallib.php');
	require_once('sl_authlib.php');

	$sloodleerrors = array();

	print_header('Second Life Avatar Details', '', '', '', false, '', '', false, '');
	print_heading('Sloodle User to SL Avatar Authentication ');

//first verify that user is not a guest
if (isguest()) {
    error(get_string('noguestauthentication', 'sloodle'), $referrer);
    exit;
}

if (!isloggedin()) {
    error(get_string('not logged in', 'sloodle'), $referrer);
    exit;
}

	$sl_uuid = required_param('uuid', PARAM_RAW);  //Pass the Subject to the Blog - PHP will not run if we don't get this.
	$sl_avname = required_param('avname', PARAM_RAW);  // Pass the actual message - PHP will not run if we don't get this.


	$sloodleuser = sloodle_get_sloodle_user_for_moodle_user($USER);
	$isnewuser = ($sloodleuser == null);
	if ($isnewuser) {
		$sloodleuser = new object();
      }
	$sloodleuser->userid = $USER->id;
	$sloodleuser->uuid = $sl_uuid;
	$sloodleuser->avname = $sl_avname;
	//}
//	if ( ($sloodleuser != null) && ($sloodleuser->avname != null) && ($sloodleuser->avname != '') ) {
//		print_simple_box('Already got your login name!', "center");
//		exit;
//	} 
	
	$dbresult = false;
	if ($isnewuser) {
		$dbresult = insert_record('sloodle_users',$sloodleuser);	
	} else {
		$dbresult = update_record('sloodle_users',$sloodleuser);	
	}
	if (!$dbresult) {
		print_simple_box('Sorry, something went wrong.', "center");
		include('progressbar.html');
		exit;
	}
	print_simple_box('Avatar Name ' .$sloodleuser->avname .'<br> Avatar UUID (key) ' .$sloodleuser->uuid, "center");
	print_simple_box('Updated your SL avatar details!', "center");
	print_footer();

?>