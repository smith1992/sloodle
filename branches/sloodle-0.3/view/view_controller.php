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
    * @param object $sloodle A database record object for a Sloodle instance
    * @param bool $showprotected True if protected data (such as prim password) should be made available
    * @return bool True if successful, or false otherwise (e.g. wrong type of module, or user not logged-in)
    */
    function sloodle_view_controller($sloodle, $showprotected = false)
    {        
        // Check that there is valid Sloodle data
        if (empty($sloodle) || $sloodle->type != SLOODLE_TYPE_CTRL) return false;
        
        // Fetch the controller data
        $controller = get_record('sloodle_controller', 'sloodleid', $sloodle->id);
        if (!$controller) return false;
        
        // The name, type and description of the module should already be displayed by the main "view.php" script.
        echo "<div style=\"text-align:center;\">\n";
        
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
            print_box_start('generalbox boxaligncenter boxwidthnormal');
            // Display a link to the configuration notecard page, as a popup window
            echo '<h3>'.get_string('objectconfig:header', 'sloodle').'</h3>';
            echo '<p>'.get_string('objectconfig:body', 'sloodle').'<br><br><span style="font-size:14pt;">';
            link_to_popup_window(SLOODLE_WWWROOT."/view/view_configuration_notecard.php?s={$sloodle->id}", 'sloodle_config', get_string('createnotecard', 'sloodle'), 350, 570, 'sloodle_config');
            echo '</span></p>';
            print_box_end();
        }
        
        echo "</div>\n";
        
        return true;
    }
    
    
?>