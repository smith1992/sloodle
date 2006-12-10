<?php

	require_once('../config.php');
	require_once('../locallib.php');

	$sloodleerrors = array();

	print_header('Sloodle login zone', '', '', '', false, '', '', false, '');
	print_heading('Sloodle logon zone');

	$loginzoneurl = SLOODLE_WWWROOT.'/login/sl_loginzone.php';
	$pasteurl = $loginzoneurl.'?pwd='.sloodle_prim_password();

	require_login($course->id, false, $cm);
	if (isadmin()) {
		//print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
	} else {
		print_simple_box('You need admin privileges to access this page.', "center");
		//print_simple_box('You would normally need admin privileges to access this page, but I\'ll let you in, since it\'s a demo.', "center");
		//print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
		exit;
	}

	$subs = array('SLOODLE_SCRIPT_URL_WITH_PASSWORD'=>$pasteurl);
	$scriptcontent = sloodle_lsl_output_substitution('login/lsl/LoginZone.txt',$subs);
	echo '<div align="center">';
	echo '<textarea rows="20" cols="80">';
	echo $scriptcontent;
	echo '</textarea>';
	echo '</div>';

	print_footer();

	exit;


?>
