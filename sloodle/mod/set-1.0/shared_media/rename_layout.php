<?php
/** Grab the Sloodle/Moodle configuration. */
require_once('../../../sl_config.php');
/** Include the Sloodle PHP API. */
/** Sloodle core library functionality */
require_once(SLOODLE_DIRROOT.'/lib.php');
/** General Sloodle functions. */
require_once(SLOODLE_LIBROOT.'/io.php');
/** Sloodle course data. */
require_once(SLOODLE_LIBROOT.'/course.php');
require_once(SLOODLE_LIBROOT.'/layout_profile.php');
require_once(SLOODLE_LIBROOT.'/user.php');

require_once '../../../lib/json/json_encoding.inc.php';

// TODO: What should this be? Probably not 1...
$course_context = get_context_instance( CONTEXT_COURSE, 1);
$can_use_layouts = has_capability('mod/sloodle:uselayouts', $course_context);
if (!$can_use_layouts) {
	exit;
}

$layoutid = optional_param('layoutid', 0, PARAM_INT);
$layoutname = optional_param('layoutname', 0, PARAM_TEXT);

if (!$layoutid) {
	error_output( 'Layout ID missing');
}

$layout = new SloodleLayout();
if (!$layout->load( $layoutid )) {
	error_output('Could not load layout');
}

$layout->name = $layoutname;
if (!$layout->update()) {
	error_output('Could not save layout');
}

$content = array(
	'result' => 'renamed',
	'layoutname' => $layoutname, // TODO: Get this from the object_configs
	'layoutid' => $layoutid,
);

print json_encode($content);
exit;

function error_output($error) {
	$content = array(
		'result' => 'failed',
		'error' => $error,
	);
	print json_encode($content);
	exit;
}
?>
