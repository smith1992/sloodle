<?php
    /**
    * Sloodle core script.
    *
    * Sets up the basic Sloodle information, and includes the necessary Moodle data/functionality.
    *
    * @package sloodle
    *
    */

	// Pull in the main moodle config
	// NB the following is necessary for when we pull in this config.php from a module under sloodle/mod
	require_once (realpath(dirname(__FILE__) . "/" . "../../config.php"));

	define('SLOODLE_WWWROOT', $CFG->wwwroot.'/mod/sloodle');
	define('SLOODLE_DIRROOT', $CFG->dirroot.'/mod/sloodle');
    define('SLOODLE_LIBROOT', $CFG->dirroot.'/mod/sloodle/lib');
    define('SLOODLE_VERSION', 0.3); // This is the release version, not the module version (which is in version.php)
    
    
//---------------------------------------------------------------------
    // Types of Sloodle module
    // These correspond to the "type" field in the "Sloodle" DB table
    // Each name should be lower-case letters only (max 50)
    
    // Each course needs to have one 'Control Center' type in it before any other type is allowed.
    // This is what grants access to the course as a whole, and sets the course prim password.
    // (Only one control center per course)
    // This defines the "Control Center" type
    global $SLOODLE_TYPE_CTRL;
    $SLOODLE_TYPE_CTRL = 'controlcenter';
    
    // These are the regular module types
    global $SLOODLE_TYPES;
    $SLOODLE_TYPES = array();
    $SLOODLE_TYPES[] = 'classroom';
    $SLOODLE_TYPES[] = 'distributor';
    $SLOODLE_TYPES[] = 'loginzone';
    
    
    

?>

