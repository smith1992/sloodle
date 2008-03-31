<?php
/*
Return a list of courses for the user
*/
	require_once('../config.php');
	require_once('../locallib.php');
	require_once('sl_authlib.php');

	sloodle_prim_require_script_authentication();

	sloodle_prim_require_user_login();

	$uuid = optional_param('uuid',null,PARAM_RAW); // TODO: Would be cleaner and safer to get this via the user object so it still works if we changed the implementation so it no longer passes as uuid arg...

	$data = array();
	$classestaught = $USER->teacher;

	$courses = get_my_courses($USER->id);
	foreach($courses as $c) {
	
		if (isteacher($c->id) || isadmin()) {
			$thiscourse = array(
				$uuid,
				$c->id,
				$c->shortname,
				$c->fullname,
				$classestaught[$c->id]
			);
			$data[] = $thiscourse;
		}
	}

	sloodle_prim_render_output($data);

?>
