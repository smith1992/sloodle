<?php
    // Sloodle teacher courses linker
    // Allows an LSL script to retrieve a list of courses for which the specified user is a teacher
    // Part of the Sloodle project (www.sloodle.org)
    //
    // Copyright (c) 2007 Sloodle
    // Released under the GNU GPL
    //
    // Contributors:
    //   Edmund Edgar - original design and implementation
    //   Peter R. Bloomfield - updated to use new API and communications format
    //
    
    // This script is expected to be requested from with Second Life.
    // The following parameters (GET or POST) are required:
    //
    //   sloodlepwd = the prim password
    //   sloodleuuid = UUID of the user's avatar (note: optional if sloodleavname is provided)
    //   sloodleavname = name of the user's avatar (note: optional if sloodleuuid is provided)
    //
    
    // The response code will 1 (OK) for success.
    // Each data line will contain course information: "<id>|<shortname>|<fullname>"
    
    
    require_once('../config.php');
    require_once(SLOODLE_DIRROOT.'/sl_debug.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');
    
    
    // Construct an LSL handler and process the basic request data
    sloodle_debug_output('Constructing LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output('Processing basic request data...<br/>');
    $lsl->request->process_request_data();
    
    // Login the user identified in the request
    sloodle_debug_output('Logging-in user...<br/>');
    $lsl->login_by_request();
    
    
    // Obtain a list of all courses (ought to make this query more efficient by reducing number of fields retrieved)
    sloodle_debug_output('Fetching list of courses...<br/>');
    $courses = get_courses();
    // Go through each
    sloodle_debug_output('Iterating through courses...<br/>');
    foreach ($courses as $c) {
        // Is the user a teacher of this course, or just an admin?
        if (isteacher($c->id) || isadmin()) {
            // Yes - add this to the response data
            $lsl->response->add_data_line(array($c->id, $c->shortname, $c->fullname));
        }
    }
    
    // Output the response
    sloodle_debug_output('Outputting response...<br/>');
    $lsl->response->set_status_code(1);
    $lsl->response->set_status_descriptor('OK');
    sloodle_debug_output('<pre>');
    $lsl->response->render_to_output();
    sloodle_debug_output('</pre>');
    
    exit();

?>