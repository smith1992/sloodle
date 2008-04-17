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
    require_once(SLOODLE_LIBROOT.'/general.php');
    /** Include the Sloodle controller structure. */
    require_once(SLOODLE_LIBROOT.'/controller.php');
    
    
    /**
    * The Sloodle course data class
    * @package sloodle
    */
    class SloodleCourse
    {
    // DATA //
    
        /**
        * The database object of the course to which this object relates.
        * Corresponds to the "course" table in Moodle.
        * Is null if not yet set
        * @var object
        * @access private
        */
        var $course_object = null;
    
        /**
        * The Sloodle course data object, if it exists.
        * Is null if not yet set.
        * @var object
        * @var private
        */
        var $sloodle_course_data = null;
        
        /**
        * The {@link SloodleController} object being used to access this course, if available.
        * @var SloodleController
        * @var public
        */
        var $controller = null;
        
        
    // FUNCTIONS //
    
        /**
        * Constructor
        */
        function SloodleCourse()
        {
            $this->controller = new SloodleController();
        }
        
        /**
        * Determines whether or not course data has been loaded.
        * @return bool
        */
        function is_loaded()
        {
            if (empty($this->course_object) || empty($this->sloodle_course_data)) return false;
            return true;
        }
        
        /**
        * Gets the identifier of the course in the VLE.
        * @return mixed Course identifier. Type depends on VLE. (In Moodle, it will be an integer).
        */
        function get_course_id()
        {
            return (int)$this->course_object->id;
        }
        
        /**
        * Gets the VLE course object.
        * WARNING: this should only be used when ABSOLUTELY necessary.
        * The contents are specific to the VLE.
        * @return mixed Type and content depends upon VLE. In Moodle, it is an object representing a record from the 'course' table.
        */
        function get_course_object()
        {
            return $this->course_object;
        }
        
        
        /**
        * Gets the short name of this course in the VLE.
        * @return string Shortname of this course.
        */
        function get_short_name()
        {
            return $this->course_object->shortname;
        }
        
        /**
        * Gets the full name of this course in the VLE.
        * @return string Fullname of this course.
        */
        function get_full_name()
        {
            return $this->course_object->fullname;
        }
        
        /**
        * Is auto registration permitted on this site AND course?
        * Takes into account the site-wide setting as well.
        * @return bool
        */
        function check_autoreg()
        {
            // Check the site *and* the course value
            return ((bool)sloodle_autoreg_enabled_site() && (bool)$this->sloodle_course_data->autoreg);
        }
        
        /**
        * Gets the autoregistration value for this course only.
        * (Ignores the site setting).
        * @return bool
        */
        function get_autoreg()
        {
            return (bool)$this->sloodle_course_data->autoreg;
        }
        
        /**
        * Enables auto-registration for this course.
        * NOTE: it may still be disabled at site-level.
        * @return void
        */
        function enable_autoreg()
        {
            $this->sloodle_course_data->autoreg = 1;
        }
        
        /**
        * Disables auto-registration for this course.
        * NOTE: does not affect the site setting.
        * @return void
        */
        function disable_autoreg()
        {
            $this->sloodle_course_data->autoreg = 0;
        }
        
        /**
        * Determines whether or not the course is available.
        * Checks that the course has not been disabled or hidden etc..
        * @return bool True if the course is available
        */
        function is_available()
        {
            // Check visbility
            if (empty($this->course_object->visible)) return false;
            
            return true;
        }

    
        /**
        * Reads fresh data into the structure from the database.
        * Fetches Moodle and Sloodle data about the course specified.
        * If necessary, it creates a new Sloodle entry with default settings.
        * Returns true if successful, or false on failure.
        * @param mixed $course Either a unique course ID, or a course data object. If the former, then VLE course data is read from the database. Otherwise, the data object is used as-is.
        * @return bool
        */
        function load($course)
        {
            // Reset everything
            $this->course_object = null;
            $this->sloodle_course_data = null;
        
            // Check what we are dealing with
            if (is_int($course)) {
                // It is a course ID - make sure it's valid
                if ($course <= 0) return false;
                // Load the course data
                $this->course_object = get_record('course', 'id', $course);
                if (!$this->course_object) {
                    $this->course_object = null;
                    return false;
                }
            } else if (is_object($course)) {
                // It is an object - make sure it has an ID
                if (!isset($course->id)) return false;
                $this->course_object = $course;
            } else {
                // Don't know what it is - do nothing
                return false;
            }
            
            // Fetch the Sloodle course data
            $this->sloodle_course_data = get_record('sloodle_course', 'course', $this->course_object->id);
            // Did it fail?
            if (!$this->sloodle_course_data) {
                // Create the new entry
                $this->sloodle_course_data = new stdClass();
                $this->sloodle_course_data->course = $this->course_object->id;
                $this->sloodle_course_data->autoreg = 0;
                $this->sloodle_course_data->id = insert_record('sloodle_course', $this->sloodle_course_data);
                // Did something go wrong?
                if (!$this->sloodle_course_data->id) {
                    $this->course_object = null;
                    $this->sloodle_course_data = null;
                    return false;
                }
            }
            
            return true;
        }
        
        /**
        * Loads course and controller data by the unqiue site-wide identifier of a Sloodle controller.
        * @param mixed $controllerid The unique site-wide identifier for a Sloodle Controller. (For Moodle, an integer cmi)
        * @return bool True if successful, or false on failure.        
        */
        function load_by_controller($controllerid)
        {
            // Clear out all our data
            $this->course_object = null;
            $this->sloodle_course_data = null;
            
            // Construct a new controller object, and attempt to load its data
            $this->controller = new SloodleController();
            if (!$this->controller->load($controllerid)) return false;
            
            // Now attempt to load all the course data
            if (!$this->load($this->controller->get_course_id())) {
                $this->controller = null;
                return false;
            }
            
            return true;
        }
        
        /**
        * Writes current Sloodle course data back to the database.
        * Requires that a course structure has already been retrieved.
        * @return bool True if successful, or false on failure
        */
        function write()
        {
            // Make sure the course data is valid
            if (empty($this->course_object) || $this->course_object->id <= 0) return false;
            if (empty($this->sloodle_course_data) || $this->sloodle_course_data->id <= 0) return false;
            // Update the Sloodle data
            return update_record('sloodle_course', $this->sloodle_course_data);
        }
    
    }

?>