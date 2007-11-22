<?php // 

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of sloodle
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

// Sloodle version
define("SLOODLE_VERSION", 0.2);

// Internal module version number
$module->version  = 2007112100;  // YYYYMMDD##
$module->requires = 2006050512;   // The version of Moodle that is required
$module->cron     = 0;            // How often should cron check this module (seconds)?

?>
