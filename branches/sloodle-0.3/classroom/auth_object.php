<?php
    /**
    * Sloodle object authorization page.
    *
    * Allows users in Moodle to authorize or deny in-world objects' access to Moodle.
    *
    * @package sloodleclassroom
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    // Initially, the script should be accessed with a single parameter:
    //
    //  sloodleauthid = the integer ID of an (unauthorised) entry in the Sloodle 'active objects' table
    //
    // When the user clicks "yes" or "no" to confirm or deny the object's authorisation,
    //  the following additional parameters will be added:
    //
    //  sloodlecontrollerid = the ID of the controller which is to be used
    //  action = either 'confirm' or 'deny'
    //
    // If the action is 'confirm' then the object is authorised. Otherwise, its entry is deleted.
    //
    // The following parameter will usually also need to be specified:
    //
    //  sloodleobjtype = gives the type identifier of the object (not necessary if type is already registered)
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Make sure the user is logged-in
    require_login();
    // Make sure the user is not a guest
    if (isguestuser()) {
        exit(get_string('notguestaccess','sloodle'));
    }
    
    // We need to know which object is being authorised
    $sloodleauthid = required_param('sloodleauthid', PARAM_INT);
    $auth_obj = SloodleController::get_object($sloodleauthid);
    if (!$auth_obj) {
        error(get_string('objectauthnotfound','sloodle'));
        exit();
    }
    // Is the object already authorised?
    $object_authorised = $auth_obj->course->controller->is_loaded() && $auth_obj->user->is_user_loaded();
    
    // Get the object type (only necessary if it was not already in the database)
    $sloodleobjtype = null;
    if (empty($auth_obj->type)) $sloodleobjtype = required_param('sloodleobjtype', PARAM_TEXT);
    else optional_param('sloodleobjtype', null, PARAM_TEXT);
    
    // Treat this like a Sloodle session, but do not authenticate anything
    $sloodle = new SloodleSession();
    // Was a controller loaded by the request?
    $loaded_controller = false;
    if ($sloodle->course->controller->is_loaded()) {
        $loaded_controller = true;
    } else {
        // If a controller was already specified for the object, then use that
        if ($auth_obj->course->controller->is_loaded()) {
            $loaded_controller = true;
            $sloodle->course = $auth_obj->course;
        }
    }
    
    // Load the user
    $sloodle->user->load_user($USER->id);
    $sloodle->user->load_linked_avatar();
    
    // Fetch localization strings
    $stryes = get_string('yes');
    $strno = get_string('no');
    
    // Check if the confirmation action has been specified
    $action = optional_param('action', '', PARAM_TEXT);
    // If the action was not recognised, then ignore it
    if ($action != 'auth' && $action != 'cancel') $action = '';
    
    
    // Construct a breadcrumb navigation menu
    $nav = get_string('modulename', 'sloodle').' -> ';
    $nav .= get_string('objectauth','sloodle');
    // Display the page header
    print_header(get_string('objectauth','sloodle'), get_string('objectauth','sloodle'), $nav, '', '', false);
    
    
    // Was an action specified? (Don't do this is the object was already authorised)
    $action_success = false;
    if (!empty($action) && $object_authorised == false) {
        // Check if we were instructed to authorise, and also that we have a controller to authorise on
        if ($action == 'auth' && $loaded_controller == true) {
            // Make sure the user is allowed to authorise objects
            $module_context = get_context_instance(CONTEXT_MODULE, $sloodle->course->controller->get_id());
            require_capability('mod/sloodle:objectauth', $module_context);
        
            // Indicate that we are attempting authorisation
            echo '<div style="text-align:center;">';
            echo get_string('authorizingfor').$sloodle->course->get_full_name().' &gt; '.$sloodle->course->controller->get_name().'<br><br>';
        
            // Attempt to authorise the entry
            if ($sloodle->course->controller->authorise_object($auth_obj->uuid, $sloodle->user)) {
                echo '<span style="font-weight:bold; color:green;">'.get_string('objectauthsuccessful','sloodle').'</span><br>';
                $action_success = true;
            } else {
                echo '<span style="font-weight:bold; color:red;">'.get_string('objectauthfailed','sloodle').'</span><br>';
            }
            
            // Store the object's type if necessary
            if (!empty($sloodleobjtype)) {
                $sloodle->course->controller->update_object_type($auth_obj->uuid, $sloodleobjtype);
            }
            
            echo "</div><br>\n";
            
        } else if ($action == 'cancel') {
            // Attempt to delete the entry
            $sloodle->course->controller->remove_object($sloodleauthid);
            // Display the result
            print_box(get_string('objectauthcancelled', 'sloodle'), 'generalbox boxaligncenter boxwidthnarrow');
            $action_success = true;
        }
    }
    
    // If the object has already been authorised, then display a message about
    if ($object_authorised) {
        print_box_start('generalbox boxwidthnarrow boxaligncenter');
        echo '<span style="color:red; font-weight:bold; text-align:center;">'.get_string('objectauthalready','sloodle').'</span>';
        print_box_end();
    }
    
    
    // Display the information about the object
    print_box_start('generalbox boxaligncenter boxwidthnarrow');
    echo '<div style="text-align:center;">';
    echo '<span style="font-weight:bold; font-size:110%;">'.get_string('objectdetails','sloodle').'</span><br>';
    
    echo get_string('objectname','sloodle').': '.$auth_obj->name.'<br>';
    echo get_string('objectuuid','sloodle').': '.$auth_obj->uuid.'<br>';
    echo get_string('objecttype','sloodle').': ';
    if (!empty($sloodleobjtype)) echo $sloodleobjtype.'<br>';
    else if (!empty($auth_obj->type)) echo $auth_obj->type.'<br>';
    else echo '-<br>';
    
    echo '</div>';
    print_box_end();
    echo '<br>';
    
    
    // If the action was not performed or successful, then display the authorisation form
    if ($action_success == false && $object_authorised == false) {
        print_box_start('generalbox boxaligncenter boxwidthnormal');
        echo '<div style="text-align:center;">';

        // If an authorisation action was attempted but the controller failed to load, then display an error message
        if ($action == 'auth' && $loaded_controller = false) {
            echo '<span style="font-weight:bold;color:red;">'.get_string('failedauth-trydifferent','sloodle').'</span>';
        }
        
        // Get a list of controllers which the user is permitted to authorise objects on
        // (It will be a 2d array - top level will be course ID's, 2nd level will be coursemodule ID's, containing database objects from the 'sloodle' table)
        $controllers = array();
        $recs = get_records('sloodle', 'type', SLOODLE_TYPE_CTRL);
        // Make sure we have at least one controller
        if ($recs == false || count($recs) == 0) {
            error(get_string('objectauthnocontrollers','sloodle'));
            exit();
        }
        foreach ($recs as $r) {
            // Fetch the course module
            $cm = get_coursemodule_from_instance('sloodle', $r->id);
            // Check that the person can authorise objects of this module
            if (has_capability('mod/sloodle:objectauth', get_context_instance(CONTEXT_MODULE, $cm->id))) {
                // Store this controller
                $controllers[$cm->course][$cm->id] = $r;
            }
        }
        
        // Make sure that permission came through on at least one controller
        if (count($controllers) == 0) {
            error(get_string('objectauthnopermission','sloodle'));
            exit();
        }
        
        // Construct the list of course names
        $coursenames = array();
        foreach ($controllers as $cid => $ctrl) {
            $courserec = get_record('course', 'id', $cid, '','', '','', 'id,shortname,fullname');
            if (!$courserec) $coursenames[$cid] = "(unknown)";
            else $coursenames[$cid] = $courserec->fullname;
        }
        
        // Sort the list alphabetically
        natcasesort($coursenames);
        
        // Attempt to get our current controller, if possible, so we can pre-select it in the list
        $cur_controller_id = 0;
        if ($loaded_controller) {
            $cur_controller_id = $sloodle->course->controller->get_id();
        }
        
        // Display an authorisation form
        echo '<br><form action="" method="GET">';
        if (SLOODLE_DEBUG) echo '<input type="hidden" name="sloodledebug" value="true"/>';
        if (!empty($sloodleauthid)) echo '<input type="hidden" name="sloodleauthid" value="'.$sloodleauthid.'"/>';
        if (!empty($sloodleobjtype)) echo '<input type="hidden" name="sloodleobjtype" value="'.$sloodleobjtype.'"/>';
        
        // Ask the user to select a controller to authorise on
        echo get_string('selectcontroller','sloodle').':<br><br>';
        echo '<select name="sloodlecontrollerid" size="6">';
        // Go through each course
        foreach ($coursenames as $cid => $cname) { // We want to use the alphabetic sorting; get the course ID and name
            // Output a disabled entry to act as a course header
            echo "<option disabled=\"true\" style=\"font-weight:bold; font-style:italic; background-color:#eeeeee;\">{$cid}. $cname</option>\n";
            
            // Go through each controller in this course
            foreach ($controllers[$cid] as $cmid => $ctrl) { // Get each controller in the course; get the course module ID and controller data
                echo "<option ";
                if ($cmid == $cur_controller_id) echo 'selected="true" ';
                echo "style=\"margin-left:8px;\" value=\"$cmid\">{$ctrl->name}</option>\n";
            }
        }
        echo '</select><br><br>';
        
        // Ask the user if they want to confirm or deny the authorisation
        echo get_string('confirmobjectauth', 'sloodle').' ';
        echo '<select name="action" size="1">';
        echo '<option style="color:green; font-weight:bold;" value="auth" selected="true">'.$stryes.'</option>';
        echo '<option style="color:red; font-weight:bold;" value="cancel">'.$strno.'</option>';
        echo '</select><br><br>';
        
        echo '<input type="submit" value="'.get_string('submit','sloodle').'"/>';
        echo "</form>\n";
        
        echo '</div>';
        print_box_end();
    }
    
    
    
    // Finish
    print_footer();

?>
