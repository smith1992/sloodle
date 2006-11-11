<?php

	require_once('../config.php');
	require_once('../locallib.php');

	print_header('Sloodle user info setup', '', '', '', false, '', '', false, '');
	print_heading('Sloodle user info setup');

	require_login($course->id, false, $cm);

	$userinfourl = SLOODLE_WWWROOT.'/sl_auth/sl_userinfo.php';
	$pasteurl = $userinfourl.'?pwd='.SLOODLE_PRIM_PASSWORD;

	if (isadmin()) {
		print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
	} else {
		//print_simple_box('You need admin privileges to access this page.', "center");
		print_simple_box('You would normally need admin privileges to access this page, but I\'ll let you in, since it\'s a demo.', "center");
		print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
	}

	print_footer();

	exit;


?>
