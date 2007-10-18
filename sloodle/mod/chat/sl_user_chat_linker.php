<?php
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

?>