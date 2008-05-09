<?php
    /**
    * Chat 1.0 configuration form.
    *
    * This is a fragment of HTML which gives the form elements for configuration of a chat object, v1.0.
    * ONLY the basic form elements should be included.
    * The "form" tags and submit button are already specified outside.
    * The $auth_obj and $sloodleauthid variables will identify the object being configured.
    *
    * @package sloodleclassroom
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // IMPORTANT: make sure this is called from within a Sloodle script
    if (!defined('SLOODLE_VERSION')) {
        error('Not called from within a Sloodle script.');
        exit();
    }
    
    // Execute everything within a function to ensure we don't mess up the data in the other file
    sloodle_display_config_form($sloodleauthid, $auth_obj);
    
    
    
    function sloodle_display_config_form($sloodleauthid, $auth_obj)
    {
    //--------------------------------------------------------
    // SETUP
        
        // Determine which course is being accessed
        $courseid = $auth_obj->course->get_course_id();
        
        // We need to fetch a list of visible chatrooms on the course
        // Get the ID of the chat type
        $rec = get_record('modules', 'name', 'chat');
        if (!$rec) {
            sloodle_debug("Failed to get chatroom module type.");
            exit();
        }
        $chatmoduleid = $rec->id;
        
        // Get all visible chatrooms in the current course
        $recs = get_records_select('course_modules', "`course` = $courseid AND `module` = $chatmoduleid AND `visible` = 1");
        if (!$recs) {
            error(get_string('nochatrooms','sloodle'));
            exit();
        }
        $chatrooms = array();
        foreach ($recs as $cm) {
            // Fetch the chatroom instance
            $inst = get_record('chat', 'id', $cm->instance);
            if (!$inst) continue;
            // Store the chatroom details
            $chatrooms[$cm->id] = $inst->name;
        }
        // Sort the list by name
        natcasesort($chatrooms);
        
        
//--------------------------------------------------------
// FORM
        
        // Ask the user to select a chatroom
        echo get_string('selectchatroom','sloodle').': ';
        choose_from_menu($chatrooms, 'sloodlemoduleid', '', '');
    
    }
    
?>


