<?php
    /**
    * Sloodle object authorization linker.
    * Allows authorised objects in SL to delegate authorisation to other objects,
    *  or allows new objects in SL to initiate their own authorisation.
    * (Creates a new entry in the 'sloodle_active_object' DB table.)
    *
    * @package sloodleclassroom
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    // If fully authorising a new object ('delegating' trust),
    //  then the following parameters are required:
    //
    //  sloodlecontrollerid = the ID of the controller through which the current object may access Sloodle
    //  sloodlepwd = the prim password or object-specific session key to authenticate access
    //  sloodleobjuuid = the UUID of the object being authorised
    //  sloodleobjname = the name of the object being authorised
    //  sloodleobjpwd = a password for the new object
    //  sloodleuuid = the UUID of the agent requesting object authorisation
    //  sloodleavname = the name of the avatar requesting object authorisation
    //
    // The following parameters are optional:
    //
    //  sloodleobjtype = the type identifier for the object being authorised. Can be overridden later.
    //
    // With the above information, a new entry is made, indicating that the object is fully authorised.
    // The new object can ONLY be authorised against the controller the request is received on.
    // If successful, the status code returned is 1, and the data line will contain the UUID of the object being authorised.
    
    // If an object needs the user to perform web-authorisation, then it can create an unauthorised entry.
    // To do this, the following parameters are required:
    //
    //  sloodleobjuuid = the UUID of the object being authorised
    //  sloodleobjname = the name of the object being authorised
    //  sloodleobjpwd = a new password for the object (NOT including its UUID)
    //
    // The following parameter is optional:
    //
    //  sloodleobjtype = the type identifier for the object. Can be overridden later.
    //
    // With this information, a new entry is made which is not linked to a particular user account.
    // As such, the entry is deemed 'unauthorised' and cannot be used until authorised.
    // If successful, status code 1 is returned, and the ID of the active object entry is returned on the data line.
    // The object should use this to build a URL to send the user to Sloodle for manual object authorisation.
    // Unauthorised entries will expire within 5 minutes and be deleted.
    
    
    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // We want to check if the request is authenticated,
    //  and also to see if the user can be identified.
    // However, we do not need to terminate if neither is given.
    $sloodle = new SloodleSession();
    $request_auth = $sloodle->authenticate_request(false);
    $user_auth = $sloodle->validate_user(false, true, true);
    
    // Get the extra parameters
    $sloodleobjuuid = $sloodle->request->required_param('sloodleobjuuid');
    $sloodleobjname = $sloodle->request->required_param('sloodleobjname');
    $sloodleobjpwd = $sloodle->request->required_param('sloodleobjpwd');
    $sloodleobjtype = $sloodle->request->optional_param('sloodleobjtype', '');
    
    // If the request was authenticated, then the object is being fully authorised.
    // Otherwise, it is simply a 'pending' authorisation.
    if ($request_auth) {
        // Make sure the user is authenticated too
        if ($user_auth) {
            // Authorise the object on the controller
            if ($sloodle->course->controller->register_object($sloodleobjuuid, $sloodleobjname, $sloodle->user, $sloodleobjpwd, $sloodleobjtype)) {
                $sloodle->response->set_status_code(1);
                $sloodle->response->set_status_descriptor('OK');
                $sloodle->response->add_data_line($sloodleobjuuid);
            } else {
                $sloodle->response->set_status_code(-201);
                $sloodle->response->set_status_descriptor('OBJECT_AUTH');
                $sloodle->response->add_data_line('Failed to register new active object.');
            }
        } else {
            $sloodle->response->set_status_code(-212);
            $sloodle->response->set_status_descriptor('OBJECT_AUTH');
            $sloodle->response->add_data_line('Expected avatar data.');
        }
    } else {
        // Create a new unauthorised entry
        $authid = $sloodle->course->controller->register_unauth_object($sloodleobjuuid, $sloodleobjname, $sloodleobjpwd, $sloodleobjtype);
        if ($authid != 0) {
            $sloodle->response->set_status_code(1);
            $sloodle->response->set_status_descriptor('OK');
            $sloodle->response->add_data_line($authid);
        } else {
            $sloodle->response->set_status_code(-201);
            $sloodle->response->set_status_descriptor('OBJECT_AUTH');
            $sloodle->response->add_data_line('Failed to register new active object.');
        }
    }
    
    // Render the output
    sloodle_debug('<pre>');
    $sloodle->response->render_to_output();
    sloodle_debug('</pre>');

?>