<?php
    
    /**
    * Sloodle general library.
    *
    * Provides various utility functionality for general Sloodle purposes.
    *
    * @package sloodle
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    // This library expects that the Sloodle config file has already been included
    //  (along with the Moodle libraries)
    
    /** Include our email functionality. */
    require_once(SLOODLE_LIBROOT.'/mail.php');
    
    
    /**
    * Sets a Sloodle configuration value.
    * This data will be stored in Moodle's "config" table, so it will persist even after Sloodle is uninstalled.
    * After being set, it will be available (read-only) as a named member of Moodle's $CFG variable.
    * <b>NOTE:</b> in Sloodle debug mode, this function will terminate the script with an error if the name is not prefixed with "sloodle_".
    * @param string $name The name of the value to be stored (should be prefixed with "sloodle_")
    * @param string $value The string representation of the value to be stored
    * @return bool True on success, or false on failure (may fail if database query encountered an error)
    * @see sloodle_get_config()
    */
    function sloodle_set_config($name, $value)
    {
        // If in debug mode, ensure the name is prefixed appropriately for Sloodle
        if (defined('SLOODLE_DEBUG') && SLOODLE_DEBUG) {
            if (substr_count($name, 'sloodle_') < 1) {
                exit ("ERROR: sloodle_set_config(..) called with invalid value name \"$name\". Expected \"sloodle_\" prefix.");
            }
        }
        // Use the standard Moodle config function, ignoring the 3rd parameter ("plugin", which defaults to NULL)
        return set_config(strtolower($name), $value);
	}

    /**
    * Gets a Sloodle configuration value from Moodle's "config" table.
    * This function does not necessarily need to be used.
    * All configuration data is available as named members of Moodle's $CFG global variable.
    * <b>NOTE:</b> in Sloodle debug mode, this function will terminate the script with an error if the name is not prefixed with "sloodle_".
    * @param string $name The name of the value to be stored (should be prefixed with "sloodle_")
    * @return mixed A string containing the configuration value, or false if the query failed (e.g. if the named value didn't exist)
    * @see sloodle_set_config()
    */
	function sloodle_get_config($name)
    {
        // If in debug mode, ensure the name is prefixed appropriately for Sloodle
        if (defined('SLOODLE_DEBUG') && SLOODLE_DEBUG) {
            if (substr_count($name, 'sloodle_') < 1) {
                exit ("ERROR: sloodle_get_config(..) called with invalid value name \"$name\". Expected \"sloodle_\" prefix.");
            }
        }
        // Use the Moodle config function, ignoring the plugin parameter
        $val = get_config(NULL, strtolower($name));
        // Older Moodle versions return a database record object instead of the value itself
        // Workaround:
        if (is_object($val)) return $val->value;
        return $val;
	}
    
    /**
    * Determines whether or not the global module configuration allows teachers to edit Sloodle user data.
    * @return bool
    */
    function sloodle_teachers_can_edit_user_data()
    {
        return (bool)sloodle_get_config('sloodle_allow_user_edit_by_teachers');
    }
    
    /**
    * Determines whether or not auto-registration is allowed for the site.
    * 
    * @return bool True if auto-reg is allowed on the site, or false otherwise.
    */
    function sloodle_autoreg_enabled_site()
    {
        return (bool)sloodle_get_config('sloodle_allow_autoreg');
    }
    
    /**
    * Determines whether or not auto-registration is enabled on a particular course.
    * If auto-reg is disallowed for the site, then it is not allowed on any course.
    *
    * @param int $courseid Integer ID of the course to check. If <= 0 then the current $COURSE global is used.
    * @return bool True if auto-reg is allowed and enabled for the course, or false otherwise.
    */
    function sloodle_autoreg_enabled_course($courseid = 0)
    {
        // If the site disallows it, then the answer is always no
        if (!sloodle_autoreg_enabled_site()) return false;
        
        // Do we have a course ID specified?
        if ($courseid <= 0) {
            global $COURSE;
            if (empty($COURSE) || empty($COURSE->id)) return false;
            $courseid = $COURSE->id;
        }
        
        // Get the course data from Sloodle
        $sloodlecoursedata = get_record('sloodle_course_data', 'courseid', $courseid);
        // If we have no Sloodle data for the course, then by default auto-registration is not enabled
        if (!$sloodlecoursedata) return false;
        
        // Is autoreg enabled?
        return ($sloodlecoursedata->autoreg != 0);
    }
    
    /**
    * Sends an XMLRPC message into Second Life.
    * @param string $channel A string containing a UUID identifying the XMLRPC channel in SL to be used
    * @param int $intval An integer value to be sent in the message
    * @param string $strval A string value to be sent in the message
    * @return bool True if successful, or false if an error occurs
    */
    function sloodle_send_xmlrpc_message($channel,$intval,$strval)
    {
        // Include our XMLRPC library
        require_once(SLOODLE_DIRROOT.'/lib/xmlrpc.inc');
        // Instantiate a new client object for communicating with Second Life
        $client = new xmlrpc_client("http://xmlrpc.secondlife.com/cgi-bin/xmlrpc.cgi");
        // Construct the content of the RPC
        $content = '<?xml version="1.0"?><methodCall><methodName>llRemoteData</methodName><params><param><value><struct><member><name>Channel</name><value><string>'.$channel.'</string></value></member><member><name>IntValue</name><value><int>'.$intval.'</int></value></member><member><name>StringValue</name><value><string>'.$strval.'</string></value></member></struct></value></param></params></methodCall>';
        
        // Attempt to send the data via http
        $response = $client->send(
            $content,
            60,
            'http'
        );
        
        //var_dump($response); // Debug output
        // Make sure we got a response value
        if (!isset($response->val) || empty($response->val) || is_null($response->val)) {
            // Report an error if we are in debug mode
            if (defined('SLOODLE_DEBUG') && SLOODLE_DEBUG) {
                print '<p align="left">Not getting the expected XMLRPC response. Is Second Life broken again?<br/>';
                if (isset($response->errstr)) print "XMLRPC Error - ".$response->errstr;
                print '</p>';
            }
            return FALSE;
        }
        
        // Check the contents of the response value
        //if (defined('SLOODLE_DEBUG') && SLOODLE_DEBUG) {
        //    print_r($response->val);
        //}
        
        //TODO: Check the details of the response to see if this was successful or not...
        return TRUE;
    
    }

    /**
    * Old logging function
    * @todo <b>May require update?</b>
    */
    function sloodle_add_to_log($courseid = null, $module = null, $action = null, $url = null, $cmid = null, $info = null)
    {

       global $CFG;

       // TODO: Make sure we set this in the calling function, then remove this bit
       if ($courseid == null) {
          $courseid = optional_param('sloodle_courseid',0,PARAM_RAW);
       }

       // if no action is specified, use the object name
       if ($action == null) {
          $action = $_SERVER['X-SecondLife-Object-Name'];
       }

       $region = $_SERVER['X-SecondLife-Region'];
       if ($info == null) {
          $info = $region;
       }

       $slurl = '';
       if (preg_match('/^(.*)\(.*?\)$/',$region,$matches)) { // strip the coordinates, eg. Cicero (123,123)
          $region = $matches[1];
       }

       $xyz = $_SERVER['X-SecondLife-Local-Position'];
       if (preg_match('/^\((.*?),(.*?),(.*?)\)$/',$xyz,$matches)) {
          $xyz = $matches[1].'/'.$matches[2].'/'.$matches[3];
       }

       return add_to_log($courseid, null, $action, $CFG->wwwroot.'/mod/sloodle/toslurl.php?region='.urlencode($region).'&xyz='.$xyz, $userid, $info );
       //return add_to_log($courseid, null, "ok", "ok", $userid, "ok");

    }

    /**
    * Determines whether or not Sloodle is installed.
    * Queries Moodle's modules table for a Sloodle entry.
    * <b>NOTE:</b> does not check for the presence of the Sloodle files.
    * @return bool True if Sloodle is installed, or false otherwise.
    */
    function sloodle_is_installed()
    {
        // Is there a Sloodle entry in the modules table?
        return record_exists('modules', 'name', 'sloodle');
    }
    
    /**
    * Generates a random login security token.
    * Uses mixed-case letters and numbers to generate a random 16-character string.
    * @return string
    * @see sloodle_random_web_password()
    */
    function sloodle_random_security_token()
    {
        // Define the characters we can use in our token, and get the length of it
        $str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $strlen = strlen($str) - 1;
        // Prepare the token variable
        $token = '';
        // Loop once for each output character
        for($length = 0; $length < 16; $length++) {
            // Shuffle the string, then pick and store a random character
            $str = str_shuffle($str);
            $char = mt_rand(0, $strlen);
            $token .= $str[$char];
        }
        
        return $token;
    }
    
    /**
    * Generates a random web password
    * Uses mixed-case letters and numbers to generate a random 8-character string.
    * @return string
    * @see sloodle_random_security_token()
    */
    function sloodle_random_web_password()
    {
        // Define the characters we can use in our token, and get the length of it
        $str = "0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ";
        $strlen = strlen($str) - 1;
        // Prepare the password string
        $pwd = '';
        // Loop once for each output character
        for($length = 0; $length < 8; $length++) {
            // Shuffle the string, then pick and store a random character
            $str = str_shuffle($str);
            $char = mt_rand(0, $strlen);
            $pwd .= $str[$char];
        }
        
        return $pwd;
    }
    
    /**
    * Generates a random prim password (7 to 9 digit number).
    * @return string The password as a string
    */
    function sloodle_random_prim_password()
    {
        return (string)mt_rand(1000000, 999999999);
    }
    
    /**
    * Converts a string vector to an array vector.
    * String vector should be of format "<x,y,z>".
    * Converts to associative array with members 'x', 'y', and 'z'.
    * Returns false if input parameter was not of correct format.
    * @param string $vector A string vector of format "<x,y,z>".
    * @return mixed
    * @see sloodle_array_to_vector()
    * @see sloodle_round_vector()
    */
    function sloodle_vector_to_array($vector)
    {
        if (preg_match('/<(.*?),(.*?),(.*?)>/',$vector,$vectorbits)) {
            $arr = array();
            $arr['x'] = $vectorbits[1];
            $arr['y'] = $vectorbits[2];
            $arr['z'] = $vectorbits[3];
            return $arr;
        }
        return false;
    }
    
    /**
    * Converts an array vector to a string vector.
    * Array vector should be associative, containing elements 'x', 'y', and 'z'.
    * Converts to a string vector of format "<x,y,z>".
    * @return string
    * @see sloodle_vector_to_array()
    * @see sloodle_round_vector()
    */
    function sloodle_array_to_vector($arr)
    {
        $ret = '<'.$arr['x'].','.$arr['y'].','.$arr['z'].'>';
        return $ret;
    }
    
    /**
    * Obtains the identified course module instance database record.
    * @param int $id The integer ID of a course module instance
    * @return mixed  A database record if successful, or false if it could not be found
    */
    function sloodle_get_course_module_instance($id)
    {
        return get_record('course_modules', 'id', $id);
    }
    
    /**
    * Determines whether or not the specified course module instance is visible.
    * Checks that the instance itself and the course section are both valid.
    * @param int $id The integer ID of a course module instance.
    * @return bool True if visible, or false if invisible or not found
    */
    function sloodle_is_course_module_instance_visible($id)
    {
        // Get the course module instance record, whether directly from the parameter, or from the database
        if (is_object($id)) {
            $course_module_instance = $id;
        } else if (is_int($id)) {
            if (!($course_module_instance = get_record('course_modules', 'id', $id))) return FALSE;
        } else return FALSE;
        
        // Make sure the instance itself is visible
        if ((int)$course_module_instance->visible == 0) return FALSE;
        // Find out which section it is in, and if that section is valid
        if (!($section = get_record('course_sections', 'id', $course_module_instance->section))) return FALSE;
        if ((int)$section->visible == 0) return FALSE;
        
        // Looks like the module is visible
        return TRUE;
    }
    
    /**
    * Determines if the specified course module instance is of the named type.
    * For example, this can check if a particular instance is a "forum" or a "chat".
    * @param int $id The integer ID of a course module instance
    * @param string $module_name Module type to check (must be the exact name of an installed module, e.g. 'sloodle' or 'quiz')
    * @return bool True if the module is of the specified type, or false otherwise
    */
    function sloodle_check_course_module_instance_type($id, $module_name)
    {
        // Get the record for the module type
        if (!($module_record = get_record('modules', 'name', $module_name))) return FALSE;

        // Get the course module instance record, whether directly from the parameter, or from the database
        if (is_object($id)) {
            $course_module_instance = $id;
        } else if (is_int($id)) {
            if (!($course_module_instance = get_record('course_modules', 'id', $id))) return FALSE;
        } else return FALSE;
        
        // Check the type of the instance
        return ($course_module_instance->module == $module_record->id);
    }
    
    /**
    * Obtains the ID number of the specified module (type not instance).
    * @param string $name The name of the module type to check, e.g. 'sloodle' or 'forum'
    * @return mixed Integer containing module ID, or false if it is not installed
    */
    function sloodle_get_module_id($name)
    {
        // Ensure the name is a non-empty string
        if (!is_string($name) || empty($name)) return FALSE;
        // Obtain the module record
        if (!($module_record = get_record('modules', 'name', $module_name))) return FALSE;
        
        return $module_record->id;
    }
    
    /**
    * Checks if the specified position is in the current (site-wide) loginzone.
    * @param mixed $pos A string vector or an associated array vector
    * @return bool True if position is in LoginZone, or false if not
    * @see sloodle_login_zone_coordinates()
    */
    function sloodle_position_is_in_login_zone($pos)
    {
        // Get a position array from the parameter
        $posarr = NULL;
        if (is_array($pos) && count($pos) == 3) {
            $posarr = $pos;
        } else if (is_string($pos)) {
            $posarr = sloodle_vector_to_array($pos);
        } else {
            return FALSE;
        }
        // Fetch the loginzone boundaries
        list($maxarr,$minarr) = sloodle_login_zone_coordinates();

        // Make sure the position is not past the maximum bounds
        if ( ($posarr['x'] > $maxarr['x']) || ($posarr['y'] > $maxarr['y']) || ($posarr['z'] > $maxarr['z']) ) {
            return FALSE;
        }
        // Make sure the position is not past the minimum bounds
        if ( ($posarr['x'] < $minarr['x']) || ($posarr['y'] < $minarr['y']) || ($posarr['z'] < $minarr['z']) ) {
            return FALSE;
        }

        return TRUE;
    }
    
    /**
    * Generates teleport coordinates for a user who has already finished the LoginZone process.
    * @param string $pos A string vector giving the position of the LoginZone
    * @param string $size A string vector giving the size of the LoginZone
    * @return array, bool An associative array vector containing a teleport location, or false if the operation fails.
    */
    function sloodle_finished_login_coordinates($pos, $size)
    {
        // Make sure the parameters are valid types
        if (!is_string($pos) || !is_string($size)) {
            return FALSE;
        }
        // Convert both to arrays
        $posarr = sloodle_vector_to_array($pos);
        $sizearr = sloodle_vector_to_array($size);
        // Calculate a position just below the loginzone
        $coord = array();
        $coord['x'] = round($posarr['x'],0);
        $coord['y'] = round($posarr['y'],0);
        $coord['z'] = round(($posarr['z']-(($sizearr['z'])/2)-2),0);
        return $coord;
    }
    
    /**
    * Generates a random position within the specified cubic zone.
    * @param array $zonemax Associative array vector specifying the maximum boundary of the cubic zone
    * @param array $zonemin Associative array vector specifying the minimum boundary of the cubic zone
    * @return array An associative vector array
    */
    function sloodle_random_position_in_zone($zonemax,$zonemin)
    {
        $pos = array();
        $pos['x'] = rand($zonemin['x'],$zonemax['x']);	
        $pos['y'] = rand($zonemin['y'],$zonemax['y']);	
        $pos['z'] = rand($zonemin['z'],$zonemax['z']);
        return $pos;
    }

    // Round the specified 3d vector to integer values
    // $pos should be a vector string "<x,y,z>" or an associative array {x,y,z}
    // Return is the same as the type passed-in
    // If the input type is unrecognised, it simply returns it back out unchanged
    /**
    * Rounds the specified 3d vector integer values.
    * Can handle/return a string vector, or an array vector.
    * (Output type matches input type).
    * @param mixed $pos Either a string vector or an array vector
    * @return mixed
    */
    function sloodle_round_vector($pos)
    {
        // We will work with an array, but allow for conversion to/from string
        $arrayvec = $pos;
        $returnstring = FALSE;
        // Is it a string?
        if (is_string($pos)) {
            $arrayvec = sloodle_vector_to_array($pos);
            $returnstring = TRUE;
        } else if (!is_array($pos)) {
            return $pos;
        }
    
        // Construct an output array
        $output = array();
        foreach ($arrayvec as $key => $val) {
            $output[$key] = round($val, 0);
        }
        
        // If we need to convert it back to a string, then do so
        if ($returnstring) {
            return sloodle_array_to_vector($output);
        }
        
        return $output;
    }
    
    /**
    * Calculates the maximum and minimum bounds of the specified LoginZone
    * Returns the bounds as a numeric array of two associate array vectors: ($max, $min).
    * (Or returns false if no LoginZone position/size could be found in the Moodle configuration table).
    * @param string $pos A string vector giving the position of the LoginZone
    * @param string $size A string vector giving the size of the LoginZone
    * @return array
    */
    function sloodle_login_zone_bounds($pos, $size)
    {
        // Make sure the parameters are valid types
        if (($pos == FALSE) || ($size == FALSE)) {
            return FALSE;
        }
        // Convert both to arrays
        $posarr = sloodle_vector_to_array($pos);
        $sizearr = sloodle_vector_to_array($size);
        // Calculate the bounds
        $max = array();
        $max['x'] = $posarr['x']+(($sizearr['x'])/2)-2;
        $max['y'] = $posarr['y']+(($sizearr['y'])/2)-2;
        $max['z'] = $posarr['z']+(($sizearr['z'])/2)-2;
        $min = array();
        $min['x'] = $posarr['x']-(($sizearr['x'])/2)+2;
        $min['y'] = $posarr['y']-(($sizearr['y'])/2)+2;
        $min['z'] = $posarr['z']-(($sizearr['z'])/2)+2;
        
        return array($max,$min);
    }
    
    
    /**
    * Checks if the given prim password is valid.
    * @param string $password The password string to check
    * @return bool True if it is valid, or false otherwise.
    */
    function sloodle_validate_prim_password($password)
    {
        // Check that it's a string
        if (!is_string($password)) return false;
        // Check the length
        $len = strlen($password);
        if ($len < 5 || $len > 9) return false;
        // Check that it's all numbers
        if (!ctype_digit($password)) return false;
        // Check that it doesn't start with a 0
        if ($password[0] == '0') return false;
        
        // It all seems fine
        return true;
    }
    
    /**
    * Checks if the given prim password is valid, and provides feedback.
    * An array is written to by reference, each element containing error codes.
    * Each error code is a word. The full text of the error message may be obtained
    *  from the string file by looking for "primpass:errorcode".
    *
    * @param string $password The password to validate
    * @param array &$errors An array (passed by reference) which will contain any error messages
    * @return bool True if the prim password is valid, or false otherwise
    */
    function sloodle_validate_prim_password_verbose($password, &$errors)
    {
        // Initialise variables
        $errors = array();
        $result = true;
        
        // Check that it's a string
        if (!is_string($password)) {
            $errors[] = 'invalidtype';
            $result = false;
        }
        // Check the length
        $len = strlen($password);
        if ($len < 5) {
            $errors[] = 'tooshort';
            $result = false;
        }
        if ($len > 9) {
            $errors[] = 'toolong';
            $result = false;
        }
        
        // Check that it's all numbers
        if (!ctype_digit($password)) {
            $errors[] = 'numonly';
            $result = false;
        }
        
        // Check that it doesn't start with a 0
        if ($password[0] == '0') {
            $errors[] = 'leadingzero';
            $result = false;
        }
        
        return $result;
    }
    
    
    /**
    * Stores a pending login notification for an auto-registered user.
    * A cron job will process the pending notification queue.
    * @param string $destination Identifies the destination of the notification (for SL, this will be the object UUID. The send function will construct the email address)
    * @param string $avatar Identifier for the avatar being notified
    * @param string $username The username to notify the user of
    * @param string $password The (plaintext) password to notify the user of
    * @return bool True if successful, or false otherwise
    */
    function sloodle_pending_login_notification($destination, $avatar, $username, $password)
    {
        return false;
        /*// If another pending notification already exists for the same username, then delete it
        delete_records('pending_login_notifications', 'username', $username);
        
        // Add the new details
        $notification = new stdClass();
        $notification->destination = $destination;
        $notification->username = $username;
        $notification->password = $password;
        return insert_record('pending_login_notifications', $notification);*/
    }
    
    /**
    * Send a login notification.
    * @param string $destination Identifies the destination of the notification (for SL, this will be the object UUID. The target email address will be constructed)
    * @param string $avatar Identifier for the avatar being notified
    * @param string $username The username to notify the user of
    * @param string $password The (plaintext) password to notify the user of
    * @return bool True if successful, or false otherwise
    */
    function sloodle_send_login_notification($destination, $avatar, $username, $password)
    {
        sloodle_text_email_sl($destination, 'SLOODLE_LOGIN', "$avatar|{$CFG->wwwroot}|$username|$password");
    }
    
    

?>
