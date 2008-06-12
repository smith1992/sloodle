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
    require_capability('moodle/course:update', $course_context);

    // Display the page header
    $navigation = "<a href=\"{$CFG->wwwroot}/mod/sloodle/view/view_course.php?id={$course->id}\">".get_string('courseconfig','sloodle')."</a>";
    print_header_simple(get_string('courseconfig','sloodle'), "", $navigation, "", "", true, '', navmenu($course));
    
    
//------------------------------------------------------    
    
    // If the form has been submitted, then process the input
    if (isset($_REQUEST['submit_course_options'])) {
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
    echo '<div style="text-align:center;"><h3>'.get_string('coursesettings','sloodle').'</h3>';
    
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
    echo "<input type=\"submit\" value=\"$strsubmit\" name=\"submit_course_options\"\>\n</form>\n";
    
    // Finish the box
    echo '</div>';
    print_box_end();

    
//------------------------------------------------------

    // Loginzone information
    print_box_start('generalbox boxaligncenter boxwidthnarrow');
    echo '<div style="text-align:center;"><h3>'.get_string('loginzonedata','sloodle').'</h3>';
    
    $lastupdated = '('.get_string('unknown','sloodle').')';
    if ($sloodle_course->get_loginzone_time_updated() > 0) $lastupdated = date('Y-m-d H:i:s', $sloodle_course->get_loginzone_time_updated());
        
    echo get_string('position','sloodle').': '.$sloodle_course->get_loginzone_position().'<br>';
    echo get_string('size','sloodle').': '.$sloodle_course->get_loginzone_size().'<br>';
    echo get_string('region','sloodle').': '.$sloodle_course->get_loginzone_region().'<br>';
    echo get_string('lastupdated','sloodle').': '.$lastupdated.'<br>';
    echo '<br>';
    
    
    // Have we been instructed to clear all pending allocations?
    if (isset($_REQUEST['clear_loginzone_allocations'])) {
        // Delete all allocations relating to this course
        delete_records('sloodle_loginzone_allocation', 'course', $course->id);
    }
    
    // Create a form
    echo "<form action=\"view_course.php\" method=\"POST\">\n";
    echo "<input type=\"hidden\" name=\"id\" value=\"{$course->id}\">\n";
    // Determine how many allocations there are for this course
    $allocs = count_records('sloodle_loginzone_allocation', 'course', $course->id);
    echo get_string('pendingallocations','sloodle').': '.$allocs.'&nbsp;&nbsp;';
    echo '<input type="submit" name="clear_loginzone_allocations" value="'.get_string('delete','sloodle').'"/>';
    echo "</form>\n";
    
    
    echo '</div>';
    print_box_end();

    
//------------------------------------------------------

    // Check the user's permissions regarding layouts
    $layouts_can_use = has_capability('mod/sloodle:uselayouts', $course_context);
    $layouts_can_edit = has_capability('mod/sloodle:editlayouts', $course_context);
    
    // Only display the layouts if they can use them
    if ($layouts_can_use) {

        // Start the section
        print_box_start('generalbox boxaligncenter boxwidthnormal');
        echo '<div style="text-align:center;"><h3>'.get_string('storedlayouts','sloodle').'</h3>';
    
        // Has a delete layouts action been requested, and is it permitted for this user?
        if (isset($_REQUEST['delete_layouts']) && $layouts_can_edit == true) {
            
            // Count how many layouts we delete
            $numdeleted = 0;
            
            // Go through each request parameter
            foreach ($_REQUEST as $name => $val) {
                // Is this a delete objects request?
                if ($val != 'true') continue;
                $parts = explode('_', $name);
                if (count($parts) == 2 && $parts[0] == 'sloodledeletelayout') {
                    // Only delete the layout if it belongs to the course
                    if (delete_records('sloodle_layout', 'course', $course->id, 'id', (int)$parts[1])) {
                        $numdeleted++;
                        // Delete associated entries too
                        delete_records('sloodle_layout_entry', 'layout', (int)$parts[1]);
                    }
                    
                }
            }
            
            // Indicate our results
            echo '<span style="color:red; font-weight:bold;">'.get_string('numdeleted','sloodle').': '.$numdeleted.'</span><br><br>';
        }
        
        // This will store the "disabled" attribute for our delete controls, if necessary
        $disabledattr = ' disabled="true" ';
        if ($layouts_can_edit) $disabledattr = '';
        
        // Get all layouts stored in this course
        $recs = get_records('sloodle_layout', 'course', $course->id, 'name');
        if (is_array($recs) && count($recs) > 0) {
            // Construct a table
            $layouts_table = new stdClass();
            $layouts_table->head = array(get_string('name','sloodle'),get_string('numobjects','sloodle'),get_string('lastupdated','sloodle'),'');
            $layout_table->align = array('left', 'left', 'left', 'center');
            foreach ($recs as $layout) {
                // Get the number of objects associated with this layout
                $numobjects = count_records('sloodle_layout_entry', 'layout', $layout->id);
                $timeupdated = ((int)$layout->timeupdated == 0) ? '('.get_string('unknown','sloodle').')' : date('Y-m-d H:i:s', (int)$layout->timeupdated);
                $layouts_table->data[] = array($layout->name, $numobjects, $timeupdated, "<input $disabledattr type=\"checkbox\" name=\"sloodledeletelayout_{$layout->id}\" value=\"true\" /");
            }
            
            // Display a form and the table
            echo '<form action="view_course.php" method="POST">';
            echo '<input type="hidden" name="id" value="'.$course->id.'"/>';
            
            print_table($layouts_table);
            if ($layouts_can_edit) echo '<input type="submit" value="'.get_string('deleteselected','sloodle').'" name="delete_layouts"/>'; 
            
            echo '</form>';
            
        } else {
            echo '<span style="text-align:center;color:red">'.get_string('noentries','sloodle').'</span><br>';
        }
        
        echo '</div>';
        print_box_end();
    }

//------------------------------------------------------
    
    print_footer($course);
    
?>