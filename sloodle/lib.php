<?php

    /**
    * Sloodle module core library functionality.
    *
    * This script is required by Moodle to contain certain key functionality for the module.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */

// Process the configuration options for the Sloodle module
// $config is a reference to the submitted configuration settings
// We can perform validation and other processing here as necessary
function sloodle_process_options(&$config)
{
    global $CFG;

    // Determine the page which we should re-direct to if validation fails
    $redirect = $CFG->wwwroot . '/admin/module.php?module=sloodle';

    // This string will contain error codes to supply to the configuration page if validation fails
    $error_codes = "";

    // Make sure the prim password is valid
    $sloodle_pwd = $config->prim_password;
    // Is is an appropriate length?
    $len = strlen($sloodle_pwd);
    if ($len < 5) {
        $error_codes .= "&sloodlepwdshort=yes";
    } else if ($len > 9) {
        $error_codes .= "&sloodlepwdlong=yes";
    }
    // Is it numeric only?
    if (!ctype_digit($sloodle_pwd)) {
        $error_codes .= "&sloodlepwdnonnum=yes";
    }
    // Does it have a leading zero?
    if ($len >= 1 && substr($sloodle_pwd, 0, 1) == "0") {
        $error_codes .= "&sloodlepwdleadzero=yes";
    }
    
    // Is the flag for allowing teachers to edit user data valid?
    if (isset($config->allow_user_edit_by_teachers) == FALSE || ($config->allow_user_edit_by_teachers != 'true' && $config->allow_user_edit_by_teachers != 'false')) {
        // Force it to a default value
        $config->allow_user_edit_by_teachers = 'false';
    }

    // Is the auth method recognised?
    if (!($config->auth_method == "web" || $config->auth_method == "autoregister")) {
        $error_codes .= "&sloodleauthinvalid=yes";
    }

    // Were there any error messages?
    if (!empty($error_codes)) {
        // Append our parameters to the error codes string
        $error_codes .= "&sloodlepwd={$config->prim_password}&sloodleauth={$config->auth_method}";
    
        // Redirect back to the configuration page
        if (!headers_sent()) {
	    header("Location: " . $redirect . $error_codes . "&header_redirect=true");
            exit();
        }
        redirect($redirect . $error_codes . "&header_redirect=false", "There was an error in the configuration. Please try again.");
        exit();
    }
}


// Placeholder functions

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will create a new instance and return the id number 
 * of the new instance.
 * <b>Note:</b> this function is not yet used by Sloodle. Will hopefully be used in version 0.3!
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted sloodle record
 **/
function sloodle_add_instance($sloodle) {
    
    return FALSE;
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 * <b>Note:</b> this function is not yet used by Sloodle. Will hopefully be used in version 0.3!
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function sloodle_update_instance($sloodle) {

    return FALSE;
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 * <b>Note:</b> this function is not yet used by Sloodle. Will hopefully be used in version 0.3!
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function sloodle_delete_instance($id) {

    return FALSE;
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function sloodle_user_outline($course, $user, $mod, $sloodle) {
    return NULL;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function sloodle_user_complete($course, $user, $mod, $sloodle) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in sloodle activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function sloodle_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function sloodle_cron () {
    global $CFG;

    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $sloodleid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function sloodle_grades($sloodleid) {
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of sloodle. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $sloodleid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function sloodle_get_participants($sloodleid) {
    return false;
}

/**
 * This function returns if a scale is being used by one sloodle
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $sloodleid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function sloodle_scale_used ($sloodleid,$scaleid) {
    $return = false;

    //$rec = get_record("sloodle","id","$sloodleid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}


?>