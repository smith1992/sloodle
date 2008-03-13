<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * Sloodle choice linker
    * Allows Sloodle "choice" objects in Second Life to interact with Moodle choice module instances
    *
    * @package sloodlechoice
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    
    ////////////////////////////////////////////////////////////
    //
    // The script is expected to be access directly by objects from within SL, and behaves in 3 modes.
    // The mode depends on which parameters are specified (see below).
    //
    // MODES //
    //  1. Available choices query = returns a list of choice module instances available in the specified course
    //  2. Choice details query = returns the details for a specific choice instance
    //  3. Option selection = informs Moodle that a user has made a selection
    //
    //
    // PARAMETERS //
    // *Always* required:
    //    sloodlepwd = prim password for accessing site/course
    //    sloodlecourseid = ID of the course being accessed
    //
    // Required for modes 2 and 3:
    //    sloodlemoduleid = ID of the choice module instance being accessed
    //
    // Required for mode 3:
    //    sloodleoptionid = ID of the option being selected
    //    sloodleuuid = UUID (key) of avatar making the selection
    //    sloodleavname = name of the avatar making the selection
    // (NOTE: the script will function even if only uuid *or* avname is specified, but both is better)
    //
    // The script will default to mode 1.
    // If the "optionid" is specified, it will attempt to adopt mode 3.
    // Otherwise, if the additional "sloodlemoduleid" parameter is specified, then it will adopt mode 2.
    //
    ////////////////////////////////////////////////////////////
    
    
    require_once('../../config.php'); // Sloodle/Moodle configuration
    require_once(SLOODLE_DIRROOT.'/sl_debug.php'); // Debug functionality
    //require_once(SLOODLE_DIRROOT.'/login/authlib.php'); // Sloodle authentication library
    require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php'); // Sloodle LSL handling
    require_once(SLOODLE_DIRROOT.'/mod/choice/sl_choice_lib.php'); // Sloodle choice library
    
    sloodle_debug_output('<br/>');
    
    // The sloodleoptionid is specified to the choice, so get it explicitly
    $sloodleoptionid = optional_param('sloodleoptionid', NULL, PARAM_INT);
    
    // Process the basic request data and authenticate the request
    sloodle_debug_output('Instantiating LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output('Processing basic request data...<br/>');
    $lsl->request->process_request_data();
    sloodle_debug_output('Authenticating request...<br/>');
    $lsl->request->authenticate_request(TRUE);
        
    // Make sure we were able to get a course object
    // (With TRUE paramater, terminates the script if the course cannot be retrieved)
    sloodle_debug_output('Requiring course record retrieval...<br/>');
    $course_record = $lsl->request->get_course_record(TRUE);
    
    // If no choice ID was specified then we are in MODE 1
    if ($lsl->request->get_module_id() == NULL) {
    ///// ///// MODE 1 ///// /////
    // In this mode, we're just giving a list of choices in the specified course
    sloodle_debug_output('***** Script in Mode 1 *****<br/>');
    
        // Fetch a list of choices in this course
        sloodle_debug_output('Fetching list of visible choice module instances in course...<br/>');
        $choices = sloodle_get_visible_choices_in_course($lsl->request->get_course_id());
        if (!is_array($choices)) {
            // Something went wrong
            sloodle_debug_output('ERROR: failed to retrieve list of instances.<br/>');
            $lsl->response->set_status_code(-612);
            $lsl->response->set_status_descriptor('CHOICE_LIST_QUERY');
            $lsl->response->add_data_line('Failed to query for list of visible choice modules instances in course.');
            $lsl->response->render_to_output();
            exit();
        }
        
        // Make sure we got at least 1 choice instance
        $num_choices = count($choices);
        if ($num_choices < 1) {
            sloodle_debug_output('ERROR: no choices available.<br/>');
            $lsl->response->set_status_code(-10022);
            $lsl->response->set_status_descriptor('CHOICE_LIST_QUERY');
            $lsl->response->add_data_line('No choice module instances available in course.');
            $lsl->response->render_to_output();
            exit();
        }
        

        // Prepare our response data
        sloodle_debug_output('Setting up response...<br/>');
        $lsl->response->set_status_code(10021);
        $lsl->response->set_status_descriptor('CHOICE_LIST_QUERY');
        //$lsl->response->add_data_line(array('num_instances',$num_choices)); // removed from spec 2008-02-06
        // Go throgh each choice module instance
        sloodle_debug_output('Going through each choice module instance...<br/>');
        foreach ($choices as $id => $cur_choice) {
            // Output each instance in the format "choice_instance|{id}|{name}"
            //$lsl->response->add_data_line(array('choice_instance', $id, $cur_choice->name));
            $lsl->response->add_data_line(array($id, $cur_choice->name)); // changed in spec 2008-02-06
        }
        // Output the response and finish
        sloodle_debug_output('<pre>');
        $lsl->response->render_to_output();
        sloodle_debug_output('</pre>');
        exit();
    }
    
    // We require a course module instance of the 'choice' type
    // This function will terminate the script with an LSL message if anything goes wrong
    sloodle_debug_output('Attempting to get course module instance of \'choice\' type...<br/>');
    $course_module_instance = $lsl->request->get_course_module_instance('choice', TRUE);
    
    // Fetch the status of the specified choice
    sloodle_debug_output('Getting status of choice...<br/>');
    $choice_status = sloodle_get_choice($course_module_instance);
    if ($choice_status === FALSE) {
        sloodle_debug_output('ERROR: failed to get choice status.<br/>');
        $lsl->response->set_status_code(-10001);
        $lsl->response->set_status_descriptor('CHOICE_QUERY');
        $lsl->response->add_data_line('Failed to retreive status of specified choice module instance.');
        $lsl->response->render_to_output();
        exit();
    }
    
    // If no option ID was specified then we are in MODE 2
    if (is_null($sloodleoptionid)) {
    ///// ///// MODE 2 ///// /////
    // In this mode, we want to show the status of a particular choice
    //  (i.e. the question, possible answers, results so far etc.)
        sloodle_debug_output('***** Script in Mode 2 *****<br/>');        
        
        // Determine if the choice is open
        sloodle_debug_output('Determining if the choice is open or closed...<br/>');
        $is_open = 'false';
        $cur_time = time();
        // If timeopen or timeclose is 0, then it is either always open or it never closes (respectively)
        // Otherwise, make sure it has opened and hasn't yet closed
        if (    ($choice_status->timeopen == '0' || (int)$choice_status->timeopen <= $cur_time) &&
                ($choice_status->timeclose == '0' || (int)$choice_status->timeclose >= $cur_time)) {
                
            $is_open = 'true';
        }
        
        // Should results be given?
        // (Either always, or after close)
        sloodle_debug_output('Determine if results should be shown...<br/>');
        $show_results = FALSE;
        if ($choice_status->showresults == CHOICE_SHOWRESULTS_ALWAYS) {
            $show_results = TRUE;
            sloodle_debug_output('&nbsp;Always show results.<br/>');
        } else if ($choice_status->showresults == CHOICE_SHOWRESULTS_AFTER_CLOSE && $is_open == 'false') {
            $show_results = TRUE;
            sloodle_debug_output('&nbsp;Showing results because choice is closed.<br/>');
        } else {
            sloodle_debug_output('&nbsp;Results display is disallowed.<br/>');
        }
        
        // Get the number of people who have not yet answered, if we are OK to show results
        $num_unanswered = -1;
        if ($show_results == TRUE && (int)$choice_status->showunanswered != 0) {
            sloodle_debug_output('Checking how many course users have not yet answered...<br/>');
            $num_unanswered = sloodle_get_num_users_not_answered_choice($choice_status);
            // Make sure an error did not occur
            if (!is_int($num_unanswered)) {
                sloodle_debug_output("ERROR getting num unanswered: $num_unanswered<br/>");
                $num_unanswered = -1;
            }
        } else {
            sloodle_debug_output('Not allowed to display number unanswered.<br/>');
        }
        
        // Make sure our choice text is safe... remove newlines and such like as they mess up our response data! :-(
        sloodle_debug_output('Cleaning choice text (removing HTML tags etc.)...<br/>');
        $choice_text = str_replace("\n", " ", $choice_status->text);
        $choice_text = str_replace("\r", "", $choice_text);
        $choice_text = stripslashes(strip_tags($choice_text));
    
        // Prepare our response data
        sloodle_debug_output('Setting up response...<br/>');
        $lsl->response->set_status_code(10001);
        $lsl->response->set_status_descriptor('CHOICE_QUERY');
        $lsl->response->add_data_line(array('choice_name',$choice_status->name));
        $lsl->response->add_data_line(array('choice_text',$choice_text));
        //$lsl->response->add_data_line(array('num_options',count($choice_status->option))); // removed from spec 2008-02-06
                
        // Go throgh each option
        sloodle_debug_output('Going through each option in the choice...<br/>');
        foreach ($choice_status->option as $optionid => $cur_option) {
            // Make sure it's OK to show the results
            $selections = -1;
            if ($show_results) $selections = $choice_status->selections[$optionid];
            // Output each option in the format: "option|{option_id}|{option_text}|{num_selected}"
            $lsl->response->add_data_line(array('option', $optionid, $cur_option, $selections));
        }
        
        // Add other response data
        $lsl->response->add_data_line(array('num_unanswered',$num_unanswered));
        $lsl->response->add_data_line(array('accepting_answers',$is_open));
        
        // Output the response and finish
        sloodle_debug_output('<pre>');
        $lsl->response->render_to_output();
        sloodle_debug_output('</pre>');
        
        exit();
    }
        
///// ///// MODE 3 ///// /////
// We must be in mode 3
// In this mode, the user has selected a choice, and we must apply it return the result

    // Attempt to login the Moodle user
    sloodle_debug_output('Attempting to login Moodle user...<br/>');
    $lsl->login_by_request();
    // Make sure the user is in the course specified
    sloodle_debug_output('Ensuring that the Moodle user is enrolled in the course...<br/>');
    $lsl->is_user_enrolled_by_request(); // By default, this is a 'require' style function -- it will terminate the script with an LSL error message if it fails
    
    // TODO (future): check that the user is allowed to access the choice (i.e. group mode)
    
    // Attempt to make the selection
    sloodle_debug_output('Attempting to selected choice...<br/>');
    $result = sloodle_select_choice_option($choice_status, $sloodleoptionid, $lsl->user->get_moodle_user_id());
    // The response will be an integer status code, or a string
    // Negative status codes and strings mean error messages
    if (is_int($result)) {
        if ($result >= 0) sloodle_debug_output("Success with code $result<br/>");
        else sloodle_debug_output("Failed with code $result<br/>");
        $lsl->response->set_status_code($result);
    } else {
        sloodle_debug_output("Failed with error: $result<br/>");
        $lsl->response->set_status_code(-10016);
        $lsl->response->add_data_line($result);
    }
    
    // The status descriptor will be the same in all cases
    $lsl->response->set_status_descriptor('CHOICE_SELECT');
    $lsl->response->render_to_output();
    exit();

?>
