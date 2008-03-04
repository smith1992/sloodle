<?php    
    /**
    * Sloodle LSL handling library.
    *
    * Provides the central API functionality, automatically combining several other API elements.
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
    
    /** Include the general Sloodle functionality. */
    require_once(SLOODLE_DIRROOT.'/lib/sl_generallib.php');
    /** Include the Sloodle IO library. */
    require_once(SLOODLE_DIRROOT.'/lib/sl_iolib.php');
    /** Include the Sloodle user management library. */
    require_once(SLOODLE_DIRROOT.'/lib/sl_userlib.php');
    
    
    /** 
    * A helper class to automate many API features.
    * Most interaction with the object-oriented parts of the Sloodle API should occur via this class.
    * It brings together the other classes, and automatically handles various features.
    * @package sloodle
    */
    class SloodleLSLHandler
    {
    ///// PUBLIC DATA /////
    
        /**
        * A Sloodle user object.
        * Instantiated to the {@link: SloodleUser} type in the class constructor.
        * No accessors - should be accessed directly.
        * @var SloodleUser
        * @access public
        */
        var $user = NULL;
        
        /**
        * A Sloodle request handling object.
        * Instantiated to the {@link: SloodleLSLRequest} type in the class constructor.
        * No accessors - should be accessed directly.
        * @var SloodleLSLRequest
        * @access public
        */
        var $request = NULL;
        
        /**
        * A Sloodle response handling object.
        * Instantiated to the {@link: SloodleLSLResponse} type in the class constructor.
        * No accessors - should be accessed directly.
        * @var SloodleLSLHandler
        * @access public
        */
        var $response = NULL;
        
        
    ///// FUNCTIONS /////
    
        /**
        * Class constructor.
        * Instantiates member objects.
        * @return void
        * @access public
        */
        function SloodleLSLHandler()
        {
            // Instantiate and link our components
            $this->user = new SloodleUser();
            $this->response = new SloodleLSLResponse();
            $this->request = new SloodleLSLRequest($this->response, $this->user);
        }
        
        /**
        * Performs an internal 'login' based on user identified in request.
        * Checks for avatar UUID and/or name request parameters, and checks for an associated Moodle account.
        * If found, the user is logged-in (details stored in Moodle's global $USER variable).
        * @param bool $require If true (default), the function will terminate the script with an error message if login fails. (Error message will be LSL-friendly).
        * @param bool $suppress_autoreg If true (not default), the function will always suppress auto-registration of new users. Note: this parameter will be ignored if auto-registration is disabled.
        * @return bool True if login was successful. False if login failed, but parameter $require was false.
        * @access public
        */
        function login_by_request($require = TRUE, $suppress_autoreg = FALSE)
        {
            // Make sure the request is processed and authenticated
            $this->request->process_request_data(TRUE); // Force re-processing to ensure accurate user data
            if (!$this->request->authenticate_request($require)) return FALSE;
            
            // Make sure the items of data were specified
            if ($this->request->get_avatar_uuid() == NULL && $this->request->get_avatar_name() == NULL) {
                if ($require) {
                    $this->response->set_status_code(-311);
                    $this->response->set_status_descriptor('USER_AUTH');
                    $this->response->add_data_line('User not identified in request.');
                    $this->response->render_to_output();
                    exit();
                }
                return FALSE;
            }
            
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
		$avname = $this->request->get_avatar_name();
		$avbits = array();
                // Expecting that all SL names are first last, with a space in between
                if (preg_match('/^(.*)\s(.*?)$/', $avname, $avbits)) {
                    $firstname = $avbits[1];
                    $lastname = $avbits[2];
                } else {
                    // Something wasn't quite right about the name
                    if ($require) {
                        $this->response->set_status_code(-322);
                        $this->response->set_status_descriptor('USER_AUTO_REG');
                        $this->response->add_data_line('User auto registration failed - could not extract first and last names from avatar name "'.$avname.'".');
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
                $newsloodle = $this->user->create_sloodle_user(
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
        
        /**
        * Authenticates the avatar identified in the request by the login security token.
        * Uses the avatar UUID and/or name, and the security token request to parameters to
        *  check against the details in the database.
        * @param bool $use_cache If true (not default) then the function will use the user data stored in Sloodle user cache of the {@link: $user} member object (instead of querying the database for new data).
        * @return bool True if authentication was successful, or false if not.
        * @access public
        */
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
        
        /**
        * Checks that a user is enrolled in a course, based on request data.
        * Fetches user and course identification from the standard request parameters.
        * @param bool $require If true (default) then the function will terminate with an LSL-friendly error message if user is not enrolled, or not all data was present.
        * @param bool $use_cache If true (not default) then the courses cache in the {@link: $user} member will be used, instead of querying the database for fresh data.
        * @return bool True if the user is enrolled, or false otherwise.
        * @access public
        */
        function is_user_enrolled_by_request( $require = TRUE, $use_cache = FALSE )
        {
            // Make sure the course exists
            $course = $this->request->get_course_record($require);
            // Is the user enrolled?
            if (!$this->user->is_user_in_course($course->id, $use_cache)) {
                if ($require) {
                    $this->response->set_status_code(-421);
                    $this->response->set_status_descriptor('USER_ENROL');
                    $this->response->add_data_line('User not enroled in course, and auto-enrolment is not yet implemented.');
                    $this->response->render_to_output();
                    exit();
                }
                return FALSE;
            }
            
            // Everything seems fine
            return TRUE;
        }
        
    }
    

?>
