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

    /** The path for browsing to the root of the Sloodle folder. */
	define('SLOODLE_WWWROOT', $CFG->wwwroot.'/mod/sloodle');
    /** The data path for the root of the Sloodle folder. */
	define('SLOODLE_DIRROOT', $CFG->dirroot.'/mod/sloodle');
    /** The data path for the root of the Sloodle library folder. */
    define('SLOODLE_LIBROOT', $CFG->dirroot.'/mod/sloodle/lib');
    /** The Sloodle version number. */
    define('SLOODLE_VERSION', 0.3); // This is the release version, not the module version (which is in version.php)
    
//---------------------------------------------------------------------

    /** The name of the HTTP parameter which can be used to activate Sloodle debug mode. */
    define('SLOODLE_DEBUG_MODE_PARAM_NAME', 'sloodledebug');
    
    // Check if debug mode is active
    $sloodle_debug = 'false';
    if (isset($_REQUEST[SLOODLE_DEBUG_MODE_PARAM_NAME])) $sloodle_debug = $_REQUEST[SLOODLE_DEBUG_MODE_PARAM_NAME];
    if (strcasecmp($sloodle_debug, 'true') == 0 || $sloodle_debug == '1') {
        define('SLOODLE_DEBUG', true);
    } else {
        define('SLOODLE_DEBUG', false);
    }
    
    // Apply the effects of debug mode
    if (SLOODLE_DEBUG) {
        // Report all errors and warnings
        @ini_set('display_errors', '1');
        @error_reporting(2047);
    } else {
        // Debug mode is NOT active. Are we in a linker script?
        if (defined('SLOODLE_LINKER_SCRIPT') && SLOODLE_LINKER_SCRIPT == true) {
            // Yes - suppress the display of messages
            @ini_set('display_errors', '0');
            //@error_reporting(0); // Possibly don't do this - it may interfere with log files?
        }
    }
    
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



//---------------------------------------------------------------------
?>