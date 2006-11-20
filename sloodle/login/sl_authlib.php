<?php

	function sloodle_get_prim_password() {
		
	}

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
		if ($userid == null) {
			return null;
		}
		return get_record('sloodle_users','userid',$userid);

	}
	
	function sloodle_get_sloodle_user_for_security_code($sc) {
		return get_record('sloodle_users','loginsecuritytoken',$sc);
	}

	function sloodle_match_sloodle_user_to_current_user($sloodleuser) {
		global $USER;
		if ( ($USER == null) || ($USER->id == null) ) {
			return false;
		}
		$sloodleuser->userid = $USER->id;
		return update_record('sloodle_users', $sloodleuser);
	}

	// registers a sloodle user, with a security code, returns the registered user 
	function sloodle_prim_register_sloodle_only($avname,$uuid) {


		$u = null;
		if ( ($uuid != null) && ($uuid != '') ) {
			$u = get_record('sloodle_users', 'uuid', $uuid);
		}
		if ($u == null) {
			if ( ($avname != null) && ($avname != '') ) {
				$u = get_record('sloodle_users', 'avname', $avname);
			}
		}
		if ($u) {

			if ( ($u->userid != null) && ($u->userid > 0 )) {

				// already properly registered, no need for a security token
				return array(null,array('user already registered with all the info sloodle needs'));

			} else {

				if ( ($u->loginsecuritytoken == null) || ($u->loginsecuritytoken == '') ) {

					$token = sloodle_random_security_token();
					$u->loginsecuritytoken = $token;
					
					if (!$result = update_record('sloodle_users', $u)) {
						return array(null,array('could not update sloodle user'));
					}

				} else {

					// already got a security token
					return array($u, array());

				}
			}

		} else {

			$token = sloodle_random_security_token();

			$u = new stdClass();
			$u->userid=0;
			$u->uuid = $uuid;
			$u->avname = $avname;
			$u->loginposition = '';
			$u->loginpositionexpires = '';
			$u->loginpositionregion = '';
			$u->loginsecuritytoken = addslashes($token);

			if (!$result = insert_record('sloodle_users', $u)) {
				return array(null,array('could not create sloodle user'));
			}

		}

		return array($u, array());

	}

	function sloodle_random_security_token() {
		$sc = '';
		$str="0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
	    for($length = 0; $length < 16; $length++) {
			$str= str_shuffle($str);
			$char = mt_rand(0, strlen($str));
		    $sc.= $str[$char];
		}
		return $sc;
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
		$uuid = optional_param('uuid',null,PARAM_RAW);
		$avname = optional_param('avname',null,PARAM_RAW);

		if ( ($avname == null) || ($uuid == null) || ($loginpositionfromprim == null) ) {

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
					$u->uuid = $uuid;
					$u->avname = $avname;
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
		$uuid = optional_param('uuid',null,PARAM_RAW);
		$avname = optional_param('avname',null,PARAM_RAW);

		$errors = array();

		$u = null;

		if ( ($uuid != null) && ($uuid != '') ) {
			$u = get_record('sloodle_users', 'uuid', $uuid);
		}

		if (!$u) {

			if ( ($avname != null) && ($avname != '') ) {
				$u = get_record('sloodle_users', 'avname', $avname);
			}
			if ($u) {
				// Must be the first time the user has come here through SL.
				// Stick their avatar uuid in the database.
				$u->uuid = $uuid;
				$u->avname = $avname;
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
