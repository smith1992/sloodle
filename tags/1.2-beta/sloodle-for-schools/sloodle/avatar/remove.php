<?php
    /**
    * SLOODLE for Schools.
    * This script allows an avatar to be removed from the database.
    * Note that it will remove all instances of the avatar if multiple records exist (although this should not happen under normal operation).
    * Alternatively, it can be used to remove any avatar data associated with a particular user.
    *
    * @package sloodle
    * @copyright Copyright (c) 2010 (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    // This script should be called with the administration token in the header.
    // It also requires one of the following HTTP parameters (GET or POST):
    //
    //  sloodleuuid = UUID of the avatar to remove from the database
    //  idnumber = the ID number of the VLE user whose avatar(s) you want to remove
    //
    // If both parameters are provided then an error will be reported.
    // Note that if the idnumber parameter matches multiple users, then all matching users' avatars will be deleted.
    
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
    $sloodleuuid = sloodle_clean_for_db(optional_param('sloodleuuid', ''));
    $idnumber = sloodle_clean_for_db(optional_param('idnumber', ''));
    
    // Are both parameters missing?
    if (empty($sloodleuuid) && empty($idnumber))
    {
        $sloodle->response->set_status_code(-811);
        $sloodle->response->set_status_descriptor('REQUEST');
        $sloodle->response->add_data_line('Required parameter not found. Expected either \'sloodleuuid\' or \'idnumber\'.');
        $sloodle->response->render_to_output();
        exit();
    }
    
    // Are both parameters specified?
    if (!empty($sloodleuuid) && !empty($idnumber))
    {
        $sloodle->response->set_status_code(-811);
        $sloodle->response->set_status_descriptor('REQUEST');
        $sloodle->response->add_data_line('Too many parameters. Expected either \'sloodleuuid\' or \'idnumber\'. (Not both!)');
        $sloodle->response->render_to_output();
        exit();
    }
    
    
    // We want to report the number of avatars deleted
    $delCount = 0;
    // Which mode are we in?
    if (!empty($sloodleuuid))
    {
        // Delete all avatars with the specified UUID
        $numRecs = (int)count_records('sloodle_users', 'uuid', $sloodleuuid);
        $del = delete_records('sloodle_users', 'uuid', $sloodleuuid);
        if ($del) $delCount += $numRecs;
    
    } else if (!empty($idnumber))
    {
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
        
        // Go through each user record
        foreach ($vleUserRecs as $vleUserRec)
        {
            // Delete all associated avatars
            $numRecs = (int)count_records('sloodle_users', 'userid', (int)$vleUserRec->id);
            $del = delete_records('sloodle_users', 'userid', (int)$vleUserRec->id);
            if ($del) $delCount += $numRecs;
        }
    }
    
    $sloodle->response->set_status_code(1);
    $sloodle->response->set_status_descriptor('OK');
    $sloodle->response->add_data_line('AVATARS_DELETED');
    $sloodle->response->add_data_line($delCount);
    $sloodle->response->render_to_output();
    
?>