<?php
    // Sloodle LSL handling library
    // Provides functionality for handling LSL requests etc.
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
    
    // Make sure our other necessary libraries have been included
    require_once(SLOODLE_DIRROOT.'/lib/sl_generallib.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_iolib.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_userlib.php');
    
    
    // This class simplifies the handling of LSL interactions
    class SloodleLSLHandler
    {
    ///// PUBLIC DATA /////
    
        // A user object
        // Instantiated to a SloodleUser object on construction
        var $user = NULL;
        
        // A request object
        // Instantiated to a SloodleLSLRequest object on construction
        var $request = NULL;
        
        // A response object
        // Instantiated to a SloodleLSLResponse object on construction
        var $response = NULL;
        
        
    ///// FUNCTIONS /////
    
        // Constructor
        function SloodleLSLHandler()
        {
            // Instantiate and link our components
            $this->user = new SloodleUser();
            $this->response = new SloodleLSLResponse();
            $this->request = new SloodleLSLRequest($this->response, $this->user);
        }
        
        // Login a user from the request parameters
        // If $require is TRUE (default) then the script will terminate with an LSL error message if login fails
        // Otherwise, the function will return TRUE if successful, or FALSE if not
        // If $suppress_autoreg is TRUE, then auto registration will be explicitly suppressed
        //  (note: this will be ignored if auto registration is not enabled by the site anyway)
        function login_by_request($require = TRUE, $suppress_autoreg = FALSE)
        {
            // Make sure the request is processed and authenticated
            $this->request->process_request_data(TRUE); // Force re-processing to ensure accurate user data
            if (!$this->request->authenticate_request($require)) return FALSE;
            
            // Is the user already fully registered?
            if ($this->user->get_sloodle_user_id() > 0 && $this->user->get_moodle_user_id() > 0) {
                // Yes - attempt to login
                if (!$this->user->login_moodle_user()) {
                    // Login failed
                    if ($require) {
                        $this->response->set_status_code(-301);
                        $this->response->set_status_descriptor('USER_AUTH');
                        $this->response->add_data_line('Failed to login Moodle user for unkown reason.');
                        $this->response->render_to_output();
                        exit();
                    }
                    return FALSE;
                }
                
                // Login succeeded
                return TRUE;
            }
            
            // Is auto-registration disabled or suppressed?
            if (sloodle_is_automatic_registration_on() == FALSE || $suppress_autoreg == TRUE) {
                // We cannot do auto-registration
                if ($require) {
                    $this->response->set_status_code(-321);
                    $this->response->set_status_descriptor('USER_AUTH');
                    $this->response->add_data_line('User not registered, and auto registration was not permitted.');
                    if ($suppress_autoreg) $this->response->add_data_line('Note: auto registration was explicitly suppressed.');
                    $this->response->render_to_output();
                    exit();
                }
                return FALSE;
            }
            
            
            // Make sure we have both the avatar name and UUID
            // [Note: if the user was fully registered with Sloodle and not Moodle, and only name OR UUID was provided,
            //   then the earlier data processing will have retrieved the missing value... cunning, eh? :-)]
            if (is_null($this->request->get_avatar_uuid())) {
                if ($require) {
                    $this->response->set_status_code(-311);
                    $this->response->set_status_descriptor('USER_AUTO_REG');
                    $this->response->add_data_line('User auto registration not possible - avatar UUID was not provided or found in the database.');
                    $this->response->render_to_output();
                    exit();
                }
                return FALSE;
            }
            if (is_null($this->request->get_avatar_name())) {
                if ($require) {
                    $this->response->set_status_code(-311);
                    $this->response->set_status_descriptor('USER_AUTO_REG');
                    $this->response->add_data_line('User auto registration not possible - avatar name was not provided or found in the database.');
                    $this->response->render_to_output();
                    exit();
                }
                return FALSE;
            }
        
            // Do we need to register a Moodle account?
            if ($this->user->get_moodle_user_id() <= 0) {
                // Extract the first and last name parts
                $firstname = NULL;
                $lastname = NULL;
                // Expecting that all SL names are first last, with a space in between
                if (preg_match('/^(.*)\s(.*?)$/', $avname, $avbits)) {
                    $firstname = $avbits[1];
                    $lastname = $avbits[2];
                } else {
                    // Something wasn't quite right about the name
                    if ($require) {
                        $this->response->set_status_code(-322);
                        $this->response->set_status_descriptor('USER_AUTO_REG');
                        $this->response->add_data_line('User auto registration failed - could not extract first and last names from the avatar name.');
                        $this->response->render_to_output();
                        exit();
                    }
                    return FALSE;
                }
                
                // Register the new Moodle account
                $autoreg = $this->user->create_moodle_user($firstname, $lastname, $this->request->get_avatar_uuid().'@lsl.secondlife.com');
                // Did something go wrong?
                if ($autoreg !== TRUE) {
                    if ($require) {
                        $this->response->set_status_code(-322);
                        $this->response->set_status_descriptor('USER_AUTO_REG');
                        $this->response->add_data_line('Moodle user creation failed.');
                        if (is_string($autoreg)) $this->response->add_data_line($autoreg);
                        $this->response->render_to_output();
                        exit();
                    }
                    return FALSE;
                }
            }
            
            // Do we need to register a Sloodle entry?
            if ($this->user->get_sloodle_user_id() <= 0) {
                // Create the Sloodle entry
                $newsloodle = $this->create_sloodle_user(
                                $this->request->get_avatar_uuid(),
                                $this->request->get_avatar_name(),
                                $this->user->get_moodle_user_id()
                                ); // Other parameters can be default
                // Did it fail?
                if ($newsloodle !== TRUE) {
                    if ($require) {
                        $this->response->set_status_code(-322);
                        $this->response->set_status_descriptor('USER_AUTO_REG');
                        $this->response->add_data_line('Sloodle user creation failed.');
                        if (is_string($newsloodle)) $this->response->add_data_line($newsloodle);
                        $this->response->render_to_output();
                        exit();
                    }
                    return FALSE;
                }
            } else {
                // We already have a Sloodle user
                // Just link the users together
                $link = $this->user->link_users();
                if ($link !== TRUE) {
                    if ($require) {
                        $this->response->set_status_code(-322);
                        $this->response->set_status_descriptor('USER_AUTO_REG');
                        $this->response->add_data_line('Failed to link new Sloodle and Moodle users together.');
                        if (is_string($link)) $this->response->add_data_line($link);
                        $this->response->render_to_output();
                        exit();
                    }
                    return FALSE;
                }
            }
            
            // User has been auto-registered, so add a side-effect code to our response
            $this->response->add_side_effect(322);
            // Finally, attempt to login the new user
            return $this->user->login_moodle_user();
        }
        
        // Can the current Sloodle user in the request be confirmed by a login security token?
        // (This checks if a token was specified in the request, and compares it against the
        //  Sloodle user specified in the request).
        // If $use_cache is TRUE then the user data from the current Sloodle user cache is used for the query
        // If $use_cache is FALSE (default) then the Sloodle user cache is updated from the database prior to the query
        // Returns TRUE if so, or FALSE if not
        function confirm_by_login_security_token($use_cache = FALSE)
        {
            // Was a login security token specified in the request? Do nothing if not
            if ($this->request->get_login_security_token() == NULL) return FALSE;
            // Are we using the cache?
            if (!$use_cache) {
                // No - fetch fresh data and ensure it worked
                if ($this->update_sloodle_user_cache_from_db() !== TRUE) return FALSE;
            }
            
            // Make sure the login security token is specified in the cache
            if (!isset($this->user->sloodle_user_cache->loginsecuritytoken)) return FALSE;
            // Compare the security tokens
            return ($this->request->get_login_security_token() === $this->user->sloodle_user_cache->loginsecuritytoken);
        }
        
    }
    

?>