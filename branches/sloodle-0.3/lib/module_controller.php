<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the Sloodle Controller module sub-type.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    
    /**
    * The Sloodle Controller module class.
    * @package sloodle
    */
    class SloodleModuleController extends SloodleModule
    {
    // PRIVATE DATA //
    
        /**
        * Indicates whether or not this controller is enabled.
        * @var bool
        * @access private
        */
        var $enabled = false;
            
        /**
        * The prim password for this controller.
        * @var string
        * @access private
        */
        var $password = '';
                
        
    // CONSTRUCTOR //
    
        /**
        * Constructor
        */
        function SloodleModuleController()
        {
        }
        
        
    // ACCESSORS //
    
        /**
        * Checks whether or not this controller is enabled.
        * @return bool
        */
        function is_enabled()
        {
            return $this->enabled;
        }
        
        /**
        * Enables this controller
        * @return void
        */
        function enable()
        {
            $this->enabled = true;
        }
        
        /**
        * Disables this controller
        * @return void
        */
        function disable()
        {
            $this->enabled = false;
        }
            
        /**
        * Gets the prim password of this instance
        * @return string
        */
        function get_password()
        {
            return $this->password;
        }
        
        /**
        * Sets the prim password of this instance.
        * Also checks for validity before storing.
        * @param string $password The new prim password
        * @return bool True if successfully stored, or false if the password is invalid
        */
        function set_password($password)
        {
            // Check validity
            if (!sloodle_validate_prim_password($password)) return false;
            // Store it
            $this->password = $password;
            return true;
        }
        
        
    // OPERATIONS //
        
        /**
        * Reads fresh data into the structure from the database.
        * Returns true if successful, or false on failure.
        * @param int $sloodleid Integer ID of the Sloodle instance to read data from
        * @return bool
        *
        * @todo Implement me!
        */
        function read_database($sloodleid)
            return false;
        }
        
        /**
        * Writes current Sloodle module data back to the database.
        * Updates the existing entry, or creates a new one, as necessary.
        * @return bool True if successful, or false on failure
        *
        * @todo Implement me!
        */
        function write_database()
        {
            return false;
        }
        
    
    }

?>