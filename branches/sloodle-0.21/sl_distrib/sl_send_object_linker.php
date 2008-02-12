<?php
    /**
    * Sloodle object distribution linker script.
    *
    * Can be called by scripts to request in-world distribution of objects.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
        
    // This script should only be accessed by a browser (a script linker version can be found at "sl_send_object_linker.php")
    // There are two modes: query and sendobject.
    // The following parameters are required:
    //
    //   sloodlepwd = prim password for authenticating the request
    //   sloodlecmd = indicates what action should be carried out (can be 'query' or 'sendobject')
    //
    // The following parameters are required if 'sloodlecmd' is set to 'sendobject':
    //    
    //   sloodleuuid = UUID of avatar to whom to send an object (NOTE: avatar does NOT need to be in the Sloodle database to use this!)
    //   sloodleavname = name of avatar to whom to send an object (NOTE: avatar DOES need to be in the Sloodle database to use this!)
    //   sloodleobject = name of the object to send
    //
    
    // In 'query' mode, this script will return a list of the names of all objects available for distribution.
    // Success status code is 1. Each object name will be on its own line following the status line, e.g.:
    //
    //   1|OK
    //   Sloodle Set v0.701
    //   Sloodle Toolbar v1.2 (customizable)
    //   Sloodle Toolbar v1.2 (sloodle.org)
    //
    // NOTE: if not objects are available, then success code 1 is still returned, but no objects are listed in the data lines.
    //
    //
    // In 'sendobject' mode, this script will return status code 1 if successful.
    // There will be a single data line containing the name of the sent object.
    // The status line will contain the UUID of the avatar to whom the object was sent.
    //
    //
    // If the requested object is not listed in the database, then -812 is returned.
    // If the distribution channel cannot be found, then status code -103 is returned.
    // If the object distribution fails (e.g. the XML-RPC could not connect) then -105 is returned.
    // If the 'sloodlecmd' value is not recognised, then -811 is returned.
    //    
    
    
	require_once('../config.php');
    require_once(SLOODLE_DIRROOT.'/sl_debug.php');    
	require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');
    
    // Construct the LSL handler, and process the basic request data
    sloodle_debug_output('Constructing LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output('Processing basic request data...<br/>');
    $lsl->request->process_request_data();
    
    // Ensure the request is authenticated
    sloodle_debug_output('Authenticating request...<br/>');
    $lsl->request->authenticate_request();
    
    // Fetch other additional parameters
    sloodle_debug_output('Fetching additional parameters...<br/>');
    $sloodlecmd = $lsl->request->required_param('sloodlecmd', PARAM_RAW);
    $sloodleobject = optional_param('sloodleobject', NULL, PARAM_RAW);
    
    
    // Make sure the command is recognised
    sloodle_debug_output('Checking mode...<br/>');
    $sloodlecmd = strtolower($sloodlecmd);
    if ($sloodlecmd != 'query' && $sloodlecmd != 'sendobject') {
        $lsl->response->quick_output(-811, "DISTRIB", "Parameter 'sloodlecmd' gave unrecognised script mode: '$sloodlecmd'", FALSE);
        exit();
    }    
        
    // Fetch the key for the XMLRPC channel
    sloodle_debug_output('Getting the distribution channel...<br/>');
	$distribchannel = sloodle_get_config('sloodle_distrib_channel');
	if (($distribchannel == NULL) || ($distribchannel == '') || ($distribchannel == FALSE)) {
        $lsl->response->quick_output(-103, "DISTRIB", "XML-RPC distribution channel not found in database.", FALSE);
        exit();
    }
    
    // Fetch the list of objects
    sloodle_debug_output('Fetching list of distribution objects...<br/>');
    $distribobjects = sloodle_get_distribution_list();
    if (!is_array($distribobjects) || count($distribobjects) == 0) {
        // No objects available - check which mode we're in to determine our response
        if ($sloodlecmd == 'query') {
            $lsl->response->quick_output(1, "DISTRIB", "", FALSE);
        } else {
            $lsl->response->quick_output(-812, "DISTRIB", "No objects available for distribution.", FALSE);
        }
        exit();
    }
    sort($distribobjects, SORT_STRING);
    
    
    // What command was specified?
    sloodle_debug_output('Checking mode type...<br/>');
    switch ($sloodlecmd) {
    case 'query':
        sloodle_debug_output('***** QUERY MODE *****<br/>');
        // Prepare the response
        sloodle_debug_output('Preparing response...<br/>');
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor("DISTRIB");
        // Go through each distribution object, and add it as a data line
        sloodle_debug_output('Iterating through each available object...<br/>');
        foreach($distribobjects as $obj) {
            $lsl->response->add_data_line($obj);
        }
       
        break;
        
     case 'sendobject':
        sloodle_debug_output('***** SENDOBJECT MODE *****<br/>');
        // Send an object to the specified user
        
        // Was the UUID  ommitted?
        sloodle_debug_output('Checking for UUID...<br/>');
        if ($lsl->request->get_avatar_uuid() == NULL) {
            // Yes - make sure the avatar name was specified
            sloodle_debug_output('-&gt; UUID not specified.<br/>');
            if ($lsl->request->get_avatar_name() == NULL) {
                $lsl->response->quick_output(-311, "USER_AUTH", "Neither the avatar name or UUID were specified.", FALSE);
                exit();
            }
            
            // The request object would have found the matching UUID when asked to process data. The user is obviously not in the database.
            $lsl->response->quick_output(-321, "USER_AUTH", "Failed to find named avatar in Moodle database.", FALSE);
            exit();
        }
        
        // Make sure the object was specified
        $lsl->request->required_param('sloodleobject', PARAM_RAW);
        // Make sure it was found in the object list
        sloodle_debug_output('Ensuring object is available...<br/>');
        if (array_search($sloodleobject, $distribobjects) === FALSE) {
            $lsl->response->quick_output(-812, "DISTRIB", "Requested object not available for distribution.", FALSE);
            exit();
        }
        
        // Construct our XML-RPC data message
        sloodle_debug_output('Preparing XMLRPC message...<br/>');
        $response = new SloodleLSLResponse();
        $response->set_status_code(1);
        $response->set_status_descriptor('OK');
        $response->add_data_line(array('SENDOBJECT', $lsl->request->get_avatar_uuid(), $sloodleobject));
		// Render it to a string so we can send it
        $str = '';
        $response->render_to_string($str);
        $str = str_replace("\n", "\\n", $str); // Ugly hack to stop the newline character disappearing before it reaches SL
        
		// Send the XMLRPC
        sloodle_debug_output('Sending XMLRPC message...<br/>');
		$ok = sloodle_send_xmlrpc_message($distribchannel, 0, $str);
        
        // What was the result?
        sloodle_debug_output('Checking response...<br/>');
		if ($ok) {
            sloodle_debug_output('-&gt; OK<br/>');
            $lsl->response->set_status_code(1);
            $lsl->response->set_status_descriptor("DISTRIB");
            $lsl->response->add_data_line($sloodleobject);
		} else {
            sloodle_debug_output('-&gt; Failed.<br/>');
            $lsl->response->set_status_code(-105);
            $lsl->response->set_status_descriptor("DISTRIB");
            $lsl->response->add_data_line("XML-RPC distribution failed.");
		}
        break;        
    }
    
    // Output the response
    sloodle_debug_output('Outputting response...<br/>');
    sloodle_debug_output("<pre>");
    $lsl->response->render_to_output();
    sloodle_debug_output("</pre>");
    
    exit();

?>
