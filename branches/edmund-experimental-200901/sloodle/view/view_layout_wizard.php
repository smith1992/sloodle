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
    $navigation = "<a href=\"{$CFG->wwwroot}/mod/sloodle/view/view_course.php?id={$course->id}\">".get_string('layoutwizard','sloodle')."</a>";
    print_header_simple(get_string('layoutwizard','sloodle'), "", $navigation, "", "", true, '', navmenu($course));
    
    
//------------------------------------------------------    
    
    // Check the user's permissions regarding layouts
    $layouts_can_use = has_capability('mod/sloodle:uselayouts', $course_context);
    $layouts_can_edit = has_capability('mod/sloodle:editlayouts', $course_context);
    
    // Only display the layouts if they can use them
    if (!$layouts_can_edit) {
        print "You don not have permission to edit layouts";
        exit;
    }
   
    $modinfo =& get_fast_modinfo($COURSE);

    $cmsmodules = $modinfo->cms;
    $instancemodules = $modinfo->instances;

    // TODO: Is this information stored anywhere already or could it be - eg. in the object_config's?
    $standardobjects = array(
        'regbooth-1.0',
        'enrolbooth-1.0',
        'accesscheckerdoor-1.0',
        'distributor-1.0',
        'loginzone-1.0'
    );
    $instanceobjectmappingspercourse = array(
        'quiz'=>array('quiz-1.0','quiz_pile_on-1.0'),
        'glossary'=>array('glossary-1.0'),
        'chat'=>array('chat-1.0') 
    );
    $instanceobjectmappingsperstudent = array(
        // TODO 
    );

    $rezoptions = array();
    foreach($standardobjects as $obj) {
       $rezoptions[] = array($obj);
    }

    $posx = 1;
    $posy = 1;
    $posz = 1;
    // make a list of potential objects and their potential and default configuration settings
    $possiblemoduleobjects = array();
    $allpossiblemodules = array_merge($cmsmodules, $instancemodules);
    foreach($allpossiblemodules as $mod) {
        $modname = $mod->modname;
        if (isset($instanceobjectmappingspercourse[$modname])) {
           $modobjects = $instanceobjectmappingspercourse[$modname];
           $isdefault = true;
           foreach($modobjects as $mo) {
              $pm = array(
                 'object'=>$mo, 
                 'id'=>$mod->id, 
                 'name'=>$mod->name, 
                 'isdefault'=>$isdefault
              );
              $possiblemoduleobjects[] = $pm;
              $isdefault = false; 
           }
        }
    }

//------------------------------------------------------

    // Create a form
    echo "<form action=\"store_layout_config.php\" method=\"POST\">\n";
    echo "<input type=\"hidden\" name=\"id\" value=\"{$course->id}\">\n";

    echo "<table>";
    $item = 0;
    echo "<tr>";
    echo "<td>&nbsp;</td>";
    echo "<td>Object</td>";
    echo "<td>Module</td>";
    echo "<td>X</td>";
    echo "<td>Y</td>";
    echo "<td>Z</td>";
    echo "</tr>";
    foreach($standardobjects as $so) {
       echo "\n";
       echo "<tr>";
       echo "<td><input type=\"checkbox\" name=\"layout_entry_on_{$item}\" checked=\"checked\" /></td>";
       echo "<td>{$so}</td>";
       echo "<td>&nbsp;</td>";
       echo "<td><input type=\"text\" name=\"layout_entry_x_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posx\" /></td>";
       echo "<td><input type=\"text\" name=\"layout_entry_y_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posy\" /></td>";
       echo "<td><input type=\"text\" name=\"layout_entry_z_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posz\" /></td>";
       echo "</tr>";
       $posy++;
       $item++; 
    }

    foreach($possiblemoduleobjects as $pmo) {
       echo "\n";
       echo "<input type=\"hidden\" name=\"course_module_id_{$item}\" value=\"{$pmo['id']}\" /";
       echo "\n";
       echo "<tr>";
       $checkedflag = '';
       if ($pmo['isdefault']) {
           $checkedflag = "checked=\"checked\""; 
       }
       echo "<td><input type=\"checkbox\" name=\"layout_entry_on_{$item}\" {$checkedflag} /></td>";
       echo "<td>{$pmo['object']}</td>";
       echo "<td>{$pmo['name']}</td>";
       echo "<td><input type=\"text\" name=\"layout_entry_x_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posx\" /></td>";
       echo "<td><input type=\"text\" name=\"layout_entry_y_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posy\" /></td>";
       echo "<td><input type=\"text\" name=\"layout_entry_z_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posz\" /></td>";
       echo "</tr>";
       $posy++;
       $item++; 
    }
    echo "</table>";

    // TODO: localize
    echo '<input type="submit" value="Save layout" />';
    // Determine how many allocations there are for this course
    echo "</form>\n";
    print_box_end();

//------------------------------------------------------
    
    print_footer($course);
    
?>
