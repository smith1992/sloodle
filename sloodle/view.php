<?php
    // This file is part of the Sloodle project (www.sloodle.org)

    /**
    * Index page for listing a particular instances of the Sloodle module.
    * Used as an interface script by the Moodle framework.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */

    /** Sloodle/Moodle configuration script. */
    require_once('sl_config.php');
    /** Sloodle core library functionality */
    require_once(SLOODLE_DIRROOT.'/lib.php');
    /** General Sloodle functions. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    /** Sloodle course data. */
    require_once(SLOODLE_LIBROOT.'/course.php');
    
    /** Viewing functionality for the Sloodle Controller. */
    require_once(SLOODLE_DIRROOT.'/view/view_controller.php');
    /** Viewing functionality for the Distributor. */
    require_once(SLOODLE_DIRROOT.'/view/view_distributor.php');    
    /** Viewing functionality for the Slideshow. */
    require_once(SLOODLE_DIRROOT.'/view/view_slideshow.php');
    /** Viewing functionality for the Map. */ 
    require_once(SLOODLE_DIRROOT.'/view/view_map.php');
    

    // Fetch our request parameters
    $id = optional_param('id', 0, PARAM_INT); // Course Module instance ID
    $s = optional_param('s', 0, PARAM_INT); // Sloodle instance ID
    $editing = optional_param('edit', 0, PARAM_BOOL); // Editing mode
    $formsubmit = optional_param('formsubmit', 0, PARAM_BOOL); // Form submission
    
    
    // Fetch string table text
    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
    $strsavechanges = get_string('savechanges');
    $stryes = get_string('yes');
    $strno = get_string('no');
        
    // Attempt to fetch the course module instance
    if ($id) {
        if (! $cm = get_coursemodule_from_id('sloodle', $id)) {
            error("Course Module ID was incorrect");
        }
    } else if ($s) {       
        if (! $cm = get_coursemodule_from_instance('sloodle', $s)) {
            error("Instance ID was incorrect");
        }
    } else {
        error('Must specify a course module or a module instance');
    }
    
    // Get the course data
    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }
    // Get the Sloodle instance
    if (! $sloodle = get_record('sloodle', 'id', $cm->instance)) {
        error('Failed to find Sloodle module instance.');
    }
    
    // Get the Sloodle course data
    $sloodle_course = new SloodleCourse();
    if (!$sloodle_course->load($course)) error(get_string('failedcourseload','sloodle'));
    
    // Ensure that the user is logged-in for this course
    require_course_login($course, true, $cm);
    $module_context = get_context_instance(CONTEXT_MODULE, $cm->id);
    $course_context = get_context_instance(CONTEXT_COURSE, $course->id);
    
    // Is the user allowed to edit the module?
    $canedit = false;
    if (has_capability('moodle/course:manageactivities', $module_context)) {
        $canedit = true;
    } else {
        $editing = false;
    }
    
    // Only show the editing button if the user is allowed to edit this stuff
    $editbuttons = '';
    if ($canedit) $editbuttons = $buttontext = update_module_button($cm->id, $course->id, $strsloodle);

    // Display the page header
    $navigation = "<a href=\"index.php?id=$course->id\">$strsloodles</a> ->";
    print_header_simple(format_string($sloodle->name), "", "$navigation ".format_string($sloodle->name), "", "", true, $editbuttons, navmenu($course, $cm));

    // We can display the Sloodle module info... log the view
    add_to_log($course->id, 'sloodle', 'view sloodle module', "view.php?id=$cm->id", "$sloodle->id", $cm->id);
    
    // Get the full Sloodle module type name
    $fulltypename = get_string("moduletype:{$sloodle->type}", 'sloodle');
    
    
    
