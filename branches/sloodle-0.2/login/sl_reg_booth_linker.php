<?php
// Sloodle registration booth linker script
// Allows a registration booth in Second Life to setup an SL-Moodle user registration process
// Part of the Sloodle Project (www.sloodle.org)
//
// Copyright (c) Sloodle 2007
// Released under the GNU GPL v3
//
// Contributors:
//  Edmund Edgar - original version
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
require_once('../sl_debug.php'); // Sloodle debug mode/functionality
require_once('../lib/sl_lsllib.php'); // Sloodle LSL library


// Construct our LSL handler
sloodle_debug_output("Constructing LSL handler...<br>");
$lsl = new SloodleLSLHandler();
// Process the request data
sloodle_debug_output("Processing request data...<br>");
$lsl->request->process_request_data();
// Authenticate the request
// (Unless we pass in FALSE, the script will be terminated if authentication fails)
sloodle_debug_output("Authenticating request...<br>");
$lsl->request->authenticate_request();

// Ensure that the avatar UUID and name were both provided
// (Note: due to cunning request processing trickery, if the is already fully registered with Sloodle,
//  and only UUID OR name was provided, then the missing value will have been filled in)
if (is_null($lsl->request->get_avatar_uuid())) {
    $lsl->response->set_status_code(-311);
    $lsl->response->set_status_descriptor('USER_REG');
    $lsl->response->add_data_line('Avatar UUID not provided.');
    $lsl->response->render_to_output();
    exit();
}
if (is_null($lsl->request->get_avatar_name())) {
    $lsl->response->set_status_code(-311);
    $lsl->response->set_status_descriptor('USER_REG');
    $lsl->response->add_data_line('Avatar name not provided.');
    $lsl->response->render_to_output();
    exit();
}

// Do we have a Sloodle account already?
sloodle_debug_output("Checking for a Sloodle user...<br>");
$has_sloodle_account = ($lsl->user->get_sloodle_user_id() > 0);
// Note that, if the user already has a Moodle account, we will still output the security token
// The reason for this is to allow a change of account link
sloodle_debug_output("Checking for a Moodle user...<br>");
$has_moodle_account = $has_sloodle_account && ($lsl->user->get_moodle_user_id() > 0);


// Is the user completely un-registered?
if (!$has_sloodle_account) {
    sloodle_debug_output("User not registered with Sloodle. Registering...<br>");
    // Yes - a new Sloodle entry is required
    $result = $lsl->user->create_sloodle_user($lsl->request->get_avatar_uuid(), $lsl->request->get_avatar_name());
    if ($result !== TRUE) {
        // Something went wrong
        sloodle_debug_output("Failed to register user with Sloodle...<br>");
        $lsl->response->set_status_code(-301);
        $lsl->response->set_status_descriptor('USER_REG');
        $lsl->response->add_data_line('Failed to add Sloodle user entry to database.');
        if (is_string($result)) $lsl->response->add_data_line($result);
        $lsl->response->render_to_output();
        exit();
    }
}

// Make sure there is a login security token specified for the Sloodle user
if (!$lsl->user->has_login_security_token()) {
    sloodle_debug_output("Generating a login security token...<br>");
    if (!$lsl->user->regenerate_login_security_token()) {
        $lsl->response->set_status_code(-301);
        $lsl->response->set_status_descriptor('USER_REG');
        $lsl->response->add_data_line('Failed to generate a new login security token for the user.');       
        $lsl->response->render_to_output();
        exit();
    }
}

sloodle_debug_output("Outputting response...<br>");

// Get the Sloodle user cache
$sloodle_user = $lsl->user->sloodle_user_cache;
// Output the token (note that the request object will already have set the user's UUID in the response object... cunning, eh? :-) )
// The status code depends on whether or not the user is already fully registered
if ($has_moodle_account) $lsl->response->set_status_code(301);
else $lsl->response->set_status_code(1);
$lsl->response->set_status_descriptor('USER_REG');
$lsl->response->add_data_line($sloodle_user->loginsecuritytoken);
$lsl->response->render_to_output();

exit();
?>