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


// Note: if the "sloodle_required_param" function is to be used,
//  then the Sloodle configuration file should have previously been included.

// Define the separators used in batches of data
define("SLOODLE_LINE_SEPARATOR", "\n");
define("SLOODLE_FIELD_SEPARATOR", "|");


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
    
    // Add one or moe side effect codes to the existing list
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
        SloodleLSLResponse::quick_output(-103, "SYSTEM", "Expected request parameter '$parname'.");
        exit();
    }
    
    return $par;
}


?>