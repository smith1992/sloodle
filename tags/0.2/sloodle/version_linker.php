<?php
// Version linker script, to allow in-world tools to check the Sloodle version information
// Part of the Sloodle project (www.sloodle.org)

// Copyright (c) 2008 Sloodle (various contributors)
// Released under the GNU GPL

// If called without any parameters, this script will return version info.
// If successful in checking version information, this script will return
//  with status code 1, and the data line will contain 2 fields.
// The first data field will be the Sloodle version (e.g. 0.2), and the
//  second will be the module verison (e.g. 2008020501).
// For example:
//
//  1
//  0.2|2008013101

// If an error occurs, then an appropriate standard status code will be given
//  and a message should be given in the status line.

// FUTURE WORK: the ability to query compatibility with a particular tool
//  version may implemented at some point. This will require a request
//  parameter, and will likely return "true" or "false" on the data line.


// Include our Sloodle stuff
require_once('config.php');
require_once(SLOODLE_DIRROOT.'/sl_debug.php');
require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');

// Process the request
sloodle_debug_output('Processing request...<br/>');
$lsl = new SloodleLSLHandler();
$lsl->request->process_request_data();

// Check the installed Sloodle version
sloodle_debug_output('Checking for installed Sloodle version...<br/>');
$moduleinfo = get_record('modules', 'name', 'sloodle');
if (!$moduleinfo) {
 sloodle_debug_output('ERROR: Sloodle not installed<br/>');
 $lsl->response->set_status_code(-106);
 $lsl->response->set_status_descriptor('SYSTEM');
 $lsl->response->add_data_line('The Sloodle module is not installed on this Moodle site.');
 $lsl->response->render_to_output();
 exit();
}

// Extract the module version number
$moduleversion = (string)$moduleinfo->version;
sloodle_debug_output('Sloodle version: '.(string)SLOODLE_VERSION.'<br/>');
sloodle_debug_output("Module version: $moduleversion<br/>");

// Construct and render the response
sloodle_debug_output('Rendering response...<br/>');
$lsl->response->set_status_code(1);
$lsl->response->set_status_descriptor('OK');
$lsl->response->add_data_line(array((string)SLOODLE_VERSION, $moduleversion));
sloodle_debug_output('<br/><pre>');
$lsl->response->render_to_output();
sloodle_debug_output('</pre>');

exit();

?>
