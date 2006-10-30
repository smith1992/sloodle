<?php

	// Use the URL parameters to create a global USER object

	// If we can find got an avatar uuid, we'll use it.
	// Otherwise, use the avatar name.
	$avataruuid = optional_param('avataruuid','OINK',PARAM_RAW);
	$avatarname = optional_param('avatarname',null,PARAM_RAW);

	$u = get_record(SLOODLE_USER_TABLE, SLOODLE_AVATAR_UUID_FIELD, $avataruuid);
	if (!$u) {
		$u = get_record(SLOODLE_USER_TABLE, SLOODLE_AVATAR_NAME_FIELD, $avatarname);
		if ($u) {
			// Must be the first time the user has come here through SL.
			// Stick their avatar uuid in the database.
			$avfield = SLOODLE_AVATAR_UUID_FIELD;
			$u->$avfield = $avataruuid;
			$updated = update_record(SLOODLE_USER_TABLE, $u);
			if (!$updated) {
				$sloodleerrors[] = 'Could not update user table with avatar uuid';
			}
		} else {
			$sloodleerrors[] = 'User not found';
		}
	}

	// TODO: If we're using a field in the profile of the main user table, we should be able to get this in the first trip to the database.
	// set global USER variable
	if ($u != null) {
		$USER = get_complete_user_data('username',$u->username);
	}

?>
