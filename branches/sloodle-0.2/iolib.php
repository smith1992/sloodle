<?php
// Sloodle input/output library
// Provides generalised input/output functionality for interacting with in-world LSL
// See www.sloodle.org for more information

// Note: this library expects that the core Moodle libraries are already included
// Specifically, the "optional_param" function


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
    
    
  ///// FUNCTIONS /////
  
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
  
    // Simple function to add a line of data
    // Parameter can be an array of strings (or other variables), or a variable itself
    // This is the recommended way to add lines of data, as it can perform error-checking
    function add_data_line($val)
    {
        // TODO: add error-checking
        $this->data[] = $val;
    }
    
    // Render the message to a string
    // Parameter $str is the string to which the message is rendered (by reference)
    // Returns boolean TRUE if successful
    // Returns an array of error messages otherwise
    function render_to_string(&$str)
    {
        // Clear the string
        $str = "";
        // Track any errors which may occur
        $error_msg = array();
        
        // We can omit any unnecessary items of data, but the number of field-separators must be correct
        // E.g. if item 4 is specified, but items 2 and 3 are not, then empty field-separators must be output as if items 2 and 3 were present, e.g.:
        // 1|||AVATAR_LIST
        // (where the pipe-character | is the field separator)
        
        // We will step backwards through out list of fields, and as soon as one item is specified, all of them should be
        $showall = FALSE;
        // Make sure that if the page number is specified, that the total is as well
        if (is_null($this->page_number) xor is_null($this->page_total)) {
            $error_msg[] = "You must either specify both \"page_total\" *and* \"page_number\", or specify neither.";
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
            $error_msg[] = "You must specify a status code.";
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
        
        
        // Finished. Were any errors reported?
        if (count($error_msg) > 0) {
            return $error_msg;
        }
        
        // No problems!
        return TRUE;
    }
    
    // Output the message to the HTTP response body
    // Returns boolean TRUE if successful
    // Returns an array of error messages otherwise
    function render_to_output()
    {
        // Attempt to render the output to a string
        $str = "";
        $result = $this->render_to_string($str);
        if ($result === TRUE) {
            // Successful rendering - output the message to the HTTP response
            echo $str;
            return TRUE;
        }
        
        // If we reached here, then something went wrong
        return $result;
    }
    
    
    // Quick-output
    // Can be called statically to allow simple output of basic data
    // The status code is required, but the other parameters are optional
    // Returns true if successful, or false if not (no error information is reported)
    function quick_output($status_code, $status_descriptor = NULL, $data = NULL)
    {
        // Construct and render the output of a response object
        $response = new SloodleLSLResponse($status_code, $status_descriptor, $data);
        return ($response->render_to_output() === TRUE);
    }
    
}


// Obtain a named request parameter, and terminate with an error message if it has not been provided
// Note: for LSL linker scripts, this should *always* be used instead of the Moodle function, as this will
//  render appropraitely formatted error messages, which LSL scripts can understand.
// Returns the parameter if it is found
function sloodle_required_param($parname, $type /*= PARAM_CLEAN*/)
{
    // Attempt to get the parameter
    $par = optional_param($parname, NULL, $type);
    // Was it provided?
    if (is_null($par)) {
        // No - report the error
        SloodleLSLResponse::quick_output(-103, "SYSTEM", "Expected request parameter ($parname).");
        exit();
    }
    
    return $par;
}

?>