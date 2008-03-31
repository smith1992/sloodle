<?php

	require_once('../config.php');
	require_once('../locallib.php');
	require_once('sl_authlib.php');

	$sloodleerrors = array();

	// The script in SL will always call us with a password.
	// If we're called without arguments, check for admin permissions and give the user a URL to paste into their prim.

	$sloodleinstallurl = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];

	print_header('Teleport to Second Life', '', '', '', false, '', '', false, '');
	print_heading('Sloodle entrance to login zone');

	require_login($course->id, false, $cm);
	$sloodleuser = sloodle_get_sloodle_user_for_moodle_user($USER);
	$isnewuser = ($sloodleuser == null);
	if ($isnewuser) {
		$sloodleuser = new object();
		$sloodleuser->userid = $USER->id;
		$sloodleuser->uuid = '';
		$sloodleuser->avname = '';
	}
	$link = null;
	if ( ($sloodleuser != null) && ($sloodleuser->avname != null) && ($sloodleuser->avname != '') ) {
		$coord = sloodle_finished_login_coordinates();
		$link = 'secondlife://'.sloodle_get_config('loginzoneregion').'/'.$coord['x'].'/'.$coord['y'].'/'.$coord['z'];
		print_simple_box('Already got your login name - just <a href="'.$link.'">go right ahead</a>', "center");
		exit;
	} 
	
	if ( ($isnewuser) || ($sloodleuser->loginposition == '') || ( !position_is_in_login_zone($sloodleuser->loginposition) ) ) {
		// need to generate a loginposition
		$loginpositionarr = sloodle_generate_new_login_position();	
		if ($loginpositionarr == null) {
			print_simple_box('Sorry, could not allocate a landing position. You probably need a bigger landing zone.', "center");
			exit;
		}
		$loginposition = sloodle_array_to_vector($loginpositionarr);
		$sloodleuser->loginposition = $loginposition;
	} else {
		$loginpositionarr = sloodle_vector_to_array($sloodleuser->loginposition);
	}

	$sloodleuser->loginpositionexpires = time()+(30*60);
	$dbresult = false;
	if ($isnewuser) {
		$dbresult = insert_record('sloodle_users',$sloodleuser);	
	} else {
		$dbresult = update_record('sloodle_users',$sloodleuser);	
	}
	if (!$dbresult) {
		print_simple_box('Sorry, something went wrong while trying to store your landing position in the database.', "center");
		include('progressbar.html');
		exit;
	}

	$link = 'secondlife://'.sloodle_get_config('loginzoneregion').'/'.$loginpositionarr['x'].'/'.$loginpositionarr['y'].'/'.$loginpositionarr['z'];
	print_simple_box('Please <a href="'.$link.'">click here</a> to enter Second Life. <br />', "center");
	print_simple_box('This link will expire in 15 minutes. If it takes you longer than that to enter Second Life, you\'ll have to come back to this page and get a new one.', "center");

	exit;


?>
