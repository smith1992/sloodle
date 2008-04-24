<?php
    // This file is part of the Sloodle project (www.sloodle.org)

    /**
    * This page shows and/or allows editing of Sloodle course settings.
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
    require_once('../sl_config.php');
    /** Sloodle core library functionality */
    require_once(SLOODLE_DIRROOT.'/lib.php');
    /** General Sloodle functions. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    /** Sloodle course data. */
    require_once(SLOODLE_LIBROOT.'/course.php');   
    
    

    // Fetch our request parameters
    $id = optional_param('id', 0, PARAM_INT); // Course Module instance ID
    
    
    // Fetch string table text
    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
    $strsavechanges = get_string('savechanges');
    $stryes = get_string('yes');
    $strno = get_string('no');
    
        
    // Attempt to fetch the course module instance
    if ($id) {
        if (!$course = get_record('course', 'id', $id)) {
            error('Could not find course');
        }
    } else {
        error('Must specify a course ID');
    }
    
    // Get the Sloodle course data
    $sloodle_course = new SloodleCourse();
    if (!$sloodle_course->load($course)) error(get_string('failedcourseload','sloodle'));
    
    // Ensure that the user is logged-in to this course
    require_login($course->id);
    $course_context = get_context_instance(CONTEXT_COURSE, $course->id);
    
    // Do not allow guest access
    if (isguestuser()) {
        error(get_string('noguestaccess', 'sloodle'));
        exit();
    }
    
    // Log the view
    add_to_log($course->id, 'course', 'view sloodle data', "mod/sloodle/view/view_course.php?id={$course->id}", "$course->id");
    
    // Is the user allowed to edit this course?
    if (!has_capability('moodle/course:update', $course_context)) {
        error(get_string('insufficientpermissiontoviewpage', 'sloodle'));
        exit();
    }

    // Display the page header
    $navigation = "<a href=\"{$CFG->wwwroot}/mod/sloodle/view/view_course.php?id={$course->id}\">".get_string('courseconfig','sloodle')."</a>";
    print_header_simple(get_string('courseconfig','sloodle'), "", $navigation, "", "", true, '', navmenu($course));
    
    
//------------------------------------------------------    
    
    // If the form has been submitted, then process the input
    if (isset($_REQUEST['submit'])) {
        // Get the parameters
        $form_autoreg = required_param('autoreg', PARAM_BOOL);
        $form_autoenrol = required_param('autoenrol', PARAM_BOOL);
        
        // Update the Sloodle course object
        if ($form_autoreg) $sloodle_course->enable_autoreg();
        else $sloodle_course->disable_autoreg();
        if ($form_autoenrol) $sloodle_course->enable_autoenrol();
        else $sloodle_course->disable_autoenrol();
        
        // Update the database
        if ($sloodle_course->write()) {
            redirect("view_course.php?id={$course->id}", get_string('changessaved'), 4);
            exit();
        } else {
            print_box(get_string('error'), 'generalbox boxwidthnarrow boxaligncenter');
        }
    }
    
//------------------------------------------------------

    // Display info about Sloodle course configuration
    echo "<h1 style=\"text-align:center;\">".get_string('courseconfig','sloodle')."</h1>\n";
    print_box(get_string('courseconfig:info','sloodle'), 'generalbox boxaligncenter boxwidthnormal');
    echo "<br/>\n";

//------------------------------------------------------

    // Get some localization strings
    $strenabled = get_string('enabled','sloodle');
    $strdisabled = get_string('disabled','sloodle');
    $strsubmit = get_string('submit', 'sloodle');
    
    // Get the initial form values
    $val_autoreg = (int)(($sloodle_course->get_autoreg()) ? 1 : 0);
    $val_autoenrol = (int)(($sloodle_course->get_autoenrol()) ? 1 : 0);
    
    // Make the selection options for enabling/disabling items
    $selection_menu = array(0 => $strdisabled, 1 => $strenabled);
    
    // Start the box
    print_box_start('generalbox boxaligncenter boxwidthnormal');
    echo '<center>';
    
    // Start the form (including a course ID hidden parameter)
    echo "<form action=\"view_course.php\" method=\"POST\">\n";
    echo "<input type=\"hidden\" name=\"id\" value=\"{$course->id}\">\n";
    
// AUTO REGISTRATION //
    echo "<p>\n";
    helpbutton('auto_registration', get_string('help:autoreg','sloodle'), 'sloodle', true, false, '', false);
    echo get_string('autoreg', 'sloodle').': ';
    choose_from_menu($selection_menu, 'autoreg', $val_autoreg, '', '', 0, false);
    // Add the site status
    if (!sloodle_autoreg_enabled_site()) echo '<br/>&nbsp;<span style="color:red; font-style:italic; font-size:80%;">('.get_string('autoreg:disabled','sloodle').')</span>';
    echo "</p>\n";
    
// AUTO ENROLMENT //
    echo "<p>\n";
    helpbutton('auto_enrolment', get_string('help:autoenrol','sloodle'), 'sloodle', true, false, '', false);
    echo get_string('autoenrol', 'sloodle').': ';
    choose_from_menu($selection_menu, 'autoenrol', $val_autoenrol, '', '', 0, false);
    // Add the site status
    if (!sloodle_autoenrol_enabled_site()) echo '<br/>&nbsp;<span style="color:red; font-style:italic; font-size:80%;">('.get_string('autoenrol:disabled','sloodle').')</span>';
    echo '</p>';
    
    
    // Close the form, along with a submit button
    echo "<input type=\"submit\" value=\"$strsubmit\" name=\"submit\"\>\n</form>\n";
    
    // Finish the box
    echo '</center>';
    print_box_end();
    
//------------------------------------------------------

    // Display a list of layouts in this course
    print_box_start('generalbox boxaligncenter boxwidthnarrow');
    
    // TEMP STUFF HERE!
    $layouts = $sloodle_course->get_layout_names();
    if (!$layouts || count($layouts) == 0) echo "<p>No layouts defined in this course.</p>";
    else {
        echo '<div style="text-align:center;">';
        echo "<h4>Layout Profiles</h4>\n";
        foreach ($layouts as $l) {
            echo "$l<br>\n";
        }
        echo '</div>';
    }
    
    print_box_end();

//------------------------------------------------------
    
    print_footer($course);
    
?>