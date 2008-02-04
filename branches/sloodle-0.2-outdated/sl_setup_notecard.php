<?php

	require_once('config.php');
	require_once(SLOODLE_DIRROOT.'/lib/sl_generallib.php');

	$courseid = optional_param('courseid',null,PARAM_RAW);

	print_header(get_string("cfgnotecard:header", "sloodle"), '', '', '', false, '', '', false, '');
	print_heading(get_string("cfgnotecard:header", "sloodle"));

	require_login();
	if (isadmin() || SLOODLE_ALLOW_NORMAL_USER_ACCESS_TO_ADMIN_FUNCTIONS_FOR_TESTING) {

		if ( (sloodle_get_prim_password() == null) || (sloodle_get_prim_password() == '') ) {
	
			$sloodle_pwd = (string)mtrand(100000000,999999999);
			$result = sloodle_set_config('sloodle_prim_password',$sloodle_pwd);

			if (!$result) {

				print get_string("primpass:errornotset", "sloodle");
				exit;

			}
		}

		if ($courseid == NULL) {

			print '<h3>'. get_string("choosecourse", "sloodle") .'</h3>';
			print '<p>'. get_string("cfgnotecard:choosecourse", "sloodle") .'</p>';
			print '<form method="post" action="sl_setup_notecard.php">';
			$courses = get_courses();
			foreach($courses as $c) {
				$id = $c->id;
				$fullname = $c->fullname;
				print '<input type="radio" name="courseid" value="'.$id.'" />'.$fullname;
				print '<br />';
			}
			print '<br/><input type="submit" value="'. get_string("cfgnotecard:generate", "sloodle") .'" />';
			print '</form>';

		} else {

			sloodle_print_config_notecard($CFG->wwwroot, sloodle_get_prim_password(), $courseid);

		}

	} else {

		print get_string("needadmin", "sloodle");;

	}

	print_footer();

	exit;

	function sloodle_print_config_notecard($wwwroot,$pwd,$courseid) {
        global $CFG;
		print '<div align="center">';
		print '<p>'. get_string("cfgnotecard:instructions", "sloodle") .'</p>';
       print '<p>'. get_string("cfgnotecard:security", "sloodle") .'</p>';
		print '<textarea cols=60 rows=4>';
		print 'set:sloodleserverroot|'.$wwwroot;
		print "\n";
		print 'set:pwd|'.$pwd;
		print "\n";
		print 'set:sloodle_courseid|'.$courseid;
		print "\n";
		print '</textarea>';
		print '<p>'. get_string("cfgnotecard:setnote", "sloodle") .'</p>';
		print '<p><a href="'.$CFG->wwwroot.'/admin/module.php?module=sloodle">'. get_string("backtosloodlesetup", "sloodle") .'</a>.';
		print '</div>';

	}

?>
