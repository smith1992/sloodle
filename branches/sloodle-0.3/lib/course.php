<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines a structure for Sloodle data about a particular Moodle course.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    
    /** Include the general Sloodle library. */
    require_once(SLOODLE_DIRROOT.'/general.php');
    
    
    /**
    * The Sloodle course data class
    * @package sloodle
    */
    class SloodleCourseData
    {
    // PUBLIC DATA //
    
        /**
        * The database object of the course to which this object relates.
        * Corresponds to the "course" table.
        * Is null if not yet set
        * @var object
        * @access public
        */
        var $course_object = null;
    
    // PRIVATE DATA //
    
        /**
        * Stores the ID of the entry in the Sloodle course table.
        * @var int
        * @access private
        */
        var $id = 0;
    
        /**
        * Indicates if auto-registration/enrolment is permitted on this course.
        * Note: this does not take the site-wide setting into account.
        * @var bool
        * @access private
        */
        var $autoreg_enabled = false;
        
        
        // More stuff?
        
        
    // CONSTRUCTOR //
    
        /**
        * Constructor
        */
        function sloodle_course_data()
        {
        }
        
        
    // ACCESSORS //
            
        /**
        * Gets the autoreg value for this course (ignores the site-wide setting)
        * @return bool
        */
        function get_autoreg()
        {
            return $this->autoreg_enabled;
        }
        
        /**
        * Sets the autoreg value for this course.
        * @param bool $autoreg True if autoreg should be enabled on this course, or false otherwise.
        * @return void
        */
        function set_autoreg($autoreg)
        {
            $this->autoreg_enabled = $autoreg;
        }
        
        /**
        * Is auto registration permitted through this controller?
        * Takes into account the site-wide setting as well.
        * @return bool
        */
        function is_autoreg_permitted()
        {
            // Check the site *and* the course value
            return (sloodle_autoreg_enabled_site() && $this->autoreg_enabled);
        }
        
        /**
        * Is there a database entry in Sloodle about the current course?
        * @return bool
        */
        function sloodle_entry_exists()
        {
            return ($this->id > 0);
        }
        
        
    // OPERATIONS //
    
        /**
        * Reads fresh data into the structure from the database.
        * Fetches Moodle and Sloodle data about the course specified.
        * Returns true if successful, or false on failure.
        * @param int $courseid Integer ID of the course to read data from
        * @return bool
        */
        function read_database($courseid)
        {
            // Make sure the course ID is valid
            if (!is_int($courseid) || $courseid <= 0) return false;
            
            // Reset our member data
            $this->id = 0;
            $this->autoreg_enabled = false;
            //.. add other members here
            
            // Fetch the Moodle course data
            $this->course_object = get_record('course', 'id', $courseid);
            if (!$this->course_object) return false;
            
            // Fetch the Sloodle data (but this is optional)
            $sloodle_data = get_record('sloodle_course_data', 'course', $courseid);
            if ($sloodle_data) {
                // Store the Sloodle data
                $this->id = $sloodle_data->id;
                $this->autoreg_enabled = (bool)$sloodle_data->autoreg;
                //.. add other items here
            }
            
            return true;
        }
        
        /**
        * Writes current Sloodle course data back to the database.
        * Updates the existing entry, or creates a new one, as necessary.
        * Requires that a course structure has already been retrieved.
        * @return bool True if successful, or false on failure
        */
        function write_database()
        {
            // Make sure the course ID is valid
            if (empty($this->course_object) || $this->course_object->id <= 0) return false;
            // Construct the database object
            $data = new stdClass();
            $data->autoreg = $this->autoreg_enabled;
            //.. other data here
            
            $result = false;
            
            // Was there already a record present?
            if ($this->id > 0) {
                // Yes - just update it
                $data->id = $this->id;
                $result = (bool)update_record('sloodle_course_data', $data);
                
            } else {
                // No - insert a new record
                $result = insert_record('sloodle_course_data', $data);
                if ($result) {
                    $this->id = $result;
                    $result = true;
                }
            }
            
            return $result;
        }
    
    }

?>