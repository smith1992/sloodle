<?php
    // Sloodle choice local library functions
    // Allows access to the Moodle choice data
    // See www.sloodle.org for more information
    //
    // Part of the Sloodle project (www.sloodle.org)
    // Copyright (c) 2007 Sloodle
    // Released under the GNU GPL
    //
    // Contributors:
    //  Peter R. Bloomfield - original design and implementation
    //
    
    // This script expects that the Sloodle configuration script has already been included
    
    // Include the standard choice module library
    require_once($CFG->dirroot.'/mod/choice/lib.php');
    
    
    // Get an array of choice modules appearing in the specified course
    // $course_id is the ID number of a particular course
    // Returns a numeric array, associating module instance ID's with database record objects
    // Returns FALSE if an error occurs
    // TODO: generalise this function to support any module type
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
    
    
    // Get a complete record of a particular choice
    // $course_module_instance should be a course module instance database record
    // Returns a database record, the following items added:
    //  option[] - associates option IDs with option texts
    //  maxanswers[] - associates option IDs with the maximum allowable number answers for each one
    //  selections[] - associates option IDs with the number of times each one has already been selected
    // Returns FALSE if the choice is not found
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

    // TODO: implement this
    function sloodle_get_num_users_not_answered_choice( $course_module_instance )
    {
        return (-1);
    }
    
    // TODO: implement this
    function sloodle_select_choice_option( $course_module_instance, $optionid, $moodle_user_id )
    {
        return FALSE;
    }
    

?>