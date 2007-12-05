<?php

    if (defined(SLOODLE_DIRROOT))
        require_once(SLOODLE_DIRROOT.'/sl_deprecated.php');
        

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
					if ($u != null && $u->userid != 0) {
						$USER = get_complete_user_data('id',$u->userid);
					} else {
                        $USER = null;
                    }

				}

			} else {

				$errors[] = 'No user found for position';

			}
		}

		return array($USER, $errors);

	}

	

	
?>
