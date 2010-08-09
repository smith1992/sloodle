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
    /** Include the user object data structure */
    require_once(SLOODLE_DIRROOT.'/lib/user_object.php');
    
    
    /** Simple cross-platform role definitions for SLOODLE.
    * Guests are not enrolled and cannot usually view any data.
    * This might be used when you want to see if a user can access a resource without being logged-in. */
    define('SLOODLE_ROLE_GUEST', 10);
    /** Simple cross-platform role definitions for SLOODLE.
    * Students are enrolled in courses and can typically view data, with limited interaction. */
    define('SLOODLE_ROLE_STUDENT', 20);
    /** Simple cross-platform role definitions for SLOODLE.
    * Teachers can view and modify content. */
    define('SLOODLE_ROLE_TEACHER', 30);
    /** Simple cross-platform role definitions for SLOODLE.
    * Admins can basically do anything. */
    define('SLOODLE_ROLE_ADMIN', 40);
    
    
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
        function SloodleUser(&$_session)
        {
            if (!is_null($_session)) $this->_session = &$_session;
        }
        
        
    // ACCESSORS //
    
        /**
        * Gets the unique ID of the avatar.
        * @return mixed Type depends on VLE. (Integer on Moodle). Returns null if there is no avatar.
        * @access public
        */
        function get_avatar_id()
        {
            if (!isset($this->avatar_data->id)) return null;
            return $this->avatar_data->id;
        }
        
        /**
        * Gets the unique ID of the VLE user.
        * @return mixed Type depends on VLE. (Integer on Moodle). Returns null if there is no user
        * @access public
        */
        function get_user_id()
        {
            if (!isset($this->user_data->id)) return null;
            return $this->user_data->id;
        }
        
        /**
        * Determines whether or not an avatar is loaded.
        * @return bool
        */
        function is_avatar_loaded()
        {
            return isset($this->avatar_data);
        }
        
        /**
        * Determines whether or not a VLE user is loaded.
        * @return bool
        */
        function is_user_loaded()
        {
            return isset($this->user_data);
        }
        
        
        /**
        * Gets the UUID of the avatar
        * @return string
        */
        function get_avatar_uuid()
        {
            return $this->avatar_data->uuid;
        }
        
        /**
        * Sets the UUID of the avatar
        * @param string $uuid The new UUID
        * @return void
        */
        function set_avatar_uuid($uuid)
        {
            $this->avatar_data->uuid = $uuid;
        }
        
        /**
        * Gets the name of the avatar
        * @return string
        */
        function get_avatar_name()
        {
            return $this->avatar_data->avname;
        }
        
        /**
        * Sets the name of the avatar
        * @param string $avname The new avatar name
        * @return void
        */
        function set_avatar_name($avname)
        {
            $this->avatar_data->avname = $avname;
        }
        
        /**
        * Gets the user's username
        * @return string
        */
        function get_username()
        {
            return $this->user_data->username;
        }
        
        /**
        * Gets the first name of the user
        * @return string
        */
        function get_user_firstname()
        {
            return $this->user_data->firstname;
        }
        
        /**
        * Gets the last name of the user
        * @return string
        */
        function get_user_lastname()
        {
            return $this->user_data->lastname;
        }
        
        /**
        * Gets the timestamp of whenever the avatar was last active
        * @return int
        */
        function get_avatar_last_active()
        {
            return (int)$this->avatar_data->lastactive;
        }
        
        /**
        * Sets the timestamp of when the user was last active
        * @param int $timestamp A UNIX timestamp, or null to use the current time
        * @return void
        */
        function set_avatar_last_active($timestamp = null)
        {
            if ($timestamp == null) $timestamp = time();
            $this->avatar_data->lastactive = $timestamp;
        }
        
        /**
        * Gets the user's email address.
        * @return string|null The user's email address, or null if none is specified of if email is disabled.
        */
        function get_user_email()
        {
            if (isset($this->user_data->email) && !empty($this->user_data->emailstop))
                return $this->user_data->email;
            return null;
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
        * Finds an avatar with the given UUID and name.
        * @param string $uuid The UUID of the avatar.
        * @param string $avname The name of the avatar.
        * @return bool True if successful, or false otherwise
        * @access public
        */
        function load_avatar($uuid, $avname)
        {
            $this->avatar_data = get_record('sloodle_users', 'uuid', $uuid, 'avname', $avname);
            if (empty($this->avatar_data))
            {
                $this->avatar_data = null;
                return false;
            }
            return true;
        }
        
        /**
        * Load the specified user from the database.
        * Use the 'complete' load if (and ONLY if) you are certain you need lots of capablities and permissions for this user.
        * @param mixed $id The unique identifier for the VLE user. (Type depends on VLE; integer for Moodle)
        * @param bool $complete Optional. If false (default) then shallow user data will be loaded. If true then a complete load will be done, which is quite DB-intensive, so use it with caution!
        * @return bool True if successful, or false on failure
        * @access public
        */
        function load_user($id, $complete = false)
        {
            // Make sure the ID is valid
            $id = (int)$id;
            if ($id <= 0) return false;
            
            // Are we doing a complete load or a shallow one?
            if ($complete)
            {
                // The Moodle "get_complete_user_data" function loads lots of extra stuff which is usually not necessary for SLOODLE functionality.
                $this->user_data = get_complete_user_data('id', $id);
            } else {
                $this->user_data = get_record('user', 'id', $id);
            }
            
            // Was the load successful?
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
        * Adds a new avatar to the database, and link it to the specified user.
        * If successful, it deletes any matching avatar details from pending users list.
        * @param mixed $userid Site-wide unique ID of a user (type depends on VLE; integer for Moodle)
        * @param string $uuid UUID of the avatar
        * @param string $avname Name of the avatar
        * @return bool True if successful, or false if not.
        * @access public
        */
        function add_linked_avatar($userid, $uuid, $avname)
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
            
            // Delete any pending avatars with the same details
            delete_records('sloodle_pending_avatars', 'uuid', $uuid, 'avname', $avname);
            
            return true;
        }
        
        /**
        * Adds a new unlinked avatar to the database (the entry is pending linking)
        * @param string $uuid UUID of the avatar
        * @param string $avname Name of the avatar
        * @param int $timestamp The timestamp at which to mark the update (or null to use the current timestamp). Entries expire after a certain period.
        * @return object|bool Returns the database object if successul, or false if not.
        * @access public
        */
        function add_pending_avatar($uuid, $avname, $timestamp = null)
        {
            // Setup the timestamp
            if ($timestamp == null) $timestamp = time();
            
            // Setup our object
            $pending_avatar = new stdClass();
            $pending_avatar->id = 0;
            $pending_avatar->uuid = $uuid;
            $pending_avatar->avname = $avname;
            $pending_avatar->lst = sloodle_random_security_token();
            $pending_avatar->timeupdated = $timestamp;
            
            // Add the data to the database
            $pending_avatar->id = insert_record('sloodle_pending_avatars', $pending_avatar);
            if (!$pending_avatar->id) {
                return false;
            }
            
            return $pending_avatar;
        }
        
        
        /**
        * Auto-register a new user account for the current avatar.
        * NOTE: this does NOT respect ANYTHING but the most basic Moodle accounts.
        * Use at your own risk!
        * @return string|bool The new password (plaintext) if successful, or false if not
        * @access public
        */
        function autoregister_avatar_user()
        {
            global $CFG;
        
            // Make sure we have avatar data, and reset the user data
            if (empty($this->avatar_data)) return false;
            $this->user_data = null;
            
            // Construct a basic username
            $nameparts = explode(' ', $this->avatar_data->avname);
            $baseusername = strip_tags(stripslashes(implode('', $nameparts)));
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
            
            // Looks like we got an OK username
            // Generate a random password
            $plain_password = sloodle_random_web_password();
            
            // Create the new user
            $this->user_data = create_user_record($username, $plain_password);
            if (!$this->user_data) {
                $this->user_data = null;
                return false;
            }
            
            // Get the complete user data again, so that we have the password this time
            //$this->user_data = get_complete_user_data('id', $this->user_data->id); // this should not be necessary
            $this->user_data = get_record('user', 'id', $this->user_data->id); // this should be sufficient
            
            // Attempt to use the first and last names of the avatar
            $this->user_data->firstname = $nameparts[0];
            if (isset($nameparts[1])) $this->user_data->lastname = $nameparts[1];
            else $this->user_data->lastname = $nameparts[0];
            // Prevent emails from being sent to this user
            $this->user_data->emailstop = 1;
            
            // Attempt to update the database (we don't really care if this fails, since everything else will have worked)
            update_record('user', $this->user_data);
            
            // Now link the avatar to this account
            $this->avatar_data->userid = $this->user_data->id;
            update_record('sloodle_users', $this->avatar_data);
            
            return $plain_password;
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
            reset($recs);
            $this->avatar_data = current($recs);
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
            //$this->user_data = get_complete_user_data('id', $this->avatar_data->userid); // this should not be necessary
            $this->user_data = get_record('user', 'id', $this->avatar_data->userid); // this should be sufficient
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
            $USER = get_complete_user_data('id', $this->user_data->id); // We really need to determine if this is actually necessary. Hopefully it's not.
            return true;
        }
        
        
    ///// COURSE FUNCTIONS /////
    
        /**
        * Gets a numeric array of Moodle course record objects for courses the user is enrolled in.
        * WARNING: this function is not very efficient, and will likely be very slow on large sites.
        * @param mixed $category Unique identifier of a category to limit the query to. Ignored if null. (Type depends on VLE; integer for Moodle)
        * @return array A numeric array of database objects
        * @access public
        */
        function get_enrolled_courses($category = null)
        {
            // Make sure we have user data
            if (empty($this->user_data)) return array();
            // If it is the guess user, then they are not enrolled at all
            if (isguestuser($this->user_data->id)) return array();            
            
            // Convert the category ID as appropriate
            if ($category == null || $category < 0 || !is_int($category)) $category = 0;
            
            // Modified from "get_user_capability_course()" in Moodle's "lib/accesslib.php"
            
            // Get a list of all courses on the system
            $usercourses = array();
            $courses = get_courses($category);
            // Go through each course
            foreach ($courses as $course)
            {
                // Check if the user can view this course and is not a guest in it.
                // (Note: the site course is always available to all users.)
                $course_context = get_context_instance(CONTEXT_COURSE, $course->id);
                if ($course->id == SITEID || (has_capability('moodle/course:view', $course_context, $this->user_data->id) && !has_capability('moodle/legacy:guest', $course_context, $this->user_data->id, false)))
                {
                    $usercourses[] = $course;
                }
            }
            return $usercourses;
        }
        
        /**
        * Gets a numeric array of Moodle course record objects for courses in which this user is SLOODLE staff.
        * This relates to the "mod/sloodle:staff" capability.
        * WARNING: this function is not very efficient, and will likely be very slow on large sites.
        * @param mixed $category Unique identifier of a category to limit the query to. Ignored if null. (Type depends on VLE; integer for Moodle)
        * @return array A numeric array of database objects
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
            foreach ($courses as $course)
            {
                // Check if the user can teach using Sloodle on this course
                if (has_capability('mod/sloodle:staff', get_context_instance(CONTEXT_COURSE, $course->id), $this->user_data->id))
                {
                    $usercourses[] = $courses;
                }
            }
            return $usercourses;
        }

        /**
        * Is the current user enrolled in the specified course?
        * @param mixed $course Unique identifier of the course -- type depends on VLE (integer for Moodle)
        * @param bool True if the user is enrolled, or false if not.
        * @access public
        */
        function is_enrolled($courseid)
        {
            // Create a context for this course
            if (!$context = get_context_instance(CONTEXT_COURSE, $courseid)) return false;
            
            // Check if the user can view the course, and does not simply have guest access to it
            // Allow the site course
            return ($courseid == SITEID || (has_capability('moodle/course:view', $context, $this->user_data->id) && !has_capability('moodle/legacy:guest', $context, $this->user_data->id, false)));
        }
             
        
        /**
        * Is the current user Sloodle staff in the specified course?
        * @param mixed $course Unique identifier of the course -- type depends on VLE (integer for Moodle)
        * @param bool True if the user is staff, or false if not.
        * @access public
        */
        function is_staff($courseid)
        {            
            // Create a context for this course
            if (!$context = get_context_instance(CONTEXT_COURSE, $courseid)) return false;
            // Check if the user can view the course, does not simply have guest access to it, *and* is staff
            return (has_capability('moodle/course:view', $context, $this->user_data->id) && !has_capability('moodle/legacy:guest', $context, $this->user_data->id, false) && has_capability('mod/sloodle:staff', $context, $this->user_data->id));
        }
        
    }
    

?>