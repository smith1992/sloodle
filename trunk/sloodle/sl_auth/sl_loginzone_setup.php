<?php

	require_once('../config.php');
	require_once('../locallib.php');

	$sloodleerrors = array();

	print_header('Sloodle avatar gateway', '', '', '', false, '', '', false, '');
	print_heading('Sloodle avatar gateway');

	$loginzoneurl = SLOODLE_ROOT.'/sl_auth/sl_loginzone.php';
	$pasteurl = $loginzoneurl.'?pwd='.SLOODLE_PRIM_PASSWORD;

	require_login($course->id, false, $cm);
	if (isadmin()) {
		print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
	} else {
		//print_simple_box('You need admin privileges to access this page.', "center");
		print_simple_box('You would normally need admin privileges to access this page, but I\'ll let you in, since it\'s a demo.', "center");
		print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
	}

	/*
	TODO: Make script appear here...
	echo '<textarea rows="40" cols="20">';

	echo '</textarea>';
	"SLOODLE_SCRIPT_URL_WITH_PASSWORD"
	*/

	print_footer();

	exit;


?>
