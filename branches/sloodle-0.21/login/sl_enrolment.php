<?php

/**
* Sloodle enrolment forwarding script.
* This script will force the user to login, then forward them to the appropriate Moodle course enrolment page.
* This is necessary due to the behaviour of some browsers when given a URL by SL.
*
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @contributor Peter R. Bloomfield
* @package sloodlelogin
*/

require_once('../config.php');

$sloodlecourseid = required_param('sloodlecourseid', PARAM_INT);
require_login();

redirect("{$CFG->wwwroot}/course/enrol.php?id=$sloodlecourseid");

exit;

?>
