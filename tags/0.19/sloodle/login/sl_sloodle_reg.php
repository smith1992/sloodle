<?php
/*
	Parameters:
		avname
		uuid
	Register a user in sloodle only, using a security code
	Users registered here may turn up later and bring their avatar names and a security code with them.
	We'll use the security code to match up theirre_once('sl_authlib.php');
	Return the uuid and security code so that the prim can give them a link.
*/
require_once('../config.php');
require_once('../locallib.php');
require_once('sl_authlib.php');

$sloodleerrors = array();

sloodle_prim_require_script_authentication();

$avname = optional_param('avname',null,PARAM_RAW);
$uuid = optional_param('uuid',null,PARAM_RAW);
if ( ($avname == null) || ($uuid == null) ) {
	sloodle_prim_render_errors(array('necessary parameters missing'));
}
list($sloodleuser, $errors) = sloodle_prim_register_sloodle_only($avname,$uuid);

if ($sloodleuser == null) {
	sloodle_prim_render_errors($errors);
} else {
	$data = array(
		$sloodleuser->uuid,
		$sloodleuser->loginsecuritytoken
	);
	sloodle_prim_render_output($data);
}
?>
