<?php
    /**
    * Defines a function to display information about a particular Sloodle Map.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This file expects that the core Sloodle/Moodle functionality has already been included.
    
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    /**
    * Displays information about the given Sloodle module.
    * Note: the user should already be logged-in, with information in the $USER global.
    *
    * @param object $cm A coursemodule object for the module being displayed
    * @param object $sloodle A database record object for a Sloodle instance
    * @param bool $editmode True if the user has access to edit settings (and view protected data)
    * @return bool True if successful, or false otherwise (e.g. wrong type of module, or user not logged-in)
    */
    function sloodle_view_map($cm, $sloodle, $editmode = false)
    {
        global $CFG;
    
        // Check that there is valid Sloodle data
        if (empty($cm) || empty($sloodle) || $sloodle->type != SLOODLE_TYPE_MAP) return false;
        
        // Construct a dummy session and a Map object
        $session = new SloodleSession(false);
        $map = new SloodleModuleMap($session);
        if (!$map->load($cm->id)) return false;
        
       
        // Output the script elements and the map itself
        $map->print_script();
        $map->print_map();

        echo '<p>&nbsp;</p>';

        // Add our editing info
        if ($editmode) {
            print_box_start('boxwidthnormal boxaligncenter');

            echo '<h3>Edit Map</h3>';

            print_box_end();
        }
        
        return true;
    }
    
    
?>
