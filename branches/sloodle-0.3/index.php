<?php  // $Id: index.php,v 1.90.2.3 2007/05/15 18:27:03 skodak Exp $

    require_once("../../config.php");
    require_once("lib.php");
    require_once("$CFG->libdir/rsslib.php");

    $id = optional_param('id', 0, PARAM_INT);                   // Course id
    $subscribe = optional_param('subscribe', null, PARAM_INT);  // Subscribe/Unsubscribe all forums

    if ($id) {
        if (! $course = get_record("course", "id", $id)) {
            error("Course ID is incorrect");
        }
    } else {
        if (! $course = get_site()) {
            error("Could not find a top-level course!");
        }
    }

    require_login($course, false);
    add_to_log($course->id, "sloodle", "view sloodles", "index.php?id=$course->id");

    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
    $strname = get_string('name', 'sloodle');
    $strdescription = get_string('description');
    

    // Start of the table for Sloodle Virtual Classrooms

    $generaltable->head  = array ($strname, $strdescription);
    $generaltable->align = array ('left', 'left');

    
    // Get all Sloodle modules for the current course
    $sloodles = get_records('sloodle', 'course', $course->id);
    if (!$sloodles) $sloodles = array();
    foreach ($sloodle as $s) {
        $generaltable->data[] = "<a href=\"{$CFG->wwwroot}/mod/sloodle/view.php?s={$s->id}\">$s->name</a>";
        $generaltable->data[] = $s->intro;
    }


    /// Output the page

    if ($course->id != SITEID) {
        print_header("{$course->shortname}: $strsloodles", $course->fullname,
                    "<a href=\"../../course/view.php?id=$course->id\">$course->shortname</a> -> $strsloodles",
                    "", "", true, "", navmenu($course));
    } else {
        print_header("$course->shortname: $strsloodles", $course->fullname, "$strsloodles",
                    "", "", true, "", navmenu($course));
    }

    print_table($generaltable);

    print_footer($course);

?>
