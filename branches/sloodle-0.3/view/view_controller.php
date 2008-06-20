<?php
    /**
    * Defines a function to display information about a particular Sloodle controller module.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This file expects that the core Sloodle/Moodle functionality has already been included.
    
    /**
    * Displays information about the given Sloodle module.
    * Note: the user should already be logged-in, with information in the $USER global.
    *
    * @param object $cm A coursemodule object for the module being displayed
    * @param object $sloodle A database record object for a Sloodle instance
    * @param bool $showprotected True if protected data (such as prim password) should be made available
    * @return bool True if successful, or false otherwise (e.g. wrong type of module, or user not logged-in)
    */
    function sloodle_view_controller($cm, $sloodle, $showprotected = false)
    {
        global $CFG;
    
        // Check that there is valid Sloodle data
        if (empty($cm) || empty($sloodle) || $sloodle->type != SLOODLE_TYPE_CTRL) return false;
        
        // Fetch the controller data
        $controller = get_record('sloodle_controller', 'sloodleid', $sloodle->id);
        if (!$controller) return false;
        
        // The name, type and description of the module should already be displayed by the main "view.php" script.
        echo "<div style=\"text-align:center;\">\n";
        
        // Check if some kind of action has been requested
        $action = optional_param('action', '', PARAM_TEXT);
        
        // Indicate whether or not this module is enabled
        echo '<p style="font-size:14pt;">'.get_string('status', 'sloodle').': ';
        if ($controller->enabled) {
            echo '<span style="color:green; font-weight:bold;">'.get_string('enabled','sloodle').'</span>';
        } else {
            echo '<span style="color:red; font-weight:bold;">'.get_string('disabled','sloodle').'</span>';
        }
        echo "</p>\n";
        
        // Should protected data be shown?
        if ($showprotected) {
        
            // Display a link to the configuration notecard page, as a popup window
            print_box_start('generalbox boxaligncenter boxwidthwide');
            echo '<h3>'.get_string('objectconfig:header', 'sloodle').'</h3>';
            echo '<p>'.get_string('objectconfig:body', 'sloodle').'</p>';
            //link_to_popup_window(SLOODLE_WWWROOT."/view/view_configuration_notecard.php?s={$sloodle->id}", 'sloodle_config', get_string('createnotecard', 'sloodle'), 350, 570, 'sloodle_config');
            
            // Is Prim Password access available?
            if (empty($controller->password)) {
                // No - display an error message
                echo '<span style="color:red; font-weight:bold;>'.get_string('objectconfig:noprimpassword','sloodle').'</span>';
            } else {
                print_string('objectconfig:select','sloodle');
                // Go through each installed type to produce our own array of objects.
                // (Our array will associate translated names and version numbers with complete object ID's).
                $objects = array();
                $mods = sloodle_get_installed_object_types();
                if (!$mods) error('Error fetching installed object types.');
                
                foreach ($mods as $name => $versions) {
                    // Get the translated name
                    $translatedname = get_string("object:$name", 'sloodle');
                    // Reverse-sort the version
                    $sortedversions = $versions;
                    krsort($sortedversions);
                    foreach ($sortedversions as $v => $cfg) {
                        // Construct and store the complete object ID
                        $objectid = "$name-$v";
                        $objects[$translatedname][$v] = $objectid;
                    }
                }
                
                // Sort the objects by name
                ksort($objects);
                
                // Display our list of objects
                echo '<br><br><table style="text-align:left; margin-left:auto; margin-right:auto;">';
                foreach ($objects as $name => $versions) {
                    // The primary link will always be the latest version
                    $num = 0;
                    echo '<tr><td>';
                    $multipleversions = (count($versions) > 1);
                    // Go through each version (this will be latest first)
                    foreach ($versions as $v => $objectid) {
                        // Construct a link for this object's configuration
                        $link = SLOODLE_WWWROOT."/classroom/notecard_configuration_form.php?sloodlecontrollerid={$cm->id}&sloodleobjtype=$objectid";
                    
                        // Is this the latest version?
                        if ($num == 0) {
                            // Yes - display the object name for the link
                            echo "<span style=\"font-size:14pt;\"><a href=\"$link\">$name</a>";
                        }
                        // Do we have multiple versions available?
                        if ($multipleversions) {
                            // Yes - add the version in brackets afterwards
                            if ($num == 0) echo ' <span style="font-size:11pt; font-style:italic;">[';
                            else if ($num > 0) echo ', ';
                            echo "<a href=\"$link\">$v</a>";
                        }
                        
                        $num++;
                    }
                    
                    // Close the extra versions section if necessary
                    if ($multipleversions) echo "]</span>";
                    echo "</td></tr>";
                }
                
                echo '</table>';
            }

            print_box_end();
            
            // Active (authorised) objects
            print_box_start('generalbox boxaligncenter boxwidthwide');
            echo '<h3>'.get_string('authorizedobjects','sloodle').'</h3>';
            
            // Has a delete objects action been requested
            if ($action == 'delete_objects') {
                
                // Count how many objects we delete
                $numdeleted = 0;
                
                // Go through each request parameter
                foreach ($_REQUEST as $name => $val) {
                    // Is this a delete objects request?
                    if ($val != 'true') continue;
                    $parts = explode('_', $name);
                    if (count($parts) == 2 && $parts[0] == 'sloodledeleteobj') {
                        // Only delete the object if it belongs to this controller
                        if (delete_records('sloodle_active_object', 'controllerid', $cm->id, 'id', (int)$parts[1])) {
                            $numdeleted++;
                            // Delete any associated configuration settings too
                            delete_records('sloodle_object_config', 'object', (int)$parts[1]);
                        }
                        
                    }
                }
                
                // Indicate our results
                echo '<span style="color:red; font-weight:bold;">'.get_string('numdeleted','sloodle').': '.$numdeleted.'</span><br><br>';
            }
            
            // Get all objects authorised for this controller
            $recs = get_records('sloodle_active_object', 'controllerid', $cm->id, 'timeupdated DESC');
            if (is_array($recs) && count($recs) > 0) {
                // Construct a table
                //TODO: add authorising user link
                $objects_table = new stdClass();
                $objects_table->head = array(get_string('objectname','sloodle'),get_string('objectuuid','sloodle'),get_string('objecttype','sloodle'),get_string('lastupdated','sloodle'),'');
                $objects_table->align = array('left', 'left', 'left', 'left', 'center');
                foreach ($recs as $obj) {
                    // Skip this object if it has no type information
                    if (empty($obj->type)) continue;
                    // Construct a link to this object's configuration page
                    $config_link = "<a href=\"{$CFG->wwwroot}/mod/sloodle/classroom/configure_object.php?sloodleauthid={$obj->id}\">";
                    $objects_table->data[] = array($config_link.$obj->name.'</a>', $obj->uuid, $obj->type, date('Y-m-d H:i:s T', (int)$obj->timeupdated), "<input type=\"checkbox\" name=\"sloodledeleteobj_{$obj->id}\" value=\"true\" /");
                }
                
                // Display a form and the table
                echo '<form action="" method="POST">';
                echo '<input type="hidden" name="id" value="'.$cm->id.'"/>';
                echo '<input type="hidden" name="action" value="delete_objects"/>';
                
                print_table($objects_table);
                echo '<input type="submit" value="'.get_string('deleteselected','sloodle').'"/>';
                
                echo '</form>';
                
            } else {
                echo '<span style="text-align:center;color:red">'.get_string('noentries','sloodle').'</span><br>';
            }
            
            print_box_end();
        }
        
        echo "</div>\n";
        
        return true;
    }
    
    
?>
