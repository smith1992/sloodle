<?php
// Sloodle choice linker
// Allows Sloodle "choice" objects in Second Life to interact with Moodle choice module instances
// Part of the Sloodle Project
// See www.sloodle.org for more information
//
// Copyright (c) 2007 Sloodle
// Release under the GNU GPL v3
//
// Contributors:
//  Peter R. Bloomfield - original design and implementation
//


////////////////////////////////////////////////////////////
//
// The script is expected to be access directly by objects from within SL, and behaves in 3 modes.
// The mode depends on which parameters are specified (see below).
//
// MODES //
//  1. Available choices query = returns a list of choice module instances available in the specified course
//  2. Choice details query = returns the details for a specific choice instance
//  3. Option selection = informs Moodle that a user has made a selection
//
//
// PARAMETERS //
// *Always* required:
//    sloodlepwd = prim password for accessing site/course
//    sloodlecourseid = ID of the course being accessed
//
// Required for modes 2 and 3:
//    sloodlechoiceid = ID of the choice being accessed
//
// Required for mode 3:
//    optionid = ID of the option being selected
//    uuid = UUID (key) of avatar making the selection
//    avname = name of the avatar making the selection
// (NOTE: the script will function even if only uuid *or* avname is specified, but both is better)
//
// The script will default to mode 1.
// If the "optionid" is specified, it will attempt to adopt mode 3.
// Otherwise, if the additional "sloodlechoiceid" parameter is specified, then it will adopt mode 2.
//
////////////////////////////////////////////////////////////


require_once("../../config.php"); // Sloodle/Moodle configuration
//require_once("../../lib.php"); // General Sloodle library
//require_once("../../locallib.php"); // Local Sloodle library
require_once("../../login/authlib.php"); // Sloodle authentication library
require_once("../../iolib.php"); // Sloodle IO library
require_once("sl_choice_lib.php"); // Sloodle choice library


// Authenticate the object making this request (requires specification of "sloodlepwd" parameter)
sloodle_prim_require_script_authentication(); // Replace this with updated version!

// We require this 
$param_sloodlecourseid = sloodle_required_param("sloodlecourseid", PARAM_INT);
// Obtain other possible parameters
$param_sloodlechoiceid = optional_param("sloodlechoiceid", NULL, PARAM_INT);
$param_sloodleoptionid = optional_param("sloodleoptionid", NULL, PARAM_INT);

// Validate the parameters
if (is_null($param_sloodlecourseid) == false && $param_sloodlecourseid < 0) {
//...
}

// TODO: more!

?>