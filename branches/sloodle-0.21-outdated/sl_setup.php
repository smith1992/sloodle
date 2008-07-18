<?php
    /**
    * Sloodle configuration re-direction script.
    *
    * Acts as a placeholder to re-direct users to the new configuration script.
    *
    * @ignore
    * @package sloodle
    * @deprecated
    *
    */

	require_once('config.php');
	redirect($CFG->wwwroot.'/admin/module.php?module=sloodle');
	exit;
?>
