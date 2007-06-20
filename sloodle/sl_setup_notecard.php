<?php

	require_once('config.php');
	require_once('locallib.php');

	$courseid = optional_param('courseid',null,PARAM_RAW);

	print_header('Sloodle Configuration Notecard', '', '', '', false, '', '', false, '');
	print_heading('Sloodle Configuration Notecard');

	require_login();
	if (isadmin() || SLOODLE_ALLOW_NORMAL_USER_ACCESS_TO_ADMIN_FUNCTIONS_FOR_TESTING) {

		if ( (sloodle_prim_password() == null) || (sloodle_prim_password() == '') ) {
		
			srand((double)microtime()*1000000); 
			$sloodle_pwd = rand(1000000000,21474836487);
			$result = sloodle_set_config('SLOODLE_PRIM_PASSWORD',$sloodle_pwd);

			if (!$result) {

				print "Error: Prim password isn't set, and I couldn't create one";
				exit;

			}
		}

		if ($courseid == NULL) {

			print '<h3>Choose the course you want to use in Second Life.</h3>';
			print '<p>Paste the following in the sloodle_config notecard in the Sloodle Set object.</p>';
			print '<form method="post" action="sl_setup_notecard.php">';
			$courses = get_courses();
			foreach($courses as $c) {
				$id = $c->id;
				$fullname = $c->fullname;
				print '<input type="radio" name="courseid" value="'.$id.'" />'.$fullname;
				print '<br />';
			}
			print '<input type="submit" value="Generate notecard text" />';
			print '</form>';

		} else {

			sloodle_print_config_notecard($CFG->wwwroot, sloodle_prim_password(), $courseid);

		}

	} else {

		print "admin only";

	}

	print_footer();

	exit;

	function sloodle_print_config_notecard($wwwroot,$pwd,$courseid) {

		print '<div align="center">';
		print '<p>Copy-and-paste the following into the sloodle_config notecard in your Sloodle Set object to allow it to access this course. Objects it rezzes will be able to access this course automatically; You don\'t need to configure them individually unless you want to.</p><p>For security reasons, you should make sure that the sloodle_config notecard cannot be edited except by its owner.</p>';
		print '<textarea cols=60 rows=4>';
		print 'set:sloodleserverroot|'.$wwwroot;
		print "\n";
		print 'set:pwd|'.$pwd;
		print "\n";
		print 'set:sloodle_courseid|'.$courseid;
		print "\n";
		print '</textarea>';
		print '<p>* If you prefer to configure your object in-world, just delete or rename the sloodle_config notecard in the Sloodle Set object. It will ask your avatar for the apprpriate settings.</p>';
		print '<p><a href="sl_setup.php">Back to the Sloodle Setup page</a>.';
		print '</div>';

	}

?>
