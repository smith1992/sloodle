<?php  // $Id: lib.php,v 1.6 2007/04/01 19:02:13 stronk7 Exp $
/**
 * Library of functions and constants for module sloodle
 * This file should have two well differenced parts:
 *   - All the core Moodle functions, neeeded to allow
 *     the module to work integrated in Moodle.
 *   - All the sloodle specific functions, needed
 *     to implement all the module logic. Please, note
 *     that, if the module become complex and this lib
 *     grows a lot, it's HIGHLY recommended to move all
 *     these module specific functions to a new php file,
 *     called "locallib.php" (see forum, quiz...). This will
 *     help to save some memory when Moodle is performing
 *     actions across all modules.
 */

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
    
    // temp added for debugging
    echo "ADD INSTANCE CALLED";
   // print_object($sloodle);
    
    $sloodle->timecreated = time();

    # May have to add extra stuff in here #
    
    return insert_record("sloodle", $sloodle);
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

    $sloodle->timemodified = time();
    $sloodle->id = $sloodle->instance;

    # May have to add extra stuff in here #

    return update_record("sloodle", $sloodle);
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

    if (! $sloodle = get_record("sloodle", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("sloodle", "id", "$sloodle->id")) {
        $result = false;
    }

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
 * @todo Finish documenting this function
 **/
function sloodle_user_outline($course, $user, $mod, $sloodle) {
    return $return;
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

//////////////////////////////////////////////////////////////////////////////////////
/// Any other sloodle functions go here.  Each of them must have a name that 
/// starts with sloodle_
/// Remember (see note in first lines) that, if this section grows, it's HIGHLY
/// recommended to move all funcions below to a new "localib.php" file.


?>
