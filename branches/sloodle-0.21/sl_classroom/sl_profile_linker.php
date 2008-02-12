<?php
    
    /**
    * Sloodle classroom profile linker.
    *
    * Allows in-world objects (typically Sloodle Sets) to interact with Sloodle classroom setup profiles in the Moodle database.
    *
    * @package sloodle
    * @copyright Copyright (c) 2006-7 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    // Classroom profiles consist of a series of object entries.
    // Each object entry consists of the object name, and relative position to the Sloodle Set.
    
    // This script is expected to be called from in-world objects.
    // The following parameters are required:
    //
    //   sloodlepwd = prim password for authenticating the request
    //   sloodleuuid = UUID of the user making the request (optional if 'sloodleavname' is specified)
    //   sloodleavname = avatar name of the user making the request (optional if 'sloodleuuid' is specified)
    //   sloodlecourseid = the ID of the course which the profile is in
    //   sloodlecmd = the operation ('command') to be carried out
    //
    // Certain modes also require the following parameters:
    //
    //   sloodleprofilename = the name of the profile being handled
    //   sloodleentries = the entries to be added to the profile
    //
    // The mode of the script is determined by the 'sloodlecmd' parameters.
    //  'newprofile' creates a new profile with the name given in 'sloodleprofilename'.
    //  'saveentries' will erase all existing entries, and add new object entries specified in 'sloodleentries' to the profile named in 'sloodleprofilename'.
    //  'listentries' will list all object entries in the profile named in 'sloodleprofilename'.
    //  'listprofiles' will list all profiles in the identified course.
    //
    
    // When saving entries to a profile, the format of each entry in 'sloodleentries' should be as follows:
    //
    //  "name|position"
    //
    // The position should be given as a string vector "<x,y,z>".
    // Note that object names should be unique within a given profile.
    //
    // Multiple entries should be separate by a double pipe ||.
    // For example, three entries might look like this:
    // 
    // "Sloodle WebIntercom|<1.4,0.9,2.6>||Sloodle Registration Booth|<2.1,3.0,3.5>||Sloodle MetaGloss|<0.0,1.2,2.6>"
    //
    // There is not response data.
    
    // When adding a new profile, the response code will be 1 for success, and the ID and name of the profile will be given on the sole data line, e.g.:
    //
    //  23|My Profile
    
    // When returning the entries in a given profile, each line contain the entry ID, name and position, e.g.:
    //
    //  6|Sloodle WebIntercom|<1.4,0.9,2.6>
    //  7|Sloodle Registration Booth|<2.1,3.0,3.5>
    //  11|Sloodle MetaGloss|<0.0,1.2,2.6>
    
    // When returning the available profiles for the course, each one will be returned by id and name on its own line, e.g.:
    //
    //  2|topic1
    //  15|demo
    //  26|assessment
    
    // Some specific error codes which may be returned:
    //
    //   -901 = unknown profile error
    //   -902 = profile does not exist
    //   -903 = profile already exists (i.e. cannot be created again)
    //   -904 = unknown profile command
    //
    // In some cases, error code -103 may be returned if no data was found.
    

	require_once('../config.php');
	require_once(SLOODLE_DIRROOT.'/sl_debug.php');
	require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');
	require_once(SLOODLE_DIRROOT.'/lib/sl_classroomlib.php');

    
    sloodle_debug_output('<br/>');
    
    // Create an LSL handler and process the basic request data
    sloodle_debug_output('Creating LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output('Processing basic request data...<br/>');
    $lsl->request->process_request_data();
    
    // Make sure the course id was specified
    $lsl->request->required_param('sloodlecourseid', PARAM_INT);
    
    // Ensure the request is authenticated
    sloodle_debug_output('Authenticating request...<br/>');
    $lsl->request->authenticate_request();
    // Attempt to login the user
    sloodle_debug_output('Logging-in user...<br/>');
    $lsl->login_by_request();
    
    // Make sure the user is authorized to manage profiles for this course
    sloodle_debug_output('Checking user authority...<br/>');
    if (!(isadmin() || isteacher($lsl->request->get_course_id()))) {
        // User is not authorized
        sloodle_debug_output('-&gt; ERROR: user not admin or teacher.<br/>');
        $lsl->response->set_status_code(-331);
        $lsl->response->set_status_descriptor('USER_AUTH');
        $lsl->response->add_data_line('User is not authorised to manage Sloodle classroom profiles for this course.');
        $lsl->response->render_to_output();
        exit();
    }    
    
    // Obtain the additional parameters
    sloodle_debug_output('Obtaining additional parameters...<br/>');
    $sloodlecmd = strtolower($lsl->request->required_param('sloodlecmd', PARAM_RAW));
    $sloodleprofilename = optional_param('sloodleprofilename', NULL, PARAM_RAW);
    if ($sloodleprofilename != NULL) $sloodleprofilename = trim($sloodleprofilename);
    $sloodleentries = optional_param('sloodleentries', NULL, PARAM_RAW);
        
    
    // Check which mode we're in
    sloodle_debug_output("Command type: '$sloodlecmd'...<br/>");
    switch ($sloodlecmd) {
    
    case 'newprofile':
        // We are to create a new profile
        sloodle_debug_output('Creating new profile...<br/>');
        // Make sure the profile name was specified
        $lsl->request->required_param('sloodleprofilename', PARAM_RAW);
        sloodle_debug_output("-&gt; Profile name: '$sloodleprofilename'<br/>");
        
        // Check that the named profile does not already exist for this course
        sloodle_debug_output('Checking if profile already exists...<br/>');
        $profile = sloodle_get_classroom_profile_by_name($sloodleprofilename, $lsl->request->get_course_id());
        
        if ($profile ===FALSE) {
            // Construct a new profile object
            sloodle_debug_output('Constructing new profile object...<br/>');
            $profile = new stdClass();
            $profile->name = $sloodleprofilename;
            $profile->courseid = $lsl->request->get_course_id();
            // Insert it into the database
            sloodle_debug_output('Adding profile to database...<br/>');
            $id = sloodle_add_classroom_profile($profile);
            if ($id === FALSE) {
                $lsl->response->set_status_code(-901);
                $lsl->response->set_status_descriptor('PROFILE');
                $lsl->response->add_data_line('Failed to add profile to database.');
                break;
            }
            $lsl->response->set_status_code(1);
        } else {
            sloodle_debug_output('-&gt; Profile exists.<br/>');
            $lsl->response->set_status_code(-903);
            $id = $profile->id;
        }
        
        // Everything seems fine
        $lsl->response->set_status_descriptor('OK');        
        $lsl->response->add_data_line(array($id, $sloodleprofilename));
        break;
        
        
    case 'listprofiles':
        // We are to list the profiles in the current course
        sloodle_debug_output('Fetching list of profiles in course...<br/>');
        $profiles = sloodle_get_classroom_profiles($lsl->request->get_course_id());
        // Make sure it was successful
        if (!is_array($profiles)) $profiles = array();
        
        // List each one
        sloodle_debug_output('Iterating through all profiles...<br/>');
        foreach ($profiles as $p) {
            $lsl->response->add_data_line(array($p->id, $p->name));
        }
        
        // Construct the rest of the response
        sloodle_debug_output('Constructing remainder of response...<br/>');
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');        
        break;
        
        
    case 'listentries':
        // We are to list all entries in the given profile
        sloodle_debug_output('Listing all entries in profile...<br/>');
        // Make sure the profile name was specified
        $lsl->request->required_param('sloodleprofilename', PARAM_RAW);
        sloodle_debug_output("-&gt; Profile name: '$sloodleprofilename'<br/>");
        
        // Attempt to fetch the named profile
        sloodle_debug_output('Fetching profile...<br/>');
        $profile = sloodle_get_classroom_profile_by_name($sloodleprofilename, $lsl->request->get_course_id());
        // Make sure it was successful
        if (!is_object($profile)) {
            sloodle_debug_output('-&gt; ERROR: failed to fetch profile<br/>');
            $lsl->response->set_status_code(-103);
            $lsl->response->set_status_descriptor('SYSTEM');
            $lsl->response->add_data_line("Failed to find profile '$sloodleprofilename' in specified course.");
            break;
        }
               
        // Fetch all entries in the profile
        sloodle_debug_output('Fetching all entries in profile...<br/>');
        $entries = sloodle_get_classroom_profile_entries($profile->id);
        // Make sure it was successful
        if (!is_array($entries)) $entries = array();
        
        // Go through each entry
        sloodle_debug_output('Iterating through list of entries...<br/>');
        foreach ($entries as $e) {
            // Add this entry to the response
            $lsl->response->add_data_line(array($e->id, $e->name, $e->relative_position));
        }
        
        // Construct the rest of the response
        sloodle_debug_output('Constructing remainder of response...<br/>');
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');            
        break;
        
        
    case 'saveentries':
        // We are to save a new list of entries in the profile
        sloodle_debug_output('Saving profile entries...<br/>');
        // Make sure the profile name was specified
        $lsl->request->required_param('sloodleprofilename', PARAM_RAW);
        sloodle_debug_output("-&gt; Profile name: '$sloodleprofilename'<br/>");
        // Make sure the entries string was specified
        if ($sloodleentries == NULL || empty($sloodleentries)) {
            sloodle_debug_output('ERROR: entries string not specified.<br/>');
            $lsl->response->set_status_code(-811);
            $lsl->response->set_status_descriptor('REQUEST');
            $lsl->response->add_data_line('Expected parameter \'sloodleentries\' not specified.');
            break;
        }
        
        // Attempt to fetch the named profile
        sloodle_debug_output('Fetching profile...<br/>');
        $profile = sloodle_get_classroom_profile_by_name($sloodleprofilename, $lsl->request->get_course_id());
        // Make sure it was successful
        if (!is_object($profile)) {
            sloodle_debug_output('-&gt; ERROR: failed to fetch profile<br/>');
            $lsl->response->set_status_code(-103);
            $lsl->response->set_status_descriptor('SYSTEM');
            $lsl->response->add_data_line("Failed to find profile '$sloodleprofilename' in specified course.");
            break;
        }
               
        // Fetch all entries in the profile
        sloodle_debug_output('Fetching all entries in profile...<br/>');
        $raw_entries = sloodle_get_classroom_profile_entries($profile->id);
        $entries = array();
        // Was it successful?
        if (is_array($raw_entries)) {
            // Yes - convert it to an associative array of name to entry
            sloodle_debug_output('Converting entries array...<br/>');
            foreach ($raw_entries as $e) {
                $entries[$e->name] = $e;
            }
        } else {
            // No - just an empty array
            sloodle_debug_output('-&gt; No entries.<br/>');
        }
        
        // Split the entries list into each individual entry command
        sloodle_debug_output('Processing entries string...<br/>');
        $new_entries_str = explode("||", $sloodleentries);
        $new_entries = array(); // This will store our new entries that we create
        // Go through each one
        foreach ($new_entries_str as $nes) {
            // Extract all parts of the entry
            $parts = explode("|", $nes);
            // Make sure we have the expected number of parts
            if (count($parts) != 2) {
                sloodle_debug_output("ERROR: incorrect number of parts in entry string '$nes'<br/>");
                continue;
            }
            
            // Construct a new profile entry object
            $this_entry = new stdClass();
            $this_entry->sloodle_classroom_setup_profile_id = $profile->id;
            $this_entry->name = $parts[0];
            $this_entry->uuid = '';
            $this_entry->relative_position = $parts[1];
            // Add the entry to the list of entries
            $new_entries[] = $this_entry;
        }
        
               
        // Save the new entries
        sloodle_debug_output('Saving profile entries...<br/>');
        if (sloodle_save_classroom_profile_entries($profile->id, $new_entries)) {
            sloodle_debug_output('-&gt;Success.<br/>');
            $lsl->response->set_status_code(1);
            $lsl->response->set_status_descriptor('OK');
        } else {
            sloodle_debug_output('-&gt;Failed.<br/>');
            $lsl->response->set_status_code(-901);
            $lsl->response->set_status_descriptor('PROFILE');
            $lsl->response->add_data_line('Failed to save profile entries.');
        }        
        break;
    
    
    default:
        // Unknown command
        sloodle_debug_output('-&gt; ERROR: unknown command type.<br/>');
        $lsl->response->set_status_code(-904);
        $lsl->response->set_status_descriptor('PROFILE');
        $lsl->response->add_data_line("Unknown profile command: '$sloodleprofilecmd'");
        break;
    }
    
    // Render the output
    sloodle_debug_output('Outputting response...<br/>');
    sloodle_debug_output('<pre>');
    $lsl->response->render_to_output();
    sloodle_debug_output('</pre>');
    
    exit();
?>
