<?php
    /**
    * SLOODLE for Schools.
    * This script allows a remote system to check if an avatar is already registered to a VLE user.
    *
    * @package sloodle
    * @copyright Copyright (c) 2010 (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    // This script should be called with the administration token in the header.
    // It also requires the following HTTP parameters (GET or POST):
    //
    //  sloodleuuid = UUID of the avatar to check
    //
    
    
    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Verify that this request is coming from a legitimate source
    $sloodle = new SloodleSession();
    $sloodle->authenticate_admin_request();
    
    // Fetch the expected data
    $sloodleuuid = sloodle_clean_for_db($sloodle->request->get_avatar_uuid(true)); // Avatar UUID
    
    // Can we find any matching avatars?
    $existingAvatarRecs = get_records('sloodle_users', 'uuid', $sloodleuuid);
    if (!$existingAvatarRecs)
    {
        // No matching avatar found
        $sloodle->response->set_status_code(1);
        $sloodle->response->set_status_descriptor('OK');
        $sloodle->response->add_data_line('AVATAR_NOT_FOUND');
        $sloodle->response->render_to_output();
        exit();
    }
    if (count($existingAvatarRecs) > 1)
    {
        // Multiple matching avatars found
        $sloodle->response->set_status_code(1);
        $sloodle->response->set_status_descriptor('OK');
        $sloodle->response->add_data_line('MULTIPLE_AVATARS_FOUND');
        $sloodle->response->render_to_output();
        exit();
    }
    
    // Just one avatar found - try to locate its associated user record
    $avatarRec = current($existingAvatarRecs);
    $userRec = get_record('user', 'id', (int)$avatarRec->userid); // DB schema makes this 'id' field unique
    if (!$userRec)
    {
        // No associated user found
        $sloodle->response->set_status_code(1);
        $sloodle->response->set_status_descriptor('OK');
        $sloodle->response->add_data_line('AVATAR_FOUND');
        $sloodle->response->add_data_line('USER_NOT_FOUND');
        $sloodle->response->render_to_output();
        exit();
    }
    
    // Output the user's ID number
    $sloodle->response->set_status_code(1);
    $sloodle->response->set_status_descriptor('OK');
    $sloodle->response->add_data_line('AVATAR_FOUND');
    $sloodle->response->add_data_line($userRec->idnumber);
    $sloodle->response->render_to_output();
    
?>