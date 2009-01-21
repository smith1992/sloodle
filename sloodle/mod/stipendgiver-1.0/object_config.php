<?php
    /**
    * Demo 1.0 configuration form.
    *
    * This is a fragment of HTML which gives the form elements for configuration of the SLOODLE demo object, v1.0.
    * ONLY the basic form elements should be included.
    * The "form" tags and submit button are already specified outside.
    * The $auth_obj and $sloodleauthid variables will identify the object being configured.
    *
    * The name of each form element becomes the name of a configuration parameter which is passed (via link message) to your scripts in SL.
    * For example, a form element called "sloodlemoduleid" will pass a value to your script in SL called "sloodlemoduleid".
    *
    *
    * @package sloodle
    * @copyright Copyright (c) 2009 Sloodle (various contributors)
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
        
        
    //--------------------------------------------------------
    // FORM
    
        // If the object is already configured, then we need to get its current configuration.
        // This function will grab an array of configuration settings from the database.
        $settings = SloodleController::get_object_configuration($sloodleauthid);
        
        // Use the "sloodle_get_value" function to extract specific settings from the array.
        // The second argument names the parameter, and the 3rd gives the default initial value.
        $sloodlemoduleid = (int)sloodle_get_value($settings, 'sloodlemoduleid', 0);
        $stipend = sloodle_get_value($settings, 'stipendgiver_amount', '0');
        $stipendintent = sloodle_get_value($settings, 'stipendintent', 0);
        
    
    ///// GENERAL CONFIGURATION /////
        // We will now display the configuration form.
    
        // Create a new section box for general configuration options
        print_box_start('generalbox boxaligncenter');
        echo '<h3>'.get_string('generalconfiguration','sloodle').'</h3>';
        
     
    
        // Display a text box for some random text
        echo 'How much is the stipend? '; 
        echo '<input type="text" name="stipend" id="stipend" value="'.$stipend.'" size="20" maxlength="20" />';
        echo '<br><br>"What do you want students to use the stipend for? <input type="text" name="intent" id="intent" value="'.$intent.'" size="40"  maxlength="40" />';
        echo "<br><br>\n";
        
      
        // Close the general section
        print_box_end();
        
        
    ///// ACCESS LEVELS /////
        // This is common to nearly all objects, although variations are possible.
        // There are 3 access settings, in two categories:
        //  In-world: use and control
        //  Server: access
        //
        // The in-world 'use' setting determines who can generally use the object, whether it is public, limited to an SL group, or owner-only. (Public by default)
        // The in-world 'control' setting determines who has authority to control the object, which can similarly be public, group, or owner-only. (Owner-only by default)
        // The server access lets you limit usage to avatars which are registered or enrolled, or to members of staff. By default though, it is public.
        //
        // The following function displays the appropriate form data.
        // We pass in the existing settings so that it can setup defaults.
        // The subsequent 3 parameters determine if each type of access setting should be visible, in the order specified above.
        // They are optional, and all default to true if not specified.
    
        sloodle_print_access_level_options($settings, true, true, true);
        
    }
    
?>


