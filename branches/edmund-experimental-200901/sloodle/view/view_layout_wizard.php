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
    require_once(SLOODLE_LIBROOT.'/layout_profile.php');   
 
    // Get the Sloodle course data
    $courseid = required_param('courseid', PARAM_INT);
    $layoutid = optional_param('layoutid', -1, PARAM_INT); // 0 to add a layout, -1 just to display

    $sloodle_course = new SloodleCourse();
    if (!$sloodle_course->load($courseid)) error(get_string('failedcourseload','sloodle'));
    
    // Ensure that the user is logged-in to this course
    require_login($courseid);
    $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
    
    // Do not allow guest access
    if (isguestuser()) {
        error(get_string('noguestaccess', 'sloodle'));
        exit();
    }

    // Make sure the user is logged-in and is not a guest
    if (isloggedin() == false || isguestuser() == true) {
        error(get_string('noguestaccess','sloodle'));
        exit();
    }

    // Make sure the user has permission to manage activities on this course
    $course_context = get_context_instance(CONTEXT_COURSE, $courseid);
    require_capability('moodle/course:manageactivities', $course_context);

///////////////////
    
    // Fetch string table text
    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
    $strsavechanges = get_string('savechanges');
    $stryes = get_string('yes');
    $strno = get_string('no');
    
        
    // Attempt to fetch the course module instance
    if ($courseid) {
        if (!$course = get_record('course', 'id', $courseid)) {
            error('Could not find course');
        }
    } else {
        error('Must specify a course ID');
    }
       
    // Log the view
    add_to_log($courseid, 'course', 'view sloodle layout wizard', "mod/sloodle/view/view_layout_wizard.php?courseid={$courseid}", "$course->id");
    
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

    // Show a list of layouts, if there are any
    $layout_names = $sloodle_course->get_layout_names();

//------------------------------------------------------    

    if (isset($_POST['courseid'])) {

       /// Update

          $layoutid = required_param('layoutid', PARAM_INT);
          $layoutname = required_param('layoutname', PARAM_TEXT);

          if ( in_array($layoutname,$layout_names) ) {
              $layoutnamestoids = array_flip($layout_names);
              if ( $layoutnamestoids[$layoutname] != $layoutid )  {
                  print "layout of name $layoutname already exists";
                  exit;
              }
          }

          // Define parameter names we will ignore
          $IGNORE_PARAMS = array('sloodleauthid', 'sloodledebug');
          // This structure will store our values

          $entries = array();

          $num_items = required_param('num_items', PARAM_INT);
          for ($i=0; $i<=$num_items; $i++) {

             $checkedfield = "layout_entry_on_".$i;
             $idchecked = optional_param($checkedfield, false, PARAM_TEXT);

             if ( $idchecked == "on" ) {

                $entry_id = optional_param("layout_entry_id_$i", 0, PARAM_INT);
                $object_type = required_param("object_type_$i", PARAM_TEXT);
                $layout_entry_x = required_param("layout_entry_x_$i", PARAM_INT);
                $layout_entry_y = required_param("layout_entry_y_$i", PARAM_INT);
                $layout_entry_z = required_param("layout_entry_z_$i", PARAM_INT);
                $rotation = required_param("layout_entry_rotation_$i", PARAM_RAW);
                $position = "<$layout_entry_x,$layout_entry_y,$layout_entry_z>";

		if ($object_type == '') {
			print "error: object_type missing";
			exit;
		}
		
                $entry = new SloodleLayoutEntry();
		if ($entry_id > 0) {
			$entry->load($entry_id);
		}
                $entry->name = $object_type;
                $entry->position = $position;
                $entry->rotation = $rotation;
                $entry->layout = $layoutid; // NB if this is a new entry, layoutid will be 0 and will need to be set on insert
                $configoptions = array('sloodlemoduleid');
                foreach($configoptions as $configoption) {
                    $paramname = 'layout_entry_config_'.$configoption.'_'.$i;
                    $configval = optional_param($paramname, null, PARAM_TEXT);
                    if ($configval != null) {
                        $entry->set_config($configoption, $configval);
                    }
                }
                
                $entries[] = $entry;

             }
          }

          // Define parameter names we will ignore
          $result = $sloodle_course->save_layout_by_id($layoutid, $layoutname, $entries, $add=false);

          if ($result) {
             $next = 'view_layout_wizard.php?courseid='.$courseid;
             print '<a href="'.$next.'">next</a>';
             //redirect($next);
          } else {
             print "<h3>save failed</h3>";
             exit;
	  }

    }

