<?php
    // This script is part of the Sloodle project

    /*
    * This script is intended to be shown on the surface of the scoreboard.
    *
    */ 

    /**
    * @package sloodle
    * @copyright Copyright (c) 2011 various contributors (see below)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Paul Preibisch
	*
	*/

	/** Grab the Sloodle/Moodle configuration. */
	require_once('../../../sl_config.php');
	/** Include the Sloodle PHP API. */
	/** Sloodle core library functionality */
	require_once(SLOODLE_DIRROOT.'/lib.php');
	/** General Sloodle functions. */
	require_once(SLOODLE_LIBROOT.'/general.php');
	/** Sloodle course data. */
	require_once(SLOODLE_LIBROOT.'/course.php');

	require_once(SLOODLE_LIBROOT.'/object_configs.php');
	require_once(SLOODLE_LIBROOT.'/active_object.php');

 	require_once('scoreboard_active_object.inc.php');

        $object_uuid = required_param('sloodleobjuuid');
        $sao = SloodleScoreboardActiveObject::ForUUID( $object_uuid );

        $is_logged_in = isset($USER) && ($USER->id > 0);
        $is_admin = $is_logged_in && has_capability('moodle/course:manageactivities', $sao->context);


	$student_scores = $sao->get_student_scores($include_scoreless_users = $is_admin);
	
	$full = false; 

/*
header('Cache-control: public');
header('Cache-Control: max-age=86400');
header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 60 * 60 * 24) . ' GMT');
header('Pragma: public');
*/

	include('index.template.php');

	print_html_top('', $is_logged_in);
	print_toolbar( $baseurl, $is_logged_in );

	print_site_placeholder( $sitesURL );
//	print_round_list( $roundrecs );
	print_score_list( "All groups", $student_scores, $object_uuid, $sao->currencyid, $sao->roundid, $sao->refreshtime, $is_logged_in, $is_admin); 

	print_html_bottom();

?>