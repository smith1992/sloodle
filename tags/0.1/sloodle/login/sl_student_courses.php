<?php
/*
Return a list of courses the user is signed up for.
If courseid argument is passed, restrict to that course.
*/
	require_once('../config.php');
	require_once('../locallib.php');
	require_once('sl_authlib.php');

	sloodle_prim_require_script_authentication();

	sloodle_prim_require_user_login();

	$uuid = optional_param('uuid',null,PARAM_RAW); // TODO: Would be cleaner and safer to get this via the user object so it still works if we changed the implementation so it no longer passes as uuid arg...

	$courseid = optional_param('courseid',null,PARAM_RAW); 

	/* type values: 
		enrolled - courses user is enrolled in only
		unenrolled - courses user is not enrolled in only
		enrollable - courses user can enrol in (ie. not already enrolled, but open to enrollment.
		all - all visible courses, enrolled/enrollable or not.
	*/
	$type = optional_param('type',null,PARAM_RAW); 

	$data = array();

	$courses = array();
	$mycourses = array();

	$returncourses = array();

	$mycourses = get_my_courses($USER->id);
	$mycourseids = array();
	foreach($mycourses as $mc) {
		$mycourseids[] = $mc->id;
	}

	$courses = get_courses();

	foreach($courses as $c) {
		if ( ($courseid == null) || ($c->id == $courseid) ) { // filter by courseid arg, if there is one.

			$isenrolled = (in_array($c->id,$mycourseids));
			$forbidsenrollment = (!$c->enrollable || ($c->enrollable == 2 && $c->enrolstartdate > 0 && $c->enrolstartdate > time()) || ($c->enrollable == 2 && $c->enrolenddate > 0 && $c->enrolenddate <= time())); // from course/enrol.php

			$status = null;
			if ($isenrolled) {
				$status = "enrolled";
			} else if ($forbidsenrollment) {
				$status = "unenrollable";
			} else {
				$status = "enrollable";
			}

			if ( ($type == "all") || ( ($type == "enrolled") && ($status == "enrolled") ) || ( ($type == "enrollable") && ($status == "enrollable") ) || ( ($type == "unenrolled") && ($status != "enrolled") ) ) {
				$thiscourse = array(
					$uuid,
					$c->id,
					$c->shortname,
					$c->fullname,
					'', // TODO: Put whether teacher or not here...
					$status
				);

				$data[] = $thiscourse;
			}

		}



	}

	if ($type == 'all') {
		$returncourses = $courses;
	} else if ($type == 'enrolled') {
		$returncourses = $mycourses;
	} else { // unenrolled or enrollable
		$mycourseids = array();
		foreach($mycourses as $mc) {
			$mycourseids[] = $mc->id;
		}
		foreach($courses as $c) {
			if (!in_array($c->id,$mycourseids)) { // ignore classes we're already enrolled in
				if ($type == 'unenrolled') {
					$returncourses[] = $c;
				} else if ($type == 'enrollable') {
					if (!(!$c->enrollable || ($c->enrollable == 2 && $c->enrolstartdate > 0 && $c->enrolstartdate > time()) || ($c->enrollable == 2 && $c->enrolenddate > 0 && $c->enrolenddate <= time()))) { // These checks were copied from course/enrol.php. Sorry about the confusing double-negative...
						$returncourses[] = $c;
					}
				}
			} 
		}
	}
	
	foreach($returncourses as $c) {
	}

	sloodle_prim_render_output($data);

?>
