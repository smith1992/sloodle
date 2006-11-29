<?php

	require_once('../config.php');
	require_once('../locallib.php');

	print_header('Sloodle login setup', '', '', '', false, '', '', false, '');
	print_heading('Sloodle login setup');

	require_login();

	$signupurl = SLOODLE_WWWROOT.'/login/sl_sloodle_reg.php';
	$pasteurl = $signupurl.'?pwd='.sloodle_prim_password();
	$userclickurl = SLOODLE_WWWROOT.'/login/sl_welcome_reg.php';

	if (isadmin()) {
		print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
	} else {
		//print_simple_box('You need admin privileges to access this page.', "center");
		print_simple_box('You would normally need admin privileges to access this page, but I\'ll let you in, since it\'s a demo.', "center");
		print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
	}

	$subs = array(
		'SLOODLE_SCRIPT_URL_WITH_PASSWORD'=>$pasteurl,
		'SLOODLE_URL_TARGET'=>$userclickurl
	);
	$scriptcontent = sloodle_lsl_output_substitution('login/lsl/SLToWeb.txt',$subs);
	echo '<div align="center">';
	echo '<textarea rows="20" cols="80">';
	echo $scriptcontent;
	echo '</textarea>';
	echo '</div>';

	print_footer();

	exit;


?>
