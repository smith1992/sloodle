<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the base class for Sloodle modules.
    * (Each module is effectively a sub-type of the Moodle module).
    *
    * @package sloodle
    * @copyright Copyright (c) 2007 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    /**
    * The base Sloodle module class.
    * @package sloodle
    */
    class SloodleModule
    {
    // PUBLIC DATA //
    
        /**
        * An object containing Sloodle data about the course this instance is in.
        * Is null if not yet set.
        * @var object
        * @access public
        */
        var $course = 0;
        
    
    // PRIVATE DATA //
    
        /**
        * The course module instance database record.
        * Corresponds to the "course_modules" table.
        * Is null if not yet set
        * @var object
        * @access private
        */
        var $cmi = null;
    
        /**
        * The Sloodle instance number.
        * Corresonds to the 'id' field of the "sloodle" table.
        * Has value 0 if not yet set.
        * @var int
        * @access private
        */
        var $id = 0;
        
        
    
        /**
        * The name of this instance.
        * This name is defined by the person who created the instance.
        * Empty if not yet set.
        * @var string
        * @access private
        */
        var $name = '';
        
        /**
        * The intro text of this instance.
        * This intro (or description) is defined by the person who created the instance.
        * Empty if not yet set.
        * @var string
        * @access private
        */
        var $intro = '';
        
        /**
        * The shortname of this Sloodle module type.
        * This type is selected when the instance is first created, and cannot later be changed.
        * Empty if not yet set.
        * @var string
        * @access private
        */
        var $type = '';
        
        
        /**
        * The secondary table object for this sub-type.
        * Corresponds to e.g. "sloodle_controller" or "sloodle_distributor" tables.
        * Null if there is no secondary table.
        * @var object
        * @access private
        */
        var $secondary_table = null;
        
        
    // CONSTRUCTOR //
    
        /**
        * Constructor
        */
        function SloodleModule()
        {
        }
        
        
    // ACCESSORS //
    
        /**
        * Gets the ID number of this instance
        * @return int
        */
        function get_id()
        {
            return $this->id;
        }
    
        /**
        * Gets the name of this instance
        * @return string
        */
        function get_name()
        {
            return $this->name;
        }
        
        /**
        * Gets the intro of this instance
        * @return string
        */
        function get_intro()
        {
            return $this->intro;
        }
        
        /**
        * Gets the short typename of this instance
        * @return string
        */
        function get_type()
        {
            return $this->type;
        }
        
        /**
        * Gets the full type name of this instance, according to the current language pack
        * @return string
        */
        function get_type_full()
        {
            return get_string("moduletype:{$this->type}", 'sloodle');
        }
        
        
        /**
        * Gets the secondary table object for this instance, or null if there isn't one.
        * @return object
        */
        function get_secondary_table()
        {
            return $this->secondary_table;
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