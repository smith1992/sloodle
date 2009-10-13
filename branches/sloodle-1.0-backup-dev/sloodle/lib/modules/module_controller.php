<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines a Controller module for Sloodle.
    * It is not currently used in normal SLOODLE operations.
    * It is only used for backup and restore purposes.
    *
    * @package sloodle
    * @copyright Copyright (c) 2009 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    /** The Sloodle module base. */
    require_once(SLOODLE_LIBROOT.'/modules/module_base.php');
    /** General Sloodle functions. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    
    /**
    * The Sloodle Controller module class.
    * @package sloodle
    */
    class SloodleModuleController extends SloodleModule
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
        * Internal only - Sloodle module instance database object. (Primary table)
        * Corresponds to one record from the Moodle 'sloodle' table.
        * @var object
        * @access private
        */
        var $sloodle_module_instance = null;
    
        /**
        * Internal only - SLOODLE Controller instance object. (Secondary table)
        * Corresponds to one record from the Moodle 'sloodle_controller' table.
        * @var object
        * @access private
        */
        var $sloodle_controller_instance = null;
                
        
    // FUNCTIONS //
    
        /**
        * Constructor
        */
        function SloodleModuleController(&$_session)
        {
            $constructor = get_parent_class($this);
            parent::$constructor($_session);
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
            if (!is_int($id) || $id <= 0) {
                echo "<hr><pre>ID = "; print_r($id); echo "</pre><hr>";
                return false;
            }
            
            // Fetch the course module data
            if (!($this->cm = get_coursemodule_from_id('sloodle', $id))) return false;
            // Load from the primary table: Sloodle instance
            if (!($this->sloodle_module_instance = get_record('sloodle', 'id', $this->cm->instance))) return false;
            // Check that it is the correct type
            if ($this->sloodle_module_instance->type != SLOODLE_TYPE_CTRL) return false;
            
            // Load from the secondary table: Distributor instance
            if (!($this->sloodle_controller_instance = get_record('sloodle_controller', 'sloodleid', $this->cm->instance))) return false;
            
            return true;
        }
        
        
        
        
        
    // ACCESSORS //
    
        /**
        * Gets the name of this module instance.
        * @return string The name of this controller
        */
        function get_name()
        {
            return $this->sloodle_module_instance->name;
        }
        
        /**
        * Gets the intro description of this module instance, if available.
        * @return string The intro description of this controller
        */
        function get_intro()
        {
            return $this->sloodle_module_instance->intro;
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
        * Gets the time at which this instance was created, or 0 if unknown.
        * @return int Timestamp
        */
        function get_creation_time()
        {
            return $this->sloodle_module_instance->timecreated;
        }
        
        /**
        * Gets the time at which this instance was last modified, or 0 if unknown.
        * @return int Timestamp
        */
        function get_modification_time()
        {
            return $this->sloodle_module_instance->timemodified;
        }
        
        
        /**
        * Gets the short type name of this instance.
        * @return string
        */
        function get_type()
        {
            return SLOODLE_TYPE_CTRL;
        }

        /**
        * Gets the full type name of this instance, according to the current language pack, if available.
        * Note: should be overridden by sub-classes.
        * @return string Full type name if possible, or the short name otherwise.
        */
        function get_type_full()
        {
            return get_string('moduletype:'.SLOODLE_TYPE_CTRL, 'sloodle');
        }

    }
    


?>