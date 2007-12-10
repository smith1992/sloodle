<?php

function sloodle_upgrade($oldversion) {
// This function does anything necessary to upgrade
// older versions to match current functionality

    global $CFG;
    $result = true;

    if ($result && $oldversion < 2007112100) {
    
    // Drop the UNIQUE attribute of the userid index in the sloodle_users table
        echo('Dropping UNIQUE index on userid field of sloodle_users table (fixing Issue 5)...');
        // Drop the unique index
        execute_sql("ALTER TABLE `{$CFG->prefix}sloodle_users` DROP INDEX `userid`;");
        // Add the non-unique index in its place
        execute_sql("ALTER TABLE `{$CFG->prefix}sloodle_users` ADD INDEX `userid` (`userid`);");
        
       
    // We need to remove the UNIQUE attribute from all the other tables' `id` fields, as they are also the PRIMARY KEY fields
        echo('Removing UNIQUE attribute from primary keys...');
        execute_sql("ALTER TABLE `{$CFG->prefix}sloodle_config` DROP INDEX `id`;");
        execute_sql("ALTER TABLE `{$CFG->prefix}sloodle_active_object` DROP INDEX `id`;");
        execute_sql("ALTER TABLE `{$CFG->prefix}sloodle_classroom_setup_profile` DROP INDEX `id`;");
        execute_sql("ALTER TABLE `{$CFG->prefix}sloodle_classroom_setup_profile_entry` DROP INDEX `id`;");
        
    // Add an empty sloodle instances table
    // (allows Moodle to determine the number of module instances without reporting DB errors!)
        // Table: sloodle
        echo('Adding Sloodle instance table...');
        execute_sql("
            CREATE TABLE `{$CFG->prefix}sloodle` (
                `id` int(10) unsigned NOT NULL auto_increment,
                `course` int(10) unsigned NOT NULL DEFAULT '0',
                `name` varchar(255) NOT NULL DEFAULT 'untitled',
                PRIMARY KEY (`id`),
                INDEX `course` (`course`)
            ) COMMENT='For future use. Defines Sloodle module instances.'
        ");
        
        
    // Move configuration settings to the Moodle config table
        echo('Moving Sloodle configuration settings to central Moodle table...');
        
        // Get the main configuration settings
        $sloodle_cfg_prim_password = get_record('sloodle_config','name','SLOODLE_PRIM_PASSWORD');
        $sloodle_cfg_auth_method = get_record('sloodle_config','name','SLOODLE_AUTH_METHOD');
        // Initial them to defaults if they hadn't been specified, or extract the value fields if they have
        if ($sloodle_cfg_prim_password === FALSE) $sloodle_cfg_prim_password = (string)mt_rand(100000000, 999999999);
        else $sloodle_cfg_prim_password = $sloodle_cfg_prim_password->value;        
        if ($sloodle_cfg_auth_method === FALSE) $sloodle_cfg_auth_method = 'web';
        else $sloodle_cfg_auth_method = $sloodle_cfg_auth_method->value;
        // Add them to the Moodle configuration table
        set_config('sloodle_prim_password', $sloodle_cfg_prim_password);
        set_config('sloodle_auth_method', $sloodle_cfg_auth_method);     
        
        // Now get the loginzone data
        $sloodle_loginzone_pos = get_record('sloodle_config', 'name', 'loginzonepos');
        $sloodle_loginzone_size = get_record('sloodle_config', 'name', 'loginzonesize');
        $sloodle_loginzone_region = get_record('sloodle_config', 'name', 'loginzoneregion');
        // Only transfer items which had been specified
        if ($sloodle_loginzone_pos !== FALSE) set_config('sloodle_loginzone_pos', $sloodle_loginzone_pos->value)
        if ($sloodle_loginzone_size !== FALSE) set_config('sloodle_loginzone_size', $sloodle_loginzone_size->value)
        if ($sloodle_loginzone_region !== FALSE) set_config('sloodle_loginzone_region', $sloodle_loginzone_region->value)
    
    // Drop the Sloodle config table
        echo('Dropping Sloodle configuration table...');
        execute_sql("DROP TABLE {$CFG->prefix}sloodle_config");
    }
    
    
    if ($result && $oldversion < 2007120401) {
    
    // Drop the UNIQUE attribute of the uuid index in the sloodle_users table
        echo('Dropping UNIQUE index on uuid field of sloodle_users table...');
        // Drop the unique index
        execute_sql("ALTER TABLE `{$CFG->prefix}sloodle_users` DROP INDEX `uuid`;");
        // Add the non-unique index in its place
        execute_sql("ALTER TABLE `{$CFG->prefix}sloodle_users` ADD INDEX `uuid` (`uuid`);");
    }
    
    
    if ($result && $oldversion < 2007120700) {
    
        // Add the `online` field to the `sloodle_users` table
        echo('Adding online field to sloodle_users table...');
        execute_sql("ALTER TABLE `{$CFG->prefix}sloodle_users` ADD `online` TINYINT(1) UNSIGNED DEFAULT '0' NOT NULL AFTER `loginsecuritytoken`");
    
    }

    return $result;
}

?>
