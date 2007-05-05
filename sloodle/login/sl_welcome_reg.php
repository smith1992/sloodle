<?php
/*
We expect users to arrive at this page using a URL issued in SL with the following args:
 - uuid
 - lsc (loginsecuritycode)
*/

	require_once('../config.php');
	require_once('../locallib.php');
	require_once('sl_authlib.php');

	// TODO: It would be nice, in a case where a user who we know about already has come here with a valid security code, to allow them to log straight in without forcing them to enter their password, etc.
	// However, this has some security implications that we'll need to think through before we do this...
	// (For example, we currently store the security code un-hashed..., which is less secure than the way Moodle doesthings...)
	require_login(); // this will send the user to the registration / login page, and bring them back here, hopefully with the uuid and lsc arguments intact, when they're done.

	print_header('Welcome to sloodle', '', '', '', false, '', '', false, '');
	print_heading('Welcome to sloodle');

	// see if we know who they are in sl and moodle already
	/*
	$su = sloodle_get_sloodle_user_for_moodle_user($USER);
	if ($su != null) {
print "<h3>sloodleuser</h3";
	var_dump($su);
	
		print '<center>';
		print_simple_box('Welcome to Sloodle, '.$su->avname);
		print '</center>';
		print_footer();
		exit;
	}
	*/

	$lsc = required_param('lsc',PARAM_RAW); // security code - this should already be in the database.
	$channel = optional_param('ch',NULL,PARAM_RAW); // optional channel code to tell the object we're done.
		
	if (!$sloodleuser = sloodle_get_sloodle_user_for_security_code($lsc)) {
		print '<center>';
		print_simple_box('Error: Could not find a user for your security code');
		print '</center>';
		print_footer();
		exit;
	}

	if ( ( $sloodleuser->userid == null ) || ($sloodleuser->userid == 0) ) {
	// we don't yet have them matched up
		$result = sloodle_match_sloodle_user_to_current_user($sloodleuser);
		if (!$result) {
			print '<center>';
			print_simple_box('Error: We could not match up your Second Life name to your Moodle name due to a technical problem. Please try again later.');
			print '</center>';
			print_footer();
			exit;
		}
	}

	print '<center>';
	print_simple_box('Welcome to SLoodle, '.$sloodleuser->avname);
	print '</center>';

	// If the object passed us a channel parameter, we'll use it to tell the object that the authentication is done.
	// If not, the avatar will just have to touch the object again.
	if ( ($channel != null) && ($channel != '') ) {
	print "<h1>channel is :$channel:</h1>";
		flush();
		$xmlrpcresult = sloodle_send_xmlrpc_message($channel,0,"OK|SLOODLE_AUTHENTICATION_DONE|".$sloodleuser->uuid);
		if (!$xmlrpcresult) {
			print '<center>';
			print_simple_box('Error: Unable to tell the object that sent you here that you have been authenticated.');
			print '</center>';
		}

	}

	print_footer();
	exit;

?>
