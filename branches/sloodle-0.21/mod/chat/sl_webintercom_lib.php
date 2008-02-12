<?php

    /**
    * Sloodle WebIntercom local library functions.
    *
    * Allows easier access to the Moodle chatroom data.
    *
    * @package sloodlechat
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This script expects that the Sloodle configuration script has already been included


    // Get an array of chatrooms appearing in the specified course
    // $course_id is the ID number of a particular course
    // Returns a numeric array, associating module instance ID's with database record objects of the activity modules
    // Returns FALSE if an error occurs
    // TODO: generalise this function to support any module type
    function sloodle_get_visible_chatrooms_in_course( $course_id )
    {
        // THIS IS HIDEOUSLY COMPLICATED! My brain is officially fried... :-|.... PRB
        
        // Make sure the course ID is valid
        if (!is_int($course_id) || $course_id <= 0) return FALSE;
        // Find out which module number the chatroom is (this will fail if the chat module is not installed)
        if (!($chat_module = get_record('modules', 'name', 'chat'))) return FALSE;
        $chat_module_id = $chat_module->id;
        
        // We want a list of sections in the specified course
        if (!($course_sections = get_records('course_sections', 'course', $course_id))) return FALSE;
        // We need to filter that to visible sections only
        $visible_course_sections = array();
        foreach ($course_sections as $cur_section) {
            if ((int)$cur_section->visible != 0) $visible_course_sections[] = (int)$cur_section->id;
        }
        
        // Get full records for all chatrooms in the specified course
        if (!($all_chats_in_course = get_records('chat', 'course', $course_id))) return array();        
        // We're going to want an array of records of visible chatrooms
        $visible_chat_records = array();
        
        // Get a list of module instances in the specified course (this gets everything, not just chats)
        if (!($all_course_modules = get_records('course_modules', 'course', $course_id))) return array();
        // We want to filter the module instances down to visible chat ID's
        $visible_chat_modules = array();
        foreach ($all_course_modules as $mod) {
            // Is this a chatroom, is it visible, and is it in a visible section of the course?
            // (It's ridiculously complicated... I know...)
            if ($mod->module == $chat_module_id && (int)$mod->visible != 0 && in_array((int)$mod->section, $visible_course_sections)) {
                // OK - this is a visible chatroom in the correct course.
                // Now find its database record...
                foreach ($all_chats_in_course as $cur_chat_record) {
                    // The 'instance' field of the course module instances corresponds to
                    //  the 'id' field of the chatroom records
                    if ($mod->instance == $cur_chat_record->id) {
                        // Woohoo! We found a match. Add it to our database
                        $visible_chat_records[$mod->id] = $cur_chat_record;
                    }
                }
            }
        }
        
        return $visible_chat_records;
    }

?>