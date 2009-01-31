<?php
    // This file is part of the Sloodle project (www.sloodle.org)

    /**
    * Index page for listing all instances of the Sloodle module.
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
    
    // Fetch the course ID from request parameters
    $id = optional_param('id', 0, PARAM_INT);
    
    
    // Fetch the course data
    $course = null;
    if ($id) {
        if (! $course = get_record("course", "id", $id)) {
            error("Course ID is incorrect");
        }
    } else {
        if (! $course = get_site()) {
            error("Could not find a top-level course!");
        }
    }
    
    // Get the Sloodle course data
    $sloodle_course = new SloodleCourse();
    if (!$sloodle_course->load($course)) error(get_string('failedcourseload','sloodle'));

    // Require that the user logs in
    require_login($course, false);
    // Ensure that the user is logged-in for this course
    $course_context = get_context_instance(CONTEXT_COURSE, $course->id);
    
    // Log this page view
    add_to_log($course->id, "sloodle", "view sloodle modules", "index.php?id=$course->id");

    // Fetch our string table data
    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
    $strid = get_string('ID', 'sloodle');
    $strname = get_string('name', 'sloodle');
    $strdescription = get_string('description');
    $strmoduletype = get_string('moduletype', 'sloodle');
    
    // Fetch the full names of each module type
    $sloodle_type_names = array();
    foreach ($SLOODLE_TYPES as $ST) {        
        // Get the module type name
        $sloodle_type_names[$ST] = get_string("moduletype:$ST", 'sloodle');
    }
    
    // We're going to make one table for each module type
    $sloodle_tables = array();
    
    // Get all Sloodle modules for the current course
    $sloodles = get_records('sloodle', 'course', $course->id, 'name');
    if (!$sloodles) $sloodles = array();
    // Go through each module    
    foreach ($sloodles as $s) {
        // Prepare this line of data
        $line = array();
        $line[] = $s->id;
        $line[] = "<a href=\"{$CFG->wwwroot}/mod/sloodle/view.php?s={$s->id}\">$s->name</a>";
        $line[] = $s->intro;
        // Insert it into the appropriate table
        $sloodle_tables[$s->type]->data[] = $line;
    }
    
    // Add header information to each table
    // (cannot use "foreach" on the $sloodle_tables array as PHP4 doesn't support alteration of the original array that way)
    $table_types = array_keys($sloodle_tables);
    foreach ($table_types as $k) {
        $sloodle_tables[$k]->head = array($strid, $strname, $strdescription);
        $sloodle_tables[$k]->align = array('center', 'left', 'left');
    }

    // Page header
    if ($course->id != SITEID) {
        print_header("{$course->shortname}: $strsloodles", $course->fullname,
                    "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> $strsloodles",
                    "", "", true, "", navmenu($course));
    } else {
        print_header("$course->shortname: $strsloodles", $course->fullname, "$strsloodles",
                    "", "", true, "", navmenu($course));
    }
    
    
//-----------------------------------------------------
    // Quick links (top right of page)
    
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
    

    // Make sure we got some results
    if (is_array($sloodle_tables) && count($sloodle_tables) > 0) {
        // Go through each Sloodle table
        foreach ($sloodle_tables as $type => $table) {
            // Output a heading for this type
            print_heading_with_help($sloodle_type_names[$type], "moduletype_$type", 'sloodle');
            // Display the table
            print_table($table);
        }
    } else {
        print_heading(get_string('noentries', 'sloodle'));
    }
    
    // Page footer
    print_footer($course);

?>
