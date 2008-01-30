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
    
    header ('Content-Type: text/plain; charset=UTF-8');

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

?>
