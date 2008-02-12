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


    // Get an array of glossaries appearing in the specified course
    // $course_id is the ID number of a particular course
    // Returns a numeric array, associating module instance ID's with database record objects of the activity modules
    // Returns FALSE if an error occurs
    // TODO: generalise this function to support any module type
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
    
    // Get a glossary activity module instance
    // $cmi is a course module instance object (database record)
    // Returns an object if successful, or FALSE if not
    function sloodle_get_glossary_activity_module( $cmi )
    {
        // Make sure we were given a valid object
        if (!is_object($cmi) || !isset($cmi->instance)) return FALSE;
        // Search the database for a glossary object
        return get_record('glossary', 'id', $cmi->instance);
    }
    
    // Search a glossary for entries
    // $glossary should be a glossary activity module instance
    // $concept should be a concept to search for
    // Returns an array of terms (records from the database), or FALSE if unsuccessful
    function sloodle_lookup_glossary( $glossary, $concept )
    {
        return glossary_search_entries(array($concept), $glossary, 0);
    }
    

?>