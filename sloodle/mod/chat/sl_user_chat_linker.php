<?php  // $Id: insert.php,v 1.2 2005/02/07 20:33:43 skodak Exp $
	// Edited by Paul Andrews for the Sloodle project.
	//This file should sit in the www ROOT dir of you main Moodle course.

//    require_once('../../../../config.php');

require_once('../../config.php');
require_once('../../locallib.php');
require_once('../../login/sl_authlib.php');

require_once($CFG->dirroot .'/mod/chat/lib.php');

sloodle_prim_require_script_authentication(); // make sure the client that's talking to us is allowed to do so.
//print "Moodle user ID is $sl_userid";

	// This next bit is new - we want to pass the other chat params to Moodle via Second Life.
	$sl_courseid = optional_param('courseid', 0, PARAM_INT);	
	$sl_chatid = optional_param('chat_id', 0, PARAM_INT);  //Pass the ID of the Chat room to log the chat in.

	if ( ($sl_courseid == 0) && ($sl_chatid == 0) ) {
		sloodle_prim_render_error('Course ID and Chat ID missing');
		exit;
	}

	if ($sl_chatid == 0) {
		if (! $chatroomset = get_recordset("chat", "course", $sl_courseid) ) {
			sloodle_prim_render_error('Could not find chat module');
			exit;
		}

		$chatrooms = recordset_to_array($chatroomset);
		if (count($chatrooms) == 0) {
			sloodle_prim_render_error('No chatrooms found');
			exit;
		}

		$data = array();
		foreach($chatrooms as $cr) {
			$data[] = array(
				$cr->id,
				$cr->name
			);
		}

		sloodle_prim_render_output($data);
		
	}
	// $sl_userid = required_param('user_id', PARAM_INT);  /*Pass the ID of the Chat Bot account into Moodle, this is the number at the END of the URL of the page showing the profile of the chatbot.  EG: http://www.sloodle.com/user/view.php?id=158 Thus ID for Sloodle.com Chatbot = 158. 
	$chat_message = optional_param('chat_message','', PARAM_RAW); // PA: This has changed , the message is now optional as you may just want to VIEW the chat room...
	

    //if (!$chatuser = get_record('chat_users', 'userid', $sl_userid)) {
     //   sloodle_prim_render_error('Not logged in!  Please log the chatbot INTO the chat room you wish to use.'); //PA: This Uses Moodle's built in Error handler - :o)
	//}
/// Add the message to the database - as long as the message isn't empty!
//	echo $chat_message;
    if (!empty($chat_message)) {

		sloodle_prim_require_user_login(); // check the avatar name and/or uuid argument s, and log the user in (creating a $USER) variable, or return errors to the clie nt.  

		$sl_userid = $USER->id;


		$chat_message = addslashes(clean_text(stripslashes($chat_message), FORMAT_MOODLE));  // Strip bad tags 


//      $message->chatid = $chatuser->chatid; PA: Old Moodle code.
//		This creates an array for all the data.
		$message->chatid = $sl_chatid; //Pass the ID of chat room into the database.
		$message->userid = $sl_userid; //Pass the ID of the Chat Bot into the database.
        $message->message = $chat_message; // Pass the message in.
        $message->timestamp = time(); // Add the unix timestamp

		/* PA: Notes on insert_record function - this lives in datalib.php
 		* Insert a record into a table and return the "id" field if required
		 *
		 * If the return ID isn't required, then this just reports success as true/false.
		 * $dataobject is an object containing needed data
		 *
		 * @uses $db
		 * @uses $CFG
		 * @param string $table The database table to be checked against.
		 * @param array $dataobject A data object with values for one or more fields in the record
		 * @param boolean $returnid Should the id of the newly created record entry be returned? If this option is not requested then true/false is returned.
		 * @param string $primarykey The primary key of the table we are inserting into (almost always "id")

		 function insert_record($table, $dataobject, $returnid=true, $primarykey='id')  */
			
			
					if (!insert_record('chat_messages', $message)) {  //PA: So here $message is our array :o)
						sloodle_prim_render_error('Could not insert a chat message!');
					}
			
			//        $chatuser->lastmessageping = time() - 2;
			//        update_record('chat_users', $chatuser);
			
			//        PA: This last bit updates Moodle's Global Logs - I've removed it for now...
			//        if ($cm = get_coursemodule_from_instance('chat', $chat->id, $course->id)) {
			//            add_to_log($course->id, 'chat', 'talk', "view.php?id=$cm->id", $chat->id, $cm->id);
			//        }
    }

