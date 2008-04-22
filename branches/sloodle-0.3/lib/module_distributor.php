<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the Sloodle Distributor module.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    /** The Sloodle module base. */
    require_once(SLOODLE_LIBROOT.'/module_base.php');
    /** General Sloodle functions. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    
    /**
    * The Sloodle Distributor module class.
    * @package sloodle
    */
    class SloodleModuleDistributor extends SloodleModule
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
        * Internal only - Sloodle Distributor instance database object.
        * Corresponds to one record from the Moodle 'sloodle_distributor' table.
        * @var object
        * @access private
        */
        var $sloodle_distributor_instance = null;
                
        
    // FUNCTIONS //
    
        /**
        * Constructor
        */
        function SloodleModuleDistributor(&$_session)
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
        function load_from_db($id)
        {
            // Make sure the ID is valid
            if (!is_int($id) || $id <= 0) return false;
            
            // Fetch the course module data
            if (!($this->cm = get_coursemodule_from_id('sloodle', $id))) return false;
            // Load from the primary table: Sloodle instance
            if (!($this->sloodle_module_instance = get_record('sloodle', 'id', $this->cm->instance))) return false;
            // Check that it is the correct type
            if ($this->sloodle_module_instance->type != SLOODLE_TYPE_DISTRIB) return false;
            
            // Load from the secondary table: Distributor instance
            if (!($this->sloodle_distributor_instance = get_record('sloodle_distributor', 'sloodleid', $this->cm->instance))) return false;
            
            return true;
        }
        
        
        /**
        * Gets a list of all objects for this Distributor.
        * @return array An array of strings, each string containing the name of an object in this Distributor.
        */
        function get_objects()
        {
            // Get all distributor record entries for this distributor, sorted alphabetically
            $recs = get_records('sloodle_distributor_entry', 'distributorid', $this->sloodle_distributor_instance->id, 'name');
            if (!$recs) return array();
            // Convert it to an array of strings
            $entries = array();
            foreach ($recs as $r) {
                $entries[] = $recs->name;
            }
            
            return $entries;
        }
        
        
        /**
        * Sets the list of objects in this Distributor
        * @param array $objects An array of strings, each string containing the name of an object in the Distributor.
        * @return bool True if successful, or false if not
        */
        function set_objects($objects)
        {
            // Delete all existing records for this Distributor
            delete_records('sloodle_distributor_entry', 'distributorid', $this->sloodle_distributor_instance->id);
            
            // Go through each new entry
            $result = true;
            foreach ($objects as $o) {
                // Construct the new record
                $rec = new stdClass();
                $rec->distributorid = $this->sloodle_distributor_instance->id;
                $rec->name = $o;
                // Insert it
                if (!insert_record('sloodle_distributor_entry', $rec)) $result = false;
            }
        
            return $result;
        }
        
        
        /**
        * Request that the specified object be sent to the specified avatar.
        * @param string $objname Name of the object to send
        * @param string $uuid UUID of the avatar to send the object to
        * @return bool True if successful, or false if not.
        */
        function send_object($objname, $uuid)
        {
            // Check that the object exists in this distributor
            if (!record_exists('sloodle_distributor_entry', 'distributorid', $this->distrib_id, 'name', $objname)) return false;
            // Send the XMLRPC request
            return sloodle_send_xmlrpc_message($this->sloodle_distributor_instance->channel, 0, "1|OK\\nSENDOBJECT|$uuid|$objname");
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
            return SLOODLE_TYPE_DISTRIB;
        }

        /**
        * Gets the full type name of this instance, according to the current language pack, if available.
        * Note: should be overridden by sub-classes.
        * @return string Full type name if possible, or the short name otherwise.
        */
        function get_type_full()
        {
            return get_string('moduletype:'.SLOODLE_TYPE_DISTRIB, 'sloodle');
        }

    }


?>
