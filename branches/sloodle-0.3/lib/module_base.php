<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the base class for Sloodle modules.
    * (Each module is effectively a sub-type of the Moodle module).
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    /**
    * Sloodle module base class.
    * An abstract class which must be overridden by sub-classes.
    * @package sloodle
    */
    class SloodleModule
    {
    // DATA //
    
        /**
        * Reference to the containing {@link SloodleSession} object.
        * If null, then this module is being used outwith the framework.
        * <b>Always check the status of the variable before using it!</b>
        * @var object
        * @access protected
        */
        var $_session;
        
    
    // FUNCTIONS //
    
        /**
        * Constructor - initialises the session variable
        * @param object &$_session A reference to the containing {@link SloodleSession} object, if available.
        */
        function SloodleModule(&$_session = null)
        {
            if (!is_null($_session)) $this->_session = &$_session;
        }
        
        
        /**
        * Loads data from the database.
        * Note: even if the function fails, it may still have overwritten some or all existing data in the object.
        * @param mixed $id The site-wide unique identifier for all modules. Type depends on VLE. On Moodle, it is an integer course module identifier ('id' field of 'course_modules' table)
        * @return bool True if successful, or false otherwise
        */
        function load_from_db($id)
        {
            return false;
        }
        
        
    // ACCESSORS //
    
        /**
        * Gets the name of this module instance.
        * @return string The name of this controller
        */
        function get_name()
        {
            return '';
        }
        
        /**
        * Gets the intro description of this module instance, if available.
        * @return string The intro description of this controller
        */
        function get_intro()
        {
            return '';
        }
        
        /**
        * Gets the identifier of the course this controller belongs to.
        * @return mixed Course identifier. Type depends on VLE. (In Moodle, it will be an integer).
        */
        function get_course_id()
        {
            return 0;
        }
        
        /**
        * Gets the time at which this instance was created, or 0 if unknown.
        * @return int Timestamp
        */
        function get_creation_time()
        {
            return 0;
        }
        
        /**
        * Gets the time at which this instance was last modified, or 0 if unknown.
        * @return int Timestamp
        */
        function get_modification_time()
        {
            return 0;
        }
        
        
        /**
        * Gets the short type name of this instance.
        * @return string
        */
        function get_type()
        {
            return '';
        }

        /**
        * Gets the full type name of this instance, according to the current language pack, if available.
        * Note: should be overridden by sub-classes.
        * @return string Full type name if possible, or the short name otherwise.
        */
        function get_type_full()
        {
            return '';
        }
    }

?>