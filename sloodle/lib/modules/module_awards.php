<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the Sloodle awards module.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    * @contributor Paul G. Preibisch - aka Fire Centaur 
    */
    
    /** The Sloodle module base. */
    require_once(SLOODLE_LIBROOT.'/modules/module_base.php');
    /** General Sloodle functions. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    
    /** SLOODLE course object data structure */
    require_once(SLOODLE_LIBROOT.'/sloodlecourseobject.php');

    /** SLOODLE stipendgiver object data structure */
    require_once(SLOODLE_DIRROOT.'/mod/awards-1.0/awards_object.php');
    /** Sloodle Session code. */
        
    /**
    * The Sloodle StipendGiver module class.
    * @package sloodle
    */
    class SloodleModuleAwards extends SloodleModule
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
        * Internal only - Sloodle StipendGiver instance database object.
        * Corresponds to one record from the Moodle 'sloodle_awards' table.
        * @var object
        * @access private
        */
        var $sloodle_awards_instance = null;
        
        var $sCourseObj = null;
        

        
        var $sloodleid = null;       
        
    // FUNCTIONS //
    
        /**
        * Constructor
        */
        function SloodleModuleAwards(&$_session)
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
            if (!is_int($id) || $id <= 0) return false;
            
            // Fetch the course module data
            if (!($this->cm = get_coursemodule_from_id('sloodle', $id))) return false;
            // Load from the primary table: Sloodle instance
            if (!($this->sloodle_module_instance = get_record('sloodle', 'id', $this->cm->instance))) return false;
            // Check that it is the correct type
            
            
            
            if ($this->sloodle_module_instance->type != SLOODLE_TYPE_AWARDS) return false;
            
            // Load from the secondary table: StipendGiver instance
            if (!($this->sloodle_awards_instance = get_record('sloodle_awards', 'sloodleid', $this->cm->instance))) return false;
            
            return true;
        }
        
        
        /**
        * Gets a list of all objects for this StipendGiver.
        * @return array An array of strings, each string containing the name of an object in this StipendGiver.
        */
        function get_objects()
        {
            // Get all StipendGiver record entries for this StipendGiver
            $recs = get_records('sloodle_award_trans', 'sloodleid', $this->sloodle_awards_instance->id);
            if (!$recs) return array();
            // Convert it to an array of strings
            $entries = $recs;
            
            
            return $entries;
        }
        
         /**
    * Gets a list of students in the class
    */
      function get_class_list(){
            $fulluserlist = get_users(true, '');
            if (!$fulluserlist) $fulluserlist = array();
            $userlist = array();
            // Filter it down to members of the course
            foreach ($fulluserlist as $ful) {
                // Is this user on this course?
                if (has_capability('moodle/course:view', $this->course_context, $ful->id)) {
                    // Copy it to our filtered list and exclude administrators
                    if (!isadmin($ful->id))
                      $userlist[] = $ful;
                }
            }
            return $userlist;
      
      }
      
      
        /**
        * This attempts to withdraw money.
        * @param array $info is an array which first lists the intent of what the stipend will be used for
        * the next element is the uuid of the avatar
        * @return bool True if successful, or false if not
        */
                           
        
        
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
        function get_amount(){
          return $this->sloodle_awards_instance->amount;
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
            return SLOODLE_TYPE_AWARDS;
        }

        /**
        * Gets the full type name of this instance, according to the current language pack, if available.
        * Note: should be overridden by sub-classes.
        * @return string Full type name if possible, or the short name otherwise.
        */
        function get_type_full()
        {
            return get_string('moduletype:'.SLOODLE_TYPE_AWARDS, 'sloodle');
        }

    }


?>