//------------------------------------------------------    

    if (count($layout_names) > 0) {

        echo "<center>";
        echo "<table border=\"1\">";
        foreach($layout_names as $lid=>$ln) {
            $en_cnt = count($sloodle_course->get_layout_entries($ln));
            echo "<tr><td>$ln</td><td>$en_cnt</td><td><a href=\"view_layout_wizard.php?courseid=".$courseid."&layoutid=".$lid."\">Edit</a></td></tr>";
        }
        echo "<tr><td colspan=\"2\">&nbsp;</td><td><a href=\"view_layout_wizard.php?courseid=".$courseid."&layoutid=0\">Add</a></td></tr>";
        echo "</table>";
        echo "<center>";

    } else {

        // show the add page even if the url didn't specify layoutid=0
        $layoutid = 0;

    }

//------------------------------------------------------    
// Fetch current layout

    $currentlayoutentries = array();
    $recommendedon = true; // Whether by default we turn on modules that aren't already in the layout we're looking at
    $layoutname = "My layout";

    if ($layoutid > 0) {
         
        $layout = new SloodleLayout();
        $layout->load($layoutid);
        $currentlayoutentries = $sloodle_course->get_layout_entries_for_layout_id($layoutid);
        $recommendedon = false; // The user got a chance to use our recommended defaults when they added. Now we'll only turn on things that they did.
        $layoutname = $layout_names[$layoutid];
    } 

