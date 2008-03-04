<?php  // $Id: insert.php,v 1.2 2005/02/07 20:33:43 skodak Exp $
	// Edited by Jeremy Kemp for the Sloodle project.
	//This file should sit in the www ROOT dir of you main Moodle course.
    
    /**
    * @ignore
    */

    require_once('../../../../config.php');
    require_once($CFG->dirroot .'/mod/chat/lib.php');

$obj_id = optional_param('obj_id', PARAM_ALPHANUM); 
$obj_name = optional_param('obj_name', PARAM_ALPHANUM); 
$avi_id = optional_param('avi_id', PARAM_ALPHANUM); 
$avi_name = optional_param('avi_name', PARAM_ALPHANUM); 
$course = optional_param('course', PARAM_ALPHANUM); 
$region = optional_param('region', PARAM_ALPHANUM); 
$x = optional_param('x', PARAM_ALPHANUM); 
$y = optional_param('y', PARAM_ALPHANUM); 
$z = optional_param('z', PARAM_ALPHANUM); 
$dropbox_name = optional_param('dropbox_name', PARAM_ALPHANUM); 
$dropbox_id = optional_param('dropbox_id', PARAM_ALPHANUM); 
$obj_type = optional_param('obj_type', PARAM_ALPHANUM); 
$prim_count = optional_param('prim_count', PARAM_ALPHANUM); 

$message->obj_id = $obj_id;
$message->obj_name = $obj_name;
$message->avi_id= $avi_id;
$message->avi_name = $avi_name;
$message->course = $course;
$message->region = $region;
$message->x = $x;
$message->y = $y;
$message->z = $z;
$message->dropbox_name = $dropbox_name; 
$message->dropbox_id = $dropbox_id;
$message->obj_type = $obj_type;
$message->prim_count = $prim_count;

$message->timeadded = time(); // Add the unix timestamp

	if (!insert_record('sloodle_prim', $message)) {  //PA: So here $message is our array :o)
	        error('Could not insert prim details!');
	}


Print "Received---------"; //DEBUG HERE
Print "Current results:<hr>";
Print $obj_id;

Print "<br>";
Print $obj_name;
Print "<br>";
Print $avi_id;
Print "<br>";
Print $avi_name;
Print "<br>";
Print $course;
Print "<br>";
Print $region;
Print "<br>";
Print $x;
Print "<br>";
Print $y;
Print "<br>";
Print $z;
Print "<br>";
Print $dropbox_name;
Print "<br>";
Print $dropbox_id;
Print "<br>";
Print $obj_type;
Print "<br>";



