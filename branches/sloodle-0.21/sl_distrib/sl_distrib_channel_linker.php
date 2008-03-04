<?php
    
    /**
    * Sloodle object distribution channel linker.
    *
    * Allows an in-world Sloodle Object Distributor object to send object distribution information to the Moodle database (including inventory list).
    *
    * @package sloodledistrib
    * @copyright Copyright (c) 2006-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Edmund Edgar
    * @contributor Peter R. Bloomfield
    *
    */
    
    // Expected to be called from in-world by an LSL script.
    // The following parameters are required:
    //
    //   sloodlepwd = the prim password used to authenticate the request
    //   sloodlechannel = specifies the UUID of the XMLRPC channel on which the object is listening
    //   sloodlecontents = a pipe-delimited list of inventory items
    //
    
    // On successful storage of channel, status code 1 is returned.
    // On failure to set channel, status code error code -101 is returned.
    // If a required parameter was not specified, error code -811 is returned.

	require_once('../config.php');
    require_once(SLOODLE_DIRROOT.'/sl_debug.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');
	
    // Create an LSL handler and process the basic request data
    sloodle_debug_output('Creating LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output('Processing basic request data...<br/>');
    $lsl->request->process_request_data();
    
    // Ensure the request is authenticated
    sloodle_debug_output('Authenticating request...<br/>');
    $lsl->request->authenticate_request();
    
    // Obtain our additional parameters
    sloodle_debug_output('Obtaining additional parameters...<br/>');
    $sloodlechannel = $lsl->request->required_param('sloodlechannel', PARAM_RAW);
    $sloodlecontents = $lsl->request->required_param('sloodlecontents', PARAM_RAW);
    
    $result = TRUE;
    
    // Attempt to set the channel in the configuration settings
    sloodle_debug_output('Storing distribution channel...<br/>');
    if (sloodle_set_config('sloodle_distrib_channel', $sloodlechannel)) {
        // Success
        sloodle_debug_output('-&gt; Success.<br/>');
    } else {
        // Failed
        sloodle_debug_output('-&gt; Failed.<br/>');
        $lsl->response->set_add_data_line('Failed to store object distribution XMLRPC channel in Moodle configuration table.');
        $result = FALSE;
    }
    
    // Attempt to store the contents in the configuration settings
    sloodle_debug_output('Storing distribution contents...<br/>');
    if (sloodle_set_config('sloodle_distrib_objects', $sloodlecontents)) {
        // Success
        sloodle_debug_output('-&gt; Success.<br/>');
    } else {
        // Failed
        sloodle_debug_output('-&gt; Failed.<br/>');
        $lsl->response->set_add_data_line('Failed to store object distribution XMLRPC channel in Moodle configuration table.');
        $result = FALSE;
    }
    
    // Construct the rest of the response
    if ($result) {
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
    } else {
        $lsl->response->set_status_code(-101);
        $lsl->response->set_status_descriptor('SYSTEM');
    }
    
    // Render the output
    sloodle_debug_output('Outputting response...<br/>');
    sloodle_debug_output('<pre>');
    $lsl->response->render_to_output();
    sloodle_debug_output('</pre>');
    
    exit();

?>