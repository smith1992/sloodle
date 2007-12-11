<?php
    // Sloodle WebIntercom linker script
    // Allows a Sloodle WebIntercom in-world to communicate with a web-based Moodle chat-room
    // Part of the Sloodle project (www.sloodle.org)
    //
    // Copyright (c) 2007 Sloodle
    // Released under the GNU GPL
    //
    // Contributors:
    //   various - original design and implementation
    //   Peter R. Bloomfield - rewritten for Sloodle 0.2 (using new API/comms. format, and resolved outstanding issues)
    //
    
    // This script is expected to be requested by in-world objects.
    // The following parameters are required:
    //
    //   sloodlepwd = prim password for authentication
    //   sloodlecourseid = ID of the course which the chat-room is in
    //
    // The following parameters may be required for certain modes of operation:
    //
    //   sloodlemoduleid = course module instance ID of the chatroom we are connecting to
    //   sloodlechatmsg = the body of a chat message to be sent to the chatroom
    //   sloodleuuid = UUID of the avatar who entered the chat message (optional if 'sloodleavname' is specified)
    //   sloodleavname = name of the avatar who entered the chat message (optional if 'sloodleuuid' is specified)
    //
    // The script has 3 modes of operation.
    // If only the basic required parameters are specified, then it is in selection mode (i.e. selecting your chat-room).
    // The script will return a list of available chat-rooms in the data lines, with each line taking this format:
    //  "<moduleid>|<chatroomname>"
    // Note: success return code is 1, although will return -601 if no chat-rooms are found in course.
    //
    // The other mode of operation is chat mode (which interacts with a specific chat-room).
    // It requires that the 'sloodlemoduleid' parameter is specified.
    // At the basic level, this mode queries the list of messages in the chat-room, and returns the most recent ones.
    // However, if 'sloodlechatmsg', and 'sloodleuuid' and/or 'sloodleavname' are specified, then
    //  the script will attempt to add the chat message to the chat-room, attributing it to the specified user.
    //
    // In chat mode, if all goes well (i.e. no errors adding a chat message to the database), then each line of data
    //  following the status line will contain a recent chat message. The format is as follows:
    //  "<msgid>|<chattername>|<msg>"
    // The 'msgid' is the ID number of the chat message (unique across a whole site).
    // 'chattername' is the Moodle name of the person who chatted the message.
    // 'msg' is the chat message text.
    //
    
    require_once('../../config.php');
    require_once(SLOODLE_DIRROOT.'/sl_debug.php');
    require_once(SLOODLE_DIRROOT.'/lib/sl_lsllib.php');
    require_once('sl_webintercom_lib.php');
    
    sloodle_debug_output('<br/>');
    
    // Create an LSL handler and process the basic request data
    sloodle_debug_output('Creating LSL handler...<br/>');
    $lsl = new SloodleLSLHandler();
    sloodle_debug_output('Processing basic request data...<br/>');
    $lsl->request->process_request_data();
    
    // Ensure the request is authenticated
    sloodle_debug_output('Authenticating request...<br/>');
    $lsl->request->authenticate_request();
    
    // Obtain the additional parameters
    sloodle_debug_output('Obtaining additional parameters...<br/>');
    $sloodlechatmsg = optional_param('sloodlechatmsg', NULL, PARAM_RAW);
    
    // Try to find the specified course
    // (this will fail and terminate if the course is not visible)
    sloodle_debug_output('Finding requested course...<br/>');
    $course = $lsl->request->get_course_record();
    
    // Are we in select mode?
    if ($lsl->request->get_module_id() == NULL) {
        // Get a list of all visible chatrooms module instances in the specified course
        sloodle_debug_output('Fetching all chatrooms in course...<br/>');
        $chatrooms = sloodle_get_visible_chatrooms_in_course($lsl->request->get_course_id());
        // Did we fail to retrieve any?
        if (!is_array($chatrooms) || count($chatrooms) == 0) {
            $lsl->response->set_status_code(-601);
            $lsl->response->set_status_descriptor('MODULE');
            $lsl->response->add_data_line('No chatrooms found in course.');
            $lsl->response->render_to_output();
            exit();
        }
        
        // Start building the response
        sloodle_debug_output('Constructing response...<br/>');
        $lsl->response->set_status_code(1);
        $lsl->response->set_status_descriptor('OK');
        // Add the whole list of chatrooms
        sloodle_debug_output('Adding list of chatrooms...<br/>');
        foreach ($chatrooms as $id=>$chat) {
            $lsl->response->add_data_line(array($id, $chat->name));
        }
        
        // Output the response and finish
        sloodle_debug_output('Outputting response...<br/>');
        sloodle_debug_output('<pre>');
        $lsl->response->render_to_output();
        sloodle_debug_output('</pre>');
        exit();
    }
    
    
    // Fetch the course module instance for the chatroom (terminates with LSL error message on failure)
    sloodle_debug_output('Fetching course module instance...<br/>');
    $cmi = $lsl->request->get_course_module_instance('chat');
    // Now get the chatroom module itself
    sloodle_debug_output('Fetching activity module...<br/>');
    if (!$chatroom = get_record('chat', 'id', $cmi->instance)) {
        $lsl->response->set_status_code(-712);
        $lsl->response->set_status_descriptor('MODULE_INSTANCE');
        $lsl->response->add_data_line('Failed to retrieve chatroom record from database.');
        $lsl->render_to_output();
        exit();
    }
    
    
    // Has a chat message been specified?
    if ($sloodlechatmsg != NULL) {
        sloodle_debug_output('There is a chat message to add...<br/>');
        // Yes - attempt to login the user. Allow auto-registration, but do not terminate on failure
        sloodle_debug_output('Attempting to login user...<br/>');
        if (!$lsl->login_by_request(FALSE)) {
            // Login as the guest account
            sloodle_debug_output('Login failed... attempting to login as a guest...<br/>');
            $guestinfo = get_guest();
            $USER = get_complete_user_data('id',$guestinfo->id);
        }
        
        // Make sure the chat message is 'safe'
        sloodle_debug_output('Constructing chatroom message...<br/>');
        $sloodlechatmsg = addslashes(clean_text(stripslashes($sloodlechatmsg), FORMAT_MOODLE));
        // If an avatar name was specified, then prepend it to the chat message
        if ($lsl->request->get_avatar_name() != NULL) {
            $sloodlechatmsg = ' (SL) '.$lsl->request->get_avatar_name().': '.$sloodlechatmsg;
        }
        
        // Format our message data for a database record
        $message->chatid = $chatroom->id; // Pass the ID of chat room into the database.
        $message->userid = $USER->id; //Pass the ID of the chat user into the database.
        $message->message = $sloodlechatmsg; // Pass the message in.
        $message->timestamp = time(); // Add the unix timestamp
        
        // Insert the chat message into the database
        sloodle_debug_output('Adding message to database...<br/>');
        if (!insert_record('chat_messages', $message)) {
            // Something went wrong
            sloodle_debug_output('-&gt; Failed.<br/>');
            $lsl->response->set_status_code(-10101);
            $lsl->response->set_status_descriptor('MODULE_INSTANCE');
            $lsl->response->add_data_line('Failed to add chat message to database.');
            $lsl->render_to_output();
            exit();
        }
        
        // We successfully added a chat message... make a note of it as a side-effect
        $lsl->response->add_side_effect(10101);
    }
    
    
    // OK... *finally* we can get around to querying the database for chat messages.... hurrah!
    
    // Construct an SQL query to get all recent chat messages from the database
    sloodle_debug_output('Querying for recent chat messages...<br/>');
    $time_delay = time() - 1*60;
    $query = "  SELECT {$CFG->prefix}chat_messages.id, {$CFG->prefix}chat_messages.chatid, {$CFG->prefix}chat_messages.userid, {$CFG->prefix}user.firstname, {$CFG->prefix}user.lastname, {$CFG->prefix}chat_messages.message, {$CFG->prefix}chat_messages.groupid, {$CFG->prefix}chat_messages.system, {$CFG->prefix}chat_messages.timestamp
                FROM {$CFG->prefix}chat_messages
                LEFT JOIN {$CFG->prefix}user ON {$CFG->prefix}chat_messages.userid = {$CFG->prefix}user.id
                WHERE ((({$CFG->prefix}chat_messages.chatid)={$chatroom->id}) AND(({$CFG->prefix}chat_messages.timestamp)>=$time_delay))
                ORDER BY {$CFG->prefix}chat_messages.timestamp DESC";
    $messages = get_records_sql($query);


    // Make sure we got some results
    sloodle_debug_output('Iterating through recent chat messages...<br/>');
    // Make sure we got some chat messages
    if (is_array($messages)) {
        // Go through each chat message
        foreach($messages as $m) {
            // On each line of the data response, output one message in this format: "<id>|<name>|<text>"
            $lsl->response->add_data_line(array($m->id, $m->firstname.' '.$m->lastname, $m->message));
        }
    }
    
    // Construct and output the rest of the response
    sloodle_debug_output('Outputting response...<br/>');
    $lsl->response->set_status_code(1);
    $lsl->response->set_status_descriptor('OK');
    sloodle_debug_output('<pre>');
    $lsl->response->render_to_output();
    sloodle_debug_output('</pre>');

    exit();
    