//------------------------------------------------------    
// Fetch possible options
//
 
    if ($layoutid >= 0) { // add or edit layout

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
    $posz = -2; // they'll need to be below the rezzer
    // make a list of potential objects and their potential and default configuration settings
    $possiblemoduleobjects = array();
    $allpossiblemodules = array_merge($cmsmodules, $instancemodules);
    $modulesbyid = array();
    foreach($allpossiblemodules as $mod) {

        // for easy access later
        $modid = $mod->id;
        $modulesbyid[$modid] = $mod;

        $modname = $mod->modname;
        if (isset($instanceobjectmappingspercourse[$modname])) {
           $modobjects = $instanceobjectmappingspercourse[$modname];
           
           $isdefault = true;
           foreach($modobjects as $mo) {
              $pm = array(
                 'object'=>$mo, 
                 'id'=>$mod->id, 
                 'name'=>$mod->name, 
                 'isdefault'=>($isdefault && $recommendedon)
              );
              $possiblemoduleobjects[] = $pm;
              $isdefault = false;  // By default, only turn on the first object for each module
           }
        }
    }

    //------------------------------------------------------

    echo "<center>";

    // Create a form
    echo "<form action=\"view_layout_wizard.php\" method=\"POST\">\n";
    echo "<input type=\"hidden\" name=\"courseid\" value=\"{$course->id}\">\n";
    echo "<input type=\"hidden\" name=\"layoutid\" value=\"{$layoutid}\">\n";

    echo "<table>";
    echo "<tr>";
    echo "<td>";
    echo "Layout name";
    echo "</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>";
    echo "<input type=\"text\" name=\"layoutname\" maxlength=\"40\" size=\"20\" value=\"{$layoutname}\" />";
    echo "</td>";
    echo "</tr>";
    echo "</table>";

    echo "<table>";
    $item = 0;
    echo "<tr>";
    echo "<td>&nbsp;</td>";
    echo "<td align=\"center\">Object</td>";
    echo "<td align=\"center\">Module</td>";
    echo "<td align=\"center\">X</td>";
    echo "<td align=\"center\">Y</td>";
    echo "<td align=\"center\">Z</td>";
    echo "</tr>";

    foreach($currentlayoutentries as $co) {

       $sloodlemoduleid = '';
       $modname = '';
       $confighash = $co->get_layout_entry_configs_as_name_value_hash();
       if (isset($confighash['sloodlemoduleid'])) {
           $sloodlemoduleid = $confighash['sloodlemoduleid'];
           if (isset($modulesbyid[$sloodlemoduleid])) {
              $modobj = $modulesbyid[$sloodlemoduleid];
              $modname = $modobj->name;
           }
       }
       $posxyz = $co->position;
       if (preg_match('/^<(-?\d+)\,(-?\d+)\,(-?\d+)>$/', $posxyz, $matches)) {
          $posx = $matches[1];
          $posy = $matches[2];
          $posz = $matches[3];
       } 
       echo "\n";
       echo "<input type=\"hidden\" name=\"layout_entry_id_{$item}\" value=\"{$co->id}\" />";
       echo "<input type=\"hidden\" name=\"object_type_{$item}\" value=\"{$co->name}\" />";
       echo "<input type=\"hidden\" name=\"layout_entry_rotation_{$item}\" value=\"{$co->rotation}\" />";
       echo $co->get_layout_entry_configs_as_hidden_fields('layout_entry_config_', '_'.$item); 
       echo "\n";
       echo "<tr>";
       echo "<tr>";
       echo "<td bgcolor=\"#000066\"><a href=\"../classroom/configure_layout_entry.php?courseid={$courseid}&layout_entry_id={$co->id}\">{$co->id}</a></td>";
       echo "<td><input type=\"checkbox\" name=\"layout_entry_on_{$item}\" value=\"on\" checked=\"checked\" /></td>";
       echo "<td align=\"left\">{$co->name}</td>";
       echo "<td align=\"center\">{$modname}</td>";
       echo "<td><input type=\"text\" name=\"layout_entry_x_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posx\" /></td>";
       echo "<td><input type=\"text\" name=\"layout_entry_y_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posy\" /></td>";
       echo "<td><input type=\"text\" name=\"layout_entry_z_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posz\" /></td>";
       echo "</tr>";
       $item++; 
    }

    $checkedflag = '';
    if ($recommendedon) {
        $checkedflag = 'checked="checked" ';
    }
    foreach($standardobjects as $so) {
       echo "\n";
       echo "<input type=\"hidden\" name=\"object_type_{$item}\" value=\"{$so}\" />";
       echo "<input type=\"hidden\" name=\"layout_entry_rotation_{$item}\" value=\"<0,0,0>\" />";
       echo "\n";
       echo "<tr>";
       echo "<tr>";
       echo "<td>&nbsp;</td>";
       echo "<td><input type=\"checkbox\" name=\"layout_entry_on_{$item}\" value=\"on\" {$checkedflag} /></td>";
       echo "<td align=\"left\">{$so}</td>";
       echo "<td align=\"center\">-</td>";
       echo "<td><input type=\"text\" name=\"layout_entry_x_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posx\" /></td>";
       echo "<td><input type=\"text\" name=\"layout_entry_y_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posy\" /></td>";
       echo "<td><input type=\"text\" name=\"layout_entry_z_{$item}\" size=\"2\" maxlength=\"2\" value=\"$posz\" /></td>";
       echo "</tr>";
       $posy++;
       $item++; 
    }

    foreach($possiblemoduleobjects as $pmo) {
       echo "\n";
       echo "<input type=\"hidden\" name=\"object_type_{$item}\" value=\"{$pmo['object']}\" />";
       echo "<input type=\"hidden\" name=\"layout_entry_rotation_{$item}\" value=\"<0,0,0>\" />";
       echo "<input type=\"hidden\" name=\"layout_entry_config_sloodlemoduleid_{$item}\" value=\"{$pmo['id']}\" />";

       echo "\n";
       echo "<tr>";
       $checkedflag = '';
       if ($pmo['isdefault']) {
           $checkedflag = "checked=\"checked\""; 
       }
       echo "<td>&nbsp;</td>";
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
    echo '<input type="hidden" name="num_items" value="'.($item-1).'" />';
    echo '<input type="submit" value="Save layout" />';
    // Determine how many allocations there are for this course
    echo "</form>\n";

    echo "</center>";

    }

    print_box_end();

//------------------------------------------------------
    
    print_footer($course);
    
?>
