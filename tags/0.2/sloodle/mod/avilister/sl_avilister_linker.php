<?php
    // Sloodle AviLister linker script
    // Allows a tool in-world to fetch a list of avatars' associated Moodle names
    // Part of the Sloodle project (www.sloodle.org)
    //
    // Copyright (c) 2007 Sloodle
    // Released under the GNU GPL
    //
    // Contributors:
    //   Jeremy Kemp (and others?) - original design and implementation
    //   Peter R. Bloomfield - rewritten and expanded for Sloodle 0.2 (using new API/comms. format, and added list mode)
    //
    
    // This script is expected to be called by in-world Sloodle objects.
    // The following parameters are required:
    //
    //   sloodlepwd = prim password for authentication
    //
    // The following parameters are required for certain modes:
    //
    //   sloodlecourseid = the course for which users are being retrieved
    //   sloodleuuid = UUID of an avatar
    //   sloodleavname = name of an avatar
    //
    //
    // The script operates in two modes: lookup, and list
    // If 'sloodleuuid' or 'sloodleavname' is specified, then lookup mode is assumed.
    // In this mode, the script will fetch the data for that user only.
    //
    // In list mode, the script will fetch data for all users in a particular course.
    // This requires that 'sloodlecourseid' is specified.
    
    // In either mode, the script will return status code 1 on success, and the following on each data line:
    //  "<avatar_name>|<moodle_name>|<online>"
    // (The <online> parameter is either 1 or 0, indicating whether or not that user is currently online in SL)
    // Lookup mode returns 1 entry, whereas list mode may return many
    
    require_once('../../config.php');
    require_once(SLOODLE_DIRROOT.'/sl_debug.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');
    
    sloodle_debug_output('<br/>');
    
    // Create an LSL handler and process the basic request data
    sloodle_debug_output('Creating LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output('Processing basic request data...<br/>');
    $lsl->request->process_request_data();
    
    // Ensure the request is authenticated
    sloodle_debug_output('Authenticating request...<br/>');
    $lsl->request->authenticate_request();
    
    // Are we in list mode?
    if (is_null($lsl->request->get_avatar_name()) && is_null($lsl->request->get_avatar_uuid())) {
        // List mode
        sloodle_debug_output('***** List Mode *****<br/>');
        // Make sure the course ID was specified
        if ($lsl->request->get_course_id() == NULL) {
            sloodle_debug_output('ERROR: no parameters specified.<br/>');
            $lsl->response->set_status_code(-811);
            $lsl->response->set_status_descriptor('REQUEST');
            $lsl->response->add_data_line('Expected avatar UUID and name, or course ID.');
        } else {
            // Everything seems OK... fetch a list of users in the course
            sloodle_debug_output('Fetching list of users in course...<br/>');
            $courseusers = get_course_users($lsl->request->get_course_id());
            // Construct the response header
            $lsl->response->set_status_code(1);
            $lsl->response->set_status_descriptor('OK');
            // Go through each one
            $user = new SloodleUser();
            sloodle_debug_output('Iterating through list of users...<br/>');
            foreach ($courseusers as $u) {
                // Attempt to fetch the Sloodle data for this Moodle user
                $user->set_moodle_user_id($u->id);
                if ($user->find_linked_sloodle_user() !== TRUE) continue;
                // Add the user to the response
                $lsl->response->add_data_line(array($user->sloodle_user_cache->avname, $u->firstname.' '.$u->lastname, $user->sloodle_user_cache->online));
            }
        }
        
    } else {
        // We are in lookup mode
        sloodle_debug_output('***** Lookup Mode *****<br/>');
        // Attempt to login the Sloodle user to get their info (but suppress auto-registration)
        $lsl->login_by_request(TRUE, TRUE);
        
        // Add the user data to the response
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
        $lsl->response->add_data_line(array($lsl->user->sloodle_user_cache->avname, $lsl->user->moodle_user_cache->firstname.' '.$lsl->user->moodle_user_cache->lastname, $lsl->user->sloodle_user_cache->online));
    }
    
    // Output the response
    sloodle_debug_output('Outputting response...<br/>');
    sloodle_debug_output('<pre>');
    $lsl->response->render_to_output();
    sloodle_debug_output('</pre>');
    
    exit();
?>