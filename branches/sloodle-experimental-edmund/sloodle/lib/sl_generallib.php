<?php
    // Sloodle general library
    // Provides functionality various utility functions for a variety of purposes
    // Part of the Sloodle project (www.sloodle.org)
    //
    // Copyright (c) Sloodle 2007
    // Released under the GNU GPL
    //
    // Contributors:
    //  Edmund Edgar - several original functions
    //  Peter R. Bloomfield - constructed library file and updated functions
    //
    
    // This library expects that the Sloodle config file has already been included
    //  (along with the Moodle libraries)
    
    
    // Include the IO library
    require_once(SLOODLE_DIRROOT.'/lib/sl_iolib.php');
    
    
    // Set a Sloodle configuration value
    // $name should be a string identifying the configuration value, lower-case, prefixed with "sloodle_"
    //  (typical examples are "sloodle_prim_password" and "sloodle_auth_method")
    // $value should be a string giving the value to be stored
    // This data is persistent, and is stored in Moodle's "config" table (i.e. "mdl_config" for most installations)
    // Returns TRUE if successful, or FALSE on failure
    // See also: sloodle_get_config(..)
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

    // Get a Sloodle configuration value
    // $name should be a string identifying the configuration value, lower-case, prefixed with "sloodle_"
    //  (typical examples are "sloodle_prim_password" and "sloodle_auth_method")
    // Retrieves data from Moodle's "config" table (i.e. "mdl_config" for most installations)
    // Returns a string containing the value found, or FALSE if the named value is not found
    // See also: sloodle_set_config(..)
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
    
    // Get the Sloodle prim password from the configuration table
    // Returns a string containing the prim password, or FALSE if no password has yet been specified
    function sloodle_get_prim_password()
    {
        return sloodle_get_config('sloodle_prim_password');
    }
    
    // Set the Sloodle prim password
    // $password should be a string, between 5 and 9 digits, numerical only, and not starting with a 0
    // Returns TRUE if successful, or FALSE if password is invalid (i.e. not a string) or the database query fails
    // Note: no other validation is done in this function -- that must be done elsewhere
    function sloodle_set_prim_password($password)
    {
        // Make sure it's a string
        if (!is_string($password)) return FALSE;
        return sloodle_set_config('sloodle_prim_password', $password);
    }
    
    function sloodle_prim_password()
    {
	    exit("***** Old sloodle_prim_password() function called from: ".$_SERVER['PHP_SELF']." *****");
	}
    
    // Determine whether or not automatic registration is enabled
    // Returns TRUE if so, or FALSE if no
    function sloodle_is_automatic_registration_on()
    {
        // Get the auth method from the config table
	    $method = sloodle_get_config('sloodle_auth_method');
        // Is it autoreg?
	    return ($method === 'autoregister');
	}
    
    // Get the user authentication method
    // Returns a string containing the name of the authentication method, or FALSE if none has been specified
    function sloodle_get_auth_method()
    {
        return sloodle_get_config('sloodle_auth_method');
    }
    
    // Set the user authentication method
    // Returns TRUE if successful, or FALSE if the auth method is invalid (i.e. not a string) or the database query fails
    // Note: no other validation is performed by this function
    function sloodle_set_auth_method($auth)
    {
        // Make sure it's a string
        if (!is_string($auth)) return FALSE;
        return sloodle_set_config('sloodle_auth_method', $auth);
    }
    
    // Get the position of the loginzone object
    // Returns a vector as a string "<x,y,z>", or FALSE if one was not specified
    function sloodle_get_loginzone_pos()
    {
        return sloodle_get_config('sloodle_loginzone_pos');
    }
    
    // Set the position of the loginzone object
    // $pos should be a 3d vector as a string "<x,y,z>" or an associative array {x,y,z}
    // Returns TRUE if successful, or FALSE on failure
    function sloodle_set_loginzone_pos($pos)
    {
        // If it's an array, make it a string
        if (is_array($pos)) $pos = sloodle_array_to_vector($pos);
        else if (!is_string($pos)) return FALSE;
        return sloodle_set_config('sloodle_loginzone_pos', $pos);
    }
    
    // Get the size of the loginzone object
    // Returns a vector as a string "<x,y,z>", or FALSE if one was not specified
    function sloodle_get_loginzone_size()
    {
        return sloodle_get_config('sloodle_loginzone_size');
    }
    
    // Set the size of the loginzone object
    // $size should be a 3d vector as a string "<x,y,z>" or an associative array {x,y,z}
    // Returns TRUE if successful, or FALSE on failure
    function sloodle_set_loginzone_size($size)
    {
        // If it's an array, make it a string
        if (is_array($size)) $size = sloodle_array_to_vector($size);
        else if (!is_string($size)) return FALSE;
        return sloodle_set_config('sloodle_loginzone_size', $size);
    }
    
    // Get the region where the loginzone object was rezzed
    // Returns the region name as a string, or FALSE on failure
    function sloodle_get_loginzone_region()
    {
        return sloodle_get_config('sloodle_loginzone_region');
    }
    
    // Set the region of the loginzone object
    // $region should be a string containing the name of the region
    // Returns TRUE if successful, or FALSE on failure
    function sloodle_set_loginzone_region($region)
    {
        // Make sure it's a string
        if (!is_string($region)) return FALSE;
        return sloodle_set_config('sloodle_loginzone_region', $region);
    }
    
    // Get an array of all available distribution objects
    // Returns a numeric array of strings (each string being the name of an object)
    function sloodle_get_distribution_list()
    {
        // Get the data from the configuration system
        $str = sloodle_get_config('sloodle_distrib_objects');
        if (!is_string($str) || empty($str)) return array();
        // Split it at pipe-characters |
        return explode('|', $str);
    }
    
    
    // Send an XMLRPC message into Second Life
    // $channel identifies which XMLRPC channel is being communicated with (should be an SL UUID)
    // $intval and $strval provide the integer and string parts of the message
    // Returns TRUE if successful, or FALSE if an error occurs
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

    // Old logging function - TODO: update!
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


    // Check whether or not Sloodle is installed by querying the Moodle modules table
    // Returns TRUE if so, or FALSE if not
    function sloodle_is_installed()
    {
        // Is there a Sloodle entry in the modules table?
        return record_exists('modules', 'name', 'sloodle');
    }
    
    // Generate a random login security token
    // Uses mixed-case letters and numbers to generate a random 16-character string
    // Returns a string
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
    
    // Generate a random web password
    // Uses mixed-case letters and numbers to generate a random 8-character string
    // Returns a string
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
    
    // Convert a string vector of format '<x,y,z>' to an array vector
    // $vector should be a string
    // Returns a numeric array containing 3 components: x, y and z
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
    
    // Convert an array vector to a string vector
    // $arr should be a 3 component numerical array
    // Returns a string in format '<x,y,z>'
    function sloodle_array_to_vector($arr)
    {
        $ret = '<'.$arr['x'].','.$arr['y'].','.$arr['z'].'>';
        return $ret;
    }
    
    // Obtain the identified course module instance
    // $id identifies a course module instance
    // Returns a database record if successful, or FALSE if it cannot be found
    function sloodle_get_course_module_instance($id)
    {
        return get_record('course_modules', 'id', $id);
    }
    
    // Is the specified course module instance visible?
    // If $id is an integer then it is treated as the ID number of a course module instance, and the database is queried
    // If $id is an object then it is treated as a course module instance record, so the database isn't queried
    // Returns TRUE if the module is visible, or FALSE if not (or if it does not exist)
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
    
    // Is the specified course module instance of the named type?
    // If $id is an integer then it is treated as the ID number of a course module instance, and the database is queried
    // If $id is an object then it is treated as a course module instance record, so the database isn't queried
    // $module_name is the string name of a module type
    // Returns TRUE if the module is of the specified type, or FALSE otherwise
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
    
    // Obtain the ID number of the specified module (type not instance)
    // $name should be a string representing its name
    // Returns an integer, or FALSE if the module is not found
    function sloodle_get_module_id($name)
    {
        // Ensure the name is a non-empty string
        if (!is_string($name) || empty($name)) return FALSE;
        // Obtain the module record
        if (!($module_record = get_record('modules', 'name', $module_name))) return FALSE;
        
        return $module_record->id;
    }
    
    // Check if the specified position is in the current loginzone
    // Returns TRUE if so, or FALSE if not
    // $pos can be a string containing a vector, in the form "<x,y,z>", or an associative array containing elements {x,y,z}
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
    
    // Generate teleport coordinates for a user who has already finished the loginzone process
    // Returns a 3d vector as an associative array {x,y,z}
    function sloodle_finished_login_coordinates()
    {
        // Get the size and position of the loginzone
        $pos = sloodle_get_loginzone_pos();
        $size = sloodle_get_loginzone_size();
        // Make sure we retrieved both OK
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
    
    // Generate a random position within the specified zone
    // $zonemax and $zonemin should specify the maximum and minimum bounds of the zone
    // They should be associative arrays containing the vector components {x,y,z}
    // Returns a vector as an associative array 
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
    
    // Get the bounds of the loginzone
    // Note: allows for a margin around the outside of the zone
    // Returns a two parameters: max and min
    // Both are associative arrays specifying vectors {x,y,z} - the maximum and minimum bounds of the loginzone
    // Returns FALSE if the data is not available
    function sloodle_login_zone_coordinates()
    {
        // Get the position and size of the loginzone
        $pos = sloodle_get_loginzone_pos();
        $size = sloodle_get_loginzone_size();
        // Make sure both we retrieved successfully
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

?>
