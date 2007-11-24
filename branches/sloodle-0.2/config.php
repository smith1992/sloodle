<?php

	// Pull in the main moodle config
	// NB the following is necessary for when we pull in this config.php from a module under sloodle/mod
	include realpath(dirname(__FILE__) . "/" . "../../config.php");

	define('SLOODLE_WWWROOT', $CFG->wwwroot.'/mod/sloodle');
	define('SLOODLE_DIRROOT', $CFG->dirroot.'/mod/sloodle');
    define("SLOODLE_VERSION", 0.2); // This is the release version, not the module version (which is in version.php)

	// Configuration ends

?>
