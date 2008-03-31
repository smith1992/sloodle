<?php
    //////////////////////////////////////////////////////////////////////////
    // MetaGloss linker script
    // Provides a link for the LSL MetaGloss script in-world to query the Moodle glossary
    // For more information, visit Sloodle.org
    //
    // Original by Jeremy Kemp, San Jose State University
    // Updated by Peter R. Bloomfield, University of Paisley, October 2007
    //
    // Script expects 2 parameters (via GET or POST).
    // Parameter "concept" is necessary, and defines the search string to be looked-up in the glossary.
    // Either parameter "glossaryid" OR "courseid" must also be specified.
    // The "glossaryid" is the ID number of the glossary to be searched.
    // If that is not specified, but "courseid" is given, then all glossaries in that course are searched.
    // All results are returned as plain text.
    //////////////////////////////////////////////////////////////////////////    

    require_once('../../../../config.php');
    require_once($CFG->dirroot .'/mod/glossary/lib.php');
    require_once($CFG->libdir.'/filelib.php');
    
    // Get the necessary parameters
    $courseid = optional_param('courseid', NULL, PARAM_INT);
    $concept = optional_param('concept', NULL, PARAM_RAW);
    $glossaryid = optional_param('glossaryid', NULL, PARAM_INT);
    
    // Initialise a results variable
    $result = NULL;
    
    // We *must* have a concept (search term) parameter
    if ($concept === NULL) exit("ERROR: no glossary search term specified");
    
    // We either need a glossary ID or a course ID -- if glossary ID is specified, then search that glossary only and ignore the course ID
    // If only the course ID is specified, then search all glossaries in the course
    if (is_null($glossaryid) == FALSE) {
       // Attempt to find the glossary
       $glossary = get_record("glossary", "id", $glossaryid);
       if ($glossary === FALSE) exit("ERROR: unrecognised glossary ID.");
       
       // Search the glossary
       echo "Searching glossary \"$glossary->name\" for term \"$concept\". ";
       $result = glossary_search_entries(array($concept), $glossary, 0);
    
    } else {
    
        // Make sure the course ID was specified
        if ($courseid === NULL) exit("ERROR: no glossary or course specified");
        // Get the course object
        if (! $course = get_record("course", "id", $courseid)) {
            exit("ERROR: unrecognised course ID.");
        }
    
        // Search all glossaries in the course
        echo "Searching all glossaries in \"$course->shortname\" for term \"$concept\". ";
        $result = glossary_search($course, array($concept));
    }    
    
    // Check that we have some results
    if (is_array($result) && ($numresults = count($result)) > 0) {
        if ($numresults == 1) echo " 1 result found:";
        else echo "$numresults results found:";
        // Display each result
        foreach ($result as $entry) {
            // Not very efficient, but look up the name of the glossary each result is from
            $curgloss = get_record("glossary", "id", $entry->glossaryid);
            echo "\n\n\"$entry->concept\" ($curgloss->name): $entry->definition";
        }
    } else {
        echo "No matching entries found for search term \"$concept\".";
    }
    
?>