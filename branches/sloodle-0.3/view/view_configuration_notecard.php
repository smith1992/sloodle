<html>
<head>
 <title></title>
</head>
<body>
<?php
    /**
    * Displays the text for a configuration notecard.
    * This page is expected to be shown in a small popup window.
    * It ensures the user has the capability to manage activities in the context of the given module.
    *
    * @package sloodle
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor Peter R. Bloomfield
    *
    */
    
    /** Sloodle/Moodle configuration script. */
    require_once('../sl_config.php');
    /** Sloodle core library functionality */
    require_once(SLOODLE_DIRROOT.'/lib.php');
    /** General Sloodle functions. */
    require_once(SLOODLE_LIBROOT.'/general.php');
    
    
    // Fetch our request parameters
    $s = required_param('s', PARAM_INT); // Sloodle instance ID
    
    // Fetch string table text
    $strsloodle = get_string('modulename', 'sloodle');
    $strsloodles = get_string('modulenameplural', 'sloodle');
        
    // Attempt to fetch the course module instance
    if ($s) {
        if (! $cm = get_coursemodule_from_instance('sloodle', $s)) {
            error("Instance ID was incorrect");
        }
    } else {
        error('Must specify a course module or a module instance');
    }
    
    // Ensure the user is logged-in
    if (!isloggedin()) {
        error(get_string('loggedinnot'));
        exit();
    }
    
    // Make sure the user has the appropriate capability in the current context
    $context = get_context_instance(CONTEXT_MODULE, $cm->id);
    require_capability('moodle/course:manageactivities', $context);
    
    
    // Fetch the appropriate data
    if (!$sloodle = get_record('sloodle', 'id', $s)) error(get_string('databasequeryfailed','sloodle'));
    if (!$controller = get_record('sloodle_controller', 'sloodleid', $s)) error(get_string('databasequeryfailed','sloodle'));
    
    // Construct and render the appropriate data
    $cfgtext = '';
    $cfgtext .= "set:sloodleserverroot|{$CFG->wwwroot}\n";
	$cfgtext .= "set:pwd|{$controller->password}\n";
    $cfgtext .= "set:sloodle_courseid|{$sloodle->course}";    
    
    // Get the string table text
    $strcfgheader = get_string('cfgnotecard:header', 'sloodle');
    $strcfginstructions = get_string('cfgnotecard:instructions', 'sloodle');
    $strcfgsecurity = get_string('cfgnotecard:security', 'sloodle');
    $strcfgsetnote = get_string('cfgnotecard:setnote', 'sloodle');
    
    echo <<<XXXEODXXX
 <div style="text-align:center; font-family:sans-serif;">
  <h3>$strcfgheader</h3>
  <p>$strcfginstructions</p>
  <p>$strcfgsecurity</p>
  <textarea cols=60 rows=4>set:sloodleserverroot|{$CFG->wwwroot}
set:pwd|{$controller->password}
set:sloodle_courseid|{$sloodle->course}</textarea>
  <p>$strcfgsetnote</p>
 </div>
XXXEODXXX;
?>

</body>
</html>
