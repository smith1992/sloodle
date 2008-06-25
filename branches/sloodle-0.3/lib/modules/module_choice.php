<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines a choice module for Sloodle.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    /** The Sloodle module base. */
    require_once(SLOODLE_LIBROOT.'/modules/module_base.php');
    /** General Sloodle functions. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    
    /**
    * The Sloodle choice module class.
    * @package sloodle
    */
    class SloodleModuleChoice extends SloodleModule
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
        * Internal only - Moodle choice module instance database object.
        * Corresponds to one record from the Moodle 'choice' table.
        * @var object
        * @access private
        */
        var $moodle_choice_instance = null;

        /**
        * The number of (non-admin) users on the course who have not yet answered this choice.
        * @var int
        * @access private
        */
        var $numunanswered = 0;
        
        /**
        * The options available for this choice, as an associative array of IDs to {@link SloodleChoiceOption} objects.
        * @var array
        * @access public
        */
        var $options = array();
                
        
    // FUNCTIONS //
    
        /**
        * Constructor
        */
        function SloodleModuleChoice(&$_session)
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
            $id = (int)$id;
            if ($id <= 0) return false;
            
            // Fetch the course module data
            if (!($this->cm = get_coursemodule_from_id('choice', $id))) {
                sloodle_debug("Failed to load course module instance #$id.<br/>");
                return false;
            }
            // Make sure the module is visible
            if ($this->cm->visible == 0) {
                sloodle_debug("Error: course module instance #$id not visible.<br/>");
                return false;
            }
            
            // Load from the primary table: choice instance
            if (!($this->moodle_choice_instance = get_record('choice', 'id', $this->cm->instance))) {
                sloodle_debug("Failed to load choice with instance ID #{$cm->instance}.<br/>");
                return false;
            }
            
            // Fetch options
            $this->options = array();
            if ($options = get_records('choice_options', 'choiceid', $choiceid, 'id')) {
                foreach ($options as $opt) {
                    // Create our option object and add our data
                    $this->options[$opt->id] = new SloodleChoiceOption();
                    $this->options[$opt->id]->id = $opt->id;
                    $this->options[$opt->id]->text = $opt->text;
                    $this->options[$opt->id]->maxselections = $opt->maxanswers;
                    $this->options[$opt->id]->numselections = (int)count_records('choice_answers', 'optionid', $opt->id);
                    $this->options[$opt->id]->timemodified = (int)$opt->timemodified;
                }
            }
            
            // Determine how many people on the course have not yet answered
            $users = get_course_users($course);
            if (!is_array($users)) return false;
            $num_users = count($users);
            $numanswers = (int)count_records('choice_answers', 'choiceid', $choice->id);
            $this->numunanswered = max(0, $num_users - $numanswers);
            
            return true;
        }
        
        
        
    // ACCESSORS //
    
        /**
        * Gets the name of this module instance.
        * @return string The name of this controller
        */
        function get_name()
        {
            return $this->moodle_choice_instance->name;
        }
        
        /**
        * Gets the intro description of this module instance, if available.
        * @return string The intro description of this controller
        */
        function get_intro()
        {
            return $this->moodle_choice_instance->text;
        }
        
        /**
        * Gets the identifier of the course this controller belongs to.
        * @return mixed Course identifier. Type depends on VLE. (In Moodle, it will be an integer).
        */
        function get_course_id()
        {
            return (int)$this->moodle_choice_instance->course;
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
            return (int)$this->moodle_choice_instance->timemodified;
        }
        
        
        /**
        * Gets the short type name of this instance.
        * @return string
        */
        function get_type()
        {
            return 'choice';
        }

        /**
        * Gets the full type name of this instance, according to the current language pack, if available.
        * Note: should be overridden by sub-classes.
        * @return string Full type name if possible, or the short name otherwise.
        */
        function get_type_full()
        {
            return get_string('modulename', 'choice');
        }
        
        /**
        * Gets the time at which this choice opens.
        * @return int Timestamp. 0 if choice has no opening time.
        */
        function get_opening_time()
        {
            return (int)$this->moodle_choice_instance->timeopen;
        }
        
        /**
        * Gets the time at which this choice closes.
        * @return int Timestamp. 0 if choice has no closing time.
        */
        function get_closing_time()
        {
            return (int)$this->moodle_choice_instance->timeclose;
        }
        
        /**
        * Determines if the choice is currently open.
        * @param int $timestamp The time to test. Uses the current time if none is given.
        * @return bool
        */
        function is_open($timestamp = null)
        {
            // Use the current time if necessary
            if ($timestamp === null) $timestamp = time();
            // Check against the opening and closing times
            $open = $this->get_opening_time();
            $close = $this->get_closing_time();
            if ($open > 0 && $open > $timestamp) return false;
            if ($close > 0 && $close < $timestamp) return false;
            return true;
        }
        
        /**
        * Determines if the choice has not opened yet.
        * @param int $timestamp The time to test. Uses the current time if none is given.
        * @return bool
        */
        function is_early($timestamp = null)
        {
            // Use the current time if necessary
            if ($timestamp === null) $timestamp = time();
            // Check against the opening time
            $open = $this->get_opening_time();
            if ($open == 0) return false; // No opening time - can never be early
            return ($open > $timestamp);
        }
        
        /**
        * Determines if the choice has already closed.
        * @param int $timestamp The time to test. Uses the current time if none is given.
        * @return bool
        */
        function is_late($timestamp = null)
        {
            // Use the current time if necessary
            if ($timestamp === null) $timestamp = time();
            // Check against the closing time
            $close = $this->get_closing_time();
            if ($close == 0) return false; // No opening time - can never be early
            return ($close < $timestamp);
        }
        
        /**
        * Checks if users are allowed to re-select their answer in this choice.
        * @return bool
        */
        function allow_update()
        {
            return !empty($this->moodle_choice_instance->allowupdate);
        }
        
        /**
        * Checks if results are to be shown.
        * (Some choices only allow results after the choice is closed).
        * @return bool
        */
        function can_show_results()
        {
            if ($this->moodle_choice_instance->showresults == CHOICE_SHOWRESULTS_ALWAYS) return true;
            if ($this->moodle_choice_instance->showresults == CHOICE_SHOWRESULTS_AFTER_CLOSE && $this->is_late()) return true;
            return false;
        }
        
        /**
        * Gets the number of people who have not yet answered the choice.
        * Counts all users on the course, including students and teachers.
        * @return int
        */
        function get_num_unanswered()
        {
            return $this->numanswered;
        }

    }
    
    
    /**
    * Class to represent a single available option for a choice.
    * @package sloodle
    */
    class SloodleChoiceOption
    {
        /**
        * The ID of the option (should be unique across the site).
        * @var mixed
        * @access public
        */
        var $id = 0;
        
        /**
        * The text of this option.
        * @var string
        * @access public
        */
        var $text = '';
        
        /**
        * Number of selections so far of this option.
        * @var int
        * @access public
        */
        var $numselections = 0;
        
        /**
        * Maximum allowed number of selections for this option.
        * Note: will be -1 if there is no limit.
        * @var int
        * @access public
        */
        var $maxselections = -1
        
        /**
        * Timestamp of when this option was last modified.
        * $var int
        * @access public
        */
        var $timemodified = 0;
    }


?>