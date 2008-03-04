<?php
    /**
    * Sloodle choice local library functions.
    *
    * Allows easier access to the Moodle choice data.
    *
    * @package sloodlechoice
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This script expects that the Sloodle configuration script has already been included
    
    // Include the standard choice module library
    require_once($CFG->dirroot.'/mod/choice/lib.php');
    
    
    /**
    * Gets an array of available choices in the specified course.
    * Choices which are hidden, or which are in a hidden section of the course, are ignored.
    *
    * @param int $course_id Integer ID of a Moodle course
    * @return mixed If successful, a numeric array, associating module instance ID's with database record objects of the activity modules. Returns boolean false if an error occurs.
    * @see sloodle_get_visible_glossaries_in_course()
    * @see sloodle_get_visible_chatrooms_in_course()
    * @see sloodle_get_choice()
    * @todo: Generalise this function to support any module type
    */
    function sloodle_get_visible_choices_in_course( $course_id )
    {
        // Make sure the course ID is valid
        if (!is_int($course_id) || $course_id <= 0) return FALSE;
        // Find out which module number the choice is (this will fail if the choice module is not installed)
        if (!($choice_module = get_record('modules', 'name', 'choice'))) return FALSE;
        $choice_module_id = $choice_module->id;
        
        // We want a list of sections in the specified course
        if (!($course_sections = get_records('course_sections', 'course', $course_id))) return FALSE;
        // We need to filer that to visible sections only
        $visible_course_sections = array();
        foreach ($course_sections as $cur_section) {
            if ((int)$cur_section->visible != 0) $visible_course_sections[] = (int)$cur_section->id;
        }
        
        // Get full records for all choices in the specified course
        if (!($all_choices_in_course = get_records('choice', 'course', $course_id))) return array();        
        // We're going to want an array of records of visible choices
        $visible_choice_records = array();
        
        // Get a list of module instances in the specified course (this gets everything, not just choices)
        if (!($all_course_modules = get_records('course_modules', 'course', $course_id))) return array();
        // We want to filter the module instances down to visible choice ID's
        $visible_choice_modules = array();
        foreach ($all_course_modules as $mod) {
            // Is this a choice, is it visible, and is it in a visible section of the course?
            // (It's ridiculously complicated... I know...)
            if ($mod->module == $choice_module_id && (int)$mod->visible != 0 && in_array((int)$mod->section, $visible_course_sections)) {
                // OK - this is a visible choice in the correct course.
                // Now find its database record...
                foreach ($all_choices_in_course as $cur_choice_record) {
                    // The 'instance' field of the course module instances corresponds to
                    //  the 'id' field of the choice records
                    if ($mod->instance == $cur_choice_record->id) {
                        // Woohoo! We found a match. Add it to our database
                        $visible_choice_records[$mod->id] = $cur_choice_record;
                    }
                }
            }
        }
        
        return $visible_choice_records;
    }
    
    
    /**
    * Gets a complete record of a particular choice instance.
    * If successful, returns a database record of the choice, with the following items added:
    * - option[] - associates option IDs with option texts
    * - maxanswers[] - associates option IDs with the maximum allowable number of answers for each one
    * - selections[] - associates option IDs with the number of times each one has already been selected
    *
    * @param object $course_module_instance A course module instance database record
    * @return mixed If successful, a customized database object. Otherwise, boolean false.
    * @see sloodle_get_visible_choices_in_course()
    */
    function sloodle_get_choice($course_module_instance)
    {
        // Get the choice ID
        $choiceid = $course_module_instance->instance;
        // Attempt to get the choice record
        if (!($choice = get_record('choice', 'id', $choiceid))) return FALSE;
        // Attempt to get each option as an array of record
        if (!($options = get_records('choice_options', 'choiceid', $choiceid, 'id'))) return FALSE;
        
        // Go through each option
        foreach ($options as $option) {
            // Add the option and maximum number of answers to the choice object
            $choice->option[$option->id] = $option->text;
            $choice->maxanswers[$option->id] = $option->maxanswers;
            // Determine how many times this option has already been selected
            $selections = get_records('choice_answers', 'optionid', $option->id);
            if (is_array($selections)) {
                // Count how many selections were made
                $choice->selections[$option->id] = count($selections);
            } else {
                // None selected
                $choice->selections[$option->id] = 0;
            }
        }
        
        // Done!
        return $choice;
    }

    /**
    * Gets the number of users on the course who have not yet answered the specified choice.
    * <b>Note:</b> the count includes students and teachers.
    *
    * @param object $choice A choice object from the {@link:sloodle_get_choice()} function
    * @return mixed If successful, a positive integer. Otherwise, a string error message.
    */
    function sloodle_get_num_users_not_answered_choice( $choice )
    {
        // Make sure we were give a valid choice record
        if (!is_object($choice)) return 'Choice record not valid.';
        // Make sure we can get a course number from it
        if (!isset($choice->course)) return 'Course number not set in choice record.';
        $course = $choice->course;
        // Get a list of all users in the course
        $users = get_course_users($course);
        if (!is_array($users)) return 'Failed to retrieve list of course users.';
        // Count that list
        $num_users = count($users);
        // Quick-escape: no users!
        if ($num_users == 0) return 0;
        
        // Now count the number of people who have answered the choice already
        $answers = get_records('choice_answers', 'choiceid', $choice->id);
        if (!is_array($answers)) return $num_users; // Nobody has answered it 
        
        // Calculate the number who are left to answer (do not allow negative values -- e.g. an admin may answers, but not be on the user list)
        $num_left = $num_users - count($answers);
        if ($num_left < 0) $num_left = 0;
        
        return $num_left;
    }
    
    /**
    * Select an option from a choice.
    *
    * The return will either be an integer or a string.
    * If an integer, then it is a {@link http://slisweb.sjsu.edu/sl/index.php/Sloodle_status_codes status code}.
    * The following status codes are typical responses:
    *  - 10011 = added new choice selection
    *  - 10012 = updated existing choice selection
    *  - -10011 = User already made a selection, and re-selection is not allowed
    *  - -10012 = max number of selections for this choice already made
    *  - -10013 = choice is not yet open
    *  - -10014 = choice is already closed
    * If a string, then it is an error message reporting that something went wrong.
    *
    * @param object $choice A choice object from the {@link:sloodle_get_choice()} function
    * @param int $optionid The integer ID of an option (note: these are unique across an entire site)
    * @param int $moodle_user_id The integer ID of the Moodle user making the selection
    * @return mixed If successful, a positive integer. Otherwise, a string error message.
    */
    function sloodle_select_choice_option( $choice, $optionid, $moodle_user_id )
    {
        // Make sure we were give a valid choice record
        if (!is_object($choice)) return 'Choice record not valid.';
        // Make sure the choice has opened
        $opentime = (int)$choice->timeopen;
        $closetime = (int)$choice->timeclose;
        if ($opentime > 0 && $opentime > time()) return (-10013);
        if ($closetime > 0 && $closetime < time()) return (-10014);
        
        // Make sure the specified option belongs to the choice
        if (!isset($choice->option[$optionid])) return (-10015);
        
        // Get a list of answers which have already been made for this choice
        $answers = get_records('choice_answers', 'choiceid', $choice->id);
        // Search the list to see if the user has already made a selection for this choice
        $old_selection = FALSE;
        foreach ($answers as $cur_answer) {
            // Do the user ID's match?
            if ($moodle_user_id == $cur_answer->userid) {
                // Yes - store the selection and finish
                $old_selection = $cur_answer;
                break;
            }
        }
        
        // Has the user already answered?
        if ($old_selection) {
            // Does this choice prohibit re-selection?
            if (!$choice->allowupdate) return (-10011);
            // We can just finish if the same choice is being selected again
            if ($old_selection->optionid == $optionid) return 10012;
        }
                
        // If the answers are limited, then make sure the number of selection so far has not exceeded the maximum number
        if ($choice->limitanswers && $choice->selections[$optionid] >= $choice->maxanswers[$optionid]) {
            return (-10012);
        }
        
        // Are we updating an old selection?
        if ($old_selection) {
            // Update
            $old_selection->optionid = $optionid;
            $old_selection->timemodified = time();
            if (!update_record('choice_answers', $old_selection)) return 'Failed to update database.';
            // Success!
            return 10012;
        }
        
        // We must be inserting a new selection
        $selection = new stdClass();
        $selection->choiceid = $choice->id;
        $selection->userid = $moodle_user_id;
        $selection->optionid = $optionid;
        $selection->timemodified = time();
        if (!insert_record('choice_answers', $selection)) return 'Failed to insert new database record';
        // Success!
        return 10011;
    }
    

?>