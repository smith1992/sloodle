<?php
    // This file is part of the Sloodle project (www.sloodle.org) and is released under the GNU GPL v3.
    
    /**
    * Sloodle input/output library.
    *
    * Provides general request and response functionality for interacting with in-world LSL scripts.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    * @since Sloodle 0.2
    *
    * @contributor Peter R. Bloomfield
    *
    */
            

    // NOTE: this file requires that the Sloodle "config.php" file already be included
    
    /** Include our general library. */
    require_once(SLOODLE_DIRROOT . '/lib/general.php');
    
    /** Defines what character(s) will be used to separate lines in the Sloodle communications specification. */
    define("SLOODLE_LINE_SEPARATOR", "\n");
    /** Defines what character(s) will be used to separate individual fields in the Sloodle communications specification. */
    define("SLOODLE_FIELD_SEPARATOR", "|");
    
    /** Used to indicate that authentication has been successful. */
    define("SLOODLE_AUTH_OK", 1);
    /** Used to indicate that authentication has not yet been attempted. */
    define("SLOODLE_AUTH_UNKNOWN", 0);
    /** Used to indicate that authentication has failed. */
    define("SLOODLE_AUTH_FAILED", -1);

    
    /**
    * A helper class to validate and structure data for output according to the {@link http://slisweb.sjsu.edu/sl/index.php/Sloodle_communications_specification Sloodle communications specification}.
    * @package sloodle
    */
    class SloodleLSLResponse
    {
      ///// DATA /////
      
        /**
        * Integer status code of the response.
        * Refer to the {@link http://slisweb.sjsu.edu/sl/index.php/Sloodle_status_codes status codes} page on the Sloodle wiki for a reference.
        * <b>Required.</b>
        * @var int
        * @access private
        */
        var $status_code = NULL;
        
        /**
        * Status descriptor string.
        * Should contain a generalised description/category of the status code.
        * <b>Optional but recommended. Ignored if NULL.</b>
        * @var string
        * @access private
        */
        var $status_descriptor = NULL;
        
        /**
        * Integer side effect(s) codes.
        * Status code(s) of side effect(s) incurred during the operation.
        * Can be a single integer, or an array of integers.
        * <b>Optional. Ignored if NULL.</b>
        * @var mixed
        * @access private
        */
        var $side_effects = NULL;
        
        /**
        * Request descriptor.
        * A brief string passed into the request by an LSL script (via HTTP parameter 'sloodlerequestdesc'),
        * which is returned so that it can correctly distinguish one request from anotehr.
        * <b>Optional. Ignored if NULL.</b>
        * @var string
        * @access private
        */
        var $request_descriptor = NULL;
        
        /**
        * Timestamp when the request was originally made by the LSL script.
        * This is <i>not</i> filled-in automatically. You must do it manually if you need it.
        * <b>Optional. Ignored if NULL.</b>
        * @var integer
        * @access private
        */
        var $request_timestamp = NULL;
        
        /**
        * Timestamp when the response was generated on the Moodle site.
        * This is <i>not</i> filled-in automatically. You must do it manually if you need it.
        * <b>Optional. Ignored if NULL.</b>
        * @var integer
        * @access private
        */
        var $response_timestamp = NULL;
        
        // User key
        // Should be a string specifying the UUID key of the avatar/agent in-world being handled
        // Optional
        /**
        * SL agent key.
        * Should be a string specifying the UUID key of the agent in-world being handled. (Typically of the user who initiated the request).
        * <b>Optional. Ignored if NULL.</b>
        * @var string
        * @access private
        */
        var $user_key = NULL;
        
        /**
        * Tracking code of the request.
        * Use of this value is undefined. Please do not use it.
        * <b>Optional. Ignored if NULL.</b>
        * @var mixed
        * @access private
        */
        var $tracking_code = NULL;
        
        /**
        * Total number of pages.
        * If a response requires multiple pages, this value indicates how many pages there are.
        * <b>Optional, unless $page_number is specified. Ignored if NULL.</b> <i>Not yet supported.</i>
        * @var integer
        * @access private
        */
        var $page_total = NULL;
        
        /**
        * Current page number.
        * If a response requires multiple pages, this value indicates which page is being returned in this response.
        * <b>Optional, unless $page_total is specified. Ignored if NULL.</b> <i>Not yet supported.</i>
        * @var integer
        * @access private
        */
        var $page_number = NULL;
        
        /**
        * Data to render following the status line in the response.
        * This value can either be a scalar (single value, e.g. int, string, float), or an array.
        * If it is a single scalar, it is rendered as a single line.
        * If it is an array, then each element becomes one line.
        * If an element is a scalar, then it is directly output onto the line.
        * If an element is an array, then each child element is output as a separate field on the same line.
        * <b>Optional. Ignored if NULL.</b>
        * @see SLOODLE_LINE_SEPARATOR
        * @see SLOODLE_FIELD_SEPARATOR
        * @see SloodleLSLResponse::set_data()
        * @see SloodleLSLResponse::add_data_line()
        * @see SloodleLSLResponse::clear_data()
        * @var mixed
        * @access private
        */
        var $data = NULL;
        
    
      ///// ACCESSORS /////
    
        // If invalid data is submitted to the functions, then the script is terminated with an LSL-friendly error message
    
        /**
        * Accessor function to set member value {@link $status_code}
        * @param integer $par A non-zero status code
        * @return void
        */
        function set_status_code($par)
        {
            // Validate
            if (is_int($par) == FALSE || $par == 0) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid status code specified; should be non-zero integer", 0);
            }
            // Store
            $this->status_code = $par;
        }
    
        /**
        * Accessor function to set member value {@link $status_descriptor}
        * @param mixed $par A status descriptor string, or NULL to clear it
        * @return void
        */
        function set_status_descriptor($par)
        {
            // Validate
            if (is_string($par) == FALSE && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid status descriptor specified; should be a string or null", 0);
            } else {
                $this->status_descriptor = $par;
            }
        }
    
        /**
        * Accessor function to set member value {@link $side_effects}. <b>Note:</b> it is recommended that you use {@link add_side_effect()} or {@link add_side_effects()} instead.
        * @param mixed $par An integer side effect code, an array of integer side effect codes, or null to clear it
        * @return void
        */
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

        /**
        * Adds one or more integer side effect codes to member {@link $status_code}.
        * @param mixed $par An integer side effect code, or an array of them.
        * @return void
        */
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
        
        /**
        * Adds a single side effect code to member {@link $status_code}
        * @param integer $par An integer side-effect code.
        * @return void
        */
        function add_side_effect($par)
        {
            // Make sure the parameter is valid
            if (!is_int($par))
                $this->_internal_validation_error("Sloodle - LSL response: cannot add side effect. Invalid side effect type. Should be an integer.", 0);
            $this->add_side_effects($par);
        }
        
        /**
        * Accessor function to set member value {@link $request_descriptor}
        * @param mixed $par A string request descriptor, or NULL to clear it
        * @return void
        */
        function set_request_descriptor($par)
        {
            // Validate
            if (is_string($par) == FALSE && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid request descriptor specified; should be a string or null", 0);
            } else {
                $this->request_descriptor = $par;
            }
        }
        
        /**
        * Accessor function to set member value {@link $request_timestamp}
        * @param mixed $par An integer timestamp, or NULL to clear it
        * @return void
        */
        function set_request_timestamp($par)
        {
            // Validate
            if (is_int($par) == FALSE && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid request timestamp; should be an integer, or null", 0);
            } else {
                $this->request_timestamp = $par;
            }
        }
        
        /**
        * Accessor function to set member value {@link $response_timestamp}
        * @param mixed $par An integer timestamp, or NULL to clear it
        * @return void
        */
        function set_response_timestamp($par)
        {
            // Validate
            if (is_int($par) == FALSE && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid response timestamp; should be an integer, or null", 0);
            } else {
                $this->response_timestamp = $par;
            }
        }
        
        /**
        * Accessor function to set member value {@link $user_key}
        * @param mixed $par A string containing a UUID, or NULL to clear it
        * @return void
        */
        function set_user_key($par)
        {
            // Validate
            if (is_string($par) == FALSE && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid user key specified; should be a string or null", 0);
            } else {
                $this->user_key = $par;
            }
        }
        
        /**
        * Accessor function to set member value {@link $tracking_code}
        * @param mixed $par Any scalar value
        * @return void
        */
        function set_tracking_code($par)
        {
            $this->tracking_code = $par;
        }
        
        /**
        * Accessor function to set member value {@link $page_total}
        * @param mixed $par A positive page total count, or NULL to clear it
        * @return void
        */
        function set_page_total($par)
        {
            // Validate
            if ((is_int($par) == FALSE || $par < 0) && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid page total; should be a positive integer, or null", 0);
            } else {
                $this->page_total = $par;
            }
        }
        
        /**
        * Accessor function to set member value {@link $page_number}
        * @param mixed $par A positive page number, or NULL to clear it
        * @return void
        */
        function set_page_number($par)
        {
            // Validate
            if ((is_int($par) == FALSE || $par < 0) && is_null($par) == FALSE) {
                $this->_internal_validation_error("Sloodle - LSL response: invalid page number; should be a positive integer, or null", 0);
            } else {
                $this->page_number = $par;
            }
        }
        
        /**
        * Accessor function to set member value {@link $data}. <b>Note: it is recommended that you use the {@link add_data_line()} and {@link clear_data()} functions instead of this.</b>
        * @param mixed $par Any scalar value, or a mixed array of scalars or scalar arrays, or NULL to clear it
        * @return void
        */
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
    
        /**
        * Adds one line of data to the {@link $data} member
        * @param mixed $par A scalar, or an array of scalars
        * @return void
        */
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
        
        /**
        * Clears all data from member {@link $data}
        * @return void
        */
        function clear_data()
        {
            $this->data = NULL;
        }
        
        
      ///// OTHER FUNCTIONS /////
      
        /**
        * <i>Constructor</i> - can intialise some variables
        * @param int $status_code The initial status code for the response (optional - ignore if NULL)
        * @param string $status_descriptor The initial status descriptor for the response (optional - ignore if NULL)
        * @param mixed $data The initial data for the response, which can be a scalar, or a mixed array of scalars/scalar-arrays (see {@link SloodleLSLResponse::$data}) (optional - ignore if NULL)
        * @return void
        * @access public
        */
        function SloodleLSLResponse($status_code = NULL, $status_descriptor = NULL, $data = NULL)
        {
            // Store the data
            if (!is_null($status_code)) $this->status_code = (int)$status_code;
            if (!is_null($status_descriptor)) $this->status_descriptor = (string)$status_descriptor;
            if (!is_null($data)) $this->data = $data;
        }
      
        /**
        * Renders the response to a string.
        * Prior to rendering, this function will perform final validation on all the data.
        * If anything fails, then the script will terminate with an LSL-friendly error message.
        *
        * @param string &$str Reference to a string object which the response should be rendered to.
        * @return void
        * @access public
        */
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
        
        /**
        * Outputs the response directly to the HTTP response.
        *
        * @access public
        * @return void
        * @uses SloodleLSLResponse::render_to_string() Outputs the result from this function directly to the HTTP response stream.
        */
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
        // If $static is TRUE (default) then this will be treated as a static call, and a new response object will be used
        // If $static is FALSE then this is treated as adding data to an existing response object
        /**
        * Quick output of data to avoid several accessor calls if the response is very basic.
        * Can be called statically to allow simple output of basic data.
        * The status code is required, but the other parameters are optional
        * If an error occurs, the LSL-friendly error message is output to the HTTP response, and the script terminated
        *
        * @param int $status_code The status code for the response (required)
        * @param string $status_descriptor The status descriptor for the response (optional - ignored if NULL)
        * @param mixed $data The data for the response, which can be a scalar, or a mixed array of scalars/scalar-arrays (see {@link SloodleLSLResponse::$data}) (optional - ignored if NULL)
        * @param bool $static If TRUE (default), then this function will assume it is being call statically, and construct its own response object. Otherwise, it will all the existing member data to render the output.
        * @return void
        * @access public
        */
        function quick_output($status_code, $status_descriptor = NULL, $data = NULL, $static = TRUE)
        {
            // Is this s static call?
            if ($static) {
                // Construct and render the output of a response object
                $response = new SloodleLSLResponse($status_code, $status_descriptor, $data);
                $response->render_to_output();
            } else {
                // Set all our data
                $this->status_code = $status_code;
                if ($status_descriptor != NULL) $this->status_descriptor = $status_descriptor;
                if ($data != NULL) $this->add_data_line($data);
                // Output it
                $this->render_to_output();
            }
        }
    
        
        /**
        * Internal function to report a data validation error.
        * Outputs an LSL-friendly error message, and terminates the script
        *
        * @param string $msg The error message to output.
        * @return void
        * @access private
        */
        function _internal_validation_error($msg)
        {
            exit("-104".SLOODLE_FIELD_SEPARATOR."SYSTEM".SLOODLE_LINE_SEPARATOR.$msg);
        }
    }
    
    
    /**
    * Obtains a named HTTP request parameter, and terminates script with an error message if it was not provided.
    * This is a 'Sloodle-friendly' version of the Moodle "required_param" function.
    * Instead of terminate the script with an HTML-formatted error message, it will terminate with a message
    *  which conforms for the {@link http://slisweb.sjsu.edu/sl/index.php/Sloodle_communications_specification Sloodle communications specification},
    *  making it suitable for use in {@link http://slisweb.sjsu.edu/sl/index.php/Linker_Script linker scripts}.
    *
    * @param string $parname Name of the HTTP request parameter to fetch.
    * @param int $type Type of parameter expected, such as "PARAM_RAW". See Moodle documentation for a complete list.
    * @return mixed The appropriately parsed and/or cleaned parameter value, if it was found.
    */
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
    /**
    * Handles incoming HTTP requests, typically from LSL scripts.
    * This class will perform much of the complex and repetitive processing required for handling HTTP requests.
    *
    * @uses SloodleLSLResponse Outputs error messages in appropriate format if an error occurs.
    * @uses SloodleUser Stores and processes user data incoming from an HTTP request
    * @package sloodle
    */
    class SloodleLSLRequest
    {
      ///// DATA /////
      // WARNING: all data should be treated as PRIVATE (even though PHP4 does not recognise this concept)
      
        /**
        * Indicates whether or not the request data has been processed by the {@link process_request_data()} function.
        * @var bool
        * @see SloodleLSLRequest::process_request_data()
        * @access private
        */
        var $request_data_processed = FALSE;
        
        /**
        * Contains the password specified in HTTP request parameters (or NULL if not specified).
        * @see SloodleLSLRequest::authenticate_request()
        * @access private
        */
        var $password = NULL;
        
        /**
        * Indicates the status of request authentication.
        * @see SloodleLSLRequest::authenticate_request()
        * @see SLOODLE_AUTH_OK
        * @see SLOODLE_AUTH_UNKNOWN
        * @see SLOODLE_AUTH_FAILED
        * @var int
        * @access private
        */
        var $auth_status = SLOODLE_AUTH_UNKNOWN;
        
        /**
        * A 3-element array containing the LoginZone position vector specified in HTTP request parameters (or NULL if not specified).
        * @access private
        */
        var $login_zone_pos = NULL;
        
        /**
        * Integer ID of the course specified in the request (or NULL if not specified).
        * @access private
        */
        var $course_id = NULL;
        
        /**
        * Integer ID of the course module instance specified in the request (or NULL if not specified).
        * @access private
        */
        var $module_id = NULL;

        /**
        * String UUID of the SL user agent specified in the request (or NULL if not specified).
        * @access private
        */
        var $avatar_uuid = NULL;
        
        /**
        * String name of the SL agent specified in the request (or NULL if not specified).
        * @access private
        */
        var $avatar_name = NULL;
        
        /**
        * String containing the login security token specified in the request (or NULL if not specified).
        * @access private
        */
        var $login_security_token = NULL;
        
        
    /// References to potentially external objects ///
        
        /**
        * The object used to output response data.
        * The object reference may be provided externally in the parameters of the constructor ({@link SloodleLSLRequest()}) or via the ({@link set_response()}) accessor.
        * Otherwise, it will have been instantiated by this object itself.
        * @var SloodleLSLResponse
        * @see SloodleLSLRequest::set_response()
        * @see SloodleLSLRequest::get_response()
        * @access private
        */
        var $response = NULL;

        /**
        * The object used to store and process incoming user data.
        * The object reference may be provided externally in the parameters of the constructor ({@link SloodleLSLRequest()}) or via the ({@link set_user()}) accessor.
        * Otherwise, it will have been instantiated by this object itself.
        * @var SloodleUser
        * @see SloodleLSLRequest::set_user()
        * @see SloodleLSLRequest::get_user()
        * @access private
        */
        var $user = NULL;
                
        
      ///// ACCESSORS /////
    
        /**
        * Returns member variable {@link $request_data_processed}.
        * @return bool TRUE if the request data has been processed, or FALSE if not
        * @see SloodleLSLRequest::process_request_data()
        */
        function is_request_data_processed()
        {
            return $this->request_data_processed;
        }
        
        /**
        * Returns member variable {@link $auth_status}.
        * @return int One of: {@link SLOODLE_AUTH_OK}, {@link SLOODLE_AUTH_UNKNOWN}, or {@link SLOODLE_AUTH_FAILED}.
        * @see SloodleLSLRequest::authenticate_request()
        */
        function get_auth_status()
        {
            return $this->auth_status;
        }

        /**
        * Indicates whether or not request authentication has succeeded.
        * @return bool TRUE if the request data has been authenticated, or FALSE if it failed or has not been attempted yet
        * @see SloodleLSLRequest::authenticate_request()
        * @see SloodleLSLRequest::$auth_status
        */
        function is_authenticated()
        {
            return ($this->auth_status == SLOODLE_AUTH_OK);
        }
        
        /**
        * Indicates whether or not request authentication has failed.
        * @return bool TRUE if the request data has failed authentication, or FALSE if it passed or has not been attempted yet
        * @see SloodleLSLRequest::authenticate_request()
        * @see SloodleLSLRequest::$auth_status
        */
        function is_auth_failed()
        {
            return ($this->auth_status == SLOODLE_AUTH_FAILED);
        }
        
        /**
        * Sets the {@link SloodleLSLRequest::$response} member.
        * @param SloodleLSLResponse $response Reference to a {@link SloodleLSLResponse} object
        */
        function set_response(&$response)
        {
            $this->response = &$response;
        }

        /**
        * Sets the {@link SloodleLSLRequest::$user} member.
        * @param SloodleUser $user Reference to a {@link SloodleUser} object
        */
        function set_user(&$user)
        {
            $this->user = &$user;
        }
    
        
      // NOTE: These accessors will force the request data to be processed if it hasn't already been processed

        /**
        * Returns member value {@link $password}.
        * Note: this function will ensure that {@link process_request_data()} has already been called prior to execution.
        * @return string|null The password provided in the request parameters, or NULL if there wasn't one
        */
        function get_password()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->password;
        }
        
        /**
        * Returns member value {@link $course_id}.
        * Note: this function will ensure that {@link process_request_data()} has already been called prior to execution.
        * @return integer|null The course ID provided in the request parameters, or NULL if there wasn't one
        */
        function get_course_id()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->course_id;
        }
        
        /**
        * Returns member value {@link $module_id}.
        * Note: this function will ensure that {@link process_request_data()} has already been called prior to execution.
        * @return integer|null The course module instance ID provided in the request parameters, or NULL if there wasn't one
        */
        function get_module_id()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->module_id;
        }
        
        /**
        * Returns member value {@link $avatar_uuid}.
        * Note: this function will ensure that {@link process_request_data()} has already been called prior to execution.
        * @return string|null The avatar UUID provided in the request parameters, or NULL if there wasn't one
        */
        function get_avatar_uuid()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->avatar_uuid;
        }
        
        /**
        * Returns member value {@link $avatar_name}.
        * Note: this function will ensure that {@link process_request_data()} has already been called prior to execution.
        * @return string|null The avatar name provided in the request parameters, or NULL if there wasn't one
        */
        function get_avatar_name()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->avatar_name;
        }
        
        /**
        * Returns member value {@link $login_security_token}.
        * Note: this function will ensure that {@link process_request_data()} has already been called prior to execution.
        * @return string|null The login security token provided in the request parameters, or NULL if there wasn't one
        */
        function get_login_security_token()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->login_security_token;
        }
        
        /**
        * Returns member value {@link $response}.
        * Note: this function will ensure that {@link process_request_data()} has already been called prior to execution.
        * @return &SloodleLSLResponse A reference to the response object used by this object
        */
        function get_response()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->response;
        }
        
        /**
        * Returns member value {@link $user}.
        * Note: this function will ensure that {@link process_request_data()} has already been called prior to execution.
        * @return &SloodleUser A reference to the user object used by this object
        */
        function get_user()
        {
            // Ensure the request data has been processed
            $this->process_request_data();
            return $this->user;
        }
        
        
      ///// FUNCTIONS /////
      
        /**
        * <i>Constructor</i> - initialises the user and response objects.
        * If the parameters provide references to appropriate objects, then the constructor will store them.
        * However, if the parameters are NULL, then the constructor will create its own instances.
        *
        * @param SloodleLSLResponse $response Reference to the response object which this object should use, or NULL
        * @param SloodleUser $user Refernce to the user object which this object should use, or NULL
        */
        function SloodleLSLRequest(&$response, &$user)
        {
            // Store or instantiate our response object
            if (is_object($response)) $this->response = &$response;
            else $this->response = new SloodleLSLResponse();
            
            // Store or instantiate our user object
            if (is_object($user)) $this->user = &$user;
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
        /**
        * Process all of the standard data provided by the HTTP request.
        *
        * This function should normally be called very shortly after the start of a script, as it will
        *  check all the expected HTTP request parameters, perform basic processing, and store the data.
        * It will also send data to the appropriate {@link SloodleLSLResposne} and {@link SloodleUser} objects.
        * When called, it will check the {@link $request_data_processed} member to see if it has already been called.
        * If so, it will not execute again, unless the $force parameter is TRUE.
        * <b>Note:</b> most functions in this class will automatically call this function if needed to ensure data is available.
        *
        * @param bool $force If TRUE, then the function is forced to execute again, even if it has already been executed. (Default: FALSE).
        */
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
      
        /**
        * Authenticates the request against the site using the password parameter.
        * This function can use both site-wide or object-specific prim passwords (the latter of which
        *  uses the 'sloodle_active_object' table in the database).
        * It will also set the content of member {$link $auth_status} as appropriate.
        *
        * @param bool $require If TRUE, the function will NOT return on authentication failure. Rather, it will terminate the script with an error message.
        * @return bool TRUE if successful in authenticating the request, or FALSE if not.
        */
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
        
        /**
        * Gets a database record for the course identified in the request.
        * (Note: this function does not check whether or not the user is enrolled in the course)
        *
        * @param bool $require If TRUE, the function will NOT return failure. Rather, it will terminate the script with an error message.
        * @return object A record directly from the database, or NULL if the course is not found.
        */
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
        
        /**
        * Get a course module instance for the module specified in the request
        * Uses the ID specified in {@link $module_id}.
        *
        * @param string $type specifies the name of the module type (e.g. 'forum', 'choice' etc.) - ignored if blank (default).
        * @param bool $require If TRUE, the function will NOT return failure. Rather, it will terminate the script with an error message.
        * @return object A database record if successful, or FALSE if not (e.g. if instance is not found, is not visible, or is not of the correct type)
        */
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
    
        /**
        * Obtains a named HTTP request parameter, and terminate with an error message if it has not been provided.
        * Note: for LSL linker scripts, this should *always* be used instead of the Moodle function, as this will
        *  render appropraitely formatted error messages, which LSL scripts can understand.
        *
        * @param string $parname The name of the HTTP request parameter to get.
        * @param int $type Specifies the expected type of parameter, as according to the Moodle documentation.
        * @return mixed The converted and cleaned parameter if it is found
        */
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