<?php

function sloodle_upgrade($oldversion) {
// This function does anything necessary to upgrade
// older versions to match current functionality

    global $CFG;
    $result = true;

    if ($result && $version < 2007112100) {
    
    // Add an empty sloodle instances table
    // (allows Moodle to determine the number of module instances without reporting DB errors!)
        // Table: sloodle
        echo('Adding Sloodle instance table...');
        execute_sql("
            CREATE TABLE {$CFG->prefix}sloodle (
              id SERIAL PRIMARY KEY,
              course integer NOT NULL default '0',
              name varchar(255) NOT NULL default 'untitled'
            );
        ");
        
        
    // Move configuration settings to the Moodle config table
        echo('Moving Sloodle configuration settings to central Moodle table...');
        // Get the configuration settings
        $sloodle_cfg_prim_password = get_record('sloodle_config','name','SLOODLE_PRIM_PASSWORD');
        $sloodle_cfg_auth_method = get_record('sloodle_config','name','SLOODLE_AUTH_METHOD');
        // Initialise them to defaults if necessary
        if ($sloodle_cfg_prim_password === FALSE) $sloodle_cfg_prim_password = (string)mt_rand(100000000, 999999999);
        if ($sloodle_cfg_auth_method === FALSE) $sloodle_cfg_auth_method = 'web';
        // Add them to the Moodle configuration table
        set_config('sloodle_prim_password', $sloodle_cfg_prim_password->value);
        set_config('sloodle_auth_method', $sloodle_cfg_auth_method->value);     
    
    
    // Drop the Sloodle config table
        echo('Dropping Sloodle configuration table...');
        execute_sql("DROP TABLE {$CFG->prefix}sloodle_config");
    }

    return $result;
}

?>