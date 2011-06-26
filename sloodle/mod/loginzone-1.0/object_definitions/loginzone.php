<?php
$sloodleconfig = new SloodleObjectConfig();
$sloodleconfig->primname   = 'SLOODLE LoginZone';
$sloodleconfig->object_code= 'loginzone';
$sloodleconfig->modname    = 'loginzone-1.0';
$sloodleconfig->group      = 'registration';
$sloodleconfig->show       = true;
$sloodleconfig->aliases    = array('SLOODLE 1.1 LoginZone');
$sloodleconfig->field_sets = array( 
	'general' => array(
		'sloodlerefreshtime'   => new SloodleConfigurationOptionText( 'sloodleidletimeout', 'idletimeoutseconds', '', 600, 8),
	),
);
?>
