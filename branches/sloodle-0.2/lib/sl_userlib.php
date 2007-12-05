<?php
    // Sloodle user library
    // Provides functionality for reading and editing user data
    // Part of the Sloodle project (www.sloodle.org)
    //
    // Copyright (c) Sloodle 2007
    // Released under the GNU GPL
    //
    // Contributors:
    //  Peter R. Bloomfield - original design and implementation
    //
    
    // This library expects that the Sloodle config file has already been included
    //  (along with the Moodle libraries)
    
    require_once(SLOODLE_DIRROOT.'/lib/sl_iolib.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_generallib.php');
    
    
    // This class represents a user (Sloodle and/or Moodle)
    class SloodleUser
    {
    ///// PRIVATE DATA /////
    // Note: maintaining compatibility with PHP4 - treat this data as private
    
        // ID of the Sloodle user entry
        // Should always be an integer. 0 represents no user
        var $sloodle_user_id = 0;
        
        // ID of the Moodle user entry
        // Should always be an integer. 0 represents no user
        var $moodle_user_id = 0;
        
        
    ///// PUBLIC DATA /////
    
        // Sloodle user data cache
        // This will be updated from the database when "update_sloodle_user_cache_from_db()" is called
        // (Update is *not* automatic)
        // Data placed here can also be used to edit the database using "insert_sloodle_user_cache_to_db()" or "update_sloodle_user_cache_to_db()"
        // After construction, it will always be an object
        var $sloodle_user_cache = NULL;
        
        // Moodle user data cache
        // This will be updated from the database when "update_moodle_user_cache_from_db()" is called
        // (Update is *not* automatic)
        // Unlike the Sloodle equivalent, this cannot be used to update the database
        // After construction, it will always be an object
        var $moodle_user_cache = NULL;
        
        // Cache of courses which the Moodle user is enrolled in
        // This is an array which caches the list of courses the Moodle user is enrolled in
        // Note that update is *not* automatic, so you need to manually call "update_enrolled_courses_cache_from_db()"
        // After construction, it will always be a numeric array of integers (but may be empty)
        // Each element will be a course ID
        var $enrolled_courses_cache = NULL;
        
        
    ///// CONSTRUCTOR /////
    
        function SloodleUser()
        {
            // Make the caches empty objects
            $this->sloodle_user_cache = new stdClass();
            $this->moodle_user_cache = new stdClass();
        }
        
        
    ///// ACCESSORS /////
    
        // Get the Sloodle user ID
        // Returns an integer indicate the ID of the Sloodle user
        // A value of 0 indicates no user is available
        function get_sloodle_user_id()
        {
            return $this->sloodle_user_id;
        }
        
        // Set the Sloodle user ID
        // Parameter $id should be an integer indicating the ID of the Sloodle user
        // No return value
        function set_sloodle_user_id( $id )
        {
            $this->sloodle_user_id = $id;
        }
        
        // Get the Moodle user ID
        // Returns an integer indicate the ID of the Moodle user
        // A value of 0 indicates no user is available
        function get_moodle_user_id()
        {
            return $this->moodle_user_id;
        }
        
        // Set the Moodle user ID
        // Parameter $id should be an integer indicating the ID of the Moodle user
        // No return value
        function set_moodle_user_id( $id )
        {
            $this->moodle_user_id = $id;
        }
        
        
    ///// USER LINK FUNCTIONS /////
        
        // Are the current Sloodle and Moodle users linked?
        // If $use_cache is FALSE (default) then this function queries the database
        // If $use_cache is TRUE, then the function will attempt to use the local cache
        // WARNING: the Sloodle user cache must be updated before calling this function with $use_cache = TRUE
        // Requires that valid Sloodle and Moodle users are selected
        // Returns TRUE if the accounts are linked, FALSE if not, or a string if an error occurs (the string containing the error message)
        function users_linked($use_cache = FALSE)
        {
            // If we are missing either the Sloodle or Moodle account ID's, then they cannot be linked
            if ($this->sloodle_user_id <= 0 || $this->moodle_user_id <= 0) return FALSE;
        
            // Are we to check the cache?
            if ($use_cache) {
                // Do we have a cache available?
                if (!(is_object($this->sloodle_user_cache) && isset($this->sloodle_user_cache->userid))) {
                    // No - not available - report the errorexit();
                    return 'Sloodle user data not cached.';
                }
                
                // Check the cached userid value
                return ((int)$this->sloodle_user_cache->userid == $this->moodle_user_id);
                
            }
            
            // We're checking the database instead
            $sloodle_user_data = get_record('sloodle_users', 'id', $this->sloodle_user_id);
            return ((int)$sloodle_user_data->userid == $this->moodle_user_id);
        }
    
        // Link the accounts together
        // This will attempt to enter the Moodle ID into the 'userid' field of the Sloodle user data on the database
        // Requires that valid Sloodle and Moodle users are currently selected
        // Returns TRUE if successful, FALSE on failure, or a string if a local error occurs
        function link_users()
        {
            // We cannot link an empty Sloodle user
            if ($this->sloodle_user_id <= 0) return "Failed to link users - invalid Sloodle user ID";
            // We cannot link to an invalid Moodle user
            if ($this->moodle_user_id <= 0) return "Failed to link users - invalid Moodle user ID";
            
            // Prepare an update object
            $sloodle_user_data = new stdClass();
            $sloodle_user_data->id = $this->sloodle_user_id;
            $sloodle_user_data->userid = $this->moodle_user_id;
            
            // Attempt the update
            return update_record('sloodle_users', $sloodle_user_data);
        }
        
        // Unlink the Sloodle account
        // Requires that a valid Sloodle user is currently selected
        // Returns TRUE if successful, FALSE on failure, or a string if an error occurs
        function unlink_sloodle_user()
        {
            // We cannot link an empty Sloodle user
            if ($this->sloodle_user_id <= 0) return "Failed to unlink Sloodle user - invalid Sloodle user ID";
            
            // Prepare an update object
            $sloodle_user_data = new stdClass();
            $sloodle_user_data->id = $this->sloodle_user_id;
            $sloodle_user_data->userid = 0;
            
            // Attempt the update
            return update_record('sloodle_users', $sloodle_user_data);
        }
        
        
    ///// DATABASE FUNCTIONS /////
    
        // Delete current Sloodle user
        // Requires that a valid Sloodle user is currently selected
        // Returns TRUE if successful, FALSE on failure, or a string if an error occurs
        function delete_sloodle_user()
        {
            // We cannot delete an empty Sloodle user
            if ($this->sloodle_user_id <= 0) return "Failed to delete user - invalid Sloodle user ID";
            
            // Attempt to delete the record from the database
            return delete_records('sloodle_users', 'id', $this->sloodle_user_id);
        }
        
        // Update the Sloodle user cache from the database
        // Requires that a valid Sloodle user is already selected
        // Returns TRUE if successful, FALSE if the query fails (e.g. no matching user found), or a string if an error occurs
        function update_sloodle_user_cache_from_db()
        {
            // We need a valid Sloodle user ID
            if ($this->sloodle_user_id <= 0) return "Failed to update Sloodle user cache - invalid Sloodle user ID";
            // Make and store the query
            $data = get_record('sloodle_users', 'id', $this->sloodle_user_id);
            if ($data === FALSE) return FALSE;
            $this->sloodle_user_cache = $data;
            return TRUE;
        }
        
        // Update the Moodle user cache from the database
        // Requires that a valid Moodle user is already selected
        // Returns TRUE if successful, FALSE if the query fails (e.g. no matching user found), or a string if an error occurs
        function update_moodle_user_cache_from_db()
        {
            // We need a valid Moodle user ID
            if ($this->moodle_user_id <= 0) return "Failed to update Moodle user cache - invalid Moodle user ID";
            // Make and store the query
            $data = get_record('user', 'id', $this->moodle_user_id);
            if ($data === FALSE) return FALSE;
            $this->moodle_user_cache = $data;
            return TRUE;
        }
        
        // Use the Sloodle user cache to update an entry in the Sloodle users database table
        // Uses the object entirely as-is, so the 'id' field must be accurate
        // Any fields which are unset will be ignored
        // Returns TRUE if successful, FALSE if the query fails, or a string if an error is seen by this function
        function update_sloodle_user_cache_to_db()
        {
            // Make sure we have a cache object
            if (!is_object($this->sloodle_user_cache)) {
                return "Could not update Sloodle user details in database - cache does not contain an object.";
            }
            // Make sure the ID field is set and is valid
            if (!isset($this->sloodle_user_cache) || (int)$this->sloodle_user_cache->id <= 0) {
                return "Could not update Sloodle user details in database - cache does not contain valid ID field (should be a positive non-zero integer).";
            }
            
            // Make the update
            return update_record('sloodle_users', $this->sloodle_user_cache);
        }
        
        // Use the Sloodle user cache to insert a new entry in the Sloodle user database table
        // Uses the object entirely as-is, except that the 'id' field is ignored
        // Stores the id of the new entry in $this->sloodle_user_id
        // Returns TRUE if successful, FALSE if the query fails
        function insert_sloodle_user_cache_to_db()
        {
            // Make sure we have a cache object
            if (!is_object($this->sloodle_user_cache)) {
                return "Could not update Sloodle user details in database - cache does not contain an object.";
            }
            
            // Attempt the record insertion
            $id = insert_record('sloodle_users', $this->sloodle_user_cache);
            // Check if it was successful, and store the ID if so
            if ($id == FALSE) return FALSE;
            $this->sloodle_user_id = $id;
            return TRUE;
        }
        
        // Create a new Sloodle user
        // All parameters are optional. To enable loginzone authentication, you *must* specify position and expiry time
        //  $uuid = avatar UUID
        //  $avname = avatar name
        //  $userid = ID of Moodle user to be linked with this Sloodle user
        //  $loginposition = a position vector of format <x,y,z>, representing the allocated loginzone position
        //  $loginpositionexpires = an indication of when the allocated loginposition expires (format unknown)
        //  $loginpositionregion = the name of the region in which the loginzone is (NOT IN USE YET!)
        //  $loginsecuritytoken = a security token (random letters/numbers) used to allow secure authentication
        // Note that, if no login security token is specified, it is generated automatically
        // If successful, the new ID is stored and the user data is cached
        // Returns TRUE if successful, FALSE if not, or a string if an error occurs
        function create_sloodle_user( $uuid = '', $avname = '', $userid = 0, $loginposition = '', $loginpositionexpires = '', $loginpositionregion = '', $loginsecuritytoken = '')
        {
            // If necessary, generate a login security token
            if (empty($loginsecuritytoken)) $loginsecuritytoken = sloodle_random_security_token();
            
            // Construct the user data object
            $sloodle_user_data = new stdClass();
            $sloodle_user_data->uuid = $uuid;
            $sloodle_user_data->avname = $avname;
            $sloodle_user_data->userid = $userid;
            $sloodle_user_data->loginposition = $loginposition;
            $sloodle_user_data->loginpositionexpires = $loginpositionexpires;
            $sloodle_user_data->loginpositionregion = $loginpositionregion;
            $sloodle_user_data->loginsecuritytoken = $loginsecuritytoken;
            
            // Add the data to the database
            $id = insert_record('sloodle_users', $sloodle_user_data);
            if ($id === FALSE) return FALSE;
            // Store the data
            $this->sloodle_user_id = $id;
            $this->sloodle_user_cache = $sloodle_user_data;
            
            return TRUE;
        }
        
        // Clear the login position from the currently selected Sloodle user
        // This function will user the user identified by $this->sloodle_user_id,
        //  retrieve all the user data, remove the login position values, and update the database
        // Returns TRUE if successful, FALSE if the database query fails, or a string if an error occurs
        function delete_login_position()
        {
            // We need a valid Sloodle user ID
            if ($this->sloodle_user_id <= 0) return "Failed to update Sloodle user cache - invalid Sloodle user ID";
            
            // Get the user data
            $sloodle_user_data = get_record('sloodle_users', 'id', $this->sloodle_user_id);
            if ($sloodle_user_data === FALSE) return FALSE;
            // Remove the login position values
            $sloodle_user_data->loginposition = '';
            $sloodle_user_data->loginpositionexpires = '';
            $sloodle_user_data->loginpositionregion = '';
            // Update the database
            return update_record('sloodler_users', $sloodle_user_data);
        }
        
        
        // Create a Moodle user account with the specified first name, last name and email address
        // Stores the new user ID and cache
        // Returns TRUE if successful, or a string if something goes wrong
        function create_moodle_user( $firstname, $lastname, $email )
        {
            global $CFG;
            // Include the Moodle authentication library
            require_once("{$CFG->dirroot}/auth/{$CFG->auth}/lib.php");
    
            // Make sure we have all necessary parameters
            if (!isset($firstname) || empty($firstname)) return "Cannot register Moodle user - first name not specified.";
            if (!isset($lastname) || empty($lastname)) return "Cannot register Moodle user - last name not specified.";
            if (!isset($email) || empty($email)) return "Cannot register Moodle user - email address not specified.";
            
            // Construct a base username - we will try to use this, but adapt it in the event of a conflict
            // It will start out as just the first and last names concatenated
            $moodlebaseusername = trim(moodle_strtolower($firstname.$lastname));
            
            // Construct a new Moodle user object
            $moodleuser = new stdClass();
            // Generate and store the required items of user-data
            $moodleuser->firstname = strip_tags($firstname);
            $moodleuser->lastname = strip_tags($lastname);
            $moodleuser->email = strip_tags($email);
            $moodleuser->username = $moodlebaseusername;
            $moodleuser->password = sloodle_random_web_password();
            $plainpass = $moodleuser->password;
            $moodleuser->password = hash_internal_user_password($plainpass);
            $moodleuser->confirmed = 0;            
            $moodleuser->lang = current_language();
            $moodleuser->firstaccess = time();
            $moodleuser->secret = random_string(15);
            $moodleuser->auth = $CFG->auth;
            
            // Do we need to check for username conflicts in the authentication module?
            $check_auth = empty($CFG->auth_user_create) == FALSE && function_exists('auth_user_exists') && function_exists('auth_user_create');
            // We want to find a username that does conflict with either the authentication module, or with the Moodle database        
            // Try the basic username
            $try_username = $moodlebaseusername;
            $conflict_auth = FALSE;
            if ($check_auth) $conflict_auth = auth_user_exists($try_username);
            $conflict_moodle = record_exists('user', 'username', $try_username);
            
            // If that didn't work, then try a few random variants (just a number added to the end of the name)
            $MAX_RANDOM_TRIES = 3;
            $rnd_try = 0;
            while ($rnd_try < $MAX_RANDOM_TRIES && $conflict_moodle && (($check_auth && $conflict_auth) || !check_auth)) {
                // Pick a random 3 digit number
                $rnd_num = mt_rand(100, 999);
                if ($rnd_num == 666) $rnd_num++; // Some users may object to this number
                // Construct a new username to try
                $try_username = $moodlebaseusername . (string)$rnd_num;
                // Check for conflicts
                if ($check_auth) $conflict_auth = auth_user_exists($try_username);
                $conflict_moodle = record_exists('user', 'username', $try_username);
                
                // Next attempt
                $rnd_try++;
            }
            
            // Stop if we haven't found a unique name
            if ($conflict_moodle || $conflict_auth) return "Cannot register Moodle user - failed to find unique username.";
            // Store the username
            $moodleuser->username = $try_username;
            
            // Attempt to add the user to the authentication module
            if ($check_auth) {
                // Attempt to add the user to the authentication module
                if (!auth_user_create($moodleuser, $plainpass)) return "Cannot register Moodle user - failed to add user to Moodle authentication module";
            }
    
            // Attempt to add the user data to the Moodle database
            $moodleuser->id = insert_record('user', $moodleuser, TRUE);
            // User did not exist - create a new one
            if ($moodleuser->id === FALSE) return "Cannot register Moodle user - failed to add user to Moodle database";
            
            // Store the Moodle user details
            $this->moodle_user_id = $moodleuser->id;
            $this->moodle_user_cache = get_record('user', 'id', $moodleuser->id);
            
            return TRUE;
        }
        
    
    ///// FIND USER FUNCTIONS /////
    
        // Attempt to find a Sloodle user by their UUID and/or avatar name
        // The UUID takes precedence, but the name can be used as a fall-back
        // If $cache_data is TRUE (default) then the user data is cached
        // If $cache_data if FALSE then the user data is discarded
        // If successful, the user ID is stored in $this->sloodle_user_id and the function returns TRUE
        // If no user was found for the given details, FALSE is returned
        // If an error occurs, then a string containing an error message is returned
        function find_sloodle_user( $uuid, $name, $cache_data = TRUE )
        {
            // Make sure we at least have a UUID or a name
            if (empty($uuid) && empty($name)) {
                return "Failed to find Sloodle user - neither a UUID nor a name was provided.";
            }
            
            // If we have a UUID, then search by it
            $sloodle_user = NULL;
            if (!empty($uuid)) $sloodle_user = get_record('sloodle_users', 'uuid', $uuid);
            // If that search failed, and we have a name, then search by it
            if (is_null($sloodle_user) && !empty($name)) $sloodle_user = get_record('sloodle_users', 'avname', $name);
            
            // Did we find a user?
            if (is_object($sloodle_user)) {
                // Yes - store the ID/data and finish
                $this->sloodle_user_id = $sloodle_user->id;
                if ($cache_data) $this->sloodle_user_cache = $sloodle_user;
                return TRUE;
            }
            
            return FALSE;
        }
        
        // Find the Moodle user linked to the current Sloodle user
        // Stores the Moodle user ID in $this->moodle_user_id
        // If $use_cache is FALSE (default) then this function queries the database
        // If $use_cache is TRUE, then the function will attempt to use the local cache
        // If $cache_data is TRUE (default) then the Moodle user data is automatically cached by this function
        // WARNING: the Sloodle user cache must be updated before calling this function with $use_cache = TRUE
        // Returns TRUE if successful, FALSE if no link was found, or a string if an error occurs
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
        
        // Attempt to find a Sloodle user by their loginzone position
        // If $cache_data is TRUE (default) then the user data is cached
        // If $cache_data if FALSE then the user data is discarded
        // If $position is a string then it is expected to be a vector of the format "<x,y,z>"
        // If $position is an array then it is expected to contain 3 elements - one each for x, y and z components of a vector
        // If successful, the user ID is stored in $this->sloodle_user_id and the function returns TRUE
        // If no user was found for the given position, FALSE is returned (this may occur if the loginzone position has expired)
        // If an error occurs, then a string containing an error message is returned
        // NOTE: at some point, the "region" functionality should be added
        function find_sloodle_user_by_login_position( $position, $cache_data = TRUE )
        {
            // Make sure we have a string or array for the login position
            if (!((is_string($position) && !empty($position)) || (is_array($position) && count($position) == 3)) ) {
                return "Invalid login position - expected to be a string vector '<x,y,z>' or a vector array {x,y,z}";
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
    
        // Attempt to login the current Moodle user, if there is one
        // Stores all the user data in the global $USER variable
        // Returns TRUE if successful, or FALSE if not
        // Note: if login fails, $USER is unchanged
        // Note: this function will not perform automatic registration
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
        
        
        // Generate a new login security token for the current Sloodle user
        // Requires that a Sloodle user is currently selected
        // If $cache_data is TRUE (default) then the new login security token will be stored in the Sloodle user cache
        // Returns TRUE if successful, or FALSE if an error occurs (such as there being no current Sloodle user)
        function regenerate_login_security_token( $cache_data = TRUE )
        {
            // Do nothing if we don't have a Sloodle user
            if ($this->sloodle_user_id <= 0) return FALSE;
            // Construct a new user object to alter the existing one
            $sloodle_user_data = new stdClass();
            $sloodle_user_data->id = $this->sloodle_user->id;
            $sloodle_user_data->loginsecuritytoken = sloodle_random_security_token();
            // Attempt to update the record
            if (update_record('sloodle_users', $sloodle_user_data) === FALSE) return FALSE;
            // Store the new token
            if ($cache_data) {
                $this->sloodle_user_cache->loginsecuritytoken = $sloodle_user_data->loginsecuritytoken;
            }
            return TRUE;
        }
        
        // Does the current Sloodle user have a login security token?
        // If $use_cache is FALSE (default) then a new request for Sloodle user data is made to the database
        // If $use_cache is TRUE then the cached user data will be queried instead
        // Returns TRUE if so, or FALSE if not (or if there is no current Sloodle user)
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
    
        // Use the database to update the cache of courses which the current Moodle user is enrolled in
        // Returns TRUE if successful, or FALSE if an error occurs
        // Requires that a Moodle user is selected, although they need not be logged into Moodle
        function update_enrolled_courses_cache_from_db()
        {
            // Make sure we have a Moodle user
            if ($this->moodle_user_id <= 0) return FALSE;
            // Obtain the array of courses and make sure the query succeeded
            $course_list = get_my_courses($this->moodle_user_id);
            if ($course_list === FALSE) return FALSE;
            
            // Extract just the course ID's
            $this->enrolled_courses_cache = array();
            foreach ($course_list as $course) {
                $this->enrolled_courses_cache[] = (int)$course_list->id;
            }
            
            return TRUE;
        }
        
        // Is the current Moodle user already enrolled in the specified course?
        // Parameter $course_id is the ID number of a course to check
        // Returns TRUE if so, or FALSE if not (or if an error occurs)
        // If $use_cache is TRUE then the currently cached list of enrolled courses is used
        // If $use_cache is FALSE (default) then the function refreshes the cache first, then makes the query
        function is_user_in_course($course_id, $use_cache = FALSE)
        {
            // Make sure we have a Moodle user
            if ($this->moodle_user_id <= 0) return FALSE;
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