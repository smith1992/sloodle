<?php

	require_once('../../config.php');
	require_once('locallib.php');

	$sloodleerrors = array();

	// The script in SL will always call us with a password.
	// If we're called without arguments, check for admin permissions and give the user a URL to paste into their prim.

	$sloodleinstallurl = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];

	$pwd = optional_param('pwd',null,PARAM_RAW);
	if ($pwd == null) {
	// We're talking to a human...

		$pasteurl = $sloodleinstallurl.'?pwd='.SLOODLE_PRIM_PASSWORD;

		print_header('Sloodle avatar gateway', '', '', '', false, '', '', false, '');
		print_heading('Sloodle avatar gateway');

		require_login($course->id, false, $cm);
		if (isadmin()) {
			print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
		} else {
			//print_simple_box('You need admin privileges to access this page.', "center");
			print_simple_box('You would normally need admin privileges to access this page, but I\'ll let you in, since it\'s a demo.', "center");
			print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
		}

		print_footer();

		exit;

	} else if ($pwd != SLOODLE_PRIM_PASSWORD) {

		sloodle_prim_render_errors(array('Sloodle Prim Password did not match the one set in the sloodle module configuration'));

	}

	// Anything from now on is for access by a prim taling to us from SL.
	require('authenticate.php');

	if (count($sloodleerrors) > 0) {
		sloodle_prim_render_errors($sloodleerrors);
		exit;
	} 

	// See what the script's asking for
	$data = array();
	$req = optional_param('req',null,PARAM_RAW);

	if ($req == 'courses') {
		$courses = get_courses();
		foreach($courses as $c) {
			$data[] = $c->fullname; 
		}
	} else if ($req == 'userinfo') {
		$data[] = $USER->firstname;
		$data[] = $USER->lastname;

		if ($USER->picture) {
			$file = 'f1';
			$data[] = $CFG->wwwroot .'/user/pix.php?file=/'. $USER->id.'/'. $file .'.jpg';
		} else {
			$data[] = $CFG->wwwroot .'/theme/standardlogo/logo.gif';
		}

	}

	sloodle_prim_render_output($data);

?>
