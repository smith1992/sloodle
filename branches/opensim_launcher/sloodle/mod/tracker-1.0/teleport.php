<?php

/**
OpenSim/SLOODLE Tracker teleport script.
When the user visits this script, their avatar details will be determined from their Moodle.
A new OpenSim instance will be created, and they will be direct to a teleport link.

Required parameters:
 - id = the cmid of the Tracker the user is working with

*/

/** Include the SLOODLE/Moodle configuration */
require_once('../../sl_config.php');
/** Include the general SLOODLE library functionality */
require_once(SLOODLE_LIBROOT.'/general.php');
/** Include the tracker library code */
require_once(SLOODLE_DIRROOT.'/mod/tracker-1.0/lib.php');

// Make sure all the necessary configuration data is specified
if (    empty($CFG->sloodle_tracker_opensim_address) ||
        empty($CFG->sloodle_tracker_opensim_templates_folder) ||
        empty($CFG->sloodle_tracker_opensim_instances_folder) ||
        empty($CFG->sloodle_tracker_opensim_db_host) ||
        empty($CFG->sloodle_tracker_opensim_db_user) ||
        empty($CFG->sloodle_tracker_opensim_port_min) ||
        empty($CFG->sloodle_tracker_opensim_port_max)) error(get_string('tracker:notconfigured','sloodle'));


// The ID parameter is required to identify which SLOODLE Tracker we are dealing with
$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('sloodle', $id);
if (!$cm) error('Course module ID was incorrect');
$course = get_record('course', 'id', $cm->course);
if (!$course) error('Failed to get course data');
$sloodle = get_record('sloodle', 'id', $cm->instance);
if (!$sloodle) error('Failed to get sloodle instance');
$tracker = get_record('sloodle_tracker', 'sloodleid', $sloodle->id);
if (!$tracker) error('Failed to get tracker data');

global $COURSE;
$COURSE = $course;

// Offer the user an 'update' button if they are allowed to edit the module
// Display the header
$navigation = "<a href=\"index.php?id={$course->id}\">".get_string('modulenameplural','sloodle')."</a> ->";
print_header_simple(format_string($sloodle->name), "", "{$navigation} ".format_string($sloodle->name), "", "", true, null, navmenu($course, $cm));
// Display the module name
$img = '<img src="'.$CFG->wwwroot.'/mod/sloodle/icon.gif" width="16" height="16" alt=""/> ';
print_heading($img.$sloodle->name.' - Teleport', 'center');
// Display the module type and description
$fulltypename = get_string("moduletype:{$sloodle->type}", 'sloodle');
echo '<h4 style="text-align:center;">'.get_string('moduletype', 'sloodle').': '.$fulltypename.' - Teleport';
// The user must be logged-in
require_course_login($course, true, $cm);

// Log this activity
add_to_log($course->id, 'sloodle', 'teleport sloodle tracker', "mod/tracker-1.0/teleport.php?id={$cm->id}", "{$sloodle->id}", $cm->id);

echo "<div style=\"width:50%; margin:8px auto; text-align:center;\">\n";

echo "<p>";
print_string('tracker:settingupopensim', 'sloodle');
echo "</p>\n";

// Attempt to fetch the user's avatar data
$avdata = get_record('sloodle_users', 'userid', $USER->id);
//echo "user: ".$USER->id;
if (!$avdata)
{
    // No avatar data already -- create some.
    // No point re-creating the avatar data all the time in the Moodle database.
    $avdata = new stdClass();
    $avdata->userid = $USER->id;
    $avdata->avname = $USER->firstname.' '.$USER->lastname;
    $avdata->uuid = sloodle_tracker_generate_unique_avatar_uuid();
    $avdata->lastactive = time();
    $avdata->id = insert_record('sloodle_users', $avdata);
    if (!$avdata) error(get_string('failedcreatesloodleuser','sloodle'));
}


// Get a name and port for our new instance
$instance_name = sloodle_tracker_generate_opensim_instance_name($cm->id, $avdata->id);
$port = sloodle_tracker_allocate_opensim_port($instance_name, $avdata->uuid);
if ($port == -1)
{
    error(get_string('tracker:instancealreadyallocatedport', 'sloodle'));
}
else if ($port === false)
{
    error(get_string('tracker:noportsavailable', 'sloodle'));
}

// Create and configure the new instance
//sloodle_tracker_create_opensim_instance($tracker->opensim_template, $instance_name, $port, $avdata->avname, $avdata->uuid, 'password');
sloodle_tracker_create_opensim_instance($tracker->opensim_template, $instance_name, $avdata->avname, $avdata->uuid, 'password');
sloodle_tracker_configure_opensim_instance($instance_name, $port);

// Run opensim as a background task
if (!sloodle_tracker_launch_opensim_instance($instance_name))
{
	error("Unable to launch OpenSim instance.");
}

// Generate a URL which will be able to launch an OpenSim-compatible viewer
$url = "opensim://{$CFG->sloodle_tracker_opensim_address}:{$port}/regionOne/127/124/25";
 
?>
</div>

<div style="text-align:center;width:50%;margin:16px auto;border:solid 1px #000;padding:8px 4px;">
<p><?php print_string('tracker:opensiminstancecreated','sloodle'); ?></p>
<p style="font-weight:bold;">
 <a href="<?php echo $url; ?>" title=""><?php print_string('tracker:teleport','sloodle'); ?></a>
</p>
</div>

<?php
print_footer($course);
?>
