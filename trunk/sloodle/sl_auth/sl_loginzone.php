<?php

	require_once('../config.php');
	require_once('../locallib.php');

	$sloodleerrors = array();

	// The script in SL will always call us with a password.
	// If we're called without arguments, check for admin permissions and give the user a URL to paste into their prim.

	$sloodleinstallurl = 'http://'.$_SERVER['SERVER_NAME'].$_SERVER['SCRIPT_NAME'];

	$pwd = optional_param('pwd',null,PARAM_RAW);

	//showlink argument tells us it's a user asking for a link to get them into sl.
	$showlink = optional_param('showlink',null,PARAM_RAW);
	if ($showlink) {

		print_header('Sloodle avatar gateway', '', '', '', false, '', '', false, '');
		print_heading('Sloodle entrance to login zone');

		require_login($course->id, false, $cm);
		$sloodleuser = sloodle_get_sloodle_user_for_moodle_user($USER);
		$isnewuser = ($sloodleuser == null);
		if ($isnewuser) {
			$sloodleuser = new object();
			$sloodleuser->userid = $USER->id;
			$sloodleuser->uuid = '';
			$sloodleuser->avname = '';
		}
		$link = null;
		if ( ($sloodleuser != null) && ($sloodleuser->avname != null) && ($sloodleuser->avname != '') ) {
			$coord = sloodle_finished_login_coordinates();
			$link = 'secondlife://'.sloodle_get_config('loginzoneregion').'/'.$coord['x'].'/'.$coord['y'].'/'.$coord['z'];
			print_simple_box('Already got your login name - just <a href="'.$link.'">go right ahead</a>', "center");
			exit;
		} 
		
		if ( ($isnewuser) || ($sloodleuser->loginposition == '') || ( !position_is_in_login_zone($sloodleuser->loginposition) ) ) {
			// need to generate a loginposition
			$loginpositionarr = sloodle_generate_new_login_position();	
			if ($loginpositionarr == null) {
				print_simple_box('Sorry, could not allocate a landing position. You probably need a bigger landing zone.', "center");
				exit;
			}
			$loginposition = sloodle_array_to_vector($loginpositionarr);
			$sloodleuser->loginposition = $loginposition;
		} else {
			$loginpositionarr = sloodle_vector_to_array($sloodleuser->loginposition);
		}

		$sloodleuser->loginpositionexpires = time()+(30*60);
		$dbresult = false;
		if ($isnewuser) {
			$dbresult = insert_record('sloodle_users',$sloodleuser);	
		} else {
			$dbresult = update_record('sloodle_users',$sloodleuser);	
		}
		if (!$dbresult) {
			print_simple_box('Sorry, something went wrong while trying to store your landing position in the database.', "center");
			include('progressbar.html');
			exit;
		}

		$link = 'secondlife://'.sloodle_get_config('loginzoneregion').'/'.$loginpositionarr['x'].'/'.$loginpositionarr['y'].'/'.$loginpositionarr['z'];
		print_simple_box('Please <a href="'.$link.'">click here</a> to enter Second Life. <br />', "center");
		print_simple_box('This link will expire in 15 minutes. If it takes you longer than that to enter Second Life, you\'ll have to come back to this page and get a new one.', "center");

		exit;
	}



	if ($pwd == null) {
	// We're talking to a human...

		$pasteurl = $sloodleinstallurl.'?pwd='.SLOODLE_PRIM_PASSWORD;

		print_header('Sloodle avatar gateway', '', '', '', false, '', '', false, '');
		print_heading('Sloodle avatar gateway');

		require_login($course->id, false, $cm);
		if (isadmin()) {
			print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
		} else {
			//print_simple_box('You need admin privileges to access this page.', "center");
			print_simple_box('You would normally need admin privileges to access this page, but I\'ll let you in, since it\'s a demo.', "center");
			print_simple_box('You need to tell your prim to use the following URL to talk to Moodle:<br />'.$pasteurl, "center");
		}

		print_footer();

		exit;

	} else if ($pwd != SLOODLE_PRIM_PASSWORD) {

		sloodle_prim_render_errors(array('Sloodle Prim Password did not match the one set in the sloodle module configuration'));

	}

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
		require('sloodle_auth.php');
		exit;

		if (count($sloodleerrors) > 0) {
			sloodle_prim_render_errors($sloodleerrors);
			exit;
		} 

		// See what the script's asking for
		$data = array();
		$req = optional_param('req',null,PARAM_RAW);

		if ($req == 'userinfo') {
			$data[] = $USER->firstname;
			$data[] = $USER->lastname;

			if ($USER->picture) {
				$file = 'f1';
				$data[] = $CFG->wwwroot .'/user/pix.php?file=/'. $USER->id.'/'. $file .'.jpg';
			} else {
				$data[] = $CFG->wwwroot .'/theme/standardlogo/logo.gif';
			}

		}

		sloodle_prim_render_output($data);

	}

?>
