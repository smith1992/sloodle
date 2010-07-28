// SLOODLE WebIntercom - modified for SLOODLE for Schools.
// Links in-world SL (text) chat with a Moodle chatroom
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-10 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Paul Andrews
//  Daniel Livingstone
//  Jeremy Kemp
//  Edmund Edgar
//  Peter R. Bloomfield
//  Paul Preibisch
//
integer SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST=-1828374651; // this channel is used to send status codes for translation to the error_messages lsl script
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
string SLOODLE_CHAT_LINKER = "mod/chat-1.0/linker.php";

string SLOODLE_OBJECT_TYPE = "chat-1.0";

string sloodleserverroot = "URL_NOT_STORED";
integer sloodlemoduleid = 0;
integer sloodlelistentoobjects = 0; // Should this object listen to other objects?
integer sloodleautodeactivate = 1; // Should the WebIntercom auto-deactivate when not in use?

string MOODLE_NAME = "(SL)";
string MOODLE_NAME_OBJECT = "(SL-object)";

integer listenctrl = 0; // Listening for initial control... i.e. activation/deactivation
list cmddialog = []; // Alternating list of keys and timestamps, indicating who activated a command dialog (during logging) and when

list recordingkeys = []; // Keys of people we're recording
list recordingnames = []; // Names of people we're recording

key httpchat = NULL_KEY; // Request used to send/receive chat
integer message_id = 0; // ID of the last message received from Moodle

float sensorrange = 30.0; // Senses somewhat beyond chat range
float sensorrate = 60.0; // Scan every minute
integer nosensorcount = 0; // How many recent sensor sweeps (while logging) have detected no avatars?
integer nosensormax = 2; // How many failed sensor sweeps should we allow before auto-deactivating?


///// TRANSLATION /////

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE = -1928374652;

// Translation output methods
string SLOODLE_TRANSLATE_LINK = "link";             // No output parameters - simply returns the translation on SLOODLE_TRANSLATION_RESPONSE link message channel
string SLOODLE_TRANSLATE_SAY = "say";               // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_WHISPER = "whisper";       // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_SHOUT = "shout";           // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_REGION_SAY = "regionsay";  // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_OWNER_SAY = "ownersay";    // No output parameters
string SLOODLE_TRANSLATE_DIALOG = "dialog";         // Recipient avatar should be identified in link message keyval. At least 2 output parameters: first the channel number for the dialog, and then 1 to 12 button label strings.
string SLOODLE_TRANSLATE_LOAD_URL = "loadurl";      // Recipient avatar should be identified in link message keyval. 1 output parameter giving URL to load.
string SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";  // 2 output parameters: colour <r,g,b>, and alpha value
string SLOODLE_TRANSLATE_IM = "instantmessage";     // Recipient avatar should be identified in link message keyval. No output parameters.

// Send a translation request link message
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}

///// ----------- /////


///// FUNCTIONS /////

sloodle_error_code(string method, key avuuid,integer statuscode)
{
    llMessageLinked(LINK_SET, SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST, method+"|"+(string)avuuid+"|"+(string)statuscode, NULL_KEY);
} 

sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// Checks if the given agent is permitted to control this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_ctrl(key id)
{
    return (id == llGetOwner());
}

// Checks if the given agent is permitted to user this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_use(key id)
{
    return TRUE;
}

// Add the given agent to our command dialog list
sloodle_add_cmd_dialog(key id)
{
    // Does the person already exist?
    integer pos = llListFindList(cmddialog, [id]);
    if (pos < 0) {
        // No - add the agent to the end
        cmddialog += [id, llGetUnixTime()];
    } else {
        // Yes - update the time
        cmddialog = llListReplaceList(cmddialog, [llGetUnixTime()], pos + 1, pos + 1);
    }
}

// Remove the given agent from our command dialog list
sloodle_remove_cmd_dialog(key id)
{
    // Is the person in the list?
    integer pos = llListFindList(cmddialog, [id]);
    if (pos >= 0) {
        // Yes - remove them and their timestamp
        cmddialog = llDeleteSubList(cmddialog, pos, pos + 1);
    }
}

// Purge the command dialog list of old activity
sloodle_purge_cmd_dialog()
{
    // Store the current timestamp
    integer curtime = llGetUnixTime();
    // Go through each command dialog
    integer i = 0;
    while (i < llGetListLength(cmddialog)) {
        // Is the current timestamp more than 12 seconds old?
        if ((curtime - llList2Integer(cmddialog, i + 1)) > 12) {
            // Yes - remove it
            cmddialog = llDeleteSubList(cmddialog, i, i + 1);
        } else {
            // No - advance to the next
            i += 2;
        }
    }
}

