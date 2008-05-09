// Sloodle WebIntercom (version 0.9, for Sloodle 0.3)
// Links in-world SL (text) chat with a Moodle chatroom
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-7 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Paul Andrews
//  Daniel Livingstone
//  Jeremy Kemp
//  Edmund Edgar
//  Peter R. Bloomfield
//

integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
string SLOODLE_CHAT_LINKER = "/mod/sloodle/mod/chat-1.0/linker.php";
string SLOODLE_EOF = "sloodleeof";

integer SLOODLE_ACCESS_LEVEL_OWNER = 0;
integer SLOODLE_ACCESS_LEVEL_GROUP = 1;
integer SLOODLE_ACCESS_LEVEL_PUBLIC = 2;

string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodlemoduleid = 0;
integer sloodlelistentoobjects = 0; // Should this object listen to other objects?
integer sloodleaccessleveluse = 2; // Who can use this object?
integer sloodleaccesslevelctrl = 0; // Who can control this object?

string SoundFile = ""; // Sound file used for the beep
string MOODLE_NAME="(SL)";

integer isconfigured = FALSE; // Do we have all the configuration data we need?
integer eof = FALSE; // Have we reached the end of the configuration data?

integer listenctrl = 0; // Listening for initial control... i.e. activation/deactivation
list cmddialog = []; // Alternating list of keys and timestamps, indicating who activated a command dialog (during logging) an when

list recordingkeys = []; // Keys of people we're recording
list recordingnames = []; // Names of people we're recording

key httpchat = NULL_KEY; // Request used to send/receive chat

integer message_id = 0; // ID of the last message received from Moodle


///// FUNCTIONS /////

sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// Configure by receiving a linked message from another script in the object
// Returns TRUE if the object has all the data it needs
integer sloodle_handle_command(string str) 
{
    list bits = llParseString2List(str,["|"],[]);
    string name = llList2String(bits,0);
    string value = llList2String(bits,1);
    
    if (name == "set:sloodleserverroot") sloodleserverroot = value;
    else if (name == "set:sloodlepwd") sloodlepwd = value;
    else if (name == "set:sloodlecontrollerid") sloodlecontrollerid = (integer)value;
    else if (name == "set:sloodlemoduleid") sloodlemoduleid = (integer)value;
    else if (name == "set:sloodlelistentoobjects") sloodlelistentoobjects = (integer)value;
    else if (name == "set:sloodleaccessleveluse") sloodleaccessleveluse = (integer)value;
    else if (name == "set:sloodleaccesslevelctrl") sloodleaccesslevelctrl = (integer)value;
    else if (name == SLOODLE_EOF) eof = TRUE;
    
    return (sloodleserverroot != "" && sloodlepwd != "" && sloodlecontrollerid > 0 && sloodlemoduleid > 0);
}

// Checks if the given agent is permitted to control this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_ctrl(key id)
{
    // Check the access mode
    if (sloodleaccesslevelctrl == SLOODLE_ACCESS_LEVEL_GROUP) {
        return llSameGroup(id);
    } else if (sloodleaccesslevelctrl == SLOODLE_ACCESS_LEVEL_PUBLIC) {
        return TRUE;
    }
    
    // Assume it's owner mode
    return (id == llGetOwner());
}

// Checks if the given agent is permitted to user this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_use(key id)
{
    // Check the access mode
    if (sloodleaccessleveluse == SLOODLE_ACCESS_LEVEL_GROUP) {
        return llSameGroup(id);
    } else if (sloodleaccessleveluse == SLOODLE_ACCESS_LEVEL_PUBLIC) {
        return TRUE;
    }
    
    // Assume it's owner mode
    return (id == llGetOwner());
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
        llSay(0, "Already recording " + llKey2Name(id));
        return;
    }
    
    // Add the key and name to the lists
    recordingkeys += [id];
    recordingnames += [llKey2Name(id)];
    
    // Announce the update
    llSay(0, "Started recording " + llKey2Name(id));
    sloodle_update_hover_text();
}

