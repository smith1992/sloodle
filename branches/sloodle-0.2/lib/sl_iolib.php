<?php
    // Sloodle input/output library
    // Provides generalised input/output functionality for interacting with in-world LSL
    // Part of the Sloodle Project
    // See www.sloodle.org for more information
    //
    // Copyright (c) 2007 Sloodle
    // Release under the GNU GPL v3
    //
    // Contributors:
    //  Peter R. Bloomfield - design and original implementation
    //
    
    // NOTE: this file requires that the Sloodle "config.php" file already be included
    
    require_once(SLOODLE_DIRROOT . '/lib/sl_generallib.php');
    
    // Define the separators used in batches of data
    define("SLOODLE_LINE_SEPARATOR", "\n");
    define("SLOODLE_FIELD_SEPARATOR", "|");
    // Define authentication constants
    define("SLOODLE_AUTH_OK", 1);
    define("SLOODLE_AUTH_UNKNOWN", 0);
    define("SLOODLE_AUTH_FAILED", -1);
    
    // This class manages HTTP response output for LSL scripts
    class SloodleLSLResponse
    {
      ///// DATA /////
      
        // Status code of the response: +ve = success, -ve = error
        // Should be an integer
        // *** WARNING: this data is required for the output functions to work ***
        var $status_code = NULL;
        
        // Status descriptor string
        // Should be a string giving a generalised description/category of the status code
        // Optional but recommended.
        var $status_descriptor = NULL;
        
        // Status codes of any side-effects caused by the action
        // Should be an integer or an array of integers
        // Optional.
        var $side_effects = NULL;
        
        // Request descriptor
        // Should be a string identifying the type of request, normally specified by the LSL script
        // Optional
        var $request_descriptor = NULL;
        
        // Request timestamp
        // Should be an integer specifying the time at which the request was originally made by an LSL script
        // Optional.
        var $request_timestamp = NULL;
        
        // Response timestamp
        // Should be an integer specifying the time at which the response header was generated
        // Optional
        var $response_timestamp = NULL;
        
        // User key
        // Should be a string specifying the UUID key of the avatar/agent in-world being handled
        // Optional
        var $user_key = NULL;
        
        // Tracking code
        // *** NOT USED YET ***
        // Can be any type which can be directly cast to a string
        // Optional
        var $tracking_code = NULL;
        
        // Page total
        // *** NOT USED YET ***
        // Should be an integer specifying the total number of pages which the full response requires
        // Optional (but required if "page number" is specified)
        var $page_total = NULL;
        
        // Page number
        // *** NOT USED YET ***
        // Should be an integer specifying which page number of data is currently being returned
        // Optional (but required if "page total" is specified)
        var $page_number = NULL;
        
        // Lines of data which are printed following the status line
        // *** WARNING: no data lines should contain NEWLINE or PIPE characters (\n or |) unless escaped by a back-slash (but preferably not at all!) ***
        // Can be a string, which is output directly as a single line of data
        // Can be an array:
        //  - string elements are output directly as a full line of data
        //  - array-of-strings elements are output as a pipe-delimited line of data items
        // Optional.
        var $data = NULL;
        
    
      ///// ACCESSORS /////
    
        // If invalid data is submitted to the functions, then the script is terminated with an LSL-friendly error message
    
        // Set the status code
        // Parameter $par should be a non-zero integer (cannot be null)
        function set_status_code($par)
        {
            // Validate
            if (is_int($par) == FALSE || $par == 0) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid status code specified; should be non-zero integer", 0);
            }
            // Store
            $this->status_code = $par;
        }
    
        // Set the status descriptor string
        // Parameter $par should be a string, or null
        function set_status_descriptor($par)
        {
            // Validate
            if (is_string($par) == FALSE && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid status descriptor specified; should be a string or null", 0);
            } else {
                $this->status_descriptor = $par;
            }
        }
    
        // Set the side effect codes
        // Parameter $par should be a single integer, an array of integers, or null
        // *** REMARK: for most purposes, the "add_side_effects" function should be used instead. ***
        function set_side_effects($par)
        {
            // We'll use a variable to store the validity
            $valid = TRUE;
            if (is_array($par)) {
                // Array types are acceptable
                // Make sure each array element is valid
                foreach ($par as $elem) {
                    if (!is_int($elem)) $valid = FALSE;
                }
                // Were all elements valid?
                if ($valid == FALSE) {
                    $this->_internal_validation_error("Sloodle - LSL response: invalid element in array of side effect codes; all elements should be integers", 0);
                }
            } else if (is_int($par) == FALSE && is_null($par) == FALSE) {
                // It's not an array, an integer or null
                $valid = FALSE;
                $this->_internal_validation_error("Sloodle - LSL response: invalid side effect type; should be an integer, an array of integers, or null", 0);
            }
            // Was it valid?
            if ($valid) {
                $this->side_effects = $par;
            }
        }
        
        // Add one or more side effect codes to the existing list
        // Parameter $par should be a single integer, or an array of integers
        function add_side_effects($par)
        {
            // We'll use a variable to store the validity
            $valid = TRUE;
            if (is_array($par)) {
                // Array types are acceptable
                // Make sure each array element is valid
                foreach ($par as $elem) {
                    if (!is_int($elem)) $valid = FALSE;
                }
                // Were all elements valid?
                if ($valid == FALSE) {
                    $this->_internal_validation_error("Sloodle - LSL response: cannot add side effects. Invalid element in array of side effect codes. All elements should be integers", 0);
                }
            } else if (is_int($par) == FALSE) {
                // It's not an array or an integer
                $valid = FALSE;
                $this->_internal_validation_error("Sloodle - LSL response: cannot add side effect. Invalid side effect type. should be an integer or an array of integers", 0);
            }
            // Was it valid?
            if ($valid) {
                // If we were passed just a single side effect, then convert it to an array
                if (is_int($par)) {
                    $par = array($par);
                }
                // Make sure our existing side effect member is an array
                if (is_null($this->side_effects)) $this->side_effects = array();
                else if (is_int($this->side_effects)) $this->side_effects = array($this->side_effects);
                        
                // Append our new side effect(s)               
                foreach ($par as $cur) {
                    $this->side_effects[] = $cur;
                }
            }
        }
        
        // Add one side effect code to the existing list
        // Parameter $par should be a single integer
        function add_side_effect($par)
        {
            // Make sure the parameter is valid
            if (!is_int($par))
                $this->_internal_validation_error("Sloodle - LSL response: cannot add side effect. Invalid side effect type. Should be an integer.", 0);
            $this->add_side_effects($par);
        }
        
        // Set the request descriptor string
        // Parameter $par should be a string, or null
        function set_request_descriptor($par)
        {
            // Validate
            if (is_string($par) == FALSE && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid request descriptor specified; should be a string or null", 0);
            } else {
                $this->request_descriptor = $par;
            }
        }
        
        // Set the request timestamp
        // Parameter $par should be an integer, or null
        function set_request_timestamp($par)
        {
            // Validate
            if (is_int($par) == FALSE && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid request timestamp; should be an integer, or null", 0);
            } else {
                $this->request_timestamp = $par;
            }
        }
        
        // Set the response timestamp
        // Parameter $par should be an integer, or null
        function set_response_timestamp($par)
        {
            // Validate
            if (is_int($par) == FALSE && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid response timestamp; should be an integer, or null", 0);
            } else {
                $this->response_timestamp = $par;
            }
        }
        
        // Set the user key
        // Parameter $par should be a string, or null
        function set_user_key($par)
        {
            // Validate
            if (is_string($par) == FALSE && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid user key specified; should be a string or null", 0);
            } else {
                $this->user_key = $par;
            }
        }
        
        // Set the tracking code
        // *** NOTE USED YET ***
        // No validation performed
        function set_tracking_code($par)
        {
            $this->tracking_code = $par;
        }
        
        // Set the total number of pages
        // Parameter $par should be a positive integer, or null
        function set_page_total($par)
        {
            // Validate
            if ((is_int($par) == FALSE || $par < 0) && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid page total; should be a positive integer, or null", 0);
            } else {
                $this->page_total = $par;
            }
        }
        
        // Set the current page number
        // Parameter $par should be a positive integer, or null
        function set_page_number($par)
        {
            // Validate
            if ((is_int($par) == FALSE || $par < 0) && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid page number; should be a positive integer, or null", 0);
            } else {
                $this->page_number = $par;
            }
        }
        
        // Set the data member
        // Parameter $par can be a single scalar variable, an array of scalar variables, an array of arrays of scalar variables, or null
        // A single scalar becomes one line of data.
        // An array will print each element on its own line. Any array elements are used to construct a pipe-delimeted line of items.
        // *** REMARK: for most purposes, it is preferable to use the "add_data_line(..)" function instead ***
        function set_data($par)
        {
            // We'll use a variable to store validity
            $valid = TRUE;
            if (is_array($par)) {
                // Check each element
                foreach ($par as $elem) {
                    // Is this element another array? Or is it a scalar/null value?
                    if (is_array($elem)) {
                        // Check each inner element for validity
                        foreach ($elem as $innerelem) {
                            // Is this element scalar or null? If not, it is invalid
                            if (is_scalar($innerelem) == FALSE && is_null($innerelem) == FALSE) {
                                $valid = FALSE;
                            }
                        }
                    } else if (is_scalar($elem) == FALSE && is_null($elem) == FALSE) {
                        // Not an array, nor a scalar/null value - it is invalid
                        $valid = FALSE;
                    }
                }
                if ($valid == FALSE) {
                    $this->_internal_validation_error("Sloodle - LSL response: non-scalar element in array of items for a data line");
                }
            } else if (is_scalar($par) == FALSE && is_null($par) == FALSE) {
                $valid = FALSE;
                $this->_internal_validation_error("Sloodle - LSL response: each line of data must be a scalar type, or an array of scalars");
            }
            // Store it if it is valid
            if ($valid) {
                $this->data = $par;
            }
        }
    
        // Add a line of data
        // Parameter $par can be a single scalar variable, or an array of scalar variables
        // A scalar is any basic data type, other than arrays, objects and resources
        function add_data_line($par)
        {
            // We'll use a variable to store validity
            $valid = TRUE;
            if (is_array($par)) {
                // Check each element
                foreach ($par as $elem) {
                    if (is_scalar($elem) == FALSE && is_null($elem) == FALSE) $valid = FALSE;
                }
                if ($valid == FALSE) {
                    $this->_internal_validation_error("Sloodle - LSL response: non-scalar element in array of items for a data line");
                }
            } else if (is_scalar($par) == FALSE && is_null($par) == FALSE) {
                $valid = FALSE;
                $this->_internal_validation_error("Sloodle - LSL response: each line of data must be a scalar type, or an array of scalars");
            }
            // Store it if it is valid
            if ($valid) {
                $this->data[] = $par;
            }
        }
        
        // Clear all data lines
        function clear_data()
        {
            $this->data = NULL;
        }
        
        
      ///// OTHER FUNCTIONS /////
      
        // Constructor
        // Allows the specification of some basic items of data, although each parameter is optional
        //   $status_code = integer status code
        //   $status_descriptor = string status description
        //   $data = string, array of strings, or array of arrays of strings, containing the data lines
        function SloodleLSLResponse($status_code = NULL, $status_descriptor = NULL, $data = NULL)
        {
            // Store the data
            if (!is_null($status_code)) $this->status_code = (int)$status_code;
            if (!is_null($status_descriptor)) $this->status_descriptor = (string)$status_descriptor;
            if (!is_null($data)) $this->data = $data;
        }
      
        // Render the message to a string
        // Parameter $str is the string to which the message is rendered (by reference)
        // If an error occurs, the LSL-friendly error message is output to the HTTP response, and the script terminated
        function render_to_string(&$str)
        {
            // Clear the string
            $str = "";
            
            // We can omit any unnecessary items of data, but the number of field-separators must be correct
            // E.g. if item 4 is specified, but items 2 and 3 are not, then empty field-separators must be output as if items 2 and 3 were present, e.g.:
            // 1|||AVATAR_LIST
            // (where the pipe-character | is the field separator)
            
            // We will step backwards through out list of fields, and as soon as one item is specified, all of them should be
            $showall = FALSE;
            // Make sure that if the page number is specified, that the total is as well
            if (is_null($this->page_number) xor is_null($this->page_total)) {
                $this->_internal_validation_error("Sloodle - LSL response: script must specify both \"page_total\" *and* \"page_number\", or specify neither");
            } else if ($showall || is_null($this->page_number) == FALSE) {
                $showall = TRUE;
                $str = SLOODLE_FIELD_SEPARATOR . (string)$this->page_total . SLOODLE_FIELD_SEPARATOR . (string)$this->page_number . $str;
            }
            
            // Do we have a tracking code?
            if ($showall || is_null($this->tracking_code) == FALSE) {
                $showall = TRUE;
                $str = SLOODLE_FIELD_SEPARATOR . (string)$this->tracking_code . $str;
            }
            
            // User key?
            if ($showall || is_null($this->user_key) == FALSE) {
                $showall = TRUE;
                $str = SLOODLE_FIELD_SEPARATOR . $this->user_key . $str;
            }
            
            // Response timestamp?
            if ($showall || is_null($this->response_timestamp) == FALSE) {
                $showall = TRUE;
                $str = SLOODLE_FIELD_SEPARATOR . (string)$this->response_timestamp . $str;
            }
            
            // Request timestamp?
            if ($showall || is_null($this->request_timestamp) == FALSE) {
                $showall = TRUE;
                $str = SLOODLE_FIELD_SEPARATOR . (string)$this->request_timestamp . $str;
            }
            
            // Request descriptor?
            if ($showall || is_null($this->request_descriptor) == FALSE) {
                $showall = TRUE;
                $str = SLOODLE_FIELD_SEPARATOR . $this->request_descriptor . $str;
            }
            
            // Side-effects?
            if ($showall || is_null($this->side_effects) == FALSE) {
                $showall = TRUE;
                // Is this an array?
                if (is_array($this->side_effects)) {
                    // Yes - output each side effect code in a comma-separated list
                    $selist = "";
                    $isfirst = TRUE;
                    foreach ($this->side_effects as $cur_side_effect) {
                        if (!$isfirst)  $selist .= ",";
                        else $isfirst = FALSE;
                        $selist .= (string)$cur_side_effect;
                    }
                    // Add that list to the output
                    $str = SLOODLE_FIELD_SEPARATOR . $selist . $str;
                    
                } else {
                    // Not at an array - output the single item
                    $str = SLOODLE_FIELD_SEPARATOR . (string)$this->side_effects . $str;
                }
            }
            
            // Status descriptor?
            if ($showall || is_null($this->status_descriptor) == FALSE) {
                $showall = TRUE;
                $str = SLOODLE_FIELD_SEPARATOR . $this->status_descriptor . $str;
            }
            
            // Ensure that a status code has been specified
            if (is_null($this->status_code)) {
                // Not specified - report an error
                $this->_internal_validation_error("Sloodle - LSL response: no status code specified");
            } else {
                // Output the status code
                $str = (string)$this->status_code . $str;
            }
            
            
            // Has any data been specified?
            if (is_null($this->data) == FALSE) {
                
                // Do we have an outer array?
                if (is_array($this->data)) {
                
                    // Go through each element in the outer array
                    foreach ($this->data as $outer_elem) {
                        
                        // Do we have an inner array on this element?
                        if (is_array($outer_elem)) {
                        
                            // Construct the line, piece-at-a-time
                            $line = "";
                            $isfirst = TRUE;
                            foreach ($outer_elem as $inner_elem) {
                                // Use the standard field separator
                                if (!$isfirst) $line .= SLOODLE_FIELD_SEPARATOR;
                                else $isfirst = FALSE;
                                $line .= (string)$inner_elem;
                            }
                            // Append the new line of data
                            $str .= SLOODLE_LINE_SEPARATOR . (string)$line;
                        
                        } else {
                            // Output the single item
                            $str .= SLOODLE_LINE_SEPARATOR . (string)$outer_elem;
                        }
                    }
                
                } else {
                    // Output the single item
                    $str .= SLOODLE_LINE_SEPARATOR . (string)$this->data;
                }
            }
        }
        
        // Output the message to the HTTP response body
        // Returns an array of error messages otherwise
        // If an error occurs, the LSL-friendly error message is output to the HTTP response, and the script terminated
        function render_to_output()
        {
            // Attempt to render the output to a string, and then copy that string to the HTTP response
            $str = "";
            $this->render_to_string($str);
            echo $str;
        }
        
        
        // Quick-output
        // Can be called statically to allow simple output of basic data
        // The status code is required, but the other parameters are optional
        // If an error occurs, the LSL-friendly error message is output to the HTTP response, and the script terminated
        function quick_output($status_code, $status_descriptor = NULL, $data = NULL)
        {
            // Construct and render the output of a response object
            $response = new SloodleLSLResponse($status_code, $status_descriptor, $data);
            $response->render_to_output();
        }
    
        
        // Internal function to report a data validation error
        // Outputs an LSL-friendly error message, and terminates the script
        function _internal_validation_error($msg)
        {
            exit("-104".SLOODLE_FIELD_SEPARATOR."SYSTEM".SLOODLE_LINE_SEPARATOR.$msg);
        }
    }
    
    
    // Obtain a named request parameter, and terminate with an error message if it has not been provided
    // Note: for LSL linker scripts, this should *always* be used instead of the Moodle function, as this will
    //  render appropraitely formatted error messages, which LSL scripts can understand.
    // Returns the parameter if it is found
    function sloodle_required_param($parname, $type)
    {
        // Attempt to get the parameter
        $par = optional_param($parname, NULL, $type);
        // Was it provided?
        if (is_null($par)) {
            // No - report the error
            SloodleLSLResponse::quick_output(-811, "SYSTEM", "Expected request parameter '$parname'.");
            exit();
        }
        
        return $par;
    }
    
    
    // This class handles a request from an LSL script
    class SloodleLSLRequest
    {
      ///// DATA /////
      // WARNING: all data should be treated as PRIVATE (even though PHP4 does not recognise this concept)
      
        // Has the request data been processed?
        // Boolean true if so, or false if not
        // Process data by calling the process_request_data() function
        var $request_data_processed = FALSE;
        
        // The authentication password provided by the object (if any)
        // At the moment, this will always be the site-wide prim-password, although that may change
        // Should be a string, or null
        var $password = NULL;
        
        // Authentication status
        // Should always be an integer, with one of the constant values
        // SLOODLE_AUTH_OK = authentication successful
        // SLOODLE_AUTH_UNKNOWN = authentication not yet attempted
        // SLOODLE_AUTH_FAILED = authentication failed
        var $auth_status = SLOODLE_AUTH_UNKNOWN;
        
        // Loginzone position
        // Should be an array with three floating-point elements {X,Y,Z}, or NULL
        var $login_zone_pos = NULL;
        
        // ID of the course which this request refers to
        // Should be an integer identifying a Moodle course, or NULL
        var $course_id = NULL;
        
        // Course module instance ID which this request refers to
        // Should be an integer or NULL
        var $module_id = NULL;

        // UUID of the avatar making the request (if any)
        // Should be a string containing an SL UUID, or null
        var $avatar_uuid = NULL;
        
        // The name of the avatar making the request (if applicable)
        // Should be a string containing first name and last name separated by a space, or should be null
        var $avatar_name = NULL;
        
        // The login security token
        // Should be a string containing mixed-case letters and numbers, or null
        var $login_security_token = NULL;
        
        
    /// References to potentially external objects ///
        
        // Request response object
        // This object will be customized as the request progresses to give appropriate script output
        // It should be used when the script finally requires ouptut
        var $response = NULL;
        
        // A SloodleUser object
        // The request object will forward the appropriate data to this object for handling the current user
        // This will often be a reference to an externally created object, but will be instantiated on construction if necessary
        var $user = NULL;
        
        
        
      ///// ACCESSORS /////
    
        // Have the request parameters been processed yet?
        // Returns TRUE if so, or FALSE if not
        function is_request_data_processed()
        {
            return $this->request_data_processed;
        }
        
        // Get the authentication status
        // Returns an integer with one of the constant values: SLOODLE_AUTH_OK, SLOODLE_AUTH_UNKNOWN, SLOODLE_AUTH_FAILED
        function get_auth_status()
        {
            return $this->auth_status;
        }
        
        // Is the request authenticated?
        // Returns boolean TRUE if so, or FALSE if authentication has failed or not yet been attempted
        function is_authenticated()
        {
            return ($this->auth_status == SLOODLE_AUTH_OK);
        }
        
        // Did the request authentication fail?
        // Returns boolean TRUE if it failed, or FALSE if authentication was successful or  has not yet been attempted
        function is_auth_failed()
        {
            return ($this->auth_status == SLOODLE_AUTH_FAILED);
        }
        
        // Set the response object
        // $response should be a valid SloodleLSLResponse object
        function set_response($response)
        {
            $this->response = $response;
        }
        
        // Set the user object
        // $user should be a reference to a valid SloodleUser object
        function set_user($user)
        {
            $this->user = $user;
        }
    
        
      // NOTE: These accessors will force the request data to be processed if it hasn't already been processed
        
        // Get the authentication password given in the request
        // Returns a string containing the password given, or NULL if none was given
        function get_password()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->password;
        }
        
        // Get the course ID
        // Returns an integer indicating which course was requested, or NULL if value was not specified
        function get_course_id()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->course_id;
        }
        
        // Get the course module isntance ID
        // Returns an integer identifying a course module instance ID, or NULL if value was not specified
        function get_module_id()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->module_id;
        }
        
        // Get the UUID of the avatar making the request
        // Returns a string containing the UUID passed to the request, or NULL if none was specified
        function get_avatar_uuid()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->avatar_uuid;
        }
        
        // Get the name of the avatar making the request
        // Returns a string containing the name passed to the request, or NULL if none was specified
        function get_avatar_name()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->avatar_name;
        }
        
        // Get the login security token
        // Returns a string containing the security token passed in the request, or NULL if none was specified
        function get_login_security_token()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->login_security_token;
        }
        
        // Get the response object
        // Returns a reference to a response object which has been prepared according to the request
        function get_response()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->response;
        }
        
        // Get the user object
        function get_user()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->user;
        }
        
        
      ///// FUNCTIONS /////
      
        // Constructor - initialises variables
        // If $response is an object, then it is used as the response object for this request to write to (should be SloodleLSLResponse)
        // If $user is an object, then it is used as the user object for the request (should be SloodleUser)
        // This is useful to allow the same response object to be used in the controlling script as here
        function SloodleLSLRequest($response = NULL, $user = NULL)
        {
            // Store or instantiate our response object
            if (is_object($response)) $this->response = $response;
            else $this->response = new SloodleLSLResponse();
            
            // Store or instantiate our user object
            if (is_object($user)) $this->user = $user;
            else $this->user = new SloodleUser();
        }
        
        // Process all of the data provided by the request
        // This function will usually be called automatically when needed
        // Normally, it will not process the request data if it already has done
        // However, if parameter $force is TRUE then it will force re-processing
        // Note that, if avatar_uuid and/or avatar_name are specified in the request,
        //  then this function will attempt to retreive data for them.
        //  However, it will *not* login the user or auto-register them - that should be done manually on the $user object.
        // This will also attempt to fetch a course record if a course ID is requested
        // No return value
        function process_request_data( $force = FALSE )
        {
            // Process the request data if it has not yet been procesed, or if re-processing is being forced
            if ($this->request_data_processed == FALSE || $force == TRUE) {
                // Fetch the parameters from the request
                $this->password = optional_param('sloodlepwd', NULL, PARAM_RAW);
                $this->course_id = optional_param('sloodlecourseid', NULL, PARAM_INT);
                $this->module_id = optional_param('sloodlemoduleid', NULL, PARAM_INT);
                $this->avatar_uuid = optional_param('sloodleuuid', NULL, PARAM_RAW);
                $this->avatar_name = optional_param('sloodleavname', NULL, PARAM_RAW);
                $this->login_security_token = optional_param('sloodlelst', NULL, PARAM_RAW);
                
                $this->response->set_request_descriptor(optional_param('sloodlerequestdesc', NULL, PARAM_RAW));
                
                // Fetch the login zone position string
                $temp_pos = optional_param('sloodleloginzonepos', NULL, PARAM_RAW);
                // If it was specified then convert it to an array
                if (!is_null($temp_pos) && !empty($temp_pos)) $this->login_zone_pos = vector_to_array($temp_pos);
                else $this->login_zone_pos = NULL;
                
                // Some values ought to be NULL if they are empty
                if (empty($this->avatar_uuid)) $this->avatar_uuid = NULL;
                if (empty($this->avatar_name)) $this->avatar_name = NULL;
                
                // Attempt to find a Sloodle user by UUID/name
                $found_sloodle_user = $this->user->find_sloodle_user($this->avatar_uuid, $this->avatar_name, TRUE);
                if ($found_sloodle_user === TRUE) {
                    // We found a user
                    // If the UUID or name had been previously unspecified, then attempt to get them from the database data
                    if (is_null($this->avatar_uuid)) $this->avatar_uuid = $this->user->sloodle_user_cache->uuid;
                    if (is_null($this->avatar_name)) $this->avatar_name = $this->user->sloodle_user_cache->avname;
                    
                    // Attempt to find an associated Moodle user (from the data cached above), and cache the results
                    $this->user->find_linked_moodle_user(TRUE, TRUE);                
                }
                
                // Store the avatar UUID in the response object
                $this->response->user_key = $this->avatar_uuid;
            }
            
            $this->request_data_processed = TRUE;
        }
      
        // Authenticate the request by checking its password
        // If parameter $require is TRUE, then script will be terminated with an LSL error message if authentication fails
        // Otherwise, function returns boolean TRUE if authentication succeeds, or FALSE if not (with no error information)
        function authenticate_request( $require = TRUE )
        {
            // If the request is already authenticated, then there is nothing else to do
            if ($this->auth_status == SLOODLE_AUTH_OK)
                return TRUE;
        
            // Make sure the request data is processed
            $this->process_request_data();
            // We are not initially authenticated
            $this->auth_status = SLOODLE_AUTH_UNKNOWN;
            
            // Ensure that a password was provided
            if (is_null($this->password)) {
                $this->auth_status = SLOODLE_AUTH_FAILED;
                // Should we terminate the script with an error message?
                if ($require) {
                    $this->response->set_status_code(-212);
                    $this->response->set_status_descriptor('OBJECT_AUTH');
                    $this->response->add_data_line('Prim Password not passed in request');
                    $this->response->render_to_output();
                    exit();
                } else {
                    return FALSE;
                }
            }
            
            // Does the password contain an object UUID?
            $objpwd = NULL;
            if (preg_match('/^(.*?)\|(\d\d*)$/',$this->password, $matches)) {
    			$objuuid = $matches[1]; // Object UUID
    			$objpwd = $matches[2]; // Object-specific password
                // Get an appropriate entry from the table of active objects
    			$entry = get_record('sloodle_active_object','uuid',$objuuid);            
    			if ($entry !== FALSE && $entry->pwd != NULL && $entry->pwd == $objpwd) {
                    // Authentication was successful
                    $this->auth_status = SLOODLE_AUTH_OK;
    				return TRUE;
    			}
    		}
           
            // Check the password value as a whole, and check the object-password (if one was given)
            $_prim_password = sloodle_get_prim_password();
            if ($this->password !== $_prim_password && $objpwd !== $_prim_password) {
                $this->auth_status = SLOODLE_AUTH_FAILED;
                // Should we terminate the script with an error message?
                if ($require) {
                    $this->response->set_status_code(-213);
                    $this->response->set_status_descriptor('OBJECT_AUTH');
                    $this->response->add_data_line('Password provided was invalid');
                    $this->response->render_to_output();
                    exit();
                } else {
                    return FALSE;
                }
            }
            unset($_prim_password); // For security
            
            // Authentication appears to be OK
            $this->auth_status = SLOODLE_AUTH_OK;
            return TRUE;
        }
        
        // Get a database record for the course identified in the request
        // (Note: does not check whether or not the user is enrolled in the course)
        // Returns a record directly from the database, or NULL if the course is not found
        // Note: if parameter $require is TRUE (default), then the script will terminate
        //  with an LSL error message instead of returning NULL
        function get_course_record($require = TRUE)
        {
            // Make sure the request data is processed
            $this->process_request_data();
            // Make sure the course ID was specified
            if (is_null($this->course_id)) {
                if ($require) {
                    $this->response->set_status_code(-501);
                    $this->response->set_status_descriptor('COURSE');
                    $this->response->add_data_line('No course specified in request.');
                    $this->response->render_to_output();
                    exit();
                }
                return NULL;
            }
            // Attempt to get the course data
            $course_record = get_record('course', 'id', $this->course_id);
            if ($course_record === FALSE) {
                // Course not found
                if ($require) {
                    $this->response->set_status_code(-512);
                    $this->response->set_status_descriptor('COURSE');
                    $this->response->add_data_line("Course {$this->course_id} not found.");
                    $this->response->render_to_output();
                    exit();
                }
                return NULL;
            }
            // Make sure the course is visible
            // TODO: any availability other checks here?
            if ((int)$course_record->visible == 0) {
                // Course not available
                if ($require) {
                    $this->response->set_status_code(-513);
                    $this->response->set_status_descriptor('COURSE');
                    $this->response->add_data_line("Course {$this->course_id} is not available.");
                    $this->response->render_to_output();
                    exit();
                }
                return NULL;
            }
            // TODO: in future, we need to check that the course is Sloodle-enabled
            // TODO: in future, make sure we are authenticated for this particular course
            
            // Seems fine... return the object
            return $course_record;
        }
        
        // Get a course module instance for the module specified in the request
        // Uses the ID specified in $this->module_id
        // $type specifies the name of the module type (e.g. 'forum', 'choice' etc.) - ignored if blank (default)
        // Returns a database record if successful, or FALSE if not (e.g. if instance is not found, is not visible, or is not of the correct type)
        // If $require is TRUE (default) then the script will terminate with an LSL error message instead of returning FALSE
        function get_course_module_instance( $type = '', $require = TRUE )
        {
            // Make sure the request data is processed
            $this->process_request_data();
            
            // Make sure the module ID was specified
            if ($this->module_id == NULL) {
                if ($require) {
                    $this->response->set_status_code(-711);
                    $this->response->set_status_descriptor('MODULE_DESCRIPTOR');
                    $this->response->add_data_line('Course module instance ID not specified.');
                    $this->response->render_to_output();
                    exit();
                }
                return FALSE;
            }
            
            // Attempt to get the instance
            if (!($cmi = sloodle_get_course_module_instance($this->module_id))) {
                if ($require) {
                    $this->response->set_status_code(-712);
                    $this->response->set_status_descriptor('MODULE_DESCRIPTOR');
                    $this->response->add_data_line('Could not find course module instance.');
                    $this->response->render_to_output();
                    exit();
                }
                return FALSE;
            }
            
            // If the type was specified, then verify it
            if (!empty($type)) {
                if (!sloodle_check_course_module_instance_type($cmi, strtolower($type))) {
                    if ($require) {
                        $this->response->set_status_code(-712);
                        $this->response->set_status_descriptor('MODULE_DESCRIPTOR');
                        $this->response->add_data_line("Course module instance not of expected type. (Expected: '$type').");
                        $this->response->render_to_output();
                        exit();
                    }
                    return FALSE;
                }
            }
            
            // Make sure the instance is visible
            if (!sloodle_is_course_module_instance_visible($cmi)) {
                if ($require) {
                    $this->response->set_status_code(-713);
                    $this->response->set_status_descriptor('MODULE_DESCRIPTOR');
                    $this->response->add_data_line('Specified course module instance is not available.');
                    $this->response->render_to_output();
                    exit();
                }
                return FALSE;
            }
            
            // Everything looks fine
            return $cmi;
        }
        
        
    ///// UTILITY FUNCTIONS /////
    
        // Obtain a named request parameter, and terminate with an error message if it has not been provided
        // Note: for LSL linker scripts, this should *always* be used instead of the Moodle function, as this will
        //  render appropraitely formatted error messages, which LSL scripts can understand.
        // Returns the parameter if it is found
        // $type is the same as it is for the Moodle function
        function required_param($parname, $type=PARAM_RAW)
        {
            // Attempt to get the parameter
            $par = optional_param($parname, NULL, $type);
            // Was it provided?
            if (is_null($par)) {
                // No - report the error
                $this->response->set_status_code(-811);
                $this->response->set_status_descriptor('SYSTEM');
                $this->response->add_data_line("Required parameter not provided: '$parname'.");
                $this->response->render_to_output();
                exit();
            }
            
            return $par;
        }
        
    }

?>