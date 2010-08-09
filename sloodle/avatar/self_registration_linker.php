<?php

    /**
    * Registration and enrolment linker script, modified for the SLOODLE for Schools project.
    * Allows the web-portal to initiate an avatar registration.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007-10 SLOODLE Community (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    /*
    * This script requires the the administration token is present in the HTTP headers.
    * The following GET or POST parameters are also required:
    *
    *  sloodleuuid = UUID of the avatar
    *  sloodleavname = name of the avatar
    *
    * If successful, the status code returned will be 1 and the data line will contain a URL to forward the user to.
    * If nothing needs done because the user is already registered, then status code 301 is returned.
    * Other status codes may be returned to indicate an error.
    */
    
    
    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Authenticate the request
    $sloodle = new SloodleSession();
    $sloodle->authenticate_admin_request();
    // Attempt to authenticate the user to check whether or not the avatar is already registered.
    $sloodle->validate_user(false);
    $is_registered = $sloodle->user->is_user_loaded();
    
    // Make sure UUID and avatar name were specified
    $sloodleuuid = $sloodle->request->get_avatar_uuid(true);
    $sloodleavname = $sloodle->request->get_avatar_name(true);
    
    // Unlike the conventional SLOODLE version of this script, we only do registration here.
    // Enrolment has been stripped out.
    
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
            $url = SLOODLE_WWWROOT."/login/sl_welcome_reg.php?sloodleuuid={$sloodleuuid}&sloodlelst={$pa->lst}";
            $sloodle->response->set_status_code(1);
            $sloodle->response->set_status_descriptor('OK');
            $sloodle->response->add_data_line($url);
        }
    }
    
    // Render the response
    $sloodle->response->render_to_output();
    exit();
?>
