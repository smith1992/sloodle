<?php
/*
This page authenticates a user and returns basic Moodle information about them.
Example request: TODO
*/
	require_once('../config.php');
	require_once('../locallib.php');
	require_once('sl_authlib.php');

	sloodle_prim_require_script_authentication();

	sloodle_prim_require_user_login();

	// See what the script's asking for
	$data = array();
	$req = optional_param('req',null,PARAM_RAW);

	$data[] = $USER->firstname;
	$data[] = $USER->lastname;

	if ($USER->picture) {
		$file = 'f1';
		$data[] = $CFG->wwwroot .'/user/pix.php?file=/'. $USER->id.'/'. $file .'.jpg';
	} else {
		$data[] = $CFG->wwwroot .'/theme/standardlogo/logo.gif';
	}

	sloodle_prim_render_output($data);

?>
