<?php    
    /**
    * Sloodle teacher courses linker.
    *
    * Allows an LSL script to retrieve a list of courses for which the specified user is a teacher.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This script is expected to be requested from with Second Life.
    // The following parameters (GET or POST) are required:
    //
    //   sloodlepwd = the prim password
    //   sloodleuuid = UUID of the user's avatar (note: optional if sloodleavname is provided)
    //   sloodleavname = name of the user's avatar (note: optional if sloodleuuid is provided)
    //
    // The following parameter is optional:
    //
    //   sloodlecategoryid = ID of the category to which results should be limited (leave blank or set to 'all' to retrieve all categories)
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
    
    // Authenticate the request
    sloodle_debug_output('Authenticating request...<br/>');
    $lsl->request->authenticate_request();
    
    // Login the user identified in the request
    sloodle_debug_output('Logging-in user...<br/>');
    $lsl->login_by_request();
    
    // Retreive the category parameter
    sloodle_debug_output('Fetching additional parameters...<br/>');
    $sloodlecategoryid = optional_param('sloodlecategoryid', NULL, PARAM_INT);
    if ($sloodlecategoryid == NULL) $sloodlecategoryid = 'all';
    
    // Fetch a list of all courses (just their ID's, short names, and long names)
    sloodle_debug_output("Fetching list of all courses (category: $sloodlecategoryid)...<br/>");
    // N.B.: ought to make this query more efficient by reducing number of retrieved fields
    $courses = get_courses($sloodlecategoryid);
    // Go through each course
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