<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the primary API class, SloodleSession.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    */
    
    /** General functionality. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    /** Request and response functionality. */
    require_once(SLOODLE_LIBROOT.'/io.php');
    /** User functionality. */
    require_once(SLOODLE_LIBROOT.'/user.php');
    /** Course functionality. */
    require_once(SLOODLE_LIBROOT.'/course.php');
    /** Sloodle Controller functionality. */
    require_once(SLOODLE_LIBROOT.'/controller.php');
    /** Module functionality. */
    require_once(SLOODLE_LIBROOT.'/modules.php');
    /** Plugin management. */
    require_once(SLOODLE_LIBROOT.'/plugins.php');
    
    
    /**
    * The primary API class, which manages all other parts.
    * @package sloodle
    */
    class SloodleSession
    {
    // DATA //
        
        /**
        * Incoming HTTP request.
        * @var SloodleRequest
        * @access public
        */
        var $request = null;
    
        /**
        * Outgoing response - can be rendered to HTTP or as a string.
        * @var SloodleResponse
        * @access public
        */
        var $response = null;
        
        /**
        * Current owner information - this is the owner of the object that initiated the current HTTP request.
        * $var SloodleUser
        * @access public
        */
        var $owner = null;
        
        /**
        * Current user information - this is the user identified by parameters sloodleavname and sloodleuuid.
        * @var SloodleUser
        * @access public
        */
        var $user = null;
        
        /**
        * A course object direct from the Moodle database.
        * @var object
        * @access public
        */
        var $courseobj = null;
        
        /**
        * The Sloodle module this session relates to, if any.
        * Note: this may be the base Sloodle module class, or a derivative.
        * @var SloodleModule
        * @access public
        */
        var $module = null;

        /**
        * A plugin manager to help give access to plugins for various features.
        * @var SloodlePluginManager
        * @access public
        */
        var $plugins = null;
        
        
    // FUNCTIONS //
    
        /**
        * Constructor - initialises members
        * @param bool $process If true (default) then basic request data will be processed immediately. Otherwise, it can be done manually by calling $request->process_request_data()
        */
        function SloodleSession($process = true)
        {
            // Construct the different parts of the session, as far as possible
            $this->user = new SloodleUser($this);
            $this->owner = new SloodleUser($this);
            $this->response = new SloodleResponse();
            $this->request = new SloodleRequest($this);
            $this->plugins = new SloodlePluginManager($this);
            
            // Process the basic request data
            if ($process) $this->request->process_request_data();
        }
        
        
        /**
        * Constructs and loads the appropriate module part of the session.
        * @param string $type The expected type of module - function fails if type is not correctly matched
        * @param bool $db If true then the system will also try to load appropriate data from the database, as specified in the module ID request parameter
        * @param bool $require If true, then if something goes wrong, the script will be terminated with an error message
        * @return bool True if successful, or false otherwise. (Note, if parameter $require was true, then the script will terminate before this function returns if something goes wrong)
        */
        function load_module($type, $db, $require = true)
        {
            // If the database loading is requested, then make sure we have a parameter to load with
            $db_id = null;
            if ($db)
            {
                $db_id = $this->request->get_module_id($require);
                if ($db_id == null) return false;
            }

            // Construct the module
            $this->module = sloodle_load_module($type, $this, $db_id);
            if (!$this->module)
            {
                if ($require)
                {
                    $this->response->quick_output(-601, 'MODULE', 'Failed to construct module object', false);
                    exit();
                }
                return false;
            }
        }
        
        
        /**
        * Verifies that the incoming request is from a authorised source.
        * This checks for the authentication token in the HTTP headers.
        *
        * @param bool $require If true, the function will NOT return on authentication failure. Rather, it will terminate the script with an error message.
        * @return bool true if successful in authenticating the request, or false if not.
        */
        function authenticate_request( $require = true )
        {
            // Make sure that the request data has been processed
            if (!$this->request->is_request_data_processed())
            {
                $this->request->process_request_data();
            }
            
            // Compare the provide authentication tokens with the one stored in the database
            $token = $this->request->get_auth_token(false);
            if ($token === null)
            {
                if ($require) $this->response->quick_output(-4440001, 'SFS', 'Authentication token not provided in request.');
                return false;
            }
            $storedToken = sloodle_get_stored_auth_token();
            if (!$storedToken)
            {
                if ($require) $this->response->quick_output(-4440003, 'SFS', 'Failed to retrieve correct authentication token from database');
                return false;
            }
            if ($storedToken != $token)
            {
                if ($require) $this->response->quick_output(-4440002, 'SFS', 'Provided authentication token was invalid');
                return false;
            }
            
            return true;
        }
        
        /**
        * Verifies that the incoming request is from an object owned by a teacher or admin on the current course or module.
        
        */
        //TODO
        
        
        /**
        * Validates the user account and enrolment (ensures there is an avatar linked to a VLE account, and that the VLE account is enrolled in the current course).
        * Attempts auto-registration/enrolment if that is allowed and required, and logs-in the user.
        * Server access level is checked if it is specified in the request parameters.
        * If the request indicates that it relates to an object, then the validation fails.
        * Note: if you only require to ensure that an avatar is registered, then use {@link validate_avatar()}.
        * @param bool $require If true, the script will be terminated with an error message if validation fails
        * @param bool $suppress_autoreg If true, auto-registration will be completely suppressed for this function call
        * @param bool $suppress_autoenrol If true, auto-enrolment will be completely suppressed for this function call
        * @return bool Returns true if validation and/or autoregistration were successful. Returns false on failure (unless $require was true).
        * @see SloodleSession::validate_avatar()
        */
        function validate_user($require = true, $suppress_autoreg = false, $suppress_autoenrol = false)
        {
            // Is it an object request?
            if ($this->request->is_object_request()) {
                if ($require) {
                    $this->response->quick_output(-301, 'USER_AUTH', 'Cannot validate object as user.', false);
                    exit();
                }
                return false;
            }
            
            // Ensure the avatar is linked to a user
            if (!$this->user->is_avatar_linked())
            {
                // TODO: if necessary, lookup authentication server here to create Moodle user data
                //...
                if ($require)
                {
                    $this->response->quick_output(-321, 'USER_AUTH', 'User authentication failure. Avatar not found on system.', false);
                    exit();
                }
                return false;
            }            
            
            // Make sure a the course is loaded
            if ($this->courseobj == null)
            {
                if ($require)
                {
                    $this->response->quick_output(-511, 'COURSE', 'Cannot validate user - no course data loaded.', false);
                    exit();
                }
                return false;
            }
            
           // Is the user enrolled on the current course?
            if ($this->user->is_enrolled($this->course->id))
            {
                if ($require)
                {
                    $this->response->quick_output(-421, 'USER_AUTH', 'Error: '.$this->user->get_avatar_name().' is not enrolled in this course.', false);
                    exit();
                }
                return false;
            }
            
            return ($this->user->login());
        }
    }
    

?>
