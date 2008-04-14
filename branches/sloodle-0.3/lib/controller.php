<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the Sloodle Controller module sub-type.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    
    /**
    * Represents a Sloodle Controller, including data such as prim password.
    * @package sloodle
    */
    class SloodleController
    {
    // DATA //
    
        /**
        * Internal for Moodle only - course module instance.
        * Corresponds to one record from the Moodle 'course_modules' table.
        * @var object
        * @access private
        */
        var $cm = null;
    
        /**
        * Internal only - Sloodle module instance database object.
        * Corresponds to one record from the Moodle 'sloodle' table.
        * @var object
        * @access private
        */
        var $sloodle_module_instance = null;
        
        /**
        * Internal only - Sloodle Controller instance database object.
        * Corresponds to one record from the Moodle 'sloodle_controller' table.
        * @var object
        * @access private
        */
        var $sloodle_controller_instance = null;
                
        
    // FUNCTIONS //
    
        /**
        * Constructor
        */
        function SloodleController()
        {
        }
        
        
        /**
        * Loads data from the database.
        * Note: even if the function fails, it may still have overwritten some or all existing data in the object.
        * @param mixed $id The site-wide unique identifier for all modules. Type depends on VLE. On Moodle, it is an integer course module identifier ('id' field of 'course_modules' table)
        * @return bool True if successful, or false otherwise
        */
        function load($id)
        {
            // Make sure the ID is valid
            if (!is_int($id) || $id <= 0) return false;
            
            // Fetch the course module data
            if (!($this->cm = get_record('course_modules', 'id', $id))) return false;
            // Load from the primary table: Sloodle instance
            if (!($this->sloodle_module_instance = get_record('sloodle', 'id', $this->cm->instance))) return false;
            // Check that it is the correct type
            if ($this->sloodle_module_instance->type != SLOODLE_TYPE_CTRL) return false;
            
            // Load from the secondary table: Controller instance
            if (!($this->sloodle_controller_instance = get_record('sloodle_controller', 'sloodleid', $this->cm->instance))) return false;
            
            return true;
        }
        
        /**
        * Updates the currently loaded entry in the database.
        * Note: the data *must* have been previously loaded using {@link load_from_db()}.
        * This function cannot be used to create new entries.
        * @return bool True if successful, or false otherwise
        */
        function write()
        {
            // Make sure we have all the necessary data
            if (empty($this->sloodle_module_instance) || empty($this->sloodle_controller_instance)) return false;
            // Attempt to update the primary table
            if (!update_record('sloodle', $this->sloodle_module_instance)) return false;
            // Attempt to update the secondary table
            if (!update_record('sloodle_controller', $this->sloodle_controller_instance)) return false;
            
            // Everything seems OK
            return true;
        }
        
        
    // ACCESSORS //
    
        /**
        * Gets the site-wide unique identifier for this module.
        * @return mixed Identifier. Type is dependent on VLE. On Moodle, it is an integer course module identifier.
        */
        function get_id()
        {
            return $this->cm->id;
        }
    
        /**
        * Gets the name of this controller.
        * @return string The name of this controller
        */
        function get_name()
        {
            return $this->sloodle_module_instance->name;
        }
        
        /**
        * Sets the name of this controller.
        * @param string $name The new name for this controller - ignored if empty
        * @return void
        */
        function set_name($name)
        {
            if (!empty($name)) $this->sloodle_module_instance->name = $name;
        }
        
        /**
        * Gets the intro description of this controller.
        * @return string The intro description of this controller
        */
        function get_intro()
        {
            return $this->sloodle_module_instance->intro;
        }
        
        /**
        * Sets the intro description of this controller.
        * @param string $intro The new intro for this controller - ignored if empty
        * @return void
        */
        function set_intro($intro)
        {
            if (!empty($intro)) $this->sloodle_module_instance->intro = $intro;
        }
        
        /**
        * Gets the identifier of the course this controller belongs to.
        * @return mixed Course identifier. Type depends on VLE. (In Moodle, it will be an integer).
        */
        function get_course_id()
        {
            return (int)$this->sloodle_module_instance->course;
        }
        
        /**
        * Gets the time at which this controller was created.
        * @return int Timestamp
        */
        function get_creation_time()
        {
            return $this->sloodle_module_instance->timecreated;
        }
        
        /**
        * Gets the time at which this controller was last modified.
        * @return int Timestamp
        */
        function get_modification_time()
        {
            return $this->sloodle_module_instance->timemodified;
        }
        
        /**
        * Determines if this controller is enabled or not.
        * @return bool True if the controller is enabled, or false otherwise.
        */
        function is_enabled()
        {
            return (bool)($this->sloodle_controller_instance->enabled);
        }
        
        /**
        * Enables this controller
        * @return void
        */
        function enable()
        {
            $this->sloodle_controller_instance->enabled = true;
        }
        
        /**
        * Disables this controller
        * @return void
        */
        function disable()
        {
            $this->sloodle_controller_instance->enabled = false;
        }
        
        /**
        * Gets the prim password of this controller.
        * @return string The current prim password.
        */
        function get_password($password)
        {
            return $this->sloodle_controller_instance->password;
        }
                
        /**
        * Sets the prim password of this controller.
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
        
        /**
        * Checks a password against this controller.
        * @param string $password Can either be a plain prim password, or an object-specific session key
        * @return bool True if the password matches, or false otherwise
        * @todo Implement me!
        */
        function check_password($password)
        {
            //...
            return false;
        }
        
        /**
        * Register a new active object (or renew an existing one) with this controller.
        * @param string $uuid The UUID of the object to be registered
        * @param int $timestamp The timestamp of the object's registration, or null to use the current time.
        * @return string,bool If successful, a string containig the object-specific session key. Otherwise boolean false.
        * @todo Implement me
        */
        function register_object($uuid)
        {
            //...
            return false;
        }
    }

?>