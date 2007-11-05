<?php

	// Pull in the main moodle config
	// NB the following is necessary for when we pull in this config.php from a module under sloodle/mod
	include realpath(dirname(__FILE__) . "/" . "../../config.php");

	// Configure your installation here...

	/* Password that proves to the sloodle module that it is being accessed by an object that is allowed to talk to it.
	*  This password needs to be communicated to the prim that will be accessing sloodle.
	*  TODO: It would be better to set this when the user first installs the module, and keep it in the database
	*/
	define('SLOODLE_WWWROOT', $CFG->wwwroot.'/mod/sloodle');
	define('SLOODLE_DIRROOT', $CFG->dirroot.'/mod/sloodle');

	define('SLOODLE_ALLOW_NORMAL_USER_ACCESS_TO_ADMIN_FUNCTIONS_FOR_TESTING', false);


	// Configuration ends

?>
