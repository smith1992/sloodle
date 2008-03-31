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
    
    /** Viewing functionality for the Sloodle Controller. */
    require_once(SLOODLE_DIRROOT.'/view/view_controller.php');
    /** Viewing functionality for the Distributor. */
    require_once(SLOODLE_DIRROOT.'/view/view_distributor.php');
    

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
    
    // Ensure that the user is logged-in for this course
    require_course_login($course, true, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    
    // Is the user allowed to edit the module?
    $canedit = false;
    if (has_capability('moodle/course:manageactivities', get_context_instance(CONTEXT_MODULE, $cm->id))) {
        $canedit = true;
    } else {
        $editing = false;
    }
    
    // Only show the editing buttons if the user is allowed to edit this stuff
    $editbuttons = '';
    if ($canedit) $editbuttons = $buttontext = update_module_button($cm->id, $course->id, $strsloodle);

    // Display the page header
    $navigation = "<a href=\"index.php?id=$course->id\">$strsloodles</a> ->";
    print_header_simple(format_string($sloodle->name), "", "$navigation ".format_string($sloodle->name), "", "", true, $editbuttons, navmenu($course, $cm));


    // If the module is hidden, then can the user still view it?
    if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
        // No - issue a notice
        notice(get_string("activityiscurrentlyhidden"));
    }
    
    // Find out current groups mode
    $groupmode = groupmode($course, $cm);
    $currentgroup = setup_and_print_groups($course, $groupmode, 'view.php?id=' . $cm->id);

    // We can display the Sloodle module info... log the view
    add_to_log($course->id, 'sloodle', 'view sloodle module', "view.php?id=$cm->id", "$sloodle->id", $cm->id);
    
    // Get the full Sloodle module type name
    $fulltypename = get_string("moduletype:{$sloodle->type}", 'sloodle');
    
    // Display the module name
    $img = '<img src="icon.gif" width="16" height="16" alt=""/> ';
    print_heading($img.$sloodle->name, 'center');
    
    // Display the module type and description
    echo '<h4 style="text-align:center;">'.get_string('moduletype', 'sloodle').': '.$fulltypename;
    echo helpbutton("moduletype_{$sloodle->type}", $fulltypename, 'sloodle', true, false, '', true).'</h4>';
    
    print_box_start('generalbox boxaligncenter boxwidthnormal', '');
    echo '<p style="text-align:center;">'.$sloodle->intro.'</p>';
    print_box_end();
    
    
    print_box_start('generalbox boxaligncenter boxwidthwide');

    // We need to kow the result of the display attempt
    $result = false;
    // Check what type the module is
    switch ($sloodle->type) {
    case SLOODLE_TYPE_CTRL:
        $result = sloodle_view_controller($sloodle, $canedit);
        break;
        
    case SLOODLE_TYPE_DISTRIB:
        $result = sloodle_view_distributor($sloodle, $canedit);
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