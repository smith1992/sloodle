<?php
$sloodleconfig = new SloodleObjectConfig();
$sloodleconfig->primname   = 'SLOODLE Access Checker Door';
$sloodleconfig->object_code= 'access-checker-door';
$sloodleconfig->modname    = 'accesschecker-1.0';
$sloodleconfig->group      = 'registration';
$sloodleconfig->show       = true;
$sloodleconfig->aliases    = array('SLOODLE 1.1 Access Checker');
$sloodleconfig->field_sets = array( 
	'access' => array(
		'sloodleobjectaccessleveluse'  => $sloodleconfig->access_level_object_use_option(),
	),
);
?>
