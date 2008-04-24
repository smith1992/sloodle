<?php
    // This script is part of the Sloodle project

    /**
    * Layout profile linker script.
    * Allows the use and management of layout profiles from within SL.
    * See comments in source file for further information.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    /*
    * This script should always be called with the following parameters:
    *  sloodlecontrollerid = ID of a Sloodle Controller through which to access Moodle
    *  sloodlepwd = the prim password or object-specific session key to authenticate the request
    *  sloodleuuid = the UUID of a user agent making the layout profile request
    *  sloodleavname = the name of an avatar identified by sloodleuuid
    *
    * There are 3 modes of operation: browse, query, and update
    * If called with no parameters other than the above, the it adopts browse mode.
    * This mode will return a list of all layout profiles in the current course (whichever course the sloodlecontrollerid is part of).
    * If successful, the stats code will be 1, and each data line will identify a single layout by name.
    *
    * Query mode will fetch all entries pertaining to a specific layout.
    * To activate this mode the following additional parameter must be specified:
    *  sloodlelayoutname = the name of a layout profile
    *
    * If query mode is succesful, it will return status code 1, with one entry per data line, as follows:
    *  name|position|rotation
    *
    * Update mode save a new set of entries for a given profile.
    * To activate this mode, "sloodlelayoutname" must be specified, as well as the following parameter:
    *  sloodlelayoutentries = a pipe and line-separated list of entries to save against the profile
    *
    * The format for the "sloodlelayoutentries" parameter should be the same as the response from the query mode.
    * Due to the potentially large quantity of information this type of query may generate, it is advisable to use POST instead of GET.
    */
    
    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Authenticate the request and user, but do not allow auto-registration and enrolment
    $sloodle = new SloodleSession();
    $sloodle->authenticate_request();
    $sloodle->validate_user(true, true, true);
    
    // We need to check certain capabilities
    $can_use_layouts = false;
    $can_edit_layouts = false;
    
///// MOODLE-SPECIFIC /////
    $course_context = get_context_instance(CONTEXT_COURSE, $sloodle->course->get_course_id());
    $can_use_layouts = has_capability('mod/sloodle:uselayouts', $course_context);
    $can_edit_layouts = has_capability('mod/sloodle:editlayouts', $course_context);
///// END MOODLE-SPECIFIC /////

    // If the user cannot use layouts at all, then we cannot do anything
    if (!$can_use_layouts) {
        $sloodle->response->quick_output(-301, 'USER_AUTH', 'User does not have permission to use layout profiles.', false);
        exit();
    }
    
    // Get the optional parameters
    $sloodlelayoutname = $sloodle->request->optional_param('sloodlelayoutname');
    $sloodlelayoutentries = $sloodle->request->optional_param('sloodlelayoutentries');
    // Determine which mode we're in (0 = browse, 1 = query, 2 = update)
    $mode = 0;
    if ($sloodlelayoutname == null) $mode = 0;
    else if ($sloodlelayoutentries == null) $mode = 1;
    else $mode = 2;
    
    
    // Enter the appropriate mode
    switch ($mode) {
    case 0:
        // BROWSE MODE //
        // Fetch the layouts in this course
        $layouts = $sloodle->course->get_layout_names();
        // Add one data line per layout
        $sloodle->response->set_status_code(1);
        $sloodle->response->set_status_descriptor('OK');
        foreach ($layouts as $id => $name) {
            $sloodle->response->add_data_line(array($id, $name));
        }
        
        break;
        
    case 1:
        // QUERY MODE //
        // Attempt to load the specified profile
        $layout_entries = $sloodle->course->get_layout_entries($sloodlelayoutname);
        if (!$layout_entries) {
            // Profile not found
            $sloodle->response->set_status_code(-902);
            $sloodle->response->set_status_descriptor('LAYOUT');
            $sloodle->response->add_data_line('Named profile does not exist');
        } else {
            // Output one entry per line
            $sloodle->response->set_status_code(1);
            $sloodle->response->set_status_descriptor('OK');
            foreach ($layout_entries as $le) {
                $sloodle->response->add_data_line(array($le->name, $le->position, $le->rotation)));
            }
        }
        
        break;
        
    case 2:
        // UPDATE MODE //
        // Make sure the user has permission to edit profiles
        if ($can_edit_layouts) {
        } else {
            $sloodle->response->set_status_code(-301);
            $sloodle->response->set_status_descriptor('LAYOUT_PROFILE');
            $sloodle->response->add_data_line('User does not have permission to edit layout profiles');
        }
        
        // Split the incoming data into lines
        //..
        
        
        break;
        
    default:
        // Unknown mode
        $sloodle->response->set_status_code(-904);
        $sloodle->response->set_status_descriptor('LAYOUT_PROFILE');
        $sloodle->response->add_data_line('Error determining layout operation');
        break;
    }
    
    
    // Render our output
    sloodle_debug('<pre>'); // <- to help visualising output in a browser when debugging
    $sloodle->response->render_to_output();
    sloodle_debug('</pre>');
    
?>