<?php
    // This file is part of the Sloodle project (www.sloodle.org)

    /**
    * Index page for listing a particular instances of the Sloodle module.
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

    // Fetch our request parameters
    $id = optional_param('id', 0, PARAM_INT); // Course Module instance ID
    $s = optional_param('s', 0, PARAM_INT); // Sloodle instance ID
    $editing = optional_param('edit', 0, PARAM_BOOL); // Editing mode
    $formsubmit = optional_param('formsubmit', 0, PARAM_BOOL); // Form submission
    
    
    // Fetch string table text
    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
    $strsavechanges = get_string('savechanges');
    $stryes = get_string('yes');
    $strno = get_string('no');
        
    // Attempt to fetch the course module instance
    if ($id) {
        if (! $cm = get_coursemodule_from_id('sloodle', $id)) {
            error("Course Module ID was incorrect");
        }
    } else if ($s) {       
        if (! $cm = get_coursemodule_from_instance('sloodle', $s)) {
            error("Instance ID was incorrect");
        }
    } else {
        error('Must specify a course module or a module instance');
    }
    
    // Get the course data
    if (! $course = get_record("course", "id", $cm->course)) {
        error("Course is misconfigured");
    }
    // Get the Sloodle instance
    if (! $sloodle = get_record('sloodle', 'id', $cm->instance)) {
        error('Failed to find Sloodle module instance.');
    }
    
    // Ensure that the user is logged-in for this course
    require_course_login($course, true, $cm);
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    
    // Is the user allowed to edit the module?
    $canedit = false;
    if (has_capability('moodle/course:manageactivities', get_context_instance(CONTEXT_MODULE, $cm->id))) {
        $canedit = true;
    } else {
        $editing = false;
    }    
    
    // Only show the editing buttons if the user is allowed to edit this stuff
    $editbuttons = '';
    if ($canedit) {
        // Editing button stuff
        if ($editing) {
            $streditbutton = get_string('turneditingoff');
            $editbuttonval = 0;
        } else {
            $streditbutton = get_string('turneditingon');
            $editbuttonval = 1;
        }
        $strmodulesetup = get_string('modulesetup', 'sloodle');
        $sesskey = sesskey();
        // The editing buttons
        $editbuttons = <<<XXXEODXXX
         <!-- Module editing buttons. -->
          <form  method="get" action="" onsubmit="this.target='_top'; return true">
            <input type="hidden" name="id" value="{$cm->id}" />
            <input type="hidden" name="sesskey" value="$sesskey" />
            <input type="hidden" name="edit" value="$editbuttonval" />
            <input type="submit" value="$streditbutton" />
          </form>
          
          <form  method="get" action="{$CFG->wwwroot}/course/mod.php" onsubmit="this.target='_top'; return true">
            <input type="hidden" name="update" value="7" />
            <input type="hidden" name="return" value="true" />
            <input type="hidden" name="sesskey" value="$sesskey" />
            <input type="submit" value="$strmodulesetup" />
          </form>
XXXEODXXX;
        unset($sesskey); // for security
    }

    // Display the page header
    $navigation = "<a href=\"index.php?id=$course->id\">$strsloodles</a> ->";
    print_header_simple(format_string($sloodle->name), "", "$navigation ".format_string($sloodle->name), "", "", true, $editbuttons, navmenu($course, $cm));


    // If the module is hidden, then can the user still view it?
    if (empty($cm->visible) and !has_capability('moodle/course:viewhiddenactivities', $context)) {
        // No - issue a notice
        notice(get_string("activityiscurrentlyhidden"));
    }
    
    // Find out current groups mode
    $groupmode = groupmode($course, $cm);
    $currentgroup = setup_and_print_groups($course, $groupmode, 'view.php?id=' . $cm->id);

    // We can display the Sloodle module info... log the view
    add_to_log($course->id, 'sloodle', 'view sloodle module', "view.php?id=$cm->id", "$sloodle->id", $cm->id);
    
    // Get the full Sloodle module type name
    $fulltypename = get_string("moduletype:{$sloodle->type}", 'sloodle');
    
    // Display the module name
    print_heading($sloodle->name, 'center');
    
    // Display the module type and description
    echo '<h4 style="text-align:center;">'.get_string('moduletype', 'sloodle').': '.$fulltypename;
    echo helpbutton("moduletype_{$sloodle->type}", $fulltypename, 'sloodle', true, false, '', true).'</h4>';
    
    print_box_start();
    echo '<p style="text-align:center;">'.$sloodle->intro.'</p>';
    print_box_end();
    
    
    print_box_start();

    // Check what type the module is
    switch ($sloodle->type) {
    case SLOODLE_TYPE_CTRL:
    
        // Fetch the controller data from the database
        $ctrldata = get_record('sloodle_controller', 'sloodleid', $sloodle->id);
        if (!$ctrldata) error('Failed to locate secondary data table.');
        
        // Has data been submitted AND is editing possible?
        if ($formsubmit && $canedit) {
            // Yes - fetch the data items
            $form_password = optional_param('password', '', PARAM_RAW);
            $form_enabled = optional_param('enabled', 0, PARAM_BOOL);
            
            // Validate the password
            $pwderrors = array();
            $pwdvalid = sloodle_validate_prim_password_verbose($form_password, $pwderrors);
            
            // Was everything valid?
            $msg = '';
            if ($pwdvalid) {
                // Yes - update the database
                if ($form_enabled) $ctrldata->enabled = 1;
                else $ctrldata->enabled = 0;
                $ctrldata->password = $form_password;
                if (update_record('sloodle_controller', $ctrldata)) {
                    // Everything was OK - we can deactivate edit mode
                    $editing = 0;
                    // Output a success message
                    $msg = get_string('updated', '', $strsloodle);
                } else {
                    $editing = 1;
                    $msg = get_string('failedupdate', 'sloodle');
                }
                
            } else {
                // There were errors
                $msg = get_string('failedupdate', 'sloodle').'<br><br>';
                $editing = 1;
                
                // Were there any prim password errors?
                if (count($pwderrors) > 0) {
                    $msg .= '<br><br>'.get_string('primpass:error', 'sloodle').':<br>';
                    $msg .= '<ul>';
                    // Go through each one and add it
                    foreach ($pwderrors as $pe) {
                        $msg .= '<li>'.get_string("primpass:$pe", 'sloodle').'</li>';
                    }
                    $msg .= '</ul><br>';
                }
            }
            
            // Display the message
            print_box($msg);
        }
        
        // Instead of a form submission, the active objects list may be getting cleared
        if (optional_param('clearactiveobjects', 0, PARAM_BOOL)) {
            // Clear all active objects associated with this controller
            //..
        }
    
        // If we are in edit mode, then open a new form
        if ($editing) {
            $sesskey = sesskey();
            echo <<<XXXEODXXX
             <form method="POST" action="">
             <input type="hidden" name="id" value="{$cm->id}" />
             <input type="hidden" name="sesskey" value="$sesskey" />
             <input type="hidden" name="edit" value="0" />
             <input type="hidden" name="formsubmit" value="1" />
XXXEODXXX;
            unset($sesskey); // for security
        }
        
        // We will create a table for the data
        $ctrltable = new stdClass();
        $ctrltable->head = array('Field', 'Value');
        $ctrltable->align = array('right', 'left');
        
        // The contents of the table depends on whether or not we are in editing mode
        if ($editing) {
            // Enabled?
            $ctrltable->data[0][] = get_string('enabled','sloodle').': ';
            $ctrltable->data[0][] = choose_from_menu_yesno('enabled', $ctrldata->enabled, '', true);
            
            // Prim password
            $ctrltable->data[1][] = get_string('primpass','sloodle').':';
            $ctrltable->data[1][] = "<input type=\"text\" value=\"{$ctrldata->password}\" size=\"15\" maxlength=\"9\" name=\"password\" />";
            
            // Add count of active objects through this controller, and button to clear list
            //...
            
            // Submit button
            $ctrltable->data[2][] = '&nbsp;';            
            $ctrltable->data[2][] = "<input type=\"submit\" value=\"$strsavechanges\">";
            
        } else {
            // Just display the data (where possible... but hide sensitive stuff!)
            
            // Enabled?
            $ctrltable->data[0][] = get_string('enabled','sloodle').': ';
            if (empty($ctrldata->enabled)) $ctrltable->data[0][] = '<span style="color:red;">'.$strno.'</span>';
            else $ctrltable->data[0][] = '<span style="color:green;">'.$stryes.'</span>';
            
            // Prim password
            $ctrltable->data[1][] = get_string('primpass','sloodle').':';
            $ctrltable->data[1][] = '<input type="password" value="*********" size="15" maxlength="9" readonly="true" />';
            
            // Add count of active objects through this controller
            //...
        }
        
        // Display the table
        print_table($ctrltable);
        
        // If we are in edit mode, then close the form
        if ($editing) {
            echo "</form>";
        }
        
        break;
        
    case SLOODLE_TYPE_DISTRIB:
        break;
        
    default:
        // Unknown type
        notice(get_string('moduletypeunknown'));
        break;
    }
    
    print_box_end();
    
    print_footer($course);
    
?>