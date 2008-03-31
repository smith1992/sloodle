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
	$avname = optional_param('avname',null,PARAM_RAW);
	if ( ($pos != null) && ($size != null) && ($region) ) {
		if (sloodle_set_config('loginzonepos',$pos) && sloodle_set_config('loginzonesize',$size) && sloodle_set_config('loginzoneregion',$region)) {
			sloodle_prim_render_output($data);
		} else {
			sloodle_prim_render_error('Setting position and size failed');
		}
		exit;

	} else if ($avname != null) {
	// the avatar has just arrived in the landing zone
	// request should look like this:
	// loginzone.php?pwd=drUs3-9dE&req=userinfo&avname=Edmund%20Earp&uuid=746ad236-d28d-4aab-93de-1e09a076c5f3&pos=<106.00000,87.00000,183.62387>&avsize=<0.45000,0.60000,1.98418>
	
		// First, try a conventional login in case the user has been here before.
		list($u, $errors) = sloodle_prim_user_login();
		if (!$u) {

			// the avatar should land right in the middle of the zone.
			// ...but the collision will occur with the furthest point in the direction the avatar moves, which should be down.
			$posarr = sloodle_vector_to_array($pos);
			$posarr = sloodle_round_vector_array($posarr);

			// For reasons I don't understand the avatar always appears either at exactly the place specified (round number) or 1.something meters above.
			// So if it's a round number, use it as is. If not, subtract 1.
			if (!preg_match('/\.00000>$/',$pos)) {
				$posarr['z'] = $posarr['z']-1;
			}

			$loginpositionfromprim = sloodle_array_to_vector($posarr);
			list($u, $errors) = sloodle_prim_loginzone_login($loginpositionfromprim);

		}

		$data = array($avname);
		if ($u) {
			sloodle_prim_render_output($data);
		} else {
			sloodle_prim_render_error($data);
		}

	}

?>