// Start recording the specified agent
sloodle_start_recording_agent(key id)
{
    // Do nothing if the person is already on the list
    if (llListFindList(recordingkeys, [id]) >= 0) {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "webintercom:alreadyrecording", [llKey2Name(id)], NULL_KEY, "webintercom");
        return;
    }
    
    // Add the key and name to the lists
    recordingkeys += [id];
    recordingnames += [llKey2Name(id)];
    
    // Announce the update
    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "webintercom:startedrecording", [llKey2Name(id)], NULL_KEY, "webintercom");
    sloodle_update_hover_text();
}

// Stop recording the specified agent
sloodle_stop_recording_agent(key id)
{
    // Do nothing if the person is not already on the list
    integer pos = llListFindList(recordingkeys, [id]);
    if (pos < 0) {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "webintercom:notrecording", [llKey2Name(id)], NULL_KEY, "webintercom");
        return;
    }
    
    // Remove the key and name from the list
    recordingkeys = llDeleteSubList(recordingkeys, pos, pos);
    recordingnames = llDeleteSubList(recordingnames, pos, pos);
    
    // Announce the update
    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "webintercom:stoppedrecording", [llKey2Name(id)], NULL_KEY, "webintercom");
    sloodle_update_hover_text();
}

// Is the specified agent currently being recorded?
// Returns TRUE if so, or FALSE otherwise
integer sloodle_is_recording_agent(key id)
{
    return (llListFindList(recordingkeys, [id]) >= 0);
}

// Update the hover text while logging
sloodle_update_hover_text()
{
    string recordlist = llDumpList2String(recordingnames, "\n");
    if (recordlist == "") recordlist = "-";
    sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT, [<1.0, 0.2, 0.2>, 1.0], "webintercom:recording", [recordlist], NULL_KEY, "webintercom");
}


// Default state - waiting for configuration
default
{
    state_entry()
    {
        // Reset our data 
        llSetTexture("webintercom-off",ALL_SIDES);
        llSetText("", <0.0,0.0,0.0>, 0.0);
        
        // Request configuration data from our module finder
        llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "module_info_request|chat", NULL_KEY);
    }
    
    link_message(integer sender, integer channel, string msg, key id)
    {
        // Only pay attention to the relevant channel
        if (channel != SLOODLE_CHANNEL_OBJECT_DIALOG) return;
        
        // Parse the data into lines
        list lines = llParseStringKeepNulls(msg, ["\n"], []);
        integer numLines = llGetListLength(lines);
        
        // We are looking for a module info response
        if (llList2String(lines, 0) == "module_info_response")
        {
            // We need 2 more lines: one for course and one for module data
            if (numLines < 3)
            {
                llSay(DEBUG_CHANNEL, "Incomplete configuration received internally. Expecting 3 lines.");
                return;
            }
            
            // Parse the course information
            list fields = llParseStringKeepNulls(llList2String(lines, 1), ["|"], []);
            integer numFields = llGetListLength(fields);
            if (numFields < 3)
            {
                llSay(DEBUG_CHANNEL, "Incomplete configuration received internally. Expecting 3 fields on course line.");
                return;
            }
            //courseDatabaseID = (integer)llList2String(fields, 0);
            //courseExternalID = llList2String(fields, 1);
            //courseFullName = llList2String(fields, 2);
            
            // Parse the module information
            fields = llParseStringKeepNulls(llList2String(lines, 2), ["|"], []);
            numFields = llGetListLength(fields);
            if (numFields < 3)
            {
                llSay(DEBUG_CHANNEL, "Incomplete configuration received internally. Expecting 3 fields on module line.");
                return;
            }
            sloodlemoduleid = (integer)llList2String(fields, 0);
            //moduleName = llList2String(fields, 2);
            
            // We're ready to go
            llSay(0, "Connected to VLE.");
            state ready;
        }
        
    }
    
    touch_start(integer num_detected)
    {
        // Re-attempt to get the configuration
        llSay(0, "Attempting to connect to VLE...");
        llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "module_info_request|chat", NULL_KEY);
    }
}

