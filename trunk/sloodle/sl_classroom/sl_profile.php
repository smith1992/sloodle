<?php

	require_once('../config.php');
	require_once('../locallib.php');
	require_once('../login/sl_authlib.php');
	require_once('sl_classroomlib.php');

	$sloodleerrors = array();

    sloodle_prim_require_script_authentication();
	sloodle_prim_require_user_login();

	$cmd = optional_param('cmd',null,PARAM_RAW);
	$name = optional_param('name',null,PARAM_RAW);
	$courseid = optional_param('courseid',null,PARAM_RAW);

	if (!(isadmin() || isteacher($courseid))) {
		sloodle_prim_render_error('User not allowed to manage profiles for this course');
	}

	if ($cmd == 'new') {

		// see if it already exists
		$prof = sloodle_get_classroom_profile_for_name($name);
		if ($prof != null) {
			sloodle_prim_render_error('Profile already exists');
			exit;
		}

		$newprof = new stdClass();
		$newprof->name = $name;
		$newprof->courseid = $courseid;

		$addresult = sloodle_add_classroom_profile($newprof);
		if (!$addresult) {
			sloodle_prim_render_error('Adding profile failed');
			exit;
		}

		$prof = sloodle_get_classroom_profile_for_name($name);
		if ($prof == null) {
			sloodle_prim_render_error('Adding profile failed');
			exit;
		}

		sloodle_prim_render_output(array($prof->id,$prof->name));

	} else if ($cmd == 'addentries') {
	
		// /mod/sloodle/sl_classroom/sl_profile.php?pwd=561390&cmd=addentries&profileid=0&avname=EdmundEarp&uuid=746ad236-d28d-4aab-93de-1e09a076c5f3&vals=||d10f5ba8-8843-0f1a-ac8f-9b6820eb4a48|box2|<0.000000,0.000000,1.000000>||1dcc487a-fcdc-c003-b2a1-f9f9fbbcd123|box2|<0.000000,0.000000,1.000000><0.000000,0.000000,2.000000>

		$entrystring = optional_param('vals',null,PARAM_RAW);
		$profileid = optional_param('profileid',null,PARAM_RAW);
		$items = explode('||',$entrystring);
		array_shift($items);
		$entries = array();
		foreach ($items as $it) {
			$parts = explode('|',$it);
			$thisEntry = new stdClass();	
			if ($parts[0] == 0) {
				$thisEntry->id = '';
			} else {
				$thisEntry->id = $parts[0];
			}
			$thisEntry->sloodle_classroom_setup_profile_id = $profileid;
			$thisEntry->uuid = '';
			$thisEntry->name = $parts[2];
			$thisEntry->relative_position = $parts[3];
			$result = sloodle_save_classroom_profile_entry($thisEntry);
		var_dump($thisEntry);
			if (!$result) {
				$sloodleerrors[] = "FAILED: $it";
			}
		}

		if (count($sloodleerrors) > 0) {
			sloodle_prim_render_error($sloodleerrors);
		} else {
			sloodle_prim_render_output('ok');
		}

	} else if ($cmd == 'listprofiles') {

		$data = array();

		$courseid = optional_param('courseid',null,PARAM_RAW);
		$profiles = sloodle_get_classroom_profiles($courseid);

		if (count($profiles) > 0) {
			if ( (count($profiles) == 1) && ($profiles[0] == false) ) {
				$data[] = array(0, "Default", 0);
			} else {
				foreach($profiles as $pr) {
					$entries = sloodle_get_classroom_profile_entries($pr->id);
					if (count($entries) > 0) {
						if (!( (count($entries) == 1) && ($entries[0] == false) )) {
							$data[] = array($pr->id, $pr->name, count($entries));
						}
					} 
				}
			}
		} 

		sloodle_prim_render_output($data);

	} else if ($cmd == 'entries') {

		$data = array();

		$profileid = optional_param('profileid',null,PARAM_RAW);

		if ($profileid > 0) {

			$entries = sloodle_get_classroom_profile_entries($profileid);
			//var_dump($entries);
			if (count($entries) > 0) {
				foreach($entries as $e) {
					if (!( (count($entries) == 1) && ($entries[0] == false) )) {
						$data[] = array($e->id,$e->name,$e->relative_position); // TODO: make this auto-created and saved
					}
				}
			} 

		} else {

			$defaultObjects = array('box2','Sloodle Quiz Chair');
			$z = '0.5';
			// Default profile
			foreach($defaultObjects as $obj) {
				$position = "<0,0,$z>";
				$z = $z+0.5;
				$data[] = array(0,$obj,$position); // TODO: make this auto-created and saved
			}

		}


		//$profile1 = array(0,31,'test profile','box2','<0,0,1>',1843329443);
		//$profile2 = array(0,31,'test profile','box2','<0,0,2>',1234872308);
		sloodle_prim_render_output($data);

	}

?>
