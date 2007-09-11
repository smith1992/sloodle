<?php // $Id: index.php,v 1.7 2007/09/03 12:23:36 jamiesensei Exp $
/**
 * This page lists all the instances of sloodle in a particular course
 *
 * @author
 * @version $Id: index.php,v 1.7 2007/09/03 12:23:36 jamiesensei Exp $
 * @package sloodle
 **/

    require_once("../../config.php");
    require_once("lib.php");

    $id = required_param('id', PARAM_INT);   // course

    if (! $course = get_record("course", "id", $id)) {
        error("Course ID is incorrect");
    }

    require_login($course->id);

    add_to_log($course->id, "sloodle", "view all", "index.php?id=$course->id", "");


/// Get all required stringssloodle

    $strsloodles = get_string("modulenameplural", "sloodle");
    $strsloodle  = get_string("modulename", "sloodle");


/// Print the header

    $navlinks = array();
    $navlinks[] = array('name' => $strsloodles, 'link' => '', 'type' => 'activity');
    $navigation = build_navigation($navlinks);

    print_header_simple("$strsloodles", "", $navigation, "", "", true, "", navmenu($course));

/// Get all the appropriate data

    if (! $sloodles = get_all_instances_in_course("sloodle", $course)) {
        notice("There are no sloodles", "../../course/view.php?id=$course->id");
        die;
    }

/// Print the list of instances (your module will probably extend this)

    $timenow = time();
    $strname  = get_string("name");
    $strweek  = get_string("week");
    $strtopic  = get_string("topic");

    if ($course->format == "weeks") {
        $table->head  = array ($strweek, $strname);
        $table->align = array ("center", "left");
    } else if ($course->format == "topics") {
        $table->head  = array ($strtopic, $strname);
        $table->align = array ("center", "left", "left", "left");
    } else {
        $table->head  = array ($strname);
        $table->align = array ("left", "left", "left");
    }

    foreach ($sloodles as $sloodle) {
        if (!$sloodle->visible) {
            //Show dimmed if the mod is hidden
            $link = "<a class=\"dimmed\" href=\"view.php?id=$sloodle->coursemodule\">$sloodle->name</a>";
        } else {
            //Show normal if the mod is visible
            $link = "<a href=\"view.php?id=$sloodle->coursemodule\">$sloodle->name</a>";
        }

        if ($course->format == "weeks" or $course->format == "topics") {
            $table->data[] = array ($sloodle->section, $link);
        } else {
            $table->data[] = array ($link);
        }
    }

    echo "<br />";

    print_table($table);

/// Finish the page

    print_footer($course);

?>
