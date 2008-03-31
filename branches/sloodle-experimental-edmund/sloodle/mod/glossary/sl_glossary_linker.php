<?php    
    /**
    * Sloodle glossary linker.
    *
    * Allows in-world 'MetaGloss' tools to query Moodle glossaries.
    *
    * @package sloodleglossary
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Jeremy Kemp
    * @contributor Peter R. Bloomfield
    *
    */

    // This script is expected to be accessed from in-world.
    // The following parameters are required:
    //
    //   sloodlepwd = prim password for request authentication
    //   sloodlecourseid = ID of the course this request relates to
    //
    // The following parameters are optional, depending on the mode required:
    //
    //   sloodleconcept = the search string to be looked-up
    //   sloodlemoduleid = course module instance ID of the glossary to be used
    //
    //
    // There are two modes of operation: browse and lookup.
    //
    // In browse mode, neither the sloodleconcept' nor the 'sloodlemoduleid' parameters should be specified.
    // In this case, a list of all available glossarries in the identified course is returned.
    //
    // Look-up mode requires 'sloodleconcept' and 'sloodlemoduleid' to be specified.
    // It will lookup the specified term ('concept') in the specified glossary.
    //
    // NOTE: only available (i.e. visible) glossaries may be searched
    
    // When returning a list of glossaries (browse mode), the status code returned will be 1 if successful.
    // Each line will define a glossary, like this:
    //
    // "id|name"
    //
    // The "id" is the course module instance ID.
    
    // When returning a definition (lookup mode), the status code returned will be 1 if succesful.
    // (Other status codes may be returned if the glossary is not available).
    //
    // Each line of the response will give a definition as raw text:
    //
    // "concept|definition"
    //
    // Note that some glossaries  may contain multiple definitions of a single term.
    // Also note that the search process will find partial matches (e.g. "pri" will find the definition for "prim").

    header ('Content-Type: text/plain; charset=UTF-8');
 
    require_once('../../config.php');
    require_once(SLOODLE_DIRROOT.'/sl_debug.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');
    require_once($CFG->dirroot.'/mod/glossary/lib.php');
    require_once($CFG->libdir.'/filelib.php');
    require_once('sl_glossary_lib.php');
    
    // Create an LSL handler and process the basic request data
    sloodle_debug_output('Creating LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output('Processing basic request data...<br/>');
    $lsl->request->process_request_data();
    
    // Ensure the request is authenticated
    sloodle_debug_output('Authenticating request...<br/>');
    $lsl->request->authenticate_request();
    
    // Obtain the additional parameters
    sloodle_debug_output('Obtaining additional parameters...<br/>');
    $sloodleconcept = optional_param('sloodleconcept', NULL, PARAM_RAW);
    
    
    // Attempt to get the requested course
    sloodle_debug_output('Fetching course data...<br/>');
    $course = $lsl->request->get_course_record();
    
    // Was a module ID specified?
    $cmi = NULL;
    $glossary = NULL;
    if ($lsl->request->get_module_id() != NULL) {
        // Yes - get the module instance
        sloodle_debug_output('Fetching course module instance...<br/>');
        $cmi = $lsl->request->get_course_module_instance('glossary');
        // Attempt to fetch the glossary module itself
        sloodle_debug_output('Fetching activity module...<br/>');
        if (!$glossary = sloodle_get_glossary_activity_module($cmi)) {
            sloodle_debug_output('-&gt; Failed.<br/>');
            $lsl->response->set_status_code(-101);
            $lsl->response->set_status_descriptor('SYSTEM');
            $lsl->response->add_data_line('Failed to obtain activity module instance.');
            $lsl->render_to_output();
            exit();
        }
    }
    
    //  Make sure that if we have a CMI, we also have a concept, and vice versa
    if ($cmi != NULL) $lsl->request->required_param('sloodleconcept', PARAM_RAW);
    else if ($sloodleconcept != NULL) $lsl->request->required_param('sloodlemoduleid', PARAM_RAW);
    
    // Which mode are we in?
    sloodle_debug_output('Checking mode...<br/>');
    if ($cmi == NULL) {
        // Browse mode
        sloodle_debug_output('***** BROWSE MODE *****<br/>');
        // Fetch a list of all available glossaries in the course
        sloodle_debug_output('Searching for available glossaries in course...<br/>');
        $glossaries = sloodle_get_visible_glossaries_in_course($lsl->request->get_course_id());
        // If the search failed, then there are probably no glossaries to select
        if (!is_array($glossaries)) $glossaries = array();
        // Construct the response header
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
        
        // Go through each glossary
        sloodle_debug_output('Iterating through all glossaries...<br/>');
        foreach ($glossaries as $id=>$g) {
            // Add this glossary to the response
            $lsl->response->add_data_line(array($id, $g->name));
        }
        
    } else {
        // Lookup mode
        sloodle_debug_output('***** LOOKUP MODE *****<br/>');
        // Lookup the concept in the glossary
        sloodle_debug_output('Searching glossary...<br/>');
        $defs = sloodle_lookup_glossary($glossary, $sloodleconcept);
        
        // Construct the response header
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
        
        // Did an error occur?
        if (!is_array($defs)) $defs = array();

        // Go through each entry
        sloodle_debug_output('Iterating through all entries...<br/>');
        foreach ($defs as $d) {
            // Make sure the concept is clean
            $cleanconcept = clean_text($d->concept);
            // Strip the definition of tags etc.                
            $cleandef = $d->definition;
            $cleandef = str_replace("\n", " ", $cleandef);
            $cleandef = str_replace("\r", " ", $cleandef);
            $cleandef = stripslashes($cleandef);
            $cleandef = strip_tags($cleandef);
            // Add this entry to the response
            $lsl->response->add_data_line(array($cleanconcept, $cleandef));            
        }
    }    
    
    // Render the output
    sloodle_debug_output('Outputting response...<br/>');
    sloodle_debug_output('<pre>');
    $lsl->response->render_to_output();
    sloodle_debug_output('</pre>');
    
    exit();
    
/////OLD CODE:

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
