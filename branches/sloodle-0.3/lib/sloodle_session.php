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
        * Current user information.
        * @var SloodleUser
        * @access public
        */
        var $user = null;
        
        /**
        * The Sloodle course structure for the course this session is accessing
        * @var SloodleCourse
        * @access public
        */
        var $course = null;
        
        /**
        * The Sloodle module this session relates to, if any.
        * Note: this may be the base Sloodle module class, or a derivative.
        * @var SloodleModule
        * @access public
        */
        var $module = null;
        
        
    // FUNCTIONS //
    
        /**
        * Constructor - initialises members
        */
        function SloodleSession()
        {
            // Construct the different parts of the session, as far as possible
            $this->user = new SloodleUser(&$this);
            $this->response = new SloodleResponse();
            $this->request = new SloodleRequest(&$this);
            $this->course = new SloodleCourse();
            
            // Process the basic request data
            $this->request->process_request_data();
        }
        
        
        /**
        * Constructs and loads the appropriate module part of the session.
        * Note that this function will fail if the current VLE user (in the $user member) does not have permission to access it.
        * @param string $type The expected type of module - function fails if type is not correctly matched
        * @param bool $db If true then the system will also try to load appropriate data from the database, as specified in the module ID request parameter
        * @param bool $require If true, then if something goes wrong, the script will be terminated with an error message
        * @param bool $override_access If true, then the user access permissions will be overriden to force access
        * @return bool True if successful, or false otherwise. (Note, if parameter $require was true, then the script will terminate before this function returns if something goes wrong)
        */
        function load_module($type, $db, $require = true, $override_access = false)
        {
            // If the database loading is requested, then make sure we have a parameter to load with
            $db_id = null;
            if ($db) {
                $db_id = $this->request->get_module_id($require);
                if ($db_id == null) return false;
            }
            
            // Construct the module
            $this->module = sloodle_load_module($type, &$this, $db_id);
            if (!$this->module) {
                if ($require) {
                    $this->response->quick_output(-601, 'MODULE', 'Failed to construct module object', false);
                    exit();
                }
                return false;
            }
            
            return true;
        }
        
        
        /**
        * Verifies security for the incoming request (but does not check user access).
        * Initially ensures that the request is coming in on a valid and enabled course/controller (rejects it if not).
        * The password is then checked, and it can handle prim-passwords and object-specific passwords.
        *
        * @param bool $require If true, the function will NOT return on authentication failure. Rather, it will terminate the script with an error message.
        * @return bool true if successful in authenticating the request, or false if not.
        */
        function authenticate_request( $require = true )
        {
            // Make sure that the request data has been processed
            if (!$this->request->is_request_data_processed()) {
                $this->request->process_request_data();
            }
            
            // Make sure the controller ID parameter was specified
            if ($this->request->get_controller_id($require) == null) return false;
            
            // Make sure we've got a valid course and controller object
            if (!$this->course->controller->is_loaded()) {
                if ($require) {
                    $this->response->quick_output(-514, 'COURSE', 'Course controller could not be accessed.', false);
                    exit();
                }
                return false;
            }
            if (!$this->course->is_loaded()) {
                if ($require) {
                    $this->response->quick_output(-512, 'COURSE', 'Course could not be accessed.', false);
                    exit();
                }
                return false;
            }
            
            // Make sure the course is available
            if (!$this->course->is_available()) {
                if ($require) {
                    $this->response->quick_output(-513, 'COURSE', 'Course not available.', false);
                    exit();
                }
                return false;
            }
            // Make sure the contrller is available
            if (!$this->course->controller->is_available()) {
                if ($require) {
                    $this->response->quick_output(-514, 'COURSE', 'Course controller not available.', false);
                    exit();
                }
                return false;
            }
            
            // Make sure the controller is enabled
            if (!$this->course->controller->is_enabled()) {
                if ($require) {
                    $this->response->quick_output(-514, 'COURSE', 'Course controller disabled.', false);
                    exit();
                }
                return false;
            }
        
            // Get the password parameter
            $password = $this->request->get_password($require);
            if ($password == null) return false;
            
            // Does the password contain an object UUID?
            $objpwd = null;
            $matches = null;
            if (preg_match('/^(.*?)\|(\d\d*)$/',$password, $matches)) {
    			$objuuid = $matches[1]; // Object UUID
    			$objpwd = $matches[2]; // Object-specific password
                
                // Make sure the password was provided
                if (empty($objpwd)) {
                    if ($require) {
                        $this->response->quick_output(-212, 'OBJECT_AUTH', 'Object-specific password not specified.', false);
                        exit();
                    }
                    return false;
                }
                
                // Attempt to find this object in the active objects list
                $entry = get_record('sloodle_active_object', 'controllerid', $this->course->controller->get_controller_id(), 'uuid', $objuuid);
                if (!$entry) {
                    if ($require) {
                        $this->response->quick_output(-214, 'OBJECT_AUTH', 'Object not authorised for this controller.', false);
                        exit();
                    }
                    return false;
                }
                
                // Check that the password was valid
                if ($objpwd != $entry->password) {
                    if ($require) {
                        $this->response->quick_output(-213, 'OBJECT_AUTH', 'Object-specific password was invalid.', false);
                        exit();
                    }
                    return false;
                }
                
                // Everything looks OK
                return true;
    		}
            
            // Check the password as a whole against the password in the controller
            if ($password != $this->course->controller->get_password()) {
                if ($require) {
                    $this->response->quick_output(-213, 'OBJECT_AUTH', 'Prim password was invalid.', false);
                    exit();
                }
                return false;
            }

            return true;
        }
        
        /**
        * Verify user access to the resources.
        
        */
        
    }

?>