<?php

	function sloodle_get_classroom_profiles($courseid = null) {
		return get_records('sloodle_classroom_setup_profile','courseid',$courseid);
	}

	function sloodle_get_classroom_profile_entries($profileid) {
		return get_records('sloodle_classroom_setup_profile_entry','sloodle_classroom_setup_profile_id ',$profileid);
	}

	function sloodle_get_classroom_profile($profileid) {
		return get_record('sloodle_classroom_setup_profile','id',$profileid);
	}

	function sloodle_get_classroom_profile_for_name($name) {
		return get_record('sloodle_classroom_setup_profile','name',$name);
	}


	function sloodle_add_classroom_profile($profile) {
		return insert_record('sloodle_classroom_setup_profile', $profile);
	}

	function sloodle_add_classroom_profile_entry($profileentry) {
		return insert_record('sloodle_classroom_setup_profile_entry', $profileentry);
	}

	function sloodle_update_classroom_profile_entry($profileentry) {
		return insert_record('sloodle_classroom_setup_profile_entry', $profileentry);
	}

	function sloodle_save_classroom_profile_entry($profileentry) {
		if ( isset($profileentry->id) && ($profileentry->id != '') && ($profileentry->id > 0) ) {
			return update_record('sloodle_classroom_setup_profile_entry', $profileentry);
		} else {
			return insert_record('sloodle_classroom_setup_profile_entry', $profileentry);
		}
	}

	function sloodle_save_classroom_profile_entries($profileid,$entries) {
		$deleted = delete_records('sloodle_classroom_setup_profile_entry', 'profileid', $profileid); // Just in case
		foreach($entries as $e) {
			sloodle_save_classroom_profile_entry($e);
		}
		return true;

	}

	function sloodle_save_classroom_profile($profile) {
		return update_record('sloodle_classroom_setup_profile', $profile);
	}
	
	function sloodle_validate_object_for_pwd($uuid,$pwd) {
		$entry = get_record('sloodle_active_object','uuid',$uuid);
		return ($entry->pwd == $pwd);
	}

	function sloodle_register_object($uuid,$name,$userid,$masteruuid) {
	
		$o = null;
		$isnew = true;
		$o = get_record('sloodle_active_object', 'uuid',$uuid);
		if ( ($o->id != null) && ($o->id != '') ) {
			$isnew = false;
		} else {
			$o = new stdClass();
		}

		$o->uuid = $uuid;
		$o->sloodle_classroom_setup_profile_id = 0;
		$o->name = $name;
		$o->master_uuid = $uuid;
		// if an object is aready registered, leave the password 
		$o->authenticated_by_userid = $userid;
		if ($isnew) {
			$o->pwd = sloodle_random_object_pwd();
		}
		

		$ok = false;
		if ($isnew) {	
			$ok = insert_record('sloodle_active_object',$o);
		} else {
			$ok = update_record('sloodle_active_object',$o); 
		}

		if ($ok) {
			return $o;
		} else {
			return false;
		}

	}

	function sloodle_random_object_pwd() {
		$sc = '';
		$str="0123456789";
	    for($length = 0; $length < 9; $length++) {
			$str= str_shuffle($str);
			$char = mt_rand(0, strlen($str));
		    $sc.= $str[$char];
		}
		return $sc;
	}

?>
