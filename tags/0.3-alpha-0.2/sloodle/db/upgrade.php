<?php

/**
* Database upgrade script for Moodle's db-independent XMLDB.
* @ignore
* @package sloodledb
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

function xmldb_sloodle_upgrade($oldversion=0) {

    global $CFG, $THEME, $db;
    $result = true;

    // Sloodle 0.19 -> 0.2
    if ($result && $oldversion <= 2006100701) {
        //...
        echo('<b>PLEASE NOTE: This is an early test release of Sloodle, so you cannot use it to upgrade an existing installation.<br>Please uninstall your old module first, then install 0.3.</b>');
        return false;
    }
    
    // Sloodle 0.2 -> 0.21
    if ($result && $oldversion <= 2008022800) {
        //...
        echo('<b>PLEASE NOTE: This is an early test release of Sloodle, so you cannot use it to upgrade an existing installation.<br>Please uninstall your old module first, then install 0.3.</b>');
        return false;
    }
    
    // Sloodle 0.21 -> 0.3
    if ($result && $oldversion < 2008052800) {
        //...
        echo('<b>PLEASE NOTE: This is an early test release of Sloodle, so you cannot use it to upgrade an existing installation.<br>Please uninstall your old module first, then install 0.3.</b>');
        return false;
    }
    
    echo '<b>Thanks for helping us test Sloodle 0.3! :-)</b>';

    // Inform Moodle if the upgrade was successful
    return $result;
}

?>