state ready
{
    state_entry()
    {
        llSetTimerEvent(0);
        // Set the texture on the sides to indicate we're deactivated
        llSetTexture("webintercom-off",ALL_SIDES);
        
        // Reset the list of recorded keys and names
        recordingkeys = [];
        recordingnames = [];
        cmddialog = [];

        //sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT, [<1.0, 1.0, 1.0>, 1.0], "off", [], NULL_KEY, "");
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT, [<1.0, 1.0, 1.0>, 1.0], "webintercom:inactive", [], NULL_KEY, "webintercom");
    }
    
    state_exit()
    {
        llSetTimerEvent(0.0);
    }
        
    touch_start( integer total_number)
    {
        // Activating this requires access permission
        if (sloodle_check_access_ctrl(llDetectedKey(0)) == FALSE) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "nopermission:ctrl", [llDetectedName(0)], NULL_KEY, "");
            return;
        }
    
        llListenRemove(listenctrl);
        listenctrl = llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", llDetectedKey(0), "");
        sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG, [SLOODLE_CHANNEL_AVATAR_DIALOG, "0", "1"], "webintercom:ctrlmenu", ["0", "1"], llDetectedKey(0), "webintercom");
        llSetTimerEvent(10.0);
    }
    
    listen( integer channel, string name, key id, string message)
    {
        // Check the channel
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Check access to this object
            if (sloodle_check_access_ctrl(id) == FALSE) return;
    
            // Has chat logging been activated?
            if (message == "1") {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "webintercom:chatloggingon", [llDetectedName(0)], NULL_KEY, "webintercom");
                // sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "webintercom:joinchat", [sloodleserverroot + "/mod/chat/view.php?id="+(string)sloodlemoduleid], NULL_KEY, "webintercom");
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "webintercom:touchtorecord", [], NULL_KEY, "webintercom");
                
                // Initially record the one who activated us
                recordingkeys = [id];
                recordingnames = [name];
                
                state logging;
                return;
            }
        }
    }

    timer()
    {
        // Cancel the control listen 
        llSetTimerEvent(0.0);
        llListenRemove(listenctrl);
    }
    
}

