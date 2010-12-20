<?php
// Simulates an ajax object-rezzing request

require_once '../../../lib/json/json_encoding.inc.php';

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
require_once(SLOODLE_LIBROOT.'/active_object.php');
require_once(SLOODLE_LIBROOT.'/user.php');

// TODO: We'll want to manage this info in a different way.
require_once('object_configs.php');

if (!$USER->id) {
	output( 'User not logged in' );
	exit;
}

$rezzer = new SloodleActiveObject();
$sloodleuser = new SloodleUser();
$sloodleuser->user_data = $USER;

if (!$controllerid  = optional_param('controllerid', 0, PARAM_INT)) {
	error_output( 'Controller ID missing' );
}

if ( !$rezzeruuid = optional_param('rezzeruuid', '', PARAM_SAFEDIR) ) {
	error_output( 'Rezzer UUID missing or incorrect' );
}

if ( !$rezzer->loadByUUID($rezzeruuid) ) {
	error_output( 'Controller ID missing' );
}

if ( ($rezzer->controllerid != $controllerid) || ($rezzer->userid != $USER->id) ) {
	$rezzer->controllerid = $controllerid;
	if (!$rezzer->save()) {
		error_output('Updating rezzer failed');
	}
	if (!$result = $rezzer->sendConfig()) {
		error_output('Sending config failed');
	}
	if ($result['info']['http_code'] == 404) {
		error_output('HTTP-in URL not found');
	}
}

$result = 'configured';

$content = array(
	'result' => $result,
	'error' => $error,
);

$rand = rand(0,10);
sleep($rand);

print json_encode($content);

function error_output($error) {
	$content = array(
		'result' => 'failed',
		'error' => $error,
	);
	print json_encode($content);
	exit;
}
?>
