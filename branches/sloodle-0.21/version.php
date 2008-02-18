<?php

/**
* Sloodle module version information.
*
* Code fragment required by Moodle for module management.
*
* @package sloodle
*/

/////////////////////////////////////////////////////////////////////////////////
///  Code fragment to define the version of sloodle
///  This fragment is called by moodle_needs_upgrading() and /admin/index.php
/////////////////////////////////////////////////////////////////////////////////

// Internal module version number
$module->version  = 2008021800;  // YYYYMMDD##
$module->requires = 2006050512;  // The version of Moodle that is required
$module->cron     = 0;           // How often should cron check this module (seconds)?

?>
