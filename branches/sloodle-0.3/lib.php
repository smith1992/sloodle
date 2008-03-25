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
    
    require_once($CFG->dirroot.'/mod/sloodle/sl_config.php');
    

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
 *
 * @param object $instance An object from the form in mod.html
 * @return int The id of the newly inserted sloodle record
 **/
function sloodle_add_instance($sloodle) {
    global $CFG;

    // Set the creation and modification times
    $sloodle->timecreated = time();
    $sloodle->timemodified = time();
    
    // Prefix the full type name on the instance name
    $sloodle->name = get_string("moduletype:{$sloodle->type}", 'sloodle') . ': ' . $sloodle->name;
        
    // Attempt to insert the new Sloodle record
    if (!$sloodle->id = insert_record('sloodle', $sloodle)) {
        error(get_string('failedaddinstance', 'sloodle'));
    }
    
    // We need to create a new secondary table for this module
    $sec_table = new stdClass();
    $sec_table->sloodleid = $sloodle->id;
    
    // Check the type of this module
    $result = FALSE;
    $errormsg = '';
    switch ($sloodle->type) {
    case SLOODLE_TYPE_CTRL:
        // Create a new controller record
        $sec_table->enabled = 0;
        $sec_table->password = mt_rand(100000000, 999999999);
        $sec_table->autoreg = 0;
        // Attempt to add it to the database
        if (!insert_record('sloodle_controller', $sec_table)) {
            $errormsg = get_string('failedaddsecondarytable', 'sloodle');
        } else {
            $result = TRUE;
        }
        break;
        
    case SLOODLE_TYPE_DISTRIB:
        // Attempt to add it to the database
        if (!insert_record('sloodle_distributor', $sec_table)) {
            $errormsg = get_string('failedaddsecondarytable', 'sloodle');
        } else {
            $result = TRUE;
        }        
        break;
        
    // ADD FURTHER MODULE TYPES HERE!
        
    default:
        // Type not recognised
        $errormsg = error(get_string('moduletypeunknown', 'sloodle'));
        break;
    }
    
    // Was there a problem?
    if (!$result) {
        // Yes
        // Delete the Sloodle instance
        delete_records('sloodle', 'id', $sloodle->id);
        
        // Show the error message (if there was one)
        if (!empty($errormsg)) error($errormsg);
        return FALSE;
    }
    
    // Success!
    return $sloodle->id;
}

/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function sloodle_update_instance($sloodle) {
    global $CFG;

    // Update the modification time
    $sloodle->timemodified = time();
    // Make sure the ID is correct for update
    $sloodle->id = $sloodle->instance;
    
    // Make sure the type is the same as the existing record
    $existing_record = get_record('sloodle', 'id', $sloodle->id);
    if (!$existing_record) error(get_string('modulenotfound', 'sloodle'));
    if ($existing_record->type != $sloodle->type) error(get_string('moduletypemismatch', 'sloodle'));
    
    // Check the type of this module
    switch ($sloodle->type) {
    case SLOODLE_TYPE_CTRL:
        // Attempt to fetch the controller record
        $ctrl = get_record('sloodle_controller', 'sloodleid', $sloodle->id);
        if (!$ctrl) error(get_string('secondarytablenotfound', 'sloodle'));
        break;
        
    case SLOODLE_TYPE_DISTRIB:
        // Attempt to fetch the distributor record
        $distrib = get_record('sloodle_distributor', 'sloodleid', $sloodle->id);
        if (!$distrib) error(get_string('secondarytablenotfound', 'sloodle'));
        break;
        
    // ADD FURTHER MODULE TYPES HERE!
        
    default:
        // Type not recognised
        error(get_string('moduletypeunknown', 'sloodle'));
        break;
    }

    // Attempt the update
    return update_record('sloodle', $sloodle);
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function sloodle_delete_instance($id) {

    // Determine our success or otherwise
    $result = false;

    // Attempt to delete the main Sloodle instance
    if (!delete_records('sloodle', 'id', $sloodle->id)) {
        $result = false;
    }
    
    // Delete any secondary controller tables
    delete_records('sloodle_controller', 'sloodleid', $id);
    
    // Attempt to get any secondary distributor tables
    $distribs = get_records('sloodle_distributor', 'sloodleid', $id);
    if (is_array($distribs) && count($distribs) > 0) {
        // Go through each distributor
        foreach ($distribs as $d) {
            // Delete any related distributor entries
            delete_records('sloodle_distributor_entry', 'distributorid', $d->id);
        }
    }
    // Delete all the distributors
    delete_records('sloodle_distributor', 'sloodleid', $id);
    
    
    // ADD FURTHER MODULE TYPES HERE!
    

    return $result;
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Do we need this function to do anything?
 **/
function sloodle_user_outline($course, $user, $mod, $sloodle) {
    return NULL;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Do we need this function to do anything?
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
 * @todo Figure out if we need this function to do anything!
 **/
function sloodle_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Function to be run periodically according to the Moodle cron.
 * Clears out expired pending user entries, session keys, etc.
 *
 * @return boolean
 * @todo Implement me!
 **/
function sloodle_cron () {
    
    // Delete any pending user entries which have expired
    //...
    
    // Delete any active objects and session keys which have expired
    //...
    
    // More stuff?
    //...

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
 * @todo Possibly make it return a list of users registered via the Sloodle instance?
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
 **/
function sloodle_scale_used ($sloodleid,$scaleid) {
    $return = false;
   
    return $return;
}


/**
* Gets the different sub-types of Sloodle module available as a list for the "Add Activity..." menu.
*
* $return array Entries for the "Add Activity..." sub-menu for Sloodle
*/

function sloodle_get_types() {
    global $CFG, $SLOODLE_TYPES;
    $types = array();

    // Start the group
    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "sloodle_group_start";
    $type->typestr = '--'.get_string('modulenameplural', 'sloodle');
    $types[] = $type;
     
    // Go through each Sloodle module type, and add it
    foreach ($SLOODLE_TYPES as $st) {
        $type = new object();
        $type->modclass = MOD_CLASS_ACTIVITY;
        $type->type = "sloodle&amp;type=$st";
        $type->typestr = get_string("moduletype:$st", 'sloodle');
        $types[] = $type;
    }

    // End the group
    $type = new object();
    $type->modclass = MOD_CLASS_ACTIVITY;
    $type->type = "sloodle_group_end";
    $type->typestr = '--';
    $types[] = $type;

    return $types;
}



?>