<?php 
    
    // This file is part of the Sloodle project (www.sloodle.org) and is released under the GNU GPL v3.
    
    /**
    * Sloodle blog linker.
    *
    * Allows the Sloodle Toolbar HUD object in Second Life to write to a user's Moodle blog.
    *
    * @todo Implement ability to read blog entries too.
    *
    * @package sloodleblog
    * @copyright Copyright (c) 2007-8 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor (various)
    * @contributor Peter R. Bloomfield
    *
    */

    // This script is expected to be accessed from in-world.
    // The following parameters are required:
    //
    //   sloodlepwd = the password used to authenticate the request (user-centric authentication)
    //   sloodleuuid = the SL UUID of the agent making the blog entry
    //   sloodleavname = the name of the avatar making the blog entry
    //   sloodleblogsubject = the subject line of the blog entry
    //   sloodleblogbody = the main body of the blog entry
    //

    /** Lets Sloodle know we are in a linker script. */
    define('SLOODLE_LINKER_SCRIPT', true);
    
    /** Grab the Sloodle/Moodle configuration. */
    require_once('../sl_config.php');
    /** Include the Sloodle PHP API. */
    require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
    
    // Authenticate the request as user-specific, and load a blog module
    $sloodle = new SloodleSession();
    $sloodle->authenticate_user_request();
    $sloodle->load_module('blog', false); // No database data required
    // Attempt to validate the avatar
    $sloodle->validate_avatar();

    // Get our additional parameters
    $sloodleblogsubject = $sloodle->request->required_param('sloodleblogsubject');
    $sloodleblogbody = $sloodle->request->required_param('sloodleblogbody');
    $sloodleblogvisibility = $sloodle->request->optional_param('sloodleblogvisibility');
    
    // We need to know if all header data was retrieved
    $use_slurl = (isset($_SERVER['HTTP_X_SECONDLIFE_REGION']) && isset($_SERVER['HTTP_X_SECONDLIFE_LOCAL_POSITION']));
    // Use the HTTP headers added by SL to get the region and position data, and construct a SLurl from them
    if ($use_slurl) {
        $region = $_SERVER['HTTP_X_SECONDLIFE_REGION'];
        $region = substr ( $region,0, strpos($region, '(' ) - 1 );
        $position = $_SERVER['HTTP_X_SECONDLIFE_LOCAL_POSITION'];
        sloodle_debug_output('Constructing SLurl...<br/>');
        sscanf($position, "(%f, %f, %f)", $x, $y, $z);
        $slurl = "http://slurl.com/secondlife/" .$region ."/" .$x ."/" .$y ."/" .$z;
        $slurl = '<a href="' .$slurl .'">' .$region .'</a>';
    } else {
        $slurl = '[location unknown]';
    }
    
    // Make all string data safe
    $sloodleblogsubject = addslashes(clean_text(stripslashes($sloodleblogsubject), FORMAT_PLAIN));
    $sloodleblogbody = addslashes(clean_text(stripslashes($sloodleblogbody), FORMAT_MOODLE));
    $sloodleblogvisibility = addslashes(clean_text(stripslashes($sloodleblogvisibility), FORMAT_PLAIN));
    // Construct the final blog body
    $sloodleblogbody = get_string('postedfromsl','sloodle').': '.$slurl ."\n\n" .$sloodleblogbody;
    
    // Write the entry to the database
    if ($sloodle->module->add_entry($sloodleblogsubject, $sloodleblogbody, $sloodleblogvisibility)) {
        $sloodle->response->set_status_code(1);
        $sloodle->response->set_status_descriptor('OK');
    } else {
        $sloodle->response->set_status_code(-1);
        $sloodle->response->set_status_descriptor('ERROR');
        $sloodle->response->add_data_line('Failed to insert blog entry into database.');
    }
    
    // Output the response
    $sloodle->response->render_to_output();
    exit();
?>