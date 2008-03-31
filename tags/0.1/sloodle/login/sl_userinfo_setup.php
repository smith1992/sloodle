<?php

	require_once('../config.php');
	require_once('../locallib.php');

	print_header('Sloodle user info setup', '', '', '', false, '', '', false, '');
	print_heading('Sloodle user info setup');

	require_login($course->id, false, $cm);

	$userinfourl = SLOODLE_WWWROOT.'/login/sl_userinfo.php';
	$pasteurl = $userinfourl.'?pwd='.sloodle_prim_password();

	if (isadmin()) {
		//print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
	} else {
		print_simple_box('You need admin privileges to access this page.', "center");
		//print_simple_box('You would normally need admin privileges to access this page, but I\'ll let you in, since it\'s a demo.', "center");
		//print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
		exit;
	}

	$subs = array('SLOODLE_SCRIPT_URL_WITH_PASSWORD'=>$pasteurl);
	$scriptcontent = sloodle_lsl_output_substitution('login/lsl/ExperimentalAuthenticationClient.txt',$subs);
	echo '<div align="center">';
	echo '<textarea rows="20" cols="80">';
	echo $scriptcontent;
	echo '</textarea>';
	echo '</div>';

	print_footer();

	exit;


?>
