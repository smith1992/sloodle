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
    require_once(SLOODLE_DIRROOT.'/login/sl_authlib.php');
    
    
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
        // Most parameters are not required, and default to empty
        // It is necessary to specify avatar name and UUID
        //  $uuid = avatar UUID
        //  $avname = avatar name
        //  $userid = ID of Moodle user to be linked with this Sloodle user
        //  $loginposition = a position vector of format <x,y,z>, representing the allocated loginzone position
        //  $loginpositionexpires = an indication of when the allocated loginposition expires (format unknown)
        //  $loginpositionregion = the name of the region in which the loginzone is
        //  $loginsecuritytoken = a security token (random letters/numbers) used to allow secure authentication
        // Note that, if no login security token is specified, it is generated automatically
        // If successful, the new ID is stored and the user data is cached
        // Returns TRUE if successful, FALSE if not, or a string if an error occurs
        function create_sloodle_user( $uuid, $avname, $userid = 0, $loginposition = '', $loginpositionexpires = '', $loginpositionregion = '', $loginsecuritytoken = '')
        {
            // Make sure we have the UUID and name
            if (empty($uuid)) return "Cannot create Sloodle user - UUID not specified.";
            if (empty($avname)) return "Cannot create Sloodle user - avatar name not specified.";
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
            if (!empty($uuid)) $sloodle_user = get_record('sloodle_users', 'uuid', $uuid);
            // If that search failed, and we have a name, then search by it
            if (is_object($sloodle_user) && !empty($name)) $sloodle_user = get_record('sloodle_users', 'avname', $name);
            
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
        
    }
    

?>