/*

Now we need to output the chat - unlike Moodle's "Real time" chat we only need to update the output each time this page is called by Second Life.

So first let's get the Chat messages for the chat room we are in using Moodle's built in datalib function "Get Records".

/**
 * Get a number of records as an array of objects
 *
 * Can optionally be sorted eg "time ASC" or "time DESC"
 * If "fields" is specified, only those fields are returned
 * The "key" is the first column returned, eg usually "id"
 * limitfrom and limitnum must both be specified or not at all
 *
 * @uses $CFG
 * @param string $table The database table to be checked against.
  * @param string $field The database field name to search
  * @param string $value The value to search for in $field
  * @param string $sort Sort order (as valid SQL sort parameter)
 * @param string $fields A comma separated list of fields to be returned from the chosen table.
 * @param int $limitfrom Return a subset of results starting at this value (*must* set $limitnum)
 * @param int $limitnum Return a subset of results, return this number (*must* set $limitfrom)
 * @return array|false Returns an array of found records (as objects) or false if no records or error occured.

EG function get_records($table, $field='', $value='', $sort='', $fields='*', $limitfrom='', $limitnum='')

 */


// Updated query!
/*

/**
 * Get a number of records as an array of objects
 *
 * The "key" is the first column returned, eg usually "id"
 * The sql statement is provided as a string.
 *
 * @uses $CFG
 * @uses $db
 * @param string $sql The SQL string you wish to be executed.
 * @return array|false Returns an array of found records (as objects) or false if no records or error occured.
 */
//function get_records_sql($sql)

$time_delay = time() - 1*60;


$query = 'SELECT mdl_chat_messages.id, mdl_chat_messages.chatid, mdl_chat_messages.userid, mdl_user.firstname, mdl_user.lastname, mdl_chat_messages.message, mdl_chat_messages.groupid, mdl_chat_messages.system, mdl_chat_messages.timestamp
FROM mdl_chat_messages LEFT JOIN mdl_user ON mdl_chat_messages.userid = mdl_user.id
WHERE (((mdl_chat_messages.chatid)='.$sl_chatid.') AND((mdl_chat_messages.timestamp)>='.$time_delay.')) ORDER BY mdl_chat_messages.timestamp DESC';

$rs = get_recordset_sql($query);
$results = recordset_to_array($rs);
//$res = mysql_query($query);

//var_dump($results);
//exit;
if ($results) {
	foreach($results as $m) {
		print "Line: ".$m->id . " ";
		//Print "Chat ID: ".$messages['chatid'] . " ";
		//Print "User ID: ".$messages['userid'] . " ";
		print $m->firstname . " ".$m->lastname." ";
		//Print "Lastname: ".$messages['lastname']. " ".$messages['lastname']." ";
		print $m->message. " ";
		//Print "Time Stamp: ".$messages['timestamp'] . " <br>";
	}
}
exit;
//$messages = mysql_fetch_array($res); No good - just gives me the 1st result...!

// Lets get all results...

while($messages = mysql_fetch_array($res))
{
print "Line: ".$messages['id'] . " ";
//Print "Chat ID: ".$messages['chatid'] . " ";
//Print "User ID: ".$messages['userid'] . " ";
print $messages['firstname'] . " ".$messages['lastname']." ";
//Print "Lastname: ".$messages['lastname']. " ".$messages['lastname']." ";
print $messages['message']. " ";
//Print "Time Stamp: ".$messages['timestamp'] . " <br>";



}

?>
