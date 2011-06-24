<?php

require_once($CFG->dirroot.'/mod/sloodle/sl_config.php');


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
$settings->add(new admin_setting_heading('sloodle_settings_header', "Sloodle Settings", ''));

// Get some localization strings
$stryes = get_string('yes');
$strno = get_string('no');

    
// This selection box determines whether or not auto-registration is allowed on the site
$settings->add( new admin_setting_configselect(
                'sloodle_allow_autoreg',
                '',
                get_string('autoreg:allowforsite','sloodle').helpbutton('auto_registration', get_string('help:autoreg','sloodle'), 'sloodle', true, false, '', true),
                0,
                array(0 => $strno, 1 => $stryes)
));

    
// This selection box determines whether or not auto-enrolment is allowed on the site
$settings->add( new admin_setting_configselect(
                'sloodle_allow_autoenrol',
                '',
                get_string('autoenrol:allowforsite','sloodle').helpbutton('auto_enrolment', get_string('help:autoenrol','sloodle'), 'sloodle', true, false, '', true),
                0,
                array(0 => $strno, 1 => $stryes)
));

// This text box will let the user set a number of days after which active objects should expire
$settings->add( new admin_setting_configtext(
                'sloodle_active_object_lifetime',
                get_string('activeobjectlifetime', 'sloodle'),
                get_string('activeobjectlifetime:info', 'sloodle').helpbutton('object_authorization', get_string('activeobjects','sloodle'), 'sloodle', true, false, '', true),
                7));
                

// This text box will let the user set a number of days after which user objects should expire
$settings->add( new admin_setting_configtext(
                'sloodle_user_object_lifetime',
                get_string('userobjectlifetime', 'sloodle'),
                get_string('userobjectlifetime:info', 'sloodle').helpbutton('user_objects', get_string('userobjects','sloodle'), 'sloodle', true, false, '', true),
                21));
                

// // TRACKER SETTINGS // //

// General settings section
$settings->add(new admin_setting_heading('sloodle_tracker_settings_header', get_string('tracker:settings', 'sloodle'), ''));

// This text box will let the user enter the address of the OpenSim server -- this is how students will connect to it
$settings->add( new admin_setting_configtext(
                'sloodle_tracker_opensim_address',
                get_string('tracker:opensim_address', 'sloodle'),
                get_string('tracker:opensim_address:info', 'sloodle'),
                '',
                PARAM_RAW,
                50));
				
// This text box will let the user enter the path to the main OpenSim installation folder
$settings->add( new admin_setting_configtext(
                'sloodle_tracker_main_opensim_installation_folder',
                get_string('tracker:main_opensim_folder', 'sloodle'),
                get_string('tracker:main_opensim_folder:info', 'sloodle'),
                '',
                PARAM_RAW,
                50));				

// This text box will let the user enter the bd name to the main OpenSim installation
$settings->add( new admin_setting_configtext(
                'sloodle_tracker_main_opensim_db',
                get_string('tracker:main_opensim_db', 'sloodle'),
                get_string('tracker:main_opensim_db:info', 'sloodle'),
                '',
                PARAM_RAW,
                50));								
				
// This text box will let the user enter the path to the OpenSim templates folder
$settings->add( new admin_setting_configtext(
                'sloodle_tracker_opensim_templates_folder',
                get_string('tracker:templates_folder', 'sloodle'),
                get_string('tracker:templates_folder:info', 'sloodle'),
                '',
                PARAM_RAW,
                50));
                
// This text box will let the user enter the path to the OpenSim instances folder
$settings->add( new admin_setting_configtext(
                'sloodle_tracker_opensim_instances_folder',
                get_string('tracker:instances_folder', 'sloodle'),
                get_string('tracker:instances_folder:info', 'sloodle'),
                '',
                PARAM_RAW,
                50));

// This text box will let the user enter the address of the OpenSim database server
$settings->add( new admin_setting_configtext(
                'sloodle_tracker_opensim_db_host',
                get_string('tracker:opensim_db_host', 'sloodle'),
                get_string('tracker:opensim_db_host:info', 'sloodle'),
                'localhost',
                PARAM_RAW,
                25));

// This text box will let the user enter the user login for the OpenSim database server
$settings->add( new admin_setting_configtext(
                'sloodle_tracker_opensim_db_user',
                get_string('tracker:opensim_db_user', 'sloodle'),
                get_string('tracker:opensim_db_user:info', 'sloodle'),
                '',
                PARAM_RAW,
                25));
                
// This text box will let the user enter the password to login to the OpenSim database server
$settings->add( new admin_setting_configpasswordunmask(
                'sloodle_tracker_opensim_db_password',
                get_string('tracker:opensim_db_password', 'sloodle'),
                get_string('tracker:opensim_db_password:info', 'sloodle'),
                '',
                PARAM_RAW,
                25));


// This text box will let the user enter the lower port number reserved for OpenSim instances
$settings->add( new admin_setting_configtext(
                'sloodle_tracker_opensim_port_min',
                get_string('tracker:opensim_port_min', 'sloodle'),
                get_string('tracker:opensim_port_min:info', 'sloodle'),
                '9001',
                PARAM_INT,
                6));
                
// This text box will let the user enter the upper port number reserved for OpenSim instances
$settings->add( new admin_setting_configtext(
                'sloodle_tracker_opensim_port_max',
                get_string('tracker:opensim_port_max', 'sloodle'),
                get_string('tracker:opensim_port_max:info', 'sloodle'),
                '9100',
                PARAM_INT,
                6));
				
// This text box will let the user enter the port number reserved for OpenSim template
$settings->add( new admin_setting_configtext(
                'sloodle_tracker_opensim_port_template',
                get_string('tracker:opensim_port_template', 'sloodle'),
                get_string('tracker:opensim_port_template:info', 'sloodle'),
                '9000',
                PARAM_INT,
                6));				

?>