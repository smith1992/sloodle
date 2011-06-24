<?php

/**
OpenSim/SLOODLE Tracker template manager script.
This script has a form to update an existing opensim template or create a new one

Required parameters:
 - id = the cmid of the Tracker the user is working with

*/

/** Include the SLOODLE/Moodle configuration */
require_once('../../sl_config.php');
/** Include the general SLOODLE library functionality */
require_once(SLOODLE_LIBROOT.'/general.php');
/** Include the tracker library code */
require_once(SLOODLE_DIRROOT.'/mod/tracker-1.0/lib.php');

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

// Display the header
$navigation = "<a href=\"index.php?id={$course->id}\">".get_string('modulenameplural','sloodle')."</a> ->";
print_header_simple(format_string($sloodle->name), "", "{$navigation} ".format_string($sloodle->name), "", "", true, null, navmenu($course, $cm));
// Display the module name
$img = '<img src="'.$CFG->wwwroot.'/mod/sloodle/icon.gif" width="16" height="16" alt=""/> ';
print_heading($img.$sloodle->name.' - Teleport', 'center');
// Display the module type and description
$fulltypename = get_string("moduletype:{$sloodle->type}", 'sloodle');
echo '<h4 style="text-align:center;">'.get_string('moduletype', 'sloodle').': '.$fulltypename.' - Templates manager';
// The user must be logged-in
require_course_login($course, true, $cm);
	
echo "<div style=\"text-align:center;width:50%;margin:16px auto;border:solid 1px #000;padding:8px 4px;\">\n";	
echo "<form action=\"template_request.php\" method=\"post\">";
echo "<input type=\"hidden\" name=\"id\" value=\"{$id}\"></input>";	
echo "<p><input type=\"radio\" name=\"option\" checked value=\"update\">".get_string('tracker:updatetemplate','sloodle')."</input>"; 

/*$recs_id = get_records('sloodle','course',$course->id);
echo "<select name=\"select_template\">"; 
if (is_array($recs_id) && count($recs_id) > 0)
{
   //echo "counter: ".count($recs_id);
   foreach ($recs_id as $obj)
   {
     //echo "id: ".$obj->id;
     $recs_templates = get_records('sloodle_tracker','sloodleid',$obj->id);
	 //echo "counter: ".count($recs_templates);
	 foreach ($recs_templates as $templ){
	   //echo "template name: ".$templ->opensim_template;
	   echo  "<option value=\"$templ->opensim_template\">$templ->opensim_template</option>";
	 }  
   }
}*/

//Get all the templates of this course
echo "<select name=\"select_template\">"; 
$dir = opendir($CFG->sloodle_tracker_opensim_templates_folder); 
while($file = readdir($dir))
{
 if((!is_file($file))and($file!='.')and($file!='..'))
 { 
  echo "<option value=\"$file\">$file</option>";
 }
}
echo "</select></p>";   	
echo "<p><input type=\"radio\" name=\"option\" value=\"new\">".get_string('tracker:newtemplate','sloodle')."</input>";		
echo "<input type=\"text\" name=\"new_template\" size=\"20\"><span style=\"font-size:x-small;\">".get_string('tracker:templatename:info','sloodle')."</span></p>";
echo "<p><input type=\"submit\" value=\"Submit\" name=\"send\"></p>";
echo "</form>";
echo "</div>";
	
?>
