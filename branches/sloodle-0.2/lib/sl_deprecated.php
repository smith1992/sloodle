<?php
// Contains the deprecated functions for the Sloodle module
// Part of the Sloodle project
// See www.sloodle.org for more information
//
// Copyright (c) 2007 Sloodle
// Released under the GNU GPL v3
//
// Contributors:
//  various (old code!)
//


// NOTE: please do not add code here
// These functions will be removed when no longer needed


    function sloodle_prim_render_errors($errors,$type='MISC',$doDie=true) {
		print 'ERROR|'.$type.'|'.join('|',$errors);
		if ($doDie) {
			exit;
		}
	}
	function sloodle_prim_render_error($error, $type='MISC', $doDie=true) {
		return sloodle_prim_render_errors(array($error),$type,$doDie);
	}

	function sloodle_prim_render_output($arr) {
	// Returns content in a form our prim can understand it
	// For now, a pipe-delimited list.
	// Expects either a single array or an array of arrays.
		if (is_array($arr[0])) {
			$lines = array();
			foreach ($arr as $arrArr) {
				$lines[] = 'OK|'.join('|',$arrArr);
			}
			print join("\n",$lines);
		} else {
			print 'OK|'.join('|',$arr);
		}
	}

    function sloodle_lsl_output($script) { // eg. lsl/sl_auth/ExperimentalLoginClient.txt (relative to SLOODLE_DIRROOT)
		$filename = SLOODLE_DIRROOT.'/'.$script;
		$handle = fopen($filename, "r");
		$contents = fread($handle, filesize($filename));
		fclose($handle);
		return $contents;

	}

	function sloodle_lsl_output_substitution($script, $subs) {

		if ($contents = sloodle_lsl_output($script)) {
			foreach ($subs as $k=>$v) {
				$contents = preg_replace('/'.$k.'/',$v,$contents);
			} 
			return $contents;
		} else {
			return false;
		}
	}

	function sloodle_require_setup_done($feature) {

	}
    
    
    function sloodle_prim_require_script_authentication() {
	// Check the prim is allowed to talk to us.
	// Right now we're doing this using a password, but in future we may also want to let the administrator keep a list of authenticated object uuids.

		$pwd = optional_param('pwd',null,PARAM_RAW);
		if ($pwd == null) {
			$pwd = optional_param('sloodlepwd',null,PARAM_RAW); // allow both types of arg to fit new LSL style guidelines
		}
		$errors = array();

		$objpwd = null;

		if (preg_match('/^(.*?)\|(\d\d*)$/',$pwd, $matches)) {
			// first, see if we can validate based on a numerical code set for that object
			//$headers = apache_request_headers();
			//$objuuid = $headers['X-SecondLife-Object-Key'];
			$objuuid = $matches[1];
			$objpwd = $matches[2];
			$entry = get_record('sloodle_active_object','uuid',$objuuid);
			if ( ($entry->pwd != null) && ($entry->pwd == $objpwd) ) {
				return true;
			}

		}

		// if that fails, fall back on the legacy method of having a single pwd= string for all objects
		if ($pwd == null) {
			sloodle_prim_render_error('Prim password missing. Could not verify that the object sending this request was allowed to talk to me.');
			exit;
		} else if ( ($pwd != sloodle_prim_password()) && ($objpwd != sloodle_prim_password()) )  {
			sloodle_prim_render_error('Sloodle Prim Password did not match the one set in the sloodle module configuration'.'objuuid was '.$objuuid." and pwd was $pwd and entry pwd was ".$entry->pwd);
			exit;
		}

		return true;

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

				// not found - register them automatically, if set
				if (sloodle_is_automatic_registration_on()) {
					$ok = sloodle_register_user($uuid,$avname);
					$u = get_record('sloodle_users', 'uuid', $uuid);
				}

				$errors[] = 'User not found';
			}
		}

		// set global USER variable
		if ($u != null && $u->userid != 0) {
			$USER = get_complete_user_data('id',$u->userid);
		} else {
            $USER = null;
        }

		return array($USER,$errors);

	}
    
    
    function sloodle_register_user($uuid,$avname) {
	//TODO: Improve error handling...

		global $CFG;

		require_once("../../../auth/$CFG->auth/lib.php");

		$firstname = null;
		$lastname = null;
		// Expecting that all SL names are first last, with a space in between
		if (preg_match('/^(.*)\s(.*?)$/', $avname, $avbits)) {
			$firstname = $avbits[1];
			$lastname = $avbits[2];
		}

		$user = new stdClass();
		if ( ($firstname != null) && ($lastname != null) ) {

			$user->firstname = strip_tags($firstname);
			$user->lastname = strip_tags($lastname);
			$user->email = $uuid.'@lsl.secondlife.com';
			$user->password = sloodle_random_web_password();
			$user->username = trim(moodle_strtolower($firstname.$lastname));

			if (count((array)$err) == 0) {

				$plainpass = $user->password;
				$user->password = hash_internal_user_password($plainpass);
				$user->confirmed = 0;
				$user->lang = current_language();
				$user->firstaccess = time();
				$user->secret = random_string(15);
				$user->auth = $CFG->auth;
				if (!empty($CFG->auth_user_create) and function_exists('auth_user_create') ){
					if (! auth_user_exists($user->username)) {
						if (! auth_user_create($user,$plainpass)) {
							sloodle_prim_render_error("Could not add user to authentication module!");
						}
					} else {
						sloodle_prim_render_error("User already exists on authentication database.");
					}
				}

				if (! ($user->id = insert_record("user", $user)) ) {
					sloodle_prim_render_error("Could not add your record to the database!");
				}

				$u = new stdClass();
				$u->userid=$user->id;
				$u->uuid = $uuid;
				$u->avname = $avname;
				$u->loginposition = '';
				$u->loginpositionexpires = '';
				$u->loginpositionregion = '';
				$u->loginsecuritytoken = '';

				if (!$result = insert_record('sloodle_users', $u)) {
					return array(null,array('could not create sloodle user'));
				}
			}
		}

		return array($u, array());

	}
    
?>