//-----------------------------------------------------
    // Quick links and other info (top right of page)
    
    // Open the section
    echo "<div style=\"text-align:right; font-size:80%;\">\n";
    
    // Link to own avatar profile
    echo "<a href=\"{$CFG->wwwroot}/mod/sloodle/view/view_user.php?id={$USER->id}&course={$course->id}\">".get_string('viewmyavatar', 'sloodle')."</a><br>\n";
    // Link to user management
    if (has_capability('moodle/user:viewhiddendetails', $course_context)) {
        echo "<a href=\"{$CFG->wwwroot}/mod/sloodle/view/view_users.php?course={$course->id}\">".get_string('sloodleuserprofiles', 'sloodle')."</a><br>\n";
    }
    
    // Auto-registration status
    if (!sloodle_autoreg_enabled_site()) {
        echo '<span style="color:#aa0000;">'.get_string('autoreg:disabled','sloodle')."</span><br>\n";
    } else if ($sloodle_course->get_autoreg()) {
        echo '<span style="color:#008800;">'.get_string('autoreg:courseallows','sloodle')."</span><br>\n";
    } else {
        echo '<span style="color:#aa0000;">'.get_string('autoreg:coursedisallows','sloodle')."</span><br>\n";
    }
    
    // Auto-enrolment status
    if (!sloodle_autoenrol_enabled_site()) {
        echo '<span style="color:#aa0000;">'.get_string('autoenrol:disabled','sloodle')."</span><br>\n";
    } else if ($sloodle_course->get_autoenrol()) {
        echo '<span style="color:#008800;">'.get_string('autoenrol:courseallows','sloodle')."</span><br>\n";
    } else {
        echo '<span style="color:#aa0000;">'.get_string('autoenrol:coursedisallows','sloodle')."</span><br>\n";
    }
    
    // Display the link for editing course settings
    if (has_capability('moodle/course:update', $course_context)) {
        echo "<a href=\"{$CFG->wwwroot}/mod/sloodle/view/view_course.php?id={$course->id}\">Edit Sloodle course settings</a><br>\n";
    }
    
    
    echo "</div>\n";
    
    
    
//-----------------------------------------------------
    // Check access

    // If the module is hidden, then can the user still view it?
    if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $module_context)) {
        // No - issue a notice
        notice(get_string("activityiscurrentlyhidden"));
    }
    
    
//-----------------------------------------------------
    // Module info

    // Display the module name
    $img = '<img src="icon.gif" width="16" height="16" alt=""/> ';
    print_heading($img.$sloodle->name, 'center');
    
    // Display the module type and description
    echo '<h4 style="text-align:center;">'.get_string('moduletype', 'sloodle').': '.$fulltypename;
    echo helpbutton("moduletype_{$sloodle->type}", $fulltypename, 'sloodle', true, false, '', true).'</h4>';
    
    //print_box_start('generalbox boxaligncenter boxwidthnormal', '');
    //echo '<p style="text-align:center;">'.$sloodle->intro.'</p>';
    //print_box_end();
    
    $generalintro = '';
    if ($sloodle->type == SLOODLE_TYPE_CTRL) $generalintro = '<p style="font-style:italic;">'.get_string('controllerinfo','sloodle').'</p>';
    print_box($generalintro.$sloodle->intro, 'generalbox', 'intro'); // Let's be consistent with other modules!
    
    
//-----------------------------------------------------
    // Main view

    print_box_start('generalbox boxaligncenter boxwidthwide');

    // We need to kow the result of the display attempt
    $result = false;
    // Check what type the module is
    switch ($sloodle->type) {
    case SLOODLE_TYPE_CTRL:
        $result = sloodle_view_controller($cm, $sloodle, $canedit);
        break;
        
    case SLOODLE_TYPE_DISTRIB:
        $result = sloodle_view_distributor($cm, $sloodle, $canedit);
        break;
        
    case SLOODLE_TYPE_SLIDESHOW:
        $result = sloodle_view_slideshow($cm, $sloodle, $canedit);
        break;

    case SLOODLE_TYPE_MAP:
        $result = sloodle_view_map($cm, $sloodle, $canedit);
        break;
        
    default:
        // Unknown type
        notice(get_string('moduletypeunknown'));
        break;
    }
    
    // Were we able to display the information?
    if (!$result) notice(get_string('error'));
    
    print_box_end();
    
    print_footer($course);
    
?>
