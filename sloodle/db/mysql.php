<?PHP  //$Id: mysql.php,v 1.3 2006/02/01 19:54:59 michaelpenne Exp $
//
// This file keeps track of upgrades to Moodle's
// blocks system.
//
// Sometimes, changes between versions involve
// alterations to database structures and other
// major things that may break installations.
//
// The upgrade function in this file will attempt
// to perform all the necessary actions to upgrade
// your older installtion to the current version.
//
// If there's something it cannot do itself, it
// will tell you what you need to do.
//
// Versions are defined by backup_version.php
//
// This file is tailored to MySQL

function sloodle_upgrade($oldversion=0) {

    global $CFG;
    
    $result = true;
    
//    if ($oldversion < 2005012800 && $result) {
      if ($oldversion < 2006100705 && $result) {
        execute_sql(" create table ".$CFG->prefix."sloodle_users
                    ( id int(10) unsigned not null auto_increment,
                      userid int(10) unsigned not null,
                      uuid varchar(255) not null,
                      avname varchar(255) not null,
                      PRIMARY KEY  (`id`),
					  UNIQUE (`userid`,`uuid`)
                    )");
    }

    return $result;
}
