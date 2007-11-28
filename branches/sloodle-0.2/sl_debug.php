<?php
// Sloodle debug mode script
// Activates or deactivates debug mode, depending on a request parameter
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) Sloodle 2007
// Released under the GNU GPL v3
//
// Contributors:
//  Peter R. Bloomfield - created original file
//

    // Define the name of the debug mode parameter
    define('SLOODLE_DEBUG_MODE_PARAM_NAME', 'sloodledebug');

    // Get the parameter
    if (isset($_REQUEST[SLOODLE_DEBUG_MODE_PARAM_NAME])) $_dbg = strtolower(trim($_REQUEST[SLOODLE_DEBUG_MODE_PARAM_NAME]));
    // Should debug mode be activated? Check for typical values, such as "true", "yes", "ok" and "1"
    if (isset($_dbg) && !empty($_dbg) && ($_dbg[0] == 't' || $_dbg == 'ok' || $_dbg == 'on' || $_dbg[0] == 'y' || (int)$_dbg != 0)) {
        define('SLOODLE_DEBUG', TRUE);
        // Enable PHP error display and full reporting level
        ini_set('display_errors', '1');
        error_reporting(2047);
    } else {
        define('SLOODLE_DEBUG', FALSE);
        // Disable PHP error display
        ini_set('display_errors', '0');
        // Note that the error reporting level is not changed here -- this is to ensure that the error log is still written to
    }
?>
