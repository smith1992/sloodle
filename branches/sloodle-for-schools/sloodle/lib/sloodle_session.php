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
    
    
    /** Used to indicate that a test is to be done at the level of a module.
    * For example, checking if a user is a teacher on a module (activity), rather than a whole course. */
    define('SLOODLE_CONTEXT_MODULE', 10);
    /** Used to indicate that a test is to be done at the level of a course.
    * For example, checking if a user is a teacher on a course, rather than just a module. */
    define('SLOODLE_CONTEXT_COURSE', 20);
    /** Used to indicate that a test is to be done at the level of a site.
    * For example, checking if a user is a teacher on the whole site. */
    define('SLOODLE_CONTEXT_SITE', 30);
    
    
    
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
            
            // Compare the provided authentication token with the one stored in the database
            $token = $this->request->get_auth_token(false);
            if ($token === null)
            {
                if ($require)
                {
                    $this->response->quick_output(-4440001, 'SFS', 'Authentication token not provided in request.');
                    exit;
                }
                return false;
            }
            $storedToken = sloodle_get_stored_auth_token();
            if (!$storedToken)
            {
                if ($require)
                {
                    $this->response->quick_output(-4440003, 'SFS', 'Failed to retrieve correct authentication token from database');
                    exit;
                }
                return false;
            }
            if ($storedToken != $token)
            {
                if ($require)
                {
                    $this->response->quick_output(-4440002, 'SFS', 'Provided authentication token was invalid');
                    exit;
                }
                return false;
            }
            
            return true;
        }
        
        /**
        * Verifies that the incoming request is from an authorised adminstration source.
        * This checks for the administration token in the HTTP headers.
        * The token is the same as it is for an ordinary request, but it is placed in a different header.
        *
        * @param bool $require If true, the function will NOT return on authentication failure. Rather, it will terminate the script with an error message.
        * @return bool true if successful in authenticating the request, or false if not.
        */
        function authenticate_admin_request( $require = true )
        {
            // Make sure that the request data has been processed
            if (!$this->request->is_request_data_processed())
            {
                $this->request->process_request_data();
            }
            
            // Compare the provided administration token with the one stored in the database
            $token = $this->request->get_admin_token(false);
            if ($token === null)
            {
                if ($require)
                {
                    $this->response->quick_output(-4440001, 'SFS', 'Administration token not provided in request.');
                    exit;
                }
                return false;
            }
            $storedToken = sloodle_get_stored_auth_token(); // The actual token is the same for regular auth and for admin
            if (!$storedToken)
            {
                if ($require)
                {
                    $this->response->quick_output(-4440003, 'SFS', 'Failed to retrieve correct Administration token from database');
                    exit;
                }
                return false;
            }
            if ($storedToken != $token)
            {
                if ($require)
                {
                    $this->response->quick_output(-4440002, 'SFS', 'Provided Administration token was invalid');
                    exit;
                }
                return false;
            }
            
            return true;
        }
        
        /**
        * Checks that the avatar owner of the object originating the current request is linked to a Moodle account.
        * It does NOT check any permissions / capabilities.
        * @param bool $require If true (default) the script will terminate with an error message if the check fails. Otherwise, it will return a boolean to indicate success.
        * @return bool True if the owner has the specified permission, or false otherwise.
        */
        function validate_owner($require = true)
        {
            // Check that we have the owner's avatar data, and an associated user account
            if (!$this->owner->is_avatar_loaded())
            {
                // Was the data specified?
                if ($this->request->get_owner_uuid() == '' || $this->request->get_owner_name() == '')
                {
                    if ($require)
                    {
                        $this->response->quick_output(-811, 'REQUEST', 'Owner avatar data not provided.');
                        exit;
                    }
                    return false;
                }
                
                // Looks like the avatar data just wasn't in the database.
                // We could try to add the avatar data from an authentication source here.
                //...
                // If it fails:
                if ($require)
                {
                    $this->response->quick_output(-311, 'OWNER_AUTH', 'Owner avatar not recognised.');
                    exit;
                }
                return false;
            }
            // Do we have associated Moodle user data?
            if (!$this->owner->is_user_loaded())
            {
                // No
                // TODO: if necessary and possible, search for user data in authentication system
                if ($require)
                {
                    $this->response->quick_output(-321, 'OWNER_AUTH', 'Owner avatar data is not linked to a known Moodle user account.');
                    exit;
                }
            }
            
            return true;
        }
        
        /**
        * Check that the specified user has the given Moodle capability.
        * Use this instead of Moodle's "require_capability" function when in a linker script.
        * Behaviour is the same except the it will report an LSL-friendly error message on failure.
        * @param string $capability - name of the capability
        * @param object $context - a context object (record from context table)
        * @param integer $userid - a userid number
        * @param bool $doanything - if false, ignore do anything
        * @return void
        */
        function require_capability($capability, $context, $userid = NULL, $doanything = true, $errormessage = 'nopermissions', $stringfile = '')
        {
            if (has_capability($capability, $context, $userid, $doanything)) return;
            $this->response->quick_output(-331, 'USER_AUTH', get_string($errormessage, $stringfile));
            exit();
        }
        
        
        /**
        * Validates the user account and enrolment (ensures there is an avatar linked to a VLE account, and that the VLE account is enrolled in the current course).
        * @return bool Returns true if validation was successful. Returns false on failure (unless $require was true).
        * @see SloodleSession::validate_avatar()
        *
        * @todo: possibly include a querying shared authentication mechanism, depending on auth architecture?
        */
        function validate_user($require = true)
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
            if (!$this->user->is_enrolled($this->courseobj->id))
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