/////OLD CODE:
/*
// This script allows the Sloodle WebIntercom (aka "ChatCast" or "ChatLogger") tool in Second Life to interact with a Moodle chatroom
// Part of the Sloodle Project (www.sloodle.org)

// Many contributors, including...
// Paul Andrews, Daniel Livingstone, Peter Bloomfield..... more?

require_once('../../config.php');
require_once('../../locallib.php');
require_once('../../login/sl_authlib.php');

// Get the standard chat library functions
require_once($CFG->dirroot .'/mod/chat/lib.php');

// Make sure a valid Second Life item is talking to us
sloodle_prim_require_script_authentication();

// We ought to be passed the ID's of the Moodle course and chatroom to which we are linking
// If we are passed just a course ID, then the script will output a list of all chatrooms in the course
// Otherwise, we are querying and/or writing to the chatroom
$sl_courseid = optional_param('courseid', 0, PARAM_INT);	
$sl_chatid = optional_param('chat_id', 0, PARAM_INT);

// Make sure we have at least one of the course or chatroom ID parameters
if ( ($sl_courseid == 0) && ($sl_chatid == 0) ) {
    sloodle_prim_render_error('Course ID and Chat ID missing');
    exit;
}

// Has the chatroom ID been omitted?
if ($sl_chatid == 0) {
    // Make sure there is a chatroom module
    if (! $chatroomset = get_recordset("chat", "course", $sl_courseid) ) {
        sloodle_prim_render_error('Could not find chat module');
        exit;
    }

    // Get a list of chatrooms
    $chatrooms = recordset_to_array($chatroomset);
    if (count($chatrooms) == 0) {
        // Error - no chatrooms found in this course
        sloodle_prim_render_error('No chatrooms found');
        exit;
    }

    // Go through each chatroom to format the data correctly
    $data = array();
    foreach($chatrooms as $cr) {
        $data[] = array(
                        $cr->id,
                        $cr->name
                        );
    }

    // Output the list of chatrooms
    sloodle_prim_render_output($data);
}

// Has a chat message been specified? (If not, then we are just viewing the chat room, and not sending any data to it)
$chat_message = optional_param('chat_message','', PARAM_RAW);
if (!empty($chat_message)) {

    // Initially login as the guest user
    $guestinfo = get_guest();
    $USER = get_complete_user_data('id',$guestinfo->id);    
    // Attempt to login the user from Sloodle
    // (If this fails, then the guest user is the fall-back)
    sloodle_prim_user_login();
    
    // Some systems *will* set $USER back to null or false at this point if login failed
    if (is_null($USER) || $USER == FALSE)
        $USER = get_complete_user_data('id',$guestinfo->id);

    // Extract the user ID
    $sl_userid = $USER->id;
    // Make sure the chat message is 'safe'
    $chat_message = addslashes(clean_text(stripslashes($chat_message), FORMAT_MOODLE));  // Strip bad tags 
    
    // Format our message data for a database record
    $message->chatid = $sl_chatid; //Pass the ID of chat room into the database.
    $message->userid = $sl_userid; //Pass the ID of the Chat Bot into the database.
    $message->message = $chat_message; // Pass the message in.
    $message->timestamp = time(); // Add the unix timestamp
    // Insert the chat message into the database
    if (!insert_record('chat_messages', $message)) {
        // Something went wrong...
        sloodle_prim_render_error('Could not insert a chat message!');
    }
}

// Construct an SQL query to get all recent chat messages from the database
$time_delay = time() - 1*60;
$query = 'SELECT mdl_chat_messages.id, mdl_chat_messages.chatid, mdl_chat_messages.userid, mdl_user.firstname, mdl_user.lastname, mdl_chat_messages.message, mdl_chat_messages.groupid, mdl_chat_messages.system, mdl_chat_messages.timestamp
FROM mdl_chat_messages LEFT JOIN mdl_user ON mdl_chat_messages.userid = mdl_user.id
WHERE (((mdl_chat_messages.chatid)='.$sl_chatid.') AND((mdl_chat_messages.timestamp)>='.$time_delay.')) ORDER BY mdl_chat_messages.timestamp DESC';

// Obtain the resulting recordset as an array
$rs = get_recordset_sql($query);
$results = recordset_to_array($rs);

// Make sure we got some results
if ($results) {
    // Go through each chat message
    foreach($results as $m) {
        // Output the message in the format "Line: <number> <firstname> <lastname> <message>
        
        //print "Line: ".$m->id . " "; // We would like to do this...
        // ... but currently (2007-10-17) the in-world chat logger script expects the line number to be exactly 4 digits long, so format it as such:
        print "Line: " . sprintf("%04d", $m->id) . " ";
        
        print $m->firstname . " ".$m->lastname." ";
        print $m->message. " ";
    }
}
exit;
*/
?>