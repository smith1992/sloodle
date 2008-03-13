<?php
    /**
    * Sloodle glossary local library functions.
    *
    * Allows easier access to the Moodle glossary data.
    *
    * @package sloodleglossary
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This script expects that the Sloodle configuration script has already been included


    /**
    * Gets an array of available glossaries in the specified course.
    * Glossaries which are hidden, or which are in a hidden section of the course, are ignored.
    *
    * @param int $course_id Integer ID of a Moodle course
    * @return mixed If successful, a numeric array, associating module instance ID's with database record objects of the activity modules. Returns boolean false if an error occurs.
    * @see sloodle_get_visible_chatrooms_in_course()
    * @see sloodle_get_visible_choices_in_course()
    * @todo: Generalise this function to support any module type
    */
    function sloodle_get_visible_glossaries_in_course( $course_id )
    {
        // THIS IS HIDEOUSLY COMPLICATED! My brain is officially fried... :-|.... PRB
        
        // Make sure the course ID is valid
        if (!is_int($course_id) || $course_id <= 0) return FALSE;
        // Find out which module number the glossary is (this will fail if the glossary module is not installed)
        if (!($glossary_module = get_record('modules', 'name', 'glossary'))) return FALSE;
        $glossary_module_id = $glossary_module->id;
        
        // We want a list of sections in the specified course
        if (!($course_sections = get_records('course_sections', 'course', $course_id))) return FALSE;
        // We need to filter that to visible sections only
        $visible_course_sections = array();
        foreach ($course_sections as $cur_section) {
            if ((int)$cur_section->visible != 0) $visible_course_sections[] = (int)$cur_section->id;
        }
        
        // Get full records for all glossaryes in the specified course
        if (!($all_glossaries_in_course = get_records('glossary', 'course', $course_id))) return array();        
        // We're going to want an array of records of visible glossaries
        $visible_glossary_records = array();
        
        // Get a list of module instances in the specified course (this gets everything, not just glossaries)
        if (!($all_course_modules = get_records('course_modules', 'course', $course_id))) return array();
        // We want to filter the module instances down to visible glossary ID's
        $visible_glossary_modules = array();
        foreach ($all_course_modules as $mod) {
            // Is this a glossary, is it visible, and is it in a visible section of the course?
            // (It's ridiculously complicated... I know...)
            if ($mod->module == $glossary_module_id && (int)$mod->visible != 0 && in_array((int)$mod->section, $visible_course_sections)) {
                // OK - this is a visible glossary in the correct course.
                // Now find its database record...
                foreach ($all_glossaries_in_course as $cur_glossary_record) {
                    // The 'instance' field of the course module instances corresponds to
                    //  the 'id' field of the glossary records
                    if ($mod->instance == $cur_glossary_record->id) {
                        // Woohoo! We found a match. Add it to our database
                        $visible_glossary_records[$mod->id] = $cur_glossary_record;
                    }
                }
            }
        }
        
        return $visible_glossary_records;
    }
    
    /**
    * Gets a glossary activity module instance.
    *
    * @param object $cmi A course module instance object (database record)
    * @return mixed A database record object if successful, or false if not.
    */
    function sloodle_get_glossary_activity_module( $cmi )
    {
        // Make sure we were given a valid object
        if (!is_object($cmi) || !isset($cmi->instance)) return FALSE;
        // Search the database for a glossary object
        return get_record('glossary', 'id', $cmi->instance);
    }
    
    /**
    * Searches glossary terms for a word or phrase.
    *
    * @param object $glossary An activity module instance (e.g. returned from {@link sloodle_get_glossary_activity_module()}.
    * @param string $concept A string to search for.
    * @return mixed A numeric array of entries (as records from the database) if successful, or boolean false if not.
    */
    function sloodle_lookup_glossary( $glossary, $concept )
    {
        return glossary_search_entries(array($concept), $glossary, 0);
    }
    

?>