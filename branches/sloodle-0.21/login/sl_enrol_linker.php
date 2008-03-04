<?php
    
    // This file is part of the Sloodle project (www.sloodle.org) and is released under the GNU GPL v3.
    
    /**
    * Sloodle enrolment linker.
    *
    * Allows SL objects in-world to query and initiate student enrolment in courses.
    *
    * @package sloodlelogin
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This script is expected to be called from an LSL script in-world
    // The following parameters (GET or POST) are required in all circumstances:
    //
    //   sloodlepwd = the prim password
    //   sloodlemode = name of the mode of this query (can be: 'enrol', 'check-enrolled', 'list-enrolled', 'list-unenrolled', 'list-enrollable', 'list-all')
    //
    // Some modes require or can accept additional parameters:
    //
    //   sloodlecourseid = ID of a Moodle course
    //   sloodlecategoryid = ID of the category to which results should be limited (leave blank or set to 'all' to retrieve all categories)
    //   sloodleuuid = UUID of the avatar (note: optional if sloodleavname is provided, but best to provide both)
    //   sloodleavname = name of the avatar (note: optional if sloodleuuid is provided, but best to provide both)
    //   
    //
    // Mode 'enrol' is used to fetch an enrolment URL for the identified user in the specified course.
    // This mode requires 'sloodlecourseid', and 'sloodleuuid' and/or 'sloodleavname'.
    // If successful, the return code is 1, and the data line is the enrolment URL (to which the user should be forwarded).
    // If the user is already enrolled, then the status code is 401, and there is no data line.
    //
    // Mode 'check-enrolled' checks if a user is enrolled in a specific course.
    // This mode requires 'sloodlecourseid', and 'sloodleuuid' and/or 'sloodleavname'.
    // If successful, the return code is 1. A single data line contains a 1 or a 0 (1 = enrolled, 0 = not enrolled).
    // 
    // Mode 'list-enrolled' fetches a list of all courses a particular user is enrolled in.
    // This mode requires the 'sloodleuuid' and/or 'sloodleavname' parameters. 'sloodlecategoryid' is optional.
    // If successful, the return code is 1. Each data line gives data about a course, in format "<id>|<shortname>|<longname>".
    // 
    // Mode 'list-unenrolled' fetches a list of all courses a particular user is *not* enrolled in
    // This mode requires the 'sloodleuuid' and/or 'sloodleavname' parameters. 'sloodlecategoryid' is optional.
    // If successful, the return code is 1. Each data line gives data about a course, in format "<id>|<shortname>|<longname>".
    //
    // Mode 'list-enrollable' fetches a list of all courses a particular user is not enrolled in, but in which they may enrol
    // This mode requires the 'sloodleuuid' and/or 'sloodleavname' parameters. 'sloodlecategoryid' is optional.
    // If successful, the return code is 1. Each data line gives data about the course, in format "<id>|<shortname>|<longname>".
    //
    // Mode 'list-all' fetches a list of all courses
    // No additional parameters required. 'sloodlecategoryid' is optional.
    // If successful, the return code is 1. Each data line gives data about the course, in format "<id>|<shortname>|<longname>".
    //
    
    
    require_once('../config.php');
    require_once(SLOODLE_DIRROOT.'/sl_debug.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');
    
    sloodle_debug_output('<br/>');
    
    // Construct the LSL handler, and process the basic request data
    sloodle_debug_output('Constructing LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output('Processing basic request data...<br/>');
    $lsl->request->process_request_data();
    
    // Ensure the request is authenticated
    sloodle_debug_output('Authenticating request...<br/>');
    $lsl->request->authenticate_request();
    
    // Fetch other additional parameters
    sloodle_debug_output('Fetching additional parameters...<br/>');
    $sloodlemode = strtolower($lsl->request->required_param('sloodlemode', PARAM_RAW));
    $sloodlecategoryid = optional_param('sloodlecategoryid', NULL, PARAM_INT);
    if ($sloodlecategoryid == NULL) $sloodlecategoryid = 'all';
    
    
    // Fetch a list of all courses (just their ID's, short names, and long names)
    sloodle_debug_output("Fetching list of all courses (category: $sloodlecategoryid)...<br/>");
    // N.B.: ought to make this query more efficient by reducing number of retrieved fields
    $courses = get_courses($sloodlecategoryid);
    $numcourses = count($courses);
    sloodle_debug_output("-&gt;Number of courses retrieved: $numcourses.<br/>");
    
    
    // Check which mode it is
    sloodle_debug_output('Checking mode...<br/>');
    sloodle_debug_output("***** Mode '$sloodlemode' *****<br/>");
    switch ($sloodlemode) {
    
    case 'enrol':
        // Login the user
        sloodle_debug_output('Logging-in user...<br/>');
        $lsl->login_by_request();
        // Make sure the course was specified
        sloodle_debug_output('Ensuring course ID was specified...<br/>');
        $lsl->request->required_param('sloodlecourseid', PARAM_INT);
        
        // Add an enrolment link to the response (if the user is not already enrolled)
        sloodle_debug_output('Constructing response...<br/>');
        // Is the user already enrolled?
        sloodle_debug_output('Checking if user is enrolled in course #'.$lsl->request->get_course_id().'...<br/>');
        if (isadmin() || $lsl->user->is_user_in_course($lsl->request->get_course_id()) === TRUE) {
            // Yes - nothing much to do
            $lsl->response->set_status_code(401);
            $lsl->response->set_status_descriptor('MISC_ENROL');
            sloodle_debug_output('-&gt; User is enrolled in course.<br/>');
        } else {
            // No - provide the URL
            $lsl->response->set_status_code(1);
            $lsl->response->set_status_descriptor('OK');
            sloodle_debug_output('-&gt; User is not enrolled in course.<br/>');
            $lsl->response->add_data_line($CFG->wwwroot.'/mod/sloodle/login/sl_enrolment.php?sloodlecourseid='.$lsl->request->get_course_id());
        }        
        break;
        
        
    case 'check-enrolled':
        // Login the user
        sloodle_debug_output('Logging-in user...<br/>');
        $lsl->login_by_request();        
        // Make sure the course was specified
        sloodle_debug_output('Ensuring course ID was specified...<br/>');
        $lsl->request->required_param('sloodlecourseid', PARAM_INT);        
        // Is the user in that course?
        sloodle_debug_output('Checking if user is enrolled in course #'.$lsl->request->get_course_id().'...<br/>');
        if ($lsl->user->is_user_in_course($lsl->request->get_course_id()) === TRUE) {
            sloodle_debug_output('-&gt; User is enrolled in course.<br/>');
            $in_course = '1';
        } else {
            sloodle_debug_output('-&gt; User is not enrolled in course.<br/>');
            $in_course = '0';
        }
        
        // Add an enrolment link to the response
        sloodle_debug_output('Constructing response...<br/>');
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
        $lsl->response->add_data_line($in_course);
        break;
        
        
    case 'list-enrolled':
        // Login the user
        sloodle_debug_output('Logging-in user...<br/>');
        $lsl->login_by_request();
        // Get a list of courses the user is enrolled in
        sloodle_debug_output('Fetching list of enrolled courses...<br/>');
        if ($lsl->user->update_enrolled_courses_cache_from_db() !== TRUE) {
            sloodle_debug_output('-&gt;Failed.<br/>');
            $lsl->response->set_status_code(-103);
            $lsl->response->set_status_descriptor('SYSTEM');
            $lsl->response->add_data_line('Failed to retrieve list of courses which the user is enrolled in.');
            break;
        }
        
        // Setup the response
        sloodle_debug_output('Constructing response...<br/>');
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
        // Go through each course
        sloodle_debug_output('Iterating through all available courses...<br/>');
        foreach ($courses as $c) {
            // Ignore the front-page course
            if ((int)$c->id == 1) continue;
            // Is the user enrolled in this course?
            if (in_array((int)$c->id, $lsl->user->enrolled_courses_cache)) {
                // Yes - add it to the response
                $lsl->response->add_data_line(array($c->id, $c->shortname, $c->fullname));
            }
        }        
        
        break;
        
        
    case 'list-unenrolled':
        // Login the user
        sloodle_debug_output('Logging-in user...<br/>');
        $lsl->login_by_request();
        // Get a list of courses the user is enrolled in
        sloodle_debug_output('Fetching list of enrolled courses...<br/>');
        if ($lsl->user->update_enrolled_courses_cache_from_db() !== TRUE) {
            sloodle_debug_output('-&gt;Failed.<br/>');
            $lsl->response->set_status_code(-103);
            $lsl->response->set_status_descriptor('SYSTEM');
            $lsl->response->add_data_line('Failed to retrieve list of courses which the user is enrolled in.');
            break;
        }
        
        // Setup the response
        sloodle_debug_output('Constructing response...<br/>');
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
        // Go through each course
        sloodle_debug_output('Iterating through all available courses...<br/>');
        foreach ($courses as $c) {
            // Ignore the front-page course
            if ((int)$c->id == 1) continue;
            // Is the user enrolled in this course?
            if (in_array((int)$c->id, $lsl->user->enrolled_courses_cache) == FALSE) {
                // No - add it to the response
                $lsl->response->add_data_line(array($c->id, $c->shortname, $c->fullname));
            }
        }
        break;
        
        
    case 'list-enrollable':
        // Login the user
        sloodle_debug_output('Logging-in user...<br/>');
        $lsl->login_by_request();
        // Get a list of courses the user is enrolled in
        sloodle_debug_output('Fetching list of enrolled courses...<br/>');
        if ($lsl->user->update_enrolled_courses_cache_from_db() !== TRUE) {
            sloodle_debug_output('-&gt;Failed.<br/>');
            $lsl->response->set_status_code(-103);
            $lsl->response->set_status_descriptor('SYSTEM');
            $lsl->response->add_data_line('Failed to retrieve list of courses which the user is enrolled in.');
            break;
        }
        
        // Setup the response
        sloodle_debug_output('Constructing response...<br/>');
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
        // Go through each course
        sloodle_debug_output('Iterating through all available courses...<br/>');
        foreach ($courses as $c) {
            // Ignore the front-page course
            if ((int)$c->id == 1) continue;
            // Is this course enrollable?
            if (!(!$c->enrollable || ($c->enrollable == 2 && $c->enrolstartdate > 0 && $c->enrolstartdate > time()) || ($c->enrollable == 2 && $c->enrolenddate > 0 && $c->enrolenddate <= time()))) { // These checks were copied from course/enrol.php. Sorry about the confusing double-negative...
                // Yes - add it to the response
                $lsl->response->add_data_line(array($c->id, $c->shortname, $c->fullname));
            }
        }
        break;
        
        
    case 'list-all':
        // Setup the response
        sloodle_debug_output('Constructing response...<br/>');
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
        // Go through each course
        sloodle_debug_output('Iterating through all available courses...<br/>');
        foreach ($courses as $c) {
            // We're adding all courses to the response
            $lsl->response->add_data_line(array($c->id, $c->shortname, $c->fullname));
        }
        break;
        
        
        
    default:
        // Mode not recognised
        sloodle_debug_output("ERROR: unrecognised mode, '$sloodlemode'<br/>");
        $lsl->response->set_status_code(-811);
        $lsl->response->set_status_descriptor('REQUEST');
        $lsl->response->add_data_line("Mode '$sloodlemode' not recognised.");
        break;
    }
    
    // Output the response, and finish
    sloodle_debug_output('Outputting response...<br/>');
    sloodle_debug_output('<pre>');
    $lsl->response->render_to_output();
    sloodle_debug_output('</pre>');
    exit();
?>
