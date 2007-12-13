<?php 
    // Sloodle blog linker
    // Allows the Sloodle Toolbar object in Second Life to write to a user's Moodle blog
    // Part of the Sloodle project (www.sloodle.org)
    //
    // Copyright (c) 2007 Sloodle
    // Released under the GNU GPL
    //
    // Contributors:
    //   various - original design and implementation
    //   Peter R. Bloomfield - udpated to use new API and communications format
    //

    // This script is expected to be accessed from in-world.
    // The following parameters are required:
    //
    //   sloodlepwd = the prim password used to authenticate the blog toolbar for this site
    //   sloodleuuid = the SL UUID of the agent making the blog entry (optional if 'sloodleavname' is specified)
    //   sloodleavname = the name of the avatar making the blog entry (optional if 'sloodleuuid' is specified)
    //   sloodleblogsubject = the subject line of the blog entry
    //   sloodleblogbody = the main body of the blog entry
    //
    

    require_once('../config.php');
    require_once(SLOODLE_DIRROOT.'/sl_debug.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');
    require_once($CFG->dirroot .'/blog/lib.php'); // Moodle blog functionality
    
    // Create an LSL handler and process the basic request data
    sloodle_debug_output('Creating LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output('Processing basic request data...<br/>');
    $lsl->request->process_request_data();
    
    // Ensure the request is authenticated
    sloodle_debug_output('Authenticating request...<br/>');
    $lsl->request->authenticate_request();
    // Attempt to login the user
    sloodle_debug_output('Logging-in user...<br/>');
    $lsl->login_by_request();
    
    
    // Obtain the additional parameters for the blog entry
    sloodle_debug_output('Obtaining additional parameters...<br/>');
    $sloodleblogsubject = $lsl->request->required_param('sloodleblogsubject', PARAM_RAW);
    $sloodleblogbody = $lsl->request->required_param('sloodleblogbody', PARAM_RAW);

    // We need to know if all header data was retrieved
    $use_slurl = (isset($_SERVER['HTTP_X_SECONDLIFE_REGION']) && isset($_SERVER['HTTP_X_SECONDLIFE_LOCALPOSITION']));
    // Use the HTTP headers added by SL to get the region and position data, and construct a SLurl from them
    if ($use_slurl) {
        sloodle_debug_output('Reading header data...<br/>');
        $region = $_SERVER['HTTP_X_SECONDLIFE_REGION'];
        $region = substr ( $region,0, strpos($region, '(' ) - 1 );
        $position = $_SERVER['HTTP_X_SECONDLIFE_LOCAL_POSITION'];
        sloodle_debug_output('Constructing SLurl...<br/>');
        sscanf($position, "(%f, %f, %f)", $x, $y, $z);
        $slurl = "http://slurl.com/secondlife/" .$region ."/" .$x ."/" .$y ."/" .$z;
        $slurl = '<a href="' .$slurl .'">' .$region .'</a>';
    } else {
        sloodle_debug_output('Header data not available. Skipping SLurl...<br/>');
        $slurl = '[location unknown]';
    }

    // Make all string data safe
    sloodle_debug_output('Processing data...<br/>');
    $sloodleblogsubject = addslashes(clean_text(stripslashes($sloodleblogsubject), FORMAT_MOODLE));
    $sloodleblogbody = addslashes(clean_text(stripslashes($sloodleblogbody), FORMAT_MOODLE));
    // Construct the final blog body
    sloodle_debug_output('Constructing entry text...<br/>');
    $sloodleblogbody = "Posted from Second Life: " .$slurl ."\n\n" .$sloodleblogbody;
    
    // Write a blog entry into database
    sloodle_debug_output('Constructing database entry...<br/>');
    $blogEntry = new stdClass();
    $blogEntry->subject = $sloodleblogsubject;
    $blogEntry->summary = $sloodleblogbody;
    $blogEntry->module = 'blog';
    $blogEntry->userid = $lsl->user->get_moodle_user_id();
    $blogEntry->format = 1;
    $blogEntry->publishstate = 'site'; // 'draft' or 'site' or 'public'
    $blogEntry->lastmodified = time();
    $blogEntry->created = time();
    
    // Insert the new blog entry, making sure it is successful
    sloodle_debug_output('Attempting to add post to database...<br/>');
    if ($entryID = insert_record('post',$blogEntry)) {
        sloodle_debug_output('-&gt; Success.<br/>');
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
    } else {
        sloodle_debug_output('-&gt; Failed.<br/>');
        $lsl->response->set_status_code(-1);
        $lsl->response->set_status_descriptor('ERROR');
        $lsl->response->add_data_line('Failed to insert blog entry into database.');
    }
    
    // Output the response
    sloodle_debug_output('Outputting response...<br/>');
    $lsl->response->render_to_output();
    
    exit();
?>