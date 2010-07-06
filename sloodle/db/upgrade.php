<?php

/**
* Database upgrade script for Moodle's db-independent XMLDB.
* @ignore
* @package sloodle
*/


// This file keeps track of upgrades to
// the sloodle module
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installation to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// The commands in here will all be database-neutral,
// using the functions defined in lib/ddllib.php

function xmldb_sloodle_upgrade($oldversion=0)
{
    global $CFG, $THEME, $db;
    $result = true;

    // Can't upgrade from a previous version to this one.
    if ($oldversion < 2010073100)
    {
        echo "ERROR: Can't upgrade to SLOODLE for Schools. Please uninstall SLOODLE completely, and then do a clean of install SLOODLE for Schools.";
        return false;
    }

    return $result; 
}

?>

