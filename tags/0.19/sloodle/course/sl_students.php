<?php
/*
Return a list of courses the user is signed up for.
If courseid argument is passed, restrict to that course.
*/
	require_once('../config.php');
	require_once('../locallib.php');
	require_once('../login/sl_authlib.php');

	//sloodle_prim_require_script_authentication();

	$courseid = optional_param('courseid',null,PARAM_RAW); 

	$data = array();

/// Get all existing students and teachers for this course.
    if (!$students = get_course_students($courseid, "u.firstname ASC, u.lastname ASC", "", 0, 99999, '', '', NULL, '', 'u.id,u.firstname,u.lastname,u.email')) {
		$students = array();
	}

	foreach($students as $s) {
		$su = sloodle_get_sloodle_user_for_moodle_user($s);
		$avname = '';
		$avuuid = '';
		if ($su != null) {
			$avname = $su->avname;
			$avuuid = $su->avuuid;
		}
		$data[] = array($s->id,$s->firstname,$s->lastname,$avuuid,$avname);;
	}

	sloodle_prim_render_output($data);

?>
