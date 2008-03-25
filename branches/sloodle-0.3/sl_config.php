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
    // The full name should be specified in the appropriate language file, as "moduletype:type".
    
    // Each course needs to have at least one Sloodle Access Controller before it can be accessed from in-world.
    // This is what grants access to the course as a whole, and sets prim passwords.
    define('SLOODLE_TYPE_CTRL', 'controller');
    
    // These are the regular module types
    define('SLOODLE_TYPE_DISTRIB', 'distributor');
    
    // Store the types in an array (used in lists)
    global $SLOODLE_TYPES;
    $SLOODLE_TYPES = array();
    $SLOODLE_TYPES[] = SLOODLE_TYPE_CTRL;
    $SLOODLE_TYPES[] = SLOODLE_TYPE_DISTRIB;
    
    
//---------------------------------------------------------------------


?>