// Stop recording the specified agent
sloodle_stop_recording_agent(key id)
{
    // Do nothing if the person is not already on the list
    integer pos = llListFindList(recordingkeys, [id]);
    if (pos < 0) {
        llSay(0, "Not recording " + llKey2Name(id));
        return;
    }
    
    // Remove the key and name from the list
    recordingkeys = llDeleteSubList(recordingkeys, pos, pos);
    recordingnames = llDeleteSubList(recordingnames, pos, pos);
    
    // Announce the update
    llSay(0, "Stopped recording " + llKey2Name(id));
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
    llSetText("Recording:\n" + llDumpList2String(recordingnames, "\n"), <1.0, 0.2, 0.2>, 1.0);
}


// Default state - waiting for configuration
default
{
    state_entry()
    {
        // Starting again with a new configuration
        llSetText("", <0.0,0.0,0.0>, 0.0);
        isconfigured = FALSE;
        eof = FALSE;
        // Reset our data
        sloodleserverroot = "";
        sloodlepwd = "";
        sloodlecontrollerid = 0;
        sloodlemoduleid = 0;
        sloodlelistentoobjects = 0;
        sloodleaccessleveluse = 0;
        sloodleaccesslevelctrl = 0;
    }
    
    link_message( integer sender_num, integer num, string str, key id)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Split the message into lines
            list lines = llParseString2List(str, ["\n"], []);
            integer numlines = llGetListLength(lines);
            integer i = 0;
            for (; i < numlines; i++) {
                isconfigured = sloodle_handle_command(llList2String(lines, i));
            }
            
            // If we've got all our data AND reached the end of the configuration data, then move on
            if (eof == TRUE && isconfigured == TRUE) state ready;
        }
    }
}