/*
A note on Moodle function required_param
****************************************
required_param($parname, $type=PARAM_CLEAN)   X-Ref
Returns a particular value for the named variable, taken from
POST or GET.  If the parameter doesn't exist then an error is
thrown because we require this variable.

This function should be used to initialise all required values
in a script that are based on parameters.  Usually it will be
used like this:
$id = required_param('id');

param: string $parname the name of the page parameter we want
param: int $type expected type of parameter
return: mixed   

These are the most commonly used PARAM_* types and their proper uses.

    * PARAM_CLEAN is deprecated and you should try to use a more specific type.
    * PARAM_TEXT should be used for cleaning data that is expected to be plain text. It will strip all html tags. But will still let tags for multilang support through.
    * PARAM_RAW means no cleaning whatsoever, it is used mostly for data from the html editor. Data from the editor is later cleaned before display using format_text() function. PARAM_RAW can also be used for data that is validated by some other way or printed by p() or s().
    * PARAM_INT should be used for integers.
    * PARAM_ACTION is an alias of PARAM_ALPHA and is used for hidden fields specifying form actions. 
	
	
PA: This is the full list of parameters as defined in Moodlelib.php	

Parameter constants - if set then the parameter is cleaned of scripts etc

define('PARAM_RAW',      0x0000);
define('PARAM_CLEAN',    0x0001);
define('PARAM_INT',      0x0002);
define('PARAM_INTEGER',  0x0002);  // Alias for PARAM_INT
define('PARAM_ALPHA',    0x0004);
define('PARAM_ACTION',   0x0004);  // Alias for PARAM_ALPHA
define('PARAM_FORMAT',   0x0004);  // Alias for PARAM_ALPHA
define('PARAM_NOTAGS',   0x0008);
define('PARAM_FILE',     0x0010);
define('PARAM_PATH',     0x0020);
define('PARAM_HOST',     0x0040);  // FQDN or IPv4 dotted quad
define('PARAM_URL',      0x0080);
define('PARAM_LOCALURL', 0x0180);  // NOT orthogonal to the others! Implies PARAM_URL!
define('PARAM_CLEANFILE',0x0200);
define('PARAM_ALPHANUM', 0x0400);  //numbers or letters only
define('PARAM_BOOL',     0x0800);  //convert to value 1 or 0 using empty()
define('PARAM_CLEANHTML',0x1000);  //actual HTML code that you want cleaned and slashes removed
define('PARAM_ALPHAEXT', 0x2000);  // PARAM_ALPHA plus the chars in quotes: "/-_" allowed
define('PARAM_SAFEDIR',  0x4000);  // safe directory name, suitable for include() and require()
**************************************** 
	
	
	
	
	// This next bit is new - we want to pass the other chat params to Moodle via Second Life.
	
//      $message->chatid = $chatuser->chatid; PA: Old Moodle code.
//		This creates an array for all the data.
		$message->chatid = $sl_chatid; //Pass the ID of chat room into the database.
		$message->userid = $sl_userid; //Pass the ID of the Chat Bot into the database.
        $message->message = $chat_message; // Pass the message in.
        $message->timeadded = time(); // Add the unix timestamp
			
			
		if (!insert_record('chat_messages', $message)) {  //PA: So here $message is our array :o)
						error('Could not insert a chat message!');
					}
			
			//        $chatuser->lastmessageping = time() - 2;
			//        update_record('chat_users', $chatuser);
			
			//        PA: This last bit updates Moodle's Global Logs - I've removed it for now...
			//        if ($cm = get_coursemodule_from_instance('chat', $chat->id, $course->id)) {
			//            add_to_log($course->id, 'chat', 'talk', "view.php?id=$cm->id", $chat->id, $cm->id);
			//        }
    }

    We dont need this last bit - it refresehs the Moodle chat window for the user RUNNING this script.

if ($chatuser->version == 'header_js') {
        /// force msg referesh ASAP
        echo '<script type="text/javascript">parent.jsupdate.location.href = parent.jsupdate.document.anchors[0].href;</script>';
    }

    redirect('../empty.php');


Now we need to output the chat - unlike Moodle's "Real time" chat we only need to update the output each time this page is called by Second Life.

So first let's get the Chat messages for the chat room we are in using Moodle's built in datalib function "Get Records".


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

$query = 'SELECT mdl_chat_messages.id, mdl_chat_messages.chatid, mdl_chat_messages.userid, mdl_user.firstname, mdl_user.lastname, mdl_chat_messages.message, mdl_chat_messages.groupid, mdl_chat_messages.system, mdl_chat_messages.timestamp
FROM mdl_chat_messages LEFT JOIN mdl_user ON mdl_chat_messages.userid = mdl_user.id
WHERE (((mdl_chat_messages.chatid)='.$sl_chatid.') AND((mdl_chat_messages.timestamp)>='.$time_delay.')) ORDER BY mdl_chat_messages.timestamp DESC';


$res = mysql_query($query);

//$messages = mysql_fetch_array($res); No good - just gives me the 1st result...!

// Lets get all results...

while($messages = mysql_fetch_array($res))
{
Print "Line: ".$messages['id'] . " ";
//Print "Chat ID: ".$messages['chatid'] . " ";
//Print "User ID: ".$messages['userid'] . " ";
Print $messages['firstname'] . " ".$messages['lastname']." ";
//Print "Lastname: ".$messages['lastname']. " ".$messages['lastname']." ";
Print $messages['message']. " ";
//Print "Time Stamp: ".$messages['timestamp'] . " <br>";



}


*/

?>
