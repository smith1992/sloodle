<?php

require_once($CFG->dirroot.'/mod/sloodle/sl_config.php');
require_once(SLOODLE_LIBROOT.'/general.php');


// // VERSION INFO // //

// Construct the version info
// Sloodle version
$str = print_heading(get_string('sloodleversion','sloodle').': '.(string)SLOODLE_VERSION, 'center', 4, 'main', true);
// Release number
$sloodlemodule = get_record('modules', 'name', 'sloodle');
$releasenum = 0;
if ($sloodlemodule !== FALSE) $releasenum = $sloodlemodule->version;
$str .= print_heading(get_string('releasenum','sloodle').': '.(string)$releasenum, 'center', 5, 'main', true);

// Construct a help button to
$hlp = helpbutton('version_numbers', get_string('help:versionnumbers', 'sloodle'), 'sloodle', true, false, '', true);

// Add the version info section
$settings->add(new admin_setting_heading('sloodle_version_header', "Version Info ".$hlp, $str));



// // GENERAL SETTINGS // //

// General settings section
$settings->add(new admin_setting_heading('sloodle_settings_header', "SLOODLE for Schools Settings", ''));

// Do we already have an auth token? Add it if not. (There must be a better place to do this)
$storedAuth = sloodle_get_stored_auth_token();
if (empty($storedAuth)) sloodle_set_stored_auth_token( sloodle_generate_random_auth_token() );

// This text box will let the user see and change the OpenSim authentication token
$settings->add( new admin_setting_configtext(
                'sloodle_for_schools_auth_token',
                get_string('sfs_auth_token', 'sloodle'),
                get_string('sfs_auth_token:info', 'sloodle'),
                null, PARAM_RAW, 40));

?>