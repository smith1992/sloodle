<?php    
    /**
    * Sloodle user library.
    *
    * Provides functionality for reading, managing and editing user data.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    * @since Sloodle 0.2
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This library expects that the Sloodle config file has already been included
    //  (along with the Moodle libraries)
    
    /** Include the Sloodle IO library. */
    require_once(SLOODLE_DIRROOT.'/lib/io.php');
    /** Include the general Sloodle functionality. */
    require_once(SLOODLE_DIRROOT.'/lib/general.php');
    /** Include the Sloodle course data structure. */
    require_once(SLOODLE_DIRROOT.'/lib/course.php');
    
    
    /**
    * A class to represent a single user, including Moodle and Sloodle data.
    * @package sloodle
    */
    class SloodleUser
    {
    // DATA //
    
        /**
        * Internal only - reference to the containing {@link SloodleSession} object.
        * Note: always check that it is not null before use!
        * @var object
        * @access protected
        */
        var $_session = null;
    
        /**
        * Internal only - avatar data.
        * In Moodle, corresponds to a record from the 'sloodle_users' table.
        * @var object
        * @access private
        */
        var $avatar_data = null;
        
        /**
        * Internal only - user data. (i.e. VLE user)
        * In Moodle, corresponds to a record from the 'user' table.
        * @var obejct
        * @access private
        */
        var $user_data = null;
        
        
    // CONSTRUCTOR //
    
        /**
        * Class constructor.
        * @param object &$_session Reference to the containing {@link SloodleSession} object, if available.
        * @access public
        */
        function SloodleUser(&$_session = null)
        {
            if (!is_null($_session)) $this->_session = &$_session;
        }
        
        
    // ACCESSORS //
    
        /**
        * Gets the unique ID of the avatar.
        * @return mixed Type depends on VLE. (Integer on Moodle).
        * @access public
        */
        function get_avatar_id()
        {
            return $this->avatar_data->id;
        }
        
        /**
        * Gets the unique ID of the VLE user.
        * @return mixed Type depends on VLE. (Integer on Moodle).
        * @access public
        */
        function get_user_id()
        {
            return $this->user_data->id;
        }
        
        
    // USER LINK FUNCTIONS //
        
        /**
        * Determines whether or not the current user and avatar are linked.
        * @return bool True if they are linked, or false if not.
        */
        function is_avatar_linked()
        {
            // Make sure there is data in both caches
            if (empty($this->avatar_data) || empty($this->user_data)) return false;
            // Check for the link (ignore the number 0, as that is not a valid ID)
            if ($this->avatar_data->userid != 0 && $this->avatar_data->userid == $this->user_data->id) return true;
            return false;
        }
    
        /**
        * Links the current avatar to the current user.
        * <b>NOTE:</b> does not remove any other avatar links to the VLE user.
        * @return bool True if successful or false otherwise.
        * @access public
        */
        function link_avatar()
        {
            // Make sure there is data in both caches
            if (empty($this->avatar_data) || empty($this->user_data)) return false;
            
            // Set the linked user ID and update the database record
            $olduserid = $this->avatar_data->userid;
            $this->avatar_data->userid = $this->user_data->id;
            if (update_record('sloodle_users', $this->avatar_data)) return true;
            // The operation failed, so change the user ID back
            $this->avatar_data->userid = $olduserid;
            return false;
        }
        
        
    // DATABASE FUNCTIONS //
    
        /**
        * Deletes the current avatar from the database.
        * @return bool True if successful, or false on failure
        * @access public
        */
        function delete_avatar()
        {
            // Make sure we have avatar data
            if (empty($this->avatar_data)) return false;
            
            // Attempt to delete the record from the database
            return delete_records('sloodle_users', 'id', $this->avatar_data->id);
        }
        
        /**
        * Loads the specified avatar from the database.
        * @param mixed $id The ID of the avatar (type depends on VLE; integer for Moodle)
        * @return bool True if successful, or false otherwise.
        * @access public
        */
        function load_avatar_by_id($id)
        {
            // Make sure the ID is valid
            if (!is_int($id) || $id <= 0) return false;
            // Fetch the avatar data
            $this->avatar_data = get_record('sloodle_users', 'id', $id);
            if (!$this->avatar_data) {
                $this->avatar_data = null;
                return false;
            }
            return true;
        }
        
        /**
        * Finds an avatar with the given UUID and/or name, and loads its data.
        * The UUID is searched for first. If that is not found, then the name is used.
        * @param string $uuid The UUID of the avatar, or blank to search only by name.
        * @param string $avname The name of the avatar, or blank to search only by UUID.
        * @return bool True if successful, or false otherwise
        * @access public
        */
        function load_avatar($uuid, $avname)
        {
            // Both parameters can't be empty
            if (empty($uuid) && empty($avname)) return false;
            
            // Attempt to search by UUID first
            if (!empty($uuid)) {
                $this->avatar_data = get_record('sloodle_users', 'uuid', $uuid);
                if ($this->avatar_data) return true;
            }
            
            // Attempt to search by name
            if (!empty($avname)) {
                $this->avatar_data = get_record('sloodle_users', 'avname', $avname);
                if ($this->avatar_data) return true;
            }
            
            // The search failed
            $this->avatar_data = null;
            return false;
        }
        
        /**
        * Load the specified user from the database
        * @param mixed $id The unique identifier for the VLE user. (Type depends on VLE; integer for Moodle)
        * @return bool True if successful, or false on failure
        * @access public
        */
        function load_user($id)
        {
            // Make sure the ID is valid
            if (!is_int($id) || $id <= 0) return false;
            
            // Attempt to load the data
            $this->user_data = get_complete_user_data('id', $id);
            if (!$this->user_data) {
                $this->user_data = null;
                return false;
            }
            
            return true;
        }
        
        /**
        * Uses the current avatar data to update the database.
        * @return bool True if successful, or false if the update fails
        * @access public
        */
        function write_avatar()
        {
            // Make sure we have avatar data
            if (empty($this->avatar_data) || $this->avatar_data->id <= 0) return false;
            // Make the update
            return update_record('sloodle_users', $this->avatar_data);
        }
        
        /**
        * Adds a new avatar to the database.
        * @param mixed $userid Site-wide unique ID of a user (type depends on VLE; integer for Moodle)
        * @param string $uuid UUID of the avatar
        * @param string $avname Name of the avatar
        * @return bool True if successful, or false if not.
        * @access public
        */
        function add_avatar($userid, $uuid, $avname)
        {
            // Setup our object
            $this->avatar_data = new stdClass();
            $this->avatar_data->id = 0;
            $this->avatar_data->userid = $userid;
            $this->avatar_data->uuid = $uuid;
            $this->avatar_data->avname = $avname;
            
            // Add the data to the database
            $this->avatar_data->id = insert_record('sloodle_users', $this->avatar_data);
            if (!$this->avatar_data->id) {
                $this->avatar_data = null;
                return false;
            }
            
            return true;
        }
        
        /**
        * Auto-register a new user account for the current avatar.
        * NOTE: this does NOT respect ANYTHING but the most basic Moodle accounts.
        * Use at your own risk!
        * @return bool True if successful, or false otherwise.
        * @access public
        */
        function autoregister_avatar_user()
        {
            // Make sure we have avatar data, and reset the user data
            if (empty($this->avatar_data)) return false;
            $this->user_data = null;
            
            // Construct a basic username
            $nameparts = strtok($this->avatar_data->avname, " \n\t\v");
            $baseusername = striptags(stripslashes(implode('', $nameparts)));
            $username = $baseusername;
            $conflict_moodle = record_exists('user', 'username', $username);
            
            // If that didn't work, then try a few random variants (just a number added to the end of the name)
            $MAX_RANDOM_TRIES = 3;
            $rnd_try = 0;
            while ($rnd_try < $MAX_RANDOM_TRIES && $conflict_moodle) {
                // Pick a random 3 digit number
                $rnd_num = mt_rand(100, 998);
                if ($rnd_num >= 666) $rnd_num++; // Some users may object to this number
                
                // Construct a new username to try
                $username = $baseusername . (string)$rnd_num;
                // Check for conflicts
                $conflict_moodle = record_exists('user', 'username', $username);
                
                // Next attempt
                $rnd_try++;
            }
            
            // Stop if we haven't found a unique name
            if ($conflict_moodle) return false;
            
            // Looks we got an OK username
            // Generate a random password
            $plain_password = sloodle_random_web_password();
            $password = hash_internal_user_password($plain_password);
            
            // Create the new user
            $this->user_data = create_user_record($username, $password);
            if (!$this->user_data) {
                $this->user_data = null;
                return false;
            }
            
            // Attempt to the first and last names of the avatar
            $this->user_data->firstname = $nameparts[0];
            if (isset($nameparts[1])) $this->user_data->lastname = $nameparts[1];
            else $this->user_data->lastname = $nameparts[0];
            
            // Attempt to update the database (we don't really care if this fails, since everything else will have worked)
            update_record('user', $this->user_data);
            
            return true;
        }
       
        /**
        * Load the avatar linked to the current user.
        * @return bool,string True if a link was loaded, false if there was no link, or string 'multi' if multiple avatars are linked
        * @access public
        */
        function load_linked_avatar()
        {
            // Make sure we have some user data
            if (empty($this->user_data)) return false;
            $this->avatar_data = null;
            
            // Fetch all avatar records which are linked to the user
            $recs = get_records('sloodle_users', 'userid', $this->user_data->id);
            if (!is_array($recs)) return false;
            if (count($recs) > 1) return 'multi';
            
            // Store the avatar data
            $this->avatar_data = $recs[0];
            return true;
        }

        /**
        * Find the VLE user linked to the current avatar.
        * @return bool True if successful, or false if no link was found
        * @access public
        */
        function load_linked_user()
        {
            // Make sure we have some avatar data
            if (empty($this->avatar_data)) return false;
            
            // Fetch the user data
            $this->user_data = get_complete_user_data('id', $this->avatar_data->userid);
            if ($this->user_data) return true;
            return false;
        }
        
        
    ///// LOGIN FUNCTIONS /////
    
        /**
        * Internally 'log-in' the current user.
        * In Moodle, this just stores all the user data in the global $USER variable.
        * This function will not perform automatic registration.
        * @return bool True if successful, or false otherwise.
        * @access public
        */
        function login()
        {
            global $USER;
            // Make sure we have some user data
            if (empty($this->user_data)) return false;
            $USER = get_complete_user_data('id', $this->user_data->id);
            return true;
        }
        
        
    ///// COURSE FUNCTIONS /////
    
        /**
        * Gets a numeric array of {@link SloodleCourse} objects for courses the user is enrolled in.
        * WARNING: this function is not very efficient, and will likely be very slow on large sites.
        * @param mixed $category Unique identifier of a category to limit the query to. Ignored if null. (Type depends on VLE; integer for Moodle)
        * @return array A numeric array of {@link SloodleCourse} objects
        * @access public
        */
        function get_enrolled_courses($category = null)
        {
            // Make sure we have user data
            if (empty($this->user_data)) return array();
            
            // Convert the category ID as appropriate
            if ($category == null || $category < 0 || !is_int($category)) $category = 0;
            
            // Modified from "get_user_capability_course()" in Moodle's "lib/accesslib.php"
            
            // Get a list of all courses on the system
            $usercourses = array();
            $courses = get_courses($category);
            // Go through each course
            foreach ($courses as $course) {
                // Check if the user can view this course
                if (has_capability('moodle/course:view', get_context_instance(CONTEXT_COURSE, $course->id), $this->user_data->id)) {
                    $sc = new SloodleCourse();
                    $sc->load($course);
                    $usercourses[] = $sc;
                }
            }
            return $usercourses;
        }
        
        /**
        * Gets a numeric array of {@link SloodleCourse} objects for courses the user is Sloodle staff.
        * This relates to the "mod/sloodle:staff" capability.
        * WARNING: this function is not very efficient, and will likely be very slow on large sites.
        * @param mixed $category Unique identifier of a category to limit the query to. Ignored if null. (Type depends on VLE; integer for Moodle)
        * @return array A numeric array of {@link SloodleCourse} objects
        * @access public
        */
        function get_staff_courses($category = null)
        {
            // Make sure we have user data
            if (empty($this->user_data)) return array();
            
            // Convert the category ID as appropriate
            if ($category == null || $category < 0 || !is_int($category)) $category = 0;
            
            // Modified from "get_user_capability_course()" in Moodle's "lib/accesslib.php"
            
            // Get a list of all courses on the system
            $usercourses = array();
            $courses = get_courses($category);
            // Go through each course
            foreach ($courses as $course) {
                // Check if the user can teach using Sloodle on this course
                if (has_capability('mod/sloodle:staff', get_context_instance(CONTEXT_COURSE, $course->id), $this->userdata->id)) {
                    $sc = new SloodleCourse();
                    $sc->load($course);
                    $usercourses[] = $sc;
                }
            }
            return $usercourses;
        }
        
        /**
        * Is the current user enrolled in the specified course?
        * NOTE: a side effect of this is that it logs-in the user
        * @param mixed $course Unique identifier of the course -- type depends on VLE (integer for Moodle)
        * @param bool True if the user is enrolled, or false if not.
        * @access public
        * @todo Update to match parameter format and handling of {@link enrol()} function.
        */
        function user_enrolled($courseid)
        {
            global $USER;
            // Attempt to log-in the user
            if (!$this->login()) return false;
            
            // NOTE: this stuff was lifted from the Moodle 1.8 "course/enrol.php" script
            
            // Create a context for this course
            if (!$context = get_context_instance(CONTEXT_COURSE, $courseid)) return false;
            // Ensure we have up-to-date capabilities for the current user
            load_all_capabilities();
            
            // Check if the user can view the course, and does not simply have guest access to it
            //return (has_capability('moodle/course:view', $context) && !has_capability('moodle/legacy:guest', $context, NULL, false));
            return has_capability('moodle/course:view', $context);
        }
        
        /**
        * Enrols the current user in the specified course
        * NOTE: a side effect of this is that it logs-in the user
        * @param object $sloodle_course A {@link SloodleCourse} object setup for the necessary course. If null, then the {@link $_session} member is queried instead.
        * @param bool True if successful (or the user was already enrolled), or false otherwise
        * @access public
        */
        function enrol($sloodle_course = null)
        {
            global $USER, $CFG;
            // Attempt to log-in the user
            if (!$this->login()) return false;
            
            // Was course data provided?
            if (empty($sloodle_course)) {
                // No - attempt to get some from the Sloodle session
                if (empty($this->_session)) return false;
                if (empty($this->_session->course)) return false;
                $sloodle_course = $this->_session->course;
            }
            
            // Make sure auto-registration is enabled for this site/course, and that the controller (if applicable) is enabled
            //if (!$sloodle_course->check_autoreg()) return false;
            //if (!empty($sloodle_course->controller) && !$sloodle_course->controller->is_enabled()) return false;
            
            // NOTE: much of this stuff was lifted from the Moodle 1.8 "course/enrol.php" script
            
            // Fetch the Moodle course data, and a course context
            $course = $sloodle_course->get_course_object();
            if (!$context = get_context_instance(CONTEXT_COURSE, $course->id)) return false;
            
            // Ensure we have up-to-date capabilities for the current user
            load_all_capabilities();
            
            // Check if the user can view the course, and does not simply have guest access to it
            // (No point trying to enrol somebody if they are already enrolled!)
            if (has_capability('moodle/course:view', $context) && !has_capability('moodle/legacy:guest', $context, NULL, false)) return true;
            // Can't enrol users on meta courses or the site course
            if ($course->metacourse || $course->id == SITEID) return false;
            
            // Is there an enrolment period in effect?
            if ($course->enrolperiod) {
                if ($roles = get_user_roles($context, $USER->id)) {
                    foreach ($roles as $role) {
                        if ($role->timestart && ($role->timestart >= time())) {
                            return false;
                        }
                    }
                }
            }
            
            // Make sure the course is enrollable
            if (!$course->enrollable ||
                    ($course->enrollable == 2 && $course->enrolstartdate > 0 && $course->enrolstartdate > time()) ||
                    ($course->enrollable == 2 && $course->enrolenddate > 0 && $course->enrolenddate <= time())
            ) {
                return false;
            }
            
            
            // Finally, after all that, enrol the user
            if (!enrol_into_course($course, $USER, 'manual')) return false;
        
            // Everything seems fine
            // Log the auto-enrolment
            add_to_log($course->id, 'sloodle', 'update', '', 'auto-enrolment');
            return true;
        }
    
    }
    

?>
