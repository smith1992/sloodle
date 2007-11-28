<?php
// Sloodle registration booth linker script
// Allows a registration booth in Second Life to setup an SL-Moodle user registration process
// Part of the Sloodle Project (www.sloodle.org)
//
// Copyright (c) Sloodle 2007
// Released under the GNU GPL v3
//
// Contributors:
//  Edmuind Edgar (?) - original version
//  Peter R. Bloomfield - updated to use new communications API
//
//
// This script requires to be able to authenticate the object making the request, so the "sloodlepwd" parameter should be set.
// In order to register the avatar, the name and UUID are required, in parameters "sloodleavname" and "sloodleuuid".
//
// The database will be searched to see if the specified avatar has already been entered, and it will enter it if not.
// If the user is already associated with a Moodle account, then this will be reported by the HTTP response, and nothing further needs done.
// Otherwise, a login security code will be generated for the user if necessary, and will be returned to the requesting LSL script.
// The registration booth can use the security code and the avatar's UUID to construct a URL to a login page on the Moodle site.
// By following that link and entering their Moodle account details, the user can authenticate their avatar.
//

require_once('../config.php'); // Sloodle/Moodle configuration
require_once('../locallib.php'); // Sloodle local library functions
require_once('../sl_iolib'); // Sloodle communications library
require_once('sl_authlib.php'); // Sloodle authentication library

// We want to handle the script request
$request = new SloodleLSLRequest();
$request->process_request_data();
// First of all, make sure this is an authorised request
// (Unless we pass in FALSE, the script will be terminated if authentication fails)
$request->authenticate_request();

// Are there Sloodle/Moodle entries for the specified avatar?
$has_sloodle_account = $request->find_sloodle_user();
$has_moodle_account = $has_sloodle_account && $request->find_moodle_user();

// If the user is already fully registered, then there's nothing more to do
if ($has_moodle_account) {
    $response = $request->get_response();
    $response->set_status_code(301);
    $response->set_status_descriptor('MISC_REGISTER');
    $response->render_to_output();
    exit();
}

// Is the user completely un-registered?
if (!$has_sloodle_account) {
    // Yes - a new Sloodle entry is required
    $request->create_sloodle_entry(0);
} else {
    // Make sure there is a login-security token...
    //..
}

//... do stuff....



exit();

//OLD CODE:

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
