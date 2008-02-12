<?php
    /**
    * LoginZone linker script.
    *
    * Allows an in-world LoginZone to communicate with the main server
    *
    * @package sloodle
    * @copyright Copyright (c) 2007 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    
    // This script is expected to be accessed by objects from in-world.
    // The following parameters are required at all times:
    //
    //   sloodlepwd = the prim password for accessing thesite
    //
    // Otherwise, there are two modes of operation.
    // In Mode 1, a LoginZone is reporting its own size and position. That requires the following parameters:
    //
    //   sloodlepos = a vector "<x,y,z>" indicating the position of the loginzone
    //   sloodlesize = a vector "<x,y,z>" indicating the size of the loginzone
    //   sloodleregion = the name of the region where the loginzone is located
    //
    // In Mode 2, a LoginZone is reporting that a user has teleported in to authenticate their avatar, requiring the following parameters:
    //
    //   sloodlepos = the position at which the avatar has appeared
    //   sloodleavname = the name of the avatar
    //   sloodleuuid = the UUID of the avatar
    //
    // ***** Note: the script assumes Mode 2 if either the avatar name or the UUID is specified *****

    
    // In either mode, the success response status code will be 1 ("OK").
    // Various errors codes may also be used.
    // The avatar UUID (if specified) will be returned in the request for Mode 2, according to the communications specification.


	require_once('../config.php');
	require_once(SLOODLE_DIRROOT.'/sl_debug.php');
	require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');

    // Note: parameters sloodlepwd, sloodleavname, and sloodleuuid will be handled automatically by the API
    
    // Construct an LSL handler and process the basic request data
    sloodle_debug_output("Constructing an LSL handler...<br/>");
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output("Processing basic request data...<br/>");
    $lsl->request->process_request_data();
    
    // Ensure that the request is authenticated by prim password
    sloodle_debug_output("Authenticating request...<br/>");
    $lsl->request->authenticate_request();
    
    // Are we using Mode 1?
    if ($lsl->request->get_avatar_name() == NULL && $lsl->request->get_avatar_uuid() == NULL) {
        // Mode 1
        sloodle_debug_output("***** Mode 1 *****<br/>");
        
        // We require therefore that position, size and region are all specified
        sloodle_debug_output("Obtaining Mode 1 parameters...<br/>");
        $sloodlepos = $lsl->request->required_param('sloodlepos', PARAM_RAW);
        $sloodlesize = $lsl->request->required_param('sloodlesize', PARAM_RAW);
        $sloodleregion = $lsl->request->required_param('sloodleregion', PARAM_RAW);
        
        // Remove the decimal places
        $sloodlepos = sloodle_round_vector($sloodlepos);
        $sloodlesize = sloodle_round_vector($sloodlesize);
        
        sloodle_debug_output("<hr><pre>sloodlepos=$sloodlepos\nsloodlesize=$sloodlesize\nsloodleregion=$sloodleregion</pre><hr>");
        
        // Attempt to store all the data
        sloodle_debug_output("Storing LoginZone data...<br/>");
        if (sloodle_set_loginzone_pos($sloodlepos) && sloodle_set_loginzone_size($sloodlesize) && sloodle_set_loginzone_region($sloodleregion)) {
            // Everything seems fine
            sloodle_debug_output("-&gt;Success.<br/>");
            sloodle_debug_output("Preparing response...<br/>");
            $lsl->response->set_status_code(1);
            $lsl->response->set_status_descriptor('OK');
        } else {
            // Something went wrong
            sloodle_debug_output("-&gt;Failed.<br/>");
            sloodle_debug_output("Preparing response...<br/>");
            $lsl->response->set_status_code(-101);
            $lsl->response->set_status_descriptor('SYSTEM');
            $lsl->response->add_data_line('Failed to store the LoginZone data.');
        }        
        
    } else {
        // Mode 2
        sloodle_debug_output("***** Mode 2 *****<br/>");
        
        // We require therefore that position is specified
        sloodle_debug_output("Obtaining Mode 2 parameters...<br/>");
        $sloodlepos = $lsl->request->required_param('sloodlepos', PARAM_RAW);
        // Remove the decimal places
        $sloodlepos = sloodle_round_vector($sloodlepos);
        
        // Make sure the avatar name and UUID were specified
        $sloodleuuid = $lsl->request->required_param('sloodleuuid', PARAM_RAW);
        $sloodleavname = $lsl->request->required_param('sloodleavname', PARAM_RAW);
        
        // Check to see if the user identified in the request is already in the database
        sloodle_debug_output("Checking if avatar is already in the database...<br/>");
        $sloodle_user_exists = $lsl->user->find_sloodle_user($sloodleuuid, $sloodleavname);
        if ($sloodle_user_exists === TRUE) {
            // Nothing we need to do - just stop here
            sloodle_debug_output("-&gt;Avatar already registered.<br/>");
            $lsl->response->set_status_code(301);
            $lsl->response->set_status_descriptor('MISC_REGISTER');
            $lsl->response->render_to_output();
            exit();
        }
        
        // Attempt to find the Sloodle user specified by login position in the request
        sloodle_debug_output("Finding Sloodle user by loginzone...<br/>");
        $result = $lsl->user->find_sloodle_user_by_login_position($sloodlepos);
        if ($result === TRUE) {
            // Success
            sloodle_debug_output("-&gt;Success.<br/>");
            // Store the avatar UUID and name
            sloodle_debug_output("Adding avatar details...<br/>");
            $lsl->user->sloodle_user_cache->uuid = $lsl->request->get_avatar_uuid();
            $lsl->user->sloodle_user_cache->avname = $lsl->request->get_avatar_name();
            // Remove the loginzone details
            sloodle_debug_output("Removing login position...<br/>");
            $lsl->user->sloodle_user_cache->loginposition = '';
            $lsl->user->sloodle_user_cache->loginpositionexpires = '';
            $lsl->user->sloodle_user_cache->loginpositionregion = '';
            
            // Update the database
            sloodle_debug_output("Updating database with new user data...<br/>");
            $db_result = $lsl->user->update_sloodle_user_cache_to_db();
            if ($db_result === TRUE) {
                // Success
                sloodle_debug_output("-&gt;Success.<br/>");
                // Our response will be very simple...
                sloodle_debug_output("Preparing response...<br/>");
                $lsl->response->set_status_code(1);
                $lsl->response->set_status_descriptor('OK');
                
            } else {
                // Something went wrong
                if (is_string($result)) {
                    sloodle_debug_output("-&gt;Failed: $result<br/>");
                } else {
                    sloodle_debug_output("-&gt;Failed (unknown reason).<br/>");
                }
                
                // Report the problem back in the response
                sloodle_debug_output("Preparing response...<br/>");
                $lsl->response->set_status_code(-102);
                $lsl->response->set_status_descriptor('SYSTEM');
                if (is_string($result)) $lsl->response->add_data_line($result);
            }
            
        } else {
            // Something went wrong
            if (is_string($result)) {
                sloodle_debug_output("-&gt;Failed: $result<br/>");
            } else {
                sloodle_debug_output("-&gt;Failed (unknown reason).<br/>");
            }
            
            // Report the problem back in the response
            sloodle_debug_output("Preparing response...<br/>");
            $lsl->response->set_status_code(-301);
            $lsl->response->set_status_descriptor('USER_AUTH');
            if (is_string($result)) $lsl->response->add_data_line($result);
            else $lsl->response->add_data_line('No user found with specified Login Position.');
        }
        
    }
    
    // Output the response
    sloodle_debug_output("Outputting response...<br/>");
    $lsl->response->render_to_output();
    exit();

?>
