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

    // Require that the user logs in
    require_login($course, false);
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
    foreach ($sloodle_tables as $st) {
        $st->head = array($strid, $strname, $strdescription);
        $st->align = array('center', 'left', 'left');
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
