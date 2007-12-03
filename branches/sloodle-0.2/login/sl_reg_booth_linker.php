<pre><?php
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
require_once('../sl_debug.php'); // Sloodle debug mode/functionality
require_once('../locallib.php'); // Sloodle local library functions
require_once('../lib/sl_iolib.php'); // Sloodle IO library
require_once('sl_authlib.php'); // Sloodle authentication library

sloodle_debug_output("Constructing request object...<br>");
// We want to handle the script request
$request = new SloodleLSLRequest();
sloodle_debug_output("Processing request data...<br>");
$request->process_request_data();
// First of all, make sure this is an authorised request
// (Unless we pass in FALSE, the script will be terminated if authentication fails)
sloodle_debug_output("Authenticating request...<br>");
$request->authenticate_request();

// Are there Sloodle/Moodle entries for the specified avatar?
sloodle_debug_output("Checking for a Sloodle user...<br>");
$has_sloodle_account = $request->find_sloodle_user();
sloodle_debug_output("Checking for a Moodle user...<br>");
$has_moodle_account = $has_sloodle_account && $request->find_moodle_user();

// If the user is already fully registered, then there's nothing more to do
if ($has_moodle_account) {
    sloodle_debug_output("User already fully registered...<br>");
    $response = $request->get_response();
    $response->set_status_code(301);
    $response->set_status_descriptor('MISC_REGISTER');
    $response->render_to_output();
    exit();
}

// Is the user completely un-registered?
if (!$has_sloodle_account) {
    sloodle_debug_output("User not registered with Sloodle. Registering...<br>");
    // Yes - a new Sloodle entry is required
    if (!$request->create_sloodle_entry(0)) {
        // Something went wrong
        sloodle_debug_output("Failed to register user with Sloodle...<br>");
        $response = $request->get_response();
        $response->set_status_code(-301);
        $response->set_status_descriptor('USER_REG');
        $response->add_data_line('Failed to add Sloodle user entry to database.');
        $response->render_to_output();
        exit();
    }
}

// Make sure there is a login security token specified for the Sloodle user
if (!$request->user_has_login_security_token()) {
    sloodle_debug_output("Generating a login security token...<br>");
    if (!$request->regenerate_login_security_token()) {
        $response = $request->get_response();
        $response->set_status_code(-301);
        $response->set_status_descriptor('USER_REG');
        $response->add_data_line('Failed to generate a new login security token for the user.');       
        $response->render_to_output();
        exit();
    }
}

sloodle_debug_output("Outputting response...<br>");

// Get the Sloodle user
$sloodle_user = $request->get_sloodle_user();
// Output the token (note that the request object will already have set the user's UUID in the response object... cunning, eh? :-) )
$response = $request->get_response();
$response->set_status_code(1);
$response->set_status_descriptor('USER_REG');
$response->add_data_line($sloodle_user->loginsecuritytoken);
$response->render_to_output();

exit();
?></pre>