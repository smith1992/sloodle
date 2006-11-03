<?php

	// Use the URL parameters to create a global USER object

	// If we can find got an avatar uuid, we'll use it.
	// Otherwise, use the avatar name.
	$avataruuid = optional_param('avataruuid',null,PARAM_RAW);
	$avatarname = optional_param('avatarname',null,PARAM_RAW);

	$u = get_record('sloodle_users', 'uuid', $avataruuid);
	if (!$u) {
		$u = get_record('sloodle_users', 'avname', $avatarname);
		if ( (!$u) && isset($loginpositionfromprim) && ($loginpositionfromprim != null) ) {
			// This has been picked up, cleaned up and assigned to a variable.
			// We've passed in a $loginpositionfromprim variable.
			// This was set by the script that called us - probably loginzone.php.
			$u = get_record('sloodle_users', 'loginposition', $loginpositionfromprim);
			if (!$u) {
				$u = get_record('sloodle_users', 'loginposition', $alternativeloginpositionfromprim);
				if ($u->loginpositionexpires < time()) {
					$u = null;	
					$sloodleerrors[] = 'Slot in landing zone has expired.';
				}
			}
		}

		if ($u) {
			// Must be the first time the user has come here through SL.
			// Stick their avatar uuid in the database.
			$u->uuid = $avataruuid;
			$u->avname = $avatarname;
			$updated = update_record('sloodle_users', $u);
			if (!$updated) {
				$sloodleerrors[] = 'Could not update user table with avatar uuid';
			}
		} else {
			$sloodleerrors[] = 'User not found';
		}
	}

	// set global USER variable
	if ($u != null) {
		$USER = get_complete_user_data('id',$u->userid);
	}

?>