state ready
{
    on_rez( integer param)
    {
        state default;
    }    
    
    state_entry()
    {
        llSetTimerEvent(0);
        // Set the texture on the sides to indicate we're deactivated
        llSetTexture("059eb6eb-9eef-c1b5-7e95-a4c6b3e5ed9a",ALL_SIDES);
        // Reset the list of recorded keys and names
        recordingkeys = [];
        recordingnames = [];
        cmddialog = [];

        llSetText("off",<1.0,1.0,1.0>,1.0);
        // Determine our "beep" sound file name
        SoundFile = llGetInventoryName(INVENTORY_SOUND, 0);
    }
    
    state_exit()
    {
        llSetTimerEvent(0.0);
    }
        
    touch_start( integer total_number)
    {
        // Activating this requires access permission
        if (sloodle_check_access_ctrl(llDetectedKey(0)) == FALSE) {
            llWhisper(0, "Sorry " + llDetectedName(0) + ". You do not have permission to control this object.");
            return;
        }
    
        llListenRemove(listenctrl);
        listenctrl = llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", llDetectedKey(0), "");
        llDialog(llDetectedKey(0), "Would you like to start recording?\n\n0 = no\n1 = yes",["0", "1"], SLOODLE_CHANNEL_AVATAR_DIALOG);
        llSetTimerEvent(10.0);
    }
    
    listen( integer channel, string name, key id, string message)
    {
        // Check the channel
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Check access to this object
            if (sloodle_check_access_ctrl(llDetectedKey(0)) == FALSE) return;
    
            // Has chat logging been activated?
            if (message == "1") {
                llSay(0,"Chat logging is on!");
                llSay(0,"Join this Moodle chat at "+sloodleserverroot+"/mod/chat/view.php?id="+(string)sloodlemoduleid);
                llSay(0,"Touch logger to record your chat.");
                llSetText("Chat logging is on!",<0,0,0>,1.0);
                
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
        state default;
    }
    
    state_entry()
    {
        // Udpate the texture on the side to indicate we're logging
        llSetTexture("d3c9180a-1703-3a84-8dcd-e3aa6306a343",ALL_SIDES);
        // Listen for chat and commands
        llListen(0,"",NULL_KEY,"");
        llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", NULL_KEY, "");
        
        // Update our caption indicating whom we're recording
        string text = "Recording: " + llList2String(recordingnames,0);
        llSetText(text, <1.0,0.2,0.2>, 1.0);
        
        // Regularly update the chat history and purge our list of command dialogs
        llSetTimerEvent(12.0);
    }
    
    touch_start( integer total_number)
    {
        key id = llDetectedKey(0);
        // Determine what this user can do
        integer canctrl = sloodle_check_access_ctrl(id);
        integer canuse = sloodle_check_access_use(id);
        
        // Can the agent control AND use this item?
        if (canctrl) {
            llDialog(id, "What would you like to do!\n\n0 = Stop recording me\n1 = Record me\n2 = Deactivated WebIntercom", ["0","1","2"], SLOODLE_CHANNEL_AVATAR_DIALOG);
            sloodle_add_cmd_dialog(id);
        } else if (canuse) {
            llDialog(id, "What would you like to do!\n\n0 = Stop recording me\n1 = Record me", ["0","1"], SLOODLE_CHANNEL_AVATAR_DIALOG);
            sloodle_add_cmd_dialog(id);
        } else {
            llSay(0, "Sorry " + llKey2Name(id) + ". You do not have permission to use this object.");
        }
    }
    
    listen( integer channel, string name, key id, string message)
    {
        // Check the channel
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Ignore this person if they are not on the list
            if (llListFindList(cmddialog, [id]) < 0) return;
            sloodle_remove_cmd_dialog(id);
            
            // Check what the command is
            if (message == "0") {
                // Make sure the user can use this
                if (!sloodle_check_access_use(id)) return;
                // Stop recording the user
                sloodle_stop_recording_agent(id);
                
            } else if (message == "1") {
                // Make sure the user can use this
                if (!sloodle_check_access_use(id)) return;
                // Start recording the user
                sloodle_start_recording_agent(id);
                
            } else if (message == "2") {
                // Make sure the user can control this 
                if (!sloodle_check_access_ctrl(id)) return;
                // Deactivate the WebIntercom
                state ready;
            }
            
        } else if (channel == 0) {
            // Is this an avatar?
            if (llGetOwnerKey(id) == id) {
                // Yes - check that we are listening to them
                if (!sloodle_is_recording_agent(id)) return;
            } else {
                // No - it is an object - ignore it if necessary
                if (sloodlelistentoobjects == 0) return;
            }
            
            // Is this a SLurl command?
            if(message == "/slurl")     {        
                string region = llEscapeURL(llGetRegionName());
                vector vec = llGetPos();
                string posX = (string)((integer)vec.x);
                string posY = (string)((integer)vec.y);
                string posZ = (string)((integer)vec.z);
                // Replace the message with a SLurl
                message = "http://slurl.com/secondlife/" + region + "/" + posX + "/" + posY + "/" + posZ + "/?title=" + region;
            }
            
            // Send the request as POST data
            string body = "sloodlecontrollerid=" + (string)sloodlecontrollerid;
            body += "&sloodlepwd=" + sloodlepwd;
            body += "&sloodlemoduleid=" + (string)sloodlemoduleid;
            body += "&sloodleuuid=" + (string)id;
            body += "&sloodleavname=" + llEscapeURL(name);
            body += "&message=" + MOODLE_NAME + name + message;
            
            httpchat = llHTTPRequest(sloodleserverroot + SLOODLE_CHAT_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
        }
    }
    
    timer()
    {
        // Get updated chat from Moodle
        if (httpchat == NULL_KEY) {
            string body = "sloodlecontrollerid=" + (string)sloodlecontrollerid;
            body += "&sloodlepwd=" + sloodlepwd;
            body += "&sloodlemoduleid=" + (string)sloodlemoduleid;
            
            httpchat = llHTTPRequest(sloodleserverroot + SLOODLE_CHAT_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
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
            string msg = "ERROR: linker script responded with status code " + (string)statuscode;
            // Do we have an error message to go with it?
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
        for (i = (numlines - 1); i > 0; i--) {
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
                    if (llSubStringIndex(text, MOODLE_NAME) != 0) {
                        // Is this a Moodle beep?
                        if (llSubStringIndex(text, "beep ") == 0) {
                            // Yes - play a beep sound
                            llStopSound();
                            if (SoundFile == "") 
                            { // There is no sound file in inventory - plsy default
                                llPlaySound("34b0b9d8-306a-4930-b4cd-0299959bb9f4", 1.0);
                            } else { // Play the included one
                                llPlaySound(SoundFile, 1.0);
                            }
                        }
                        // Finally... just an ordinary chat message... output it
                        llSay(0, name + ": " + text);
                    }
                }
            }
        }
    }
}


