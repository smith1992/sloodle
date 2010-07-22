<?php
    /**
    * SLOODLE for Schools.
    * This script allows an avatar to be added to the system (linked to a VLE user) remotely.
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
    //  sloodleavname = name of the avatar to add to the database
    //  sloodleuuid = UUID of the avatar to add to the database
    //  idnumber = 'ID number' uniquely identifying the VLE user (this is NOT a database primary key... it is a portable unique identifier, such as education number)
    //
    // The following parameters are optional:
    //
    //  override = (true or false) specifies that any existing avatar for the VLE user will be overridden, and that if the avatar already exists in the database it will first be deleted
    
    // Note: normally an error will be given if the target VLE user already has an avatar associated with him/her,
    //  or if the avatar being added is already associated with some other VLE user.
    // By specifiying the 'override' parameter as 'true', the conflicting avatar data (if applicable) will be deleted first.
    
    
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
    $sloodleavname = sloodle_clean_for_db($sloodle->request->get_avatar_name(true)); // Avatar name
    $sloodleuuid = sloodle_clean_for_db($sloodle->request->get_avatar_uuid(true)); // Avatar UUID
    $idnumber = sloodle_clean_for_db(trim($sloodle->request->required_param('idnumber'))); // Uniquely identifies the VLE user
    $override = (bool)optional_param('override', false); // Should the avatar be forced into the system?
    
    // Make sure the ID number isn't blank (or space-filled)
    if (empty($idnumber))
    {
        $sloodle->response->set_status_code(-811);
        $sloodle->response->set_status_descriptor('USER_AUTH');
        $sloodle->response->add_data_line('EMPTY_ID_NUMBER');
        $sloodle->response->render_to_output();
        exit();
    }
    
    // Can we find the specified user?
    $vleUserRecs = get_records('user', 'idnumber', $idnumber);
    if (!$vleUserRecs)
    {
        // No - we cannot continue
        $sloodle->response->set_status_code(-301);
        $sloodle->response->set_status_descriptor('USER_AUTH');
        $sloodle->response->add_data_line('USER_NOT_FOUND');
        $sloodle->response->render_to_output();
        exit();
    }
    
    // Did we find multiple users with a matching ID number?
    if (count($vleUserRecs) > 1)
    {
        // Yes - we cannot continue
        $sloodle->response->set_status_code(-301);
        $sloodle->response->set_status_descriptor('USER_AUTH');
        $sloodle->response->add_data_line('MULTIPLE_MATCHING_USERS_FOUND');
        $sloodle->response->render_to_output();
        exit();
    }
    
    // Grab the only user record
    $vleUserRec = current($vleUserRecs);
    
    // Are one or more avatars already associated with the user?
    $existingAvatarRecs = get_records('sloodle_users', 'userid', (int)$vleUserRec->id);
    if ($existingAvatarRecs)
    {
        // Yes - report an error, or delete the existing avatar(s), as applicable
        if (!$override)
        {
            $sloodle->response->set_status_code(-301);
            $sloodle->response->set_status_descriptor('USER_AUTH');
            $sloodle->response->add_data_line('USER_ALREADY_HAS_AVATAR');
            $sloodle->response->render_to_output();
            exit();
        }
        delete_records('sloodle_users', 'userid', (int)$vleUserRec->id);
    }
    
    // Does the avatar already exist in the database?
    $newAvatarRecs = get_records('sloodle_users', 'uuid', $sloodleuuid);
    if ($newAvatarRecs)
    {
        // Yes - report an error, or delete them, as applicable
        if (!$override)
        {
            $sloodle->response->set_status_code(-301);
            $sloodle->response->set_status_descriptor('USER_AUTH');
            $sloodle->response->add_data_line('AVATAR_ALREADY_EXISTS');
            $sloodle->response->render_to_output();
            exit();
        }
        delete_records('sloodle_users', 'uuid', $sloodleuuid);
    }
    
    // Add our new avatar data to the database
    $rec = new stdClass;
    $rec->userid = (int)$vleUserRec->id;
    $rec->uuid = $sloodleuuid;
    $rec->avname = $sloodleavname;
    $rec->id = insert_record('sloodle_users', $rec);
    
    // Did an error occur?
    if (!$rec)
    {
        // Yes - report it
        $sloodle->response->set_status_code(-301);
        $sloodle->response->set_status_descriptor('USER_AUTH');
        $sloodle->response->add_data_line('FAILED_TO_ADD_AVATAR');
        $sloodle->response->render_to_output();
        exit();
    }
    
    // Success!
    $sloodle->response->set_status_code(103);
    $sloodle->response->set_status_descriptor('SYSTEM');
    $sloodle->response->add_data_line('AVATAR_ADDED');
    $sloodle->response->add_data_line($rec->id);
    $sloodle->response->render_to_output();
    
?>