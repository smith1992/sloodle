<?php
$sloodleconfig = new SloodleObjectConfig();
$sloodleconfig->primname   = 'SLOODLE Choice (Vertical)';
$sloodleconfig->object_code= 'choice-vertical';
$sloodleconfig->modname    = 'choice-1.0';
$sloodleconfig->module     = 'choice';
$sloodleconfig->module_choice_message = 'selectchoice';
$sloodleconfig->module_no_choices_message= 'nochoices';
$sloodleconfig->group      = 'communication';
$sloodleconfig->show       = true;
$sloodleconfig->aliases    = array('SLOODLE 1.1 Choice (Vertical)');
$sloodleconfig->field_sets = array( 
	'access' => array(
		'sloodleobjectaccessleveluse'  => $sloodleconfig->access_level_object_use_option(),
		'sloodleserveraccesslevel'     => $sloodleconfig->access_level_server_option(),
	),
	'generalconfiguration' => array(
		'sloodlerefreshtime' => new SloodleConfigurationOptionText( 'sloodlerefreshtime', 'refreshtimeseconds', '', 600, 8 ),
	),
);
?>
