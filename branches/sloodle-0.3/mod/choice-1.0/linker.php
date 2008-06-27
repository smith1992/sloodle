<?php
    /**
    * Sloodle choice linker (for Sloodle 0.3).
    * Allows an SL object to access a Moodle choice instance.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    // This script should be called with the following parameters:
    //  sloodlecontrollerid = ID of a Sloodle Controller through which to access Moodle
    //  sloodlepwd = the prim password or object-specific session key to authenticate the request
    //  sloodlemoduleid = ID of a choice
    //
    // If called with only the above parameters, then summary information about the choice is fetched.
    // Status code 10001 will be returned, with the first few data lines having the following format:
    //   <choice_name>
    //   <choice_text>
    //   <is_available>|<timestamp_open>|<timestamp_close>
    //   <num_unanswered>
    //
    // Following that will be one line for each available option, with the following format:
    //   <option_id>|<option_text>|<num_selected>
    //  
    // The "num_unanswered" and "num_selected" values will be -1 if they are not allowed to be shown.
    // The "is_available" will be 1 if the choice is open and accepting answers, or 0 otherwise.
    // The timestamp values indicate when the choice opens and closes respectively, but will be 0 if there is no opening or closing time.
    //
    //
    // An option can be selected by including the following parameters
    //  sloodleuuid = UUID of the avatar
    //  sloodleavname = name of the avatar
    //  sloodleoptionid = the ID of a particular option (unique site-wide)
    //
    // If successful, the return code will be 10011, 10012, or 10013, depending what has been done. No data.
    // See the status codes list for further information.
    //
    

    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Authenticate the request, and load a choice module
    $sloodle = new SloodleSession();
    $sloodle->authenticate_request();
    $sloodle->load_module('choice', true);

    // Has an option been specified?
    $sloodleoptionid = $sloodle->request->optional_param('sloodleoptionid');
    if ($sloodleoptionid === null) {
        // No - we are simply querying for choice data
        $sloodle->response->set_status_code(10001);
        $sloodle->response->set_status_descriptor('CHOICE_QUERY');
        
        // Check availability and results
        $isavailable = '0';
        if ($sloodle->module->is_open()) $isavailable = '1';
        $canshowresults = $sloodle->module->can_show_results();
        
        // Add the data to the response
        $sloodle->response->add_data_line($sloodle->module->get_name());
        $sloodle->response->add_data_line(stripslashes(strip_tags($sloodle->module->get_intro())));
        $sloodle->response->add_data_line(array($isavailable, $sloodle->module->get_opening_time(), $sloodle->module->get_closing_time()));
        if ($canshowresults) $sloodle->response->add_data_line($sloodle->module->get_num_unanswered());
        else $sloodle->response->add_data_line('-1');
        // Go through each option
        foreach ($sloodle->module->options as $optionid => $option) {
            // Prepare a data array for this option
            $optiondata = array();
            $optiondata[] = $optionid;
            $optiondata[] = stripslashes(strip_tags($option->text));
            if ($canshowresults) $optiondata[] = $option->numselections;
            else $optiondata[] = -1;
            
            $sloodle->response->add_data_line($optiondata);
        }
        
    } else {
        // Yes - validate the user, and permit auto-registration/enrolment
        $sloodle->validate_user();
        $sloodle->response->set_status_descriptor('CHOICE_SELECT');
        
        // Attempt to select the option
        $result = $sloodle->module->select_option($sloodleoptionid);
        if (!$result) {
            $sloodle->response->set_status_code(-10016);
            $sloodle->response->add_data_line('Unknown error selecting option.');
        } else {
            $sloodle->response->set_status_code($result);
        }
    }
    
    // Output our response
    sloodle_debug('<pre>'); // For debug mode, lets us see the response in a browser
    $sloodle->response->render_to_output();
    sloodle_debug('</pre>');
    
?>
