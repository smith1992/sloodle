<?php

	require_once('../config.php');
	require_once('../locallib.php');
	require_once('sl_authlib.php');

	$sloodleerrors = array();

    sloodle_prim_require_script_authentication();


	// When the login zone is created or moved, it will send us a request like this to tell us where and how big it is:
	// loginzone.php?pwd=drUs3-9dE&pos=<106.33453,86.62961,200.00000>&size=<10.00000,10.00000,10.00000>&region=Cicero
	// NB: The following assumes we only have one landing zone per Moodle install. Which ain't necessarily so...
	// For now, we'll just hard-code the position of the object in our configuration file.
	$pos = optional_param('pos',null,PARAM_RAW);
	$size = optional_param('size',null,PARAM_RAW);
	$region = optional_param('region',null,PARAM_RAW);
	$avatarname = optional_param('avatarname',null,PARAM_RAW);
	if ( ($pos != null) && ($size != null) && ($region) ) {
		if (sloodle_set_config('loginzonepos',$pos) && sloodle_set_config('loginzonesize',$size) && sloodle_set_config('loginzoneregion',$region)) {
			sloodle_prim_render_output($data);
		} else {
			sloodle_prim_render_error('Setting position and size failed');
		}
		exit;

	} else if ($avatarname != null) {
	// the avatar has just arrived in the landing zone
	// request should look like this:
	// loginzone.php?pwd=drUs3-9dE&req=userinfo&avatarname=Edmund%20Earp&avataruuid=746ad236-d28d-4aab-93de-1e09a076c5f3&pos=<106.00000,87.00000,183.62387>&avsize=<0.45000,0.60000,1.98418>
	
		// the avatar should land right in the middle of the zone.
		// ...but the collision will occur with the furthest point in the direction the avatar moves, which should be down.
		$posarr = sloodle_vector_to_array($pos);
		$posarr = sloodle_round_vector_array($posarr);

		$alternativeposarr = $posarr;
		$alternativeposarr['z'] = $posarr['z']-1;
		//$avsizearr = sloodle_vector_to_array($avsize);
		//$recordedpos = array();
		//$recordedpos['x'] = $posarr['x'];
		//$recordedpos['y'] = $posarr['y'];
		//$recordedpos['z'] = $posarr['z'];
			
		$loginpositionfromprim = sloodle_array_to_vector($posarr);
		$alternativeloginpositionfromprim = sloodle_array_to_vector($alternativeposarr);

		// First, try a conventional login.
		list($u, $errors) = sloodle_prim_user_login();

		// If that fails, try using the login position.
		if (!$u) {
			list($u, $errors) = sloodle_prim_loginzone_login($loginpositionfromprim,$alternativeloginpositionfromprim);
		}

		$data = array($avatarname);
		if ($u) {
			sloodle_prim_render_output($data);
		} else {
			sloodle_prim_render_error($data);
		}
	}

?>
