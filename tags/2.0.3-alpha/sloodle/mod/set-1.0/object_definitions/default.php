<?php
// Only used for legacy purposes - you configure the current one with its own screen
$sloodleconfig = new SloodleObjectConfig();
$sloodleconfig->primname   = 'SLOODLE Set';
$sloodleconfig->object_code= 'default';
$sloodleconfig->modname    = 'set-1.0';
$sloodleconfig->module     = 'sloodle';
$sloodleconfig->module_choice_message = 'selectcontroller';
$sloodleconfig->module_no_choices_message = 'nocontrollers'; 
$sloodleconfig->module_filters = array( 'type' => SLOODLE_TYPE_CTRL); 
$sloodleconfig->group      = ''; // don't show in any groups - you never add one of these to a scene
$sloodleconfig->show       = true; // deprecated - use version 2.
$sloodleconfig->aliases    = array('SLOODLE 1.1 Set', 'SLOODLE 1.2 Set');
$sloodleconfig->field_sets = array( 
	'accesslevel' => array(
		'sloodleobjectaccessleveluse'   => $sloodleconfig->access_level_object_use_option(),
		'sloodleobjectaccesslevelctrl'  => $sloodleconfig->access_level_object_control_option(),
		'sloodleserveraccesslevel'      => $sloodleconfig->access_level_server_option(),
	),
);
?>
