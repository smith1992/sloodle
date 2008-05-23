<?php
    // This script is part of the Sloodle project

    /**
    * Registration and enrolment linker script.
    * Allows scripts in-world to initiate manual registration and enrolment.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    /*
    * The following parameters are required:
    *
    *  sloodlecontrollerid = the ID of the controller to connect to
    *  sloodlepwd = password for authentication (either a prim password or an object-specific session key)
    *  sloodlemode = indicates the mode: "reg", "enrol", or "regenrol"
    *  sloodleuuid = UUID of the avatar
    *  sloodleavname = name of the avatar
    *
    * If successful, the status code returned will be 1 and the data line will contain a URL to forward the user to.
    * If nothing needs done because the user is already registered, then status code 301 is returned.
    * If nothing needs done because the user is already enrolled, then status code 401 is returned.
    * If the user cannot be enrolled because they are not yet registered, then status code -321.
    */
    
    
    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Authenticate the request
    $sloodle = new SloodleSession();
    $sloodle->authenticate_request();
    // Attempt to authenticate the user, but do not allow auto-registration/enrolment
    $sloodle->validate_user(false, true, true);
    $is_registered = $sloodle->user->is_user_loaded();
    $is_enrolled = false;
    if ($is_registered) {
        $is_enrolled = $sloodle->user->is_enrolled($sloodle->course->get_course_id());
    }
    
    // Make sure UUID and avatar name were specified
    $sloodleuuid = $sloodle->request->get_avatar_uuid(TRUE);
    $sloodleavname = $sloodle->request->get_avatar_name(TRUE);
    // Get the mode value
    $sloodlemode = $sloodle->request->required_param('sloodlemode');
    
    // If the mode is 'regenrol', but the user is already registered,
    //  then just do enrolment
    if ($sloodlemode == 'regenrol' && $is_registered) $sloodlemode = 'enrol';
    
    // What mode has been requested?
    switch ($sloodlemode)
    {
    case 'reg': case 'regenrol':
        // Is the user already registered?
        if ($is_registered) {
            $sloodle->response->set_status_code(301);
            $sloodle->response->set_status_descriptor('MISC_REGISTER');
        } else {
            // Add a pending avatar
            $pa = $sloodle->user->add_pending_avatar($sloodleuuid, $sloodleavname);
            if (!$pa) {
                $sloodle->response->set_status_code(-322);
                $sloodle->response->set_status_descriptor('MISC_REGISTER');
                $sloodle->response->add_data_line('Failed to add pending avatar details.');
            } else {
                // Construct and return a registration URL
                $url = SLOODLE_WWWROOT."/login/sl_welcome_reg.php?sloodleuuid=$sloodleuuid&sloodlelst={$pa->lst}";
                if ($sloodlemode == 'regenrol') $url .= '&sloodlecourseid='.$sloodle->course->get_course_id();                
                $sloodle->response->set_status_code(1);
                $sloodle->response->set_status_descriptor('OK');
                $sloodle->response->add_data_line($url);
            }
        }
        break;
        
    case 'enrol':
        // Is the user registered?
        if (!$is_registered) {
            $sloodle->response->set_status_code(-321);
            $sloodle->response->set_status_descriptor('MISC_REGISTER');
            $sloodle->response->add_data_line('Enrolment failed -- user is not yet registered.');
        } else {
            // Is the user already enrolled?
            if ($is_enrolled) {
                $sloodle->response->set_status_code(401);
                $sloodle->response->set_status_descriptor('MISC_ENROL');
            } else {
                // Construct and return an enrolment URL
                $sloodle->response->set_status_code(1);
                $sloodle->response->set_status_descriptor('OK');
                $sloodle->response->add_data_line("{$CFG->wwwroot}/course/enrol.php?id=".$sloodle->course->get_course_id());
            }
        }        
        break;
        
        
    default:
        $sloodle->response->set_status_code(-811);
        $sloodle->response->set_status_descriptor('REQUEST');
        $sloodle->response->add_data_line("Mode '$sloodlemode' unrecognised.");
        break;
    }
    
    
    // Render the response
    $sloodle->response->render_to_output();
    exit();
?>

<?php
////////// OLD CODE //////////
    exit();
    /**
    * Sloodle registration booth linker script
    *
    * Allows a registration booth in Second Life to setup an SL-Moodle user registration process
    *
    * @package sloodlelogin
    * @copyright Copyright (c) 2007 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */


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
// Two success status codes: 1 (meaning user has been newly registered), 301 (user was already fully registered)

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

// We will return a URL which the registration booth will forward the user to
sloodle_debug_output("Constructing user authentication URL...<br>");
$auth_url = SLOODLE_WWWROOT."/login/sl_welcome_reg.php?";
$auth_url .= "sloodleuuid={$lsl->user->sloodle_user_cache->uuid}";
$auth_url .= "&sloodleavname={$lsl->user->sloodle_user_cache->avname}";
$auth_url .= "&sloodlelst={$lsl->user->sloodle_user_cache->loginsecuritytoken}";
sloodle_debug_output("Authentication URL: $auth_url");

sloodle_debug_output("Outputting response...<br>");

// Get the Sloodle user cache
$sloodle_user = $lsl->user->sloodle_user_cache;
// Output the token (note that the request object will already have set the user's UUID in the response object... cunning, eh? :-) )
// The status code depends on whether or not the user is already fully registered
if ($has_moodle_account) $lsl->response->set_status_code(301);
else $lsl->response->set_status_code(1);
$lsl->response->set_status_descriptor('USER_REG');
//$lsl->response->add_data_line($sloodle_user->loginsecuritytoken);
$lsl->response->add_data_line($auth_url); // We'll just output the whole entire URL
sloodle_debug_output("<pre>");
$lsl->response->render_to_output();
sloodle_debug_output("</pre>");

exit();
?>