state logging
{
    on_rez( integer param)
    {
        state ready;
    }
    
    state_entry()
    {
        // Udpate the texture on the side to indicate we're logging
        llSetTexture("webintercom-on",ALL_SIDES);
        
        // Listen for chat and commands
        llListen(0,"",NULL_KEY,"");
        llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", NULL_KEY, "");
        
        // Update our caption indicating whom we're recording
        sloodle_update_hover_text();
        // Regularly update the chat history and purge our list of command dialogs
        llSetTimerEvent(12.0);
        
        // Perform a regular scan to see if the WebIntercom has been abandoned
        if (sloodleautodeactivate != 0) {
            llSensorRepeat("", NULL_KEY, AGENT, sensorrange, PI, sensorrate);
        }
        nosensorcount = 0;
        
        // Inform the Moodle chatroom
        string body = "sloodlemoduleid=" + (string)sloodlemoduleid;
        body += "&sloodleuuid=" + (string)llGetKey();
        body += "&sloodleavname=" + llEscapeURL(llGetObjectName());
        body += "&firstmessageid=" + (string)(message_id + 1);
        body += "&sloodleisobject=true&message=" + MOODLE_NAME_OBJECT + " " + llList2String(recordingnames, 0) + " has activated this WebIntercom.";
        
        httpchat = llHTTPRequest("sloodle://" + SLOODLE_CHAT_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
    }
    
    state_exit()
    {
        // Inform the Moodle chatroom
        string body = "sloodlemoduleid=" + (string)sloodlemoduleid;
        body += "&sloodleuuid=" + (string)llGetKey();
        body += "&sloodleavname=" + llEscapeURL(llGetObjectName());
        body += "&firstmessageid=" + (string)(message_id + 1);
        body += "&sloodleisobject=true&message=" + MOODLE_NAME_OBJECT + " WebIntercom deactivated";
        
        httpchat = llHTTPRequest("sloodle://" + SLOODLE_CHAT_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
    }
    
    touch_start( integer total_number)
    {
        key id = llDetectedKey(0);
        // Determine what this user can do
        integer canctrl = sloodle_check_access_ctrl(id);
        integer canuse = sloodle_check_access_use(id);
        
        // Can the agent control AND use this item?
        if (canctrl) {
            sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG, [SLOODLE_CHANNEL_AVATAR_DIALOG, "0", "1", "2"], "webintercom:usectrlmenu", ["0", "1", "2"], id, "webintercom");
            sloodle_add_cmd_dialog(id);
        } else if (canuse) {
            sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG, [SLOODLE_CHANNEL_AVATAR_DIALOG, "0", "1"], "webintercom:usemenu", ["0", "1"], id, "webintercom");
            sloodle_add_cmd_dialog(id);
        } else {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "nopermission:use", [llKey2Name(id)], NULL_KEY, "webintercom");
        }
    }
    
    listen( integer channel, string name, key id, string message)
    {
        // Check the channel
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Ignore this person if they are not on the list
            if (llListFindList(cmddialog, [id]) < 0) return;
            sloodle_remove_cmd_dialog(id);
            
            // Find out what the user can do
            integer canctrl = sloodle_check_access_ctrl(id);
            integer canuse = sloodle_check_access_use(id);
            
            // Check what the command is
            if (message == "0") {
                // Make sure the user can use this
                if (!(canctrl || canuse)) return;
                // Stop recording the user
                sloodle_stop_recording_agent(id);
                
            } else if (message == "1") {
                // Make sure the user can use this
                if (!(canctrl || canuse)) return;
                // Start recording the user
                sloodle_start_recording_agent(id);

            } else if (message == "2") {
                // Make sure the user can control this
                if (!(canctrl)) return;
                // Stop logging
                state ready;
                return;
            }
            
        } else if (channel == 0) {
            // Is this an avatar?
            integer isavatar = FALSE;
            if (llGetOwnerKey(id) == id) {
                // Yes - check that we are listening to them
                if (!sloodle_is_recording_agent(id)) return;
                isavatar = TRUE;
            } else {
                // No - it is an object - ignore it if necessary
                if (sloodlelistentoobjects == 0) return;
            }
            
            // Send the request as POST data
            string body = "sloodlemoduleid=" + (string)sloodlemoduleid;
            body += "&sloodleuuid=" + (string)id;
            body += "&sloodleavname=" + llEscapeURL(name);
            body += "&firstmessageid=" + (string)(message_id + 1);
            if (isavatar) body += "&message=" + MOODLE_NAME + " ";
            else body += "&sloodleisobject=true&message=" + MOODLE_NAME_OBJECT + " ";
            body += name + ": " + message;
            
            httpchat = llHTTPRequest("sloodle://" + SLOODLE_CHAT_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
        }
    }
    
    timer()
    {
        // Get updated chat from Moodle
        if (httpchat == NULL_KEY) {
            string body = "sloodlemoduleid=" + (string)sloodlemoduleid;
            body += "&firstmessageid=" + (string)(message_id + 1);
            
            httpchat = llHTTPRequest("sloodle://" + SLOODLE_CHAT_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
        }
        
        // Purge any expired command dialogs
        sloodle_purge_cmd_dialog();
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Is this the expected data?
        if (id != httpchat) return;
        httpchat = NULL_KEY;
        // Make sure the request worked
        if (status != 200) {
            sloodle_debug("Failed HTTP response. Status: " + (string)status);
            sloodle_error_code(SLOODLE_TRANSLATE_SAY, NULL_KEY, status); // send message to error_message.lsl
            return;
        }

        // Make sure there is a body to the request
        if (llStringLength(body) == 0) return;
        // Debug output:
        sloodle_debug("Receiving chat data:\n" + body);
        
        // Split the data up into lines
        list lines = llParseStringKeepNulls(body, ["\n"], []);  
        integer numlines = llGetListLength(lines);
        // Extract all the status fields
        list statusfields = llParseStringKeepNulls( llList2String(lines,0), ["|"], [] );
        // Get the statuscode
        integer statuscode = llList2Integer(statusfields,0);
        
        // Was it an error code?
        if (statuscode <= 0) {
            
            sloodle_error_code(SLOODLE_TRANSLATE_SAY, NULL_KEY,statuscode); //send message to error_message.lsl
            // Do we have an error message to go with it?
            string msg = "ERROR: linker script responded with status code " + (string)statuscode;
            if (numlines > 1) {
                msg += "\n" + llList2String(lines,1);
            }
            sloodle_debug(msg);
            return;
        }
        
        // We will use these to store each item of data
        integer msgnum = 0;
        string name = "";
        string text = "";
        
        // Every other line should define a chat message "id|name|text"
        // Start at the line after the status line
        integer i = 1;
        for (i = 1; i < numlines; i++) {
            // Get all the different fields for this line
            list fields = llParseStringKeepNulls(llList2String(lines,i),["|"],[]);
            // Make sure we have enough fields
            if (llGetListLength(fields) >= 3) {
                // Extract each item of data
                msgnum = llList2Integer(fields,0);
                name = llList2String(fields,1);
                text = llList2String(fields,2);
                
                // Make sure this is a new message
                if (msgnum > message_id) {
                    message_id = msgnum;
                    // Make sure this wasn't an SL message originally
                    if (llSubStringIndex(text, MOODLE_NAME) != 0 && llSubStringIndex(text, MOODLE_NAME_OBJECT) != 0) {
                        llSay(0, name + ": " + text);
                    }
                }
            }
        }
    }
    
    sensor(integer num_detected)
    {
        // Nearby avatars have been detected
        nosensorcount = 0;
    }
    
    no_sensor()
    {
        // Ignore this if auto-deactivation has been disabled
        if (sloodleautodeactivate == 0) return;
    
        // No nearby avatars detected.
        // Is the object attached to an avatar? (Sensors won't detect the avatar the object is attached to)
        if (llGetAttached() > 0) {
            // Yes - treat it as though avatars have been detected
            nosensorcount = 0;
        } else {
            // No  - increment our count of failed scans
            nosensorcount++;
            // Is it time to deactivate?
            if (nosensorcount >= nosensormax) {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "webintercom:autodeactivate", [], NULL_KEY, "webintercom");
                state ready;
                return;
            }
        }
    }
}


