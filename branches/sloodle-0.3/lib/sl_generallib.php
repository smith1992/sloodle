<?php
    /**
    * Sloodle general library.
    *
    * Provides various utility functionality for general Sloodle purposes.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This library expects that the Sloodle config file has already been included
    //  (along with the Moodle libraries)
 

    /**
    * Checks whether or not the specific course has a Sloodle Control Center in it.
    * @param int $courseid Integer ID of a course to check
    * @return bool True if the course has a control center, or false if not.
    */
    function sloodle_course_has_control_center($courseid)
    {
        global $SLOODLE_TYPE_CTRL;
        return record_exists('sloodle', 'course', $courseid, 'type', $SLOODLE_TYPE_CTRL);
    }
    
    /**
    * Fetches the Control Center record for the specified course.
    * @param int $courseid Integer ID of a course to check
    * @return mixed A database record object containing the first Control Centre found for the specified course, or false if it could not be found
    */
    function sloodle_get_course_control_center($courseid)
    {
        global $SLOODLE_TYPE_CTRL;
        // Query for the first control center on the course (there should be only one)
        // (Note, however, we uses "get_records..." to prevent Moodle reporting an error in the event of multiple, in case it messes up HTML response)
        $result = get_records_select('sloodle', "`course` = $courseid AND `type` = '$SLOODLE_TYPE_CTRL'", '`id` ASC', '*', 0, 1);
        // Make sure a non-empty array was returned
        if (!is_array($result) || count($result) == 0) return false;
        // Return the first result
        reset($result);
        return current($result);
    }
    
 ?>
 