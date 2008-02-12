<?php
    /**
    * Sloodle AviLister linker script.
    *
    * Allows a tool in-world to fetch avatars' associated Moodle names.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Jeremy Kemp (and others?)
    * @contributor Peter R. Bloomfield - rewritten and expanded for Sloodle 0.2 (using new API/comms. format, and added list mode)
    *
    */
    
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
    //   sloodleavnamelist = a list of avatar names, separated by pipe characters
    //
    //
    // The script operates in 3 modes: single-lookup, multi-lookup, and list
    // If 'sloodleuuid' or 'sloodleavname' is specified, then single-lookup mode is assumed.
    // In this mode, the script will fetch the data for that user only.
    //
    // Alternatively, if 'sloodleavnamelist' is specified, then multi-lookup mode is assumed.
    // It will attempt to fetch data for each user in the list (but will ignore unrecognised names).
    //
    // In list mode, the script will fetch data for all users in a particular course.
    // This requires that 'sloodlecourseid' is specified.
    
    // In either mode, the script will return status code 1 on success, and the following on each data line:
    //  "<avatar_name>|<moodle_name>"
    // (The <online> parameter is either 1 or 0, indicating whether or not that user is currently online in SL)
    // Single lookup mode returns 1 entry, whereas multi-lookup and list modes may return many
    //
    // Note: data will *not* be given for Moodle users who have no avatar, nor avatars not linked to a Moodle user
    
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
    
    // Obtain additional parameters
    sloodle_debug_output('Obtaining additional parameters...<br/>');
    $sloodleavnamelist = optional_param('sloodleavnamelist', NULL, PARAM_RAW);
    
    // Are we in single lookup mode?
    if (!(is_null($lsl->request->get_avatar_name()) && is_null($lsl->request->get_avatar_uuid()))) {
        // We are in single lookup mode
        sloodle_debug_output('***** Single lookup Mode *****<br/>');
        // Attempt to login the Sloodle user to get their info (but suppress auto-registration)
        $lsl->login_by_request(TRUE, TRUE);
        
        // Add the user data to the response
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
        $lsl->response->add_data_line(array($lsl->user->sloodle_user_cache->avname, $lsl->user->moodle_user_cache->firstname.' '.$lsl->user->moodle_user_cache->lastname));
        
    } else if (!is_null($sloodleavnamelist) && !empty($sloodleavnamelist)) {
        // Multi-lookup mode
        sloodle_debug_output('***** Multi lookup Mode *****<br/>');
        // Split up the list by pipe characters
        $avnames = explode('|', $sloodleavnamelist);
        if (is_array($avnames)) {
            // Setup our response
            $lsl->response->set_status_code(1);
            $lsl->response->set_status_descriptor('OK');
            
            $numnames = count($avnames);
            
            // Go through each name to construct the "WHERE" part of a query
            $wherequery = "";
            $isfirst = TRUE;
            foreach ($avnames as $av) {
                if ($isfirst) $isfirst = FALSE;
                else $wherequery .= " OR";
                $wherequery .= " `avname` = '$av'";
            }
            // Construct the full query
            $query =    "
                            SELECT `avname`, `firstname`, `lastname`
                            FROM `{$CFG->prefix}sloodle_users`
                            LEFT JOIN `{$CFG->prefix}user`
                             ON `{$CFG->prefix}sloodle_users`.`userid` = `{$CFG->prefix}user`.`id`
                            WHERE $wherequery
                            LIMIT 0,$numnames
                        ";
            $recs = get_records_sql($query);
            // Go through each returned record
            if (is_array($recs)) {
                foreach ($recs as $r) {
                    $lsl->response->add_data_line(array($r->avname, $r->firstname.' '.$r->lastname));
                }
            }            
            
        } else {
            // Nothing to parse
            $lsl->response->set_status_code(-811);
            $lsl->response->set_status_descriptor('REQUEST');
            $lsl->response->add_data_line('No list of avatar names.');
        }
    
    } else {
    
        // List mode
        sloodle_debug_output('***** List Mode *****<br/>');
        // Make sure the course ID was specified
        if ($lsl->request->get_course_id() == NULL) {
            sloodle_debug_output('ERROR: no parameters specified.<br/>');
            $lsl->response->set_status_code(-811);
            $lsl->response->set_status_descriptor('REQUEST');
            $lsl->response->add_data_line('Expected avatar UUID or name, avatar name list, or course ID.');
        } else {
            // Make sure the course exists
            if (!record_exists('course', 'id', $lsl->request->get_course_id())) {
                $lsl->response->quick_output(-512, 'COURSE', 'The specified course does not exist.', FALSE);
                exit();
            }
            // Everything seems OK... fetch a list of users in the course
            sloodle_debug_output('Fetching list of users in course...<br/>');
            $courseusers = get_course_users($lsl->request->get_course_id());
            if (!is_array($courseusers)) $courseusers = array();
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
                $lsl->response->add_data_line(array($user->sloodle_user_cache->avname, $u->firstname.' '.$u->lastname));
            }
        }
        
    }
    
    // Output the response
    sloodle_debug_output('Outputting response...<br/>');
    sloodle_debug_output('<pre>');
    $lsl->response->render_to_output();
    sloodle_debug_output('</pre>');
    
    exit();
?>