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
        
        
//-------------------------------- TODO ----------------------------//
// The update of this file has only reached this point! More to do! -PRB
//-------------------------------- **** ----------------------------//
    
    // FIND USER FUNCTIONS //
    
        /**
        * Find the Sloodle user linked to the current Moodle user.
        * Stores the Sloodle user ID in {@link:$sloodle_user_id}.
        * @param bool $cache_data If true (default) then the Sloodle user data is automatically cached by this function.
        * @return mixed True if successful, false if no link was found, or a string if an error occurs
        * @access public
        */
        function find_linked_sloodle_user( $cache_data = TRUE )
        {
            
        }
        
        /**
        * Find the Moodle user linked to the current Sloodle user.
        * Stores the Moodle user ID in {@link:$moodle_user_id}.
        * @param bool $use_cache If true (not default) this function will use the local Sloodle user cache instead of querying the database again.
        * @param bool $cache_data If true (default) then the Moodle user data is automatically cached by this function
        * @return mixed True if successful, false if no link was found, or a string if an error occurs
        * @access public
        */
        function find_linked_moodle_user( $use_cache = FALSE, $cache_data = TRUE )
        {
            // This value will store the Moodle ID locally
            $moodle_id = 0;
            
            // Are we to check the cache?
            if ($use_cache) {
                // Do we have a cache available?
                if (!(is_object($this->sloodle_user_cache) && isset($this->sloodle_user_cache->userid))) {
                    // No - not available - report the error
                    return 'Sloodle user data not cached.';
                }
                
                // Check that the user has a link, and store it if so
                if ($this->sloodle_user_cache->userid <= 0) return FALSE;
                $moodle_id = $this->sloodle_user_cache->userid;
                
            } else {
                // We need a valid Sloodle user
                if ($this->sloodle_user_id <= 0) return "Cannot find linked Moodle user - Sloodle user ID is not valid";
                // Fetch the data from the database
                $sloodle_user_data = get_record('sloodle_users', 'id', $this->sloodle_user_id);
                // Check that the user has a link, and store it
                if ($sloodle_user_data->userid <= 0) return FALSE;
                $moodle_id = $sloodle_user_data->userid;
            }
            
            // Now retrieve the Moodle user record
            $moodle_user = get_record('user', 'id', $moodle_id);
            if ($moodle_user === FALSE) return "Failed to find linked Moodle user - Moodle user entry does not exist in database.";
            // Make sure the user has not been deleted
            if ((int)$moodle_user->deleted != 0) return "Failed to find linked Moodle user - Moodle user account has been deleted.";
            
            // User looks OK - store it
            $this->moodle_user_id = $moodle_id;
            if ($cache_data) $this->moodle_user_cache = $moodle_user;
            
            return TRUE;            
        }
        
        /**
        * Attempt to find a Sloodle user by their loginzone position
        * If successful, the user ID is stored in {@link:$sloodle_user_id} and the function returns true.
        * If no user was found for the given position, false is returned (this may occur if the LoginZone position has expired).
        * If an error occurs, then a string containing an error message is returned.
        * @param mixed $position A vector, either as a string ("<x,y,z>") or an associative array.
        * @param bool $cache_data If true (default) then the user data is cached. Otherwise it is discarded.
        * @access public
        */
        function find_sloodle_user_by_login_position( $position, $cache_data = TRUE )
        {
            // Make sure we have a string or array for the login position
            if (!((is_string($position) && !empty($position)) || (is_array($position) && count($position) == 3)) ) {
                return "Invalid login position - expected to be a string vector '&lt;x,y,z&gt;' or a vector array {x,y,z}";
            }
            
            // If it's an array, then convert it to a string
            if (is_array($position)) $position = sloodle_array_to_vector($position);
            
            // Keep searching until we find the right user
            $sloodle_user_data = FALSE;
            $stop = FALSE;
            $error = '';
            while ($stop == FALSE) {
                // Query for the user
                $sloodle_user_data = get_record('sloodle_users', 'loginposition', $position);
                // Did the search fail?
                if ($sloodle_user_data === FALSE) {
                    // Yes - stop searching
                    $stop = TRUE;
                } else {
                    // We found something - is the login position expired?
                    if (!empty($sloodle_user_data->loginpositionexpires) && (int)$sloodle_user_data->loginpositionexpires < time()) {
                        // Yes - remove the login position and move on with the search
                        $sloodle_user_data->loginposition = '';
                        $sloodle_user_data->loginpositionexpires = '';
                        $sloodle_user_data->loginpositionregion = '';
                        // Make sure the update works... otherwise we'll find the same record again!
                        if (!update_record('sloodle_users', $sloodle_user_data)) {
                            $sloodle_user_data = FALSE;
                            $stop = TRUE;
                            $error = 'Tried to remove an expired login position from database, but failed.';
                        }
                    } else {
                        // Login position is valid - stop searching
                        $stop = TRUE;
                    }
                }                
            } // End of while loop
            
            // Did we find a user? Stop if not
            if ($sloodle_user_data === FALSE) {
                // Return the error message if an error occurred
                if (is_string($error) && !empty($error)) return $error;
                else return FALSE;
            }
            
            // Note: it is tempting to remove the login position here,
            //  but we cannot be guaranteed at this point that the rest of the registration process will work.
            
            // Store the ID of the Sloodle user, and cache the data if necessary
            $this->sloodle_user_id = $sloodle_user_data->id;
            if ($cache_data) $this->sloodle_user_cache = $sloodle_user_data;
            return TRUE;
        }
        
        
    ///// LOGIN FUNCTIONS /////
    
        /**
        * Performs an internal login of the current Moodle user.
        * Stores all the user data in the global $USER variable.
        * Note: if login fails, $USER is unchanged.
        * Additionally, note that this function will not perform automatic registration.
        * @return bool True if successful, or false otherwise.
        * @access public
        */
        function login_moodle_user()
        {
            // Make sure we have a Moodle user selected
            if ($this->moodle_user_id <= 0) return FALSE;
            // Attempt to retrieve all the user data, and stop if it failed
            $newuser = get_complete_user_data('id', $this->moodle_user_id);
            if ($newuser === FALSE) return FALSE;
            // Store the user data
            global $USER;
            $USER = $newuser;
            return TRUE;
        }
        
        /**
        * Generates a new login security token for the current Sloodle user
        * @param bool $cache_data If true (default) then the new login security token will be stored in the Sloodle user cache (as well as the database).
        * @return bool True if successful, or false if an error occurs (such as there being no current Sloodle user)
        * @access public
        */
        function regenerate_login_security_token( $cache_data = TRUE )
        {
            // Do nothing if we don't have a Sloodle user
            if ($this->sloodle_user_id <= 0) return FALSE;
            // Construct a new user object to alter the existing one
            $sloodle_user_data = new stdClass();
            $sloodle_user_data->id = $this->sloodle_user_id;
            $sloodle_user_data->loginsecuritytoken = sloodle_random_security_token();
            // Attempt to update the record
            if (update_record('sloodle_users', $sloodle_user_data) === FALSE) return FALSE;
            // Store the new token
            if ($cache_data) {
                $this->sloodle_user_cache->loginsecuritytoken = $sloodle_user_data->loginsecuritytoken;
            }
            return TRUE;
        }
        
        /**
        * Checks if the current Sloodle user has a login security token
        * @param bool $use_cache If true then the cached user data will be used instead of querying the database.
        * @return bool True if the user has a login security token, or false otherwise.
        * @access public
        */
        function has_login_security_token($use_cache = FALSE)
        {
            // Are we to use the cache?
            if ($use_cache) {
                // Make sure we have a cache
                if (!is_object($this->sloodle_user_cache)) return FALSE;
                // Check if the login security token is set and non-empty
                return (isset($this->sloodle_user_cache) && !empty($this->sloodle_user_cache));
            }
            
            // Checking the database instead            
            // Do nothing if we don't have a Sloodle user ID
            if ($this->sloodle_user_id <= 0) return FALSE;
            
            // Attempt to obtain the user data and make sure we found it OK
            $sloodle_user_data = get_record('sloodle_users', 'id', $this->sloodle_user_id);
            if ($sloodle_user_data === FALSE) return FALSE;
                        
            // Check that the login security token member is set and not empty
            return (isset($sloodle_user_data->loginsecuritytoken) && !empty($sloodle_user_data->loginsecuritytoken));
        }
        
       
    ///// COURSE FUNCTIONS /////
    
        /**
        * Use the database to update the cache of courses which the current Moodle user is enrolled in.
        * Note that admins are considered by this function to be enrolled in all courses.
        * @return True if successul, or false otherwise.
        * @access public
        */
        function update_enrolled_courses_cache_from_db()
        {
            // Make sure we have a Moodle user
            if ($this->moodle_user_id <= 0) return FALSE;
            // Obtain the array of courses and make sure the query succeeded
            if (isadmin($this->moodle_user_id)) {
                // Admins technically have all courses
                $course_list = get_courses('all', 'c.sortorder ASC', 'c.id');
            } else {
                // Just get the enrolled courses
                $course_list = get_my_courses($this->moodle_user_id);
            }
            if ($course_list === FALSE) return FALSE;
            
            // Extract just the course ID's
            $this->enrolled_courses_cache = array();
            foreach ($course_list as $course) {
                $this->enrolled_courses_cache[] = (int)$course->id;
            }
            
            return TRUE;
        }
        
        /**
        * Checks if the current Moodle user is already enrolled in the specified course.
        * @param int $course_id ID number of a course to check.
        * @param bool $use_cache If true (not default) then the function uses the enrolled courses cache, instead of querying the database for new data.
        * @param bool True if the user is enrolled, or false if not.
        * @access public
        */
        function is_user_in_course($course_id, $use_cache = FALSE)
        {
            // Make sure we have a Moodle user
            if ($this->moodle_user_id <= 0) return FALSE;
            
            // If the user is an admin, then we needn't bother checking
            if (isadmin($this->moodle_user_id)) return TRUE;
            
            // Do we need to refresh the cache?
            if (!$use_cache) {
                if (!$this->update_enrolled_courses_cache_from_db()) return FALSE;
            }
            
            // Make sure we have a non-empty array of course ID's
            if (!is_array($this->enrolled_courses_cache) || count($this->enrolled_courses_cache) == 0) return FALSE;
            // Check if the course ID appears in the array
            return in_array((int)$course_id, $this->enrolled_courses_cache);
        }
    
    }
    

?>
