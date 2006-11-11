<?php

	function sloodle_prim_require_script_authentication() {
	// Check the prim is allowed to talk to us.
	// Right now we're doing this using a password, but in future we may also want to let the administrator keep a list of authenticated object uuids.
	
		$pwd = optional_param('pwd',null,PARAM_RAW);
		$errors = array();
		if ($pwd == null) {
			sloodle_prim_render_error('Prim password missing. Could not verify that the object sending this request was allowed to talk to me.');
			exit;
		} else if ($pwd != SLOODLE_PRIM_PASSWORD) {
			sloodle_prim_render_error('Sloodle Prim Password did not match the one set in the sloodle module configuration');
			exit;
		}

		return true;

	}

	// gets a sloodle user record for a moodle user
	function sloodle_get_sloodle_user_for_moodle_user($mu) {
		// return a sloodle user record if it exists, null if it doesn't.
		$userid = $mu->id;
		return get_record('sloodle_users','userid',$userid);

	}
	
	// Returns the top
	function sloodle_login_zone_coordinates() {
		$pos = sloodle_get_config('loginzonepos');
		$size = sloodle_get_config('loginzonesize');
		if ( ($pos == null) || ($size == null) ) {
			return null;
		}
		$max = array();
		$min = array();
		if ( ( is_array($posarr = sloodle_vector_to_array($pos) ) ) && ( is_array( $sizearr = sloodle_vector_to_array($size) ) ) ) {
			$max['x'] = $posarr['x']+(($sizearr['x'])/2)-2;
			$max['y'] = $posarr['y']+(($sizearr['y'])/2)-2;
			$max['z'] = $posarr['z']+(($sizearr['z'])/2)-2;
			$min['x'] = $posarr['x']-(($sizearr['x'])/2)+2;
			$min['y'] = $posarr['y']-(($sizearr['y'])/2)+2;
			$min['z'] = $posarr['z']-(($sizearr['z'])/2)+2;
		} else {
			return false;
		}
		return array($max,$min);
	}

	function sloodle_finished_login_coordinates() {
	// return a position below the login zone for people whose avaar name we've already got.
		$pos = sloodle_get_config('loginzonepos');
		$size = sloodle_get_config('loginzonesize');
		if ( ($pos == null) || ($size == null) ) {
			return null;
		}
		$max = array();
		$min = array();
		if ( ( is_array($posarr = sloodle_vector_to_array($pos) ) ) && ( is_array( $sizearr = sloodle_vector_to_array($size) ) ) ) {
			$coord['x'] = round($posarr['x'],0);
			$coord['y'] = round($posarr['y'],0);
			$coord['z'] = round(($posarr['z']-(($sizearr['z'])/2)-2),0);
			return $coord;
		} else {
			return false;
		}
		
	}

	function position_is_in_login_zone($pos) {
		$posarr = sloodle_vector_to_array($pos);
		list($maxarr,$minarr) = sloodle_login_zone_coordinates();
		//print '<h1>cheking whtier pos '.$pos.sloodle_array_to_vector($posarr).' is bigger than '.sloodle_array_to_vector($maxarr).' and smaller than '.sloodle_array_to_vector($minarr).'</h1>';
		if ( ($posarr['x'] > $maxarr['x']) || ($posarr['y'] > $maxarr['y']) || ($posarr['z'] > $maxarr['z']) ) {
			return false;
		}
		if ( ($posarr['x'] < $minarr['x']) || ($posarr['y'] < $minarr['y']) || ($posarr['z'] < $minarr['z']) ) {
			return false;
		}
		return true;
	}

	function sloodle_vector_to_array($vector) {
		if (preg_match('/<(.*?),(.*?),(.*?)>/',$vector,$vectorbits)) {
			$arr = array();
			$arr['x'] = $vectorbits[1];
			$arr['y'] = $vectorbits[2];
			$arr['z'] = $vectorbits[3];
			return $arr;
		}
		return false;
	}

	function sloodle_array_to_vector($arr) {
		$ret = '<'.$arr['x'].','.$arr['y'].','.$arr['z'].'>';
		//print "<h1>$ret</h1>";
		return $ret;
	}

	// finds an available landing position
	function sloodle_generate_new_login_position() {
		// need to make a landing position that isn't already in use.
		// 2 possible approaches:
		// - make a position, then check it isn't already in use
		// - get a list of positions already in use, then go through looking for a new one.
		list($max,$min) = sloodle_login_zone_coordinates();

		$maxtries = 10;
		for ($i=0; $i<$maxtries; $i++) {
			$mypos = sloodle_random_position_in_zone($max,$min);
			$taker = get_record('sloodle_users','loginposition',sloodle_array_to_vector($mypos));
			if ($taker == null) {
				return $mypos;
			}
		}

		// TODO: After 10 random tries fail, do it the other way...
		//       We should also start recycling positions of users who have already got their sloodle names
		return false;
	
	}

	function sloodle_random_position_in_zone($zonemax,$zonemin) {
		$pos = array();
		$pos['x'] = rand($zonemin['x'],$zonemax['x']);	
		$pos['y'] = rand($zonemin['y'],$zonemax['y']);	
		$pos['z'] = rand($zonemin['z'],$zonemax['z']);	
		return $pos;
	}

	function sloodle_round_vector_array($pos) {
		foreach($pos as $pk => $pval) {
			$pos[$pk] = round($pval,0);
		}
		return $pos;
	}

	function sloodle_prim_require_user_login() {
	// Use the URL parameters to create a global USER object
	// Return an error if we fail.
		
		list($u,$errors) = sloodle_prim_user_login();
		if ($u == null) {
			sloodle_prim_render_errors($errors);
			exit;
		}

	}

	function sloodle_prim_loginzone_login($loginpositionfromprim) {

		global $USER;

		$errors = array();

		// If we can find got an avatar uuid, we'll use it.
		// Otherwise, use the avatar name.
		$avataruuid = optional_param('avataruuid',null,PARAM_RAW);
		$avatarname = optional_param('avatarname',null,PARAM_RAW);

		if ( ($avatarname == null) || ($avataruuid == null) || ($loginpositionfromprim == null) ) {

			$errors[] = 'Missing arguments';

		} else {

			$u = get_record('sloodle_users', 'loginposition', $loginpositionfromprim);

			if ($u) {

				if ($u->loginpositionexpires < time()) {

					$u = null;	
					$errors[] = 'Slot in landing zone has expired.';

				} else {

					// Must be the first time the user has come here through SL.
					// Stick their avatar uuid in the database.
					$u->uuid = $avataruuid;
					$u->avname = $avatarname;
					$updated = update_record('sloodle_users', $u);
					if (!$updated) {
						$errors[] = 'Could not update user table with avatar uuid';
					}
					
					// set global USER variable
					if ($u != null) {
						$USER = get_complete_user_data('id',$u->userid);
					}

				}

			} else {

				$errors[] = 'No user found for position';

			}
		}

		return array($USER, $errors);

	}

	function sloodle_prim_user_login() {
	// Use the URL parameters to create a global USER object
	// Return the global USER object (or null) and an array of errors.

		global $USER;

		// If we can find got an avatar uuid, we'll use it.
		// Otherwise, use the avatar name.
		$avataruuid = optional_param('avataruuid',null,PARAM_RAW);
		$avatarname = optional_param('avatarname',null,PARAM_RAW);

		$errors = array();

		$u = null;

		if ( ($avataruuid != null) && ($avataruuid != '') ) {
			$u = get_record('sloodle_users', 'uuid', $avataruuid);
		}

		if (!$u) {

			if ( ($avatarname != null) && ($avatarname != '') ) {
				$u = get_record('sloodle_users', 'avname', $avatarname);
			}
			if ($u) {
				// Must be the first time the user has come here through SL.
				// Stick their avatar uuid in the database.
				$u->uuid = $avataruuid;
				$u->avname = $avatarname;
				$updated = update_record('sloodle_users', $u);
				if (!$updated) {
					$errors[] = 'Could not update user table with avatar uuid';
				}
			} else {
				$errors[] = 'User not found';
			}
		}

		// set global USER variable
		if ($u != null) {
			$USER = get_complete_user_data('id',$u->userid);
		}

		return array($USER,$errors);

	}

?>
