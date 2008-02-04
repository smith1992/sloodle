// Sloodle object distributor
// Allows Sloodle objects to be distributed in-world to Second Life users
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2007 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar - original design and implementation
//  Peter R. Bloomfield - updated to use new communications format (Sloodle 0.2)
//

// When configured, opens an XMLRPC channel, and reports the channel key and inventory list to the Moodle server.
// Note that non-copyable items are NOT made available, and neither will items on the ignore list below.


// ***** IGNORE LIST *****
//
// This is a list of names of items which should NOT be handed out
list ignorelist = ["sloodle_config","sloodle_object_distributor","sloodle_setup_notecard","sloodle_slave_object","sloodle_debug"];
//
// ***** ----------- *****


string sloodleserverroot = "";
string linkerscript = "/mod/sloodle/sl_distrib/sl_distrib_channel_linker.php";
string pwd = "";

integer lastping = -1;
integer pingtimeout = 60;
integer timebetweenpings = 60;
key ch = NULL_KEY;

integer SLOODLE_OBJECT_DIALOG_CHANNEL = -3857343;
integer SLOODLE_CHANNEL_AVATAR_SETTING = 1;
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;


sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// configure by receiving a linked message from another script in the object
sloodle_handle_command(string str)
{
    //llWhisper(0,"handling command "+str);    
    list bits = llParseString2List(str,["|"],[]);
    string name = llList2String(bits,0);
    string value = llList2String(bits,1);
    if (name == "set:sloodleserverroot") {
        sloodleserverroot = value;
    } else if (name == "set:pwd") {
        pwd = value;
        if (llGetListLength(bits) == 3) {
            pwd = pwd + "|" + llList2String(bits,2);
        }
    } 

    //llWhisper(0,"DEBUG: "+sloodleserverroot+"/"+pwd+"/"+(string)sloodle_courseid);
    
}

// Get a string containing a list of all available inventory
// NOTE: 
string get_available_inventory()
{
    // We're going to build a string of all copyable inventory items
    string invlist = "";
    integer numitems = llGetInventoryNumber(INVENTORY_ALL);
    string itemname;
    
    // Go through each item
    integer i = 0;
    
    for (i = 0; i < numitems; i++) {
        // Get the name of this item
        itemname = llGetInventoryName(INVENTORY_ALL, i);
        // Make sure it's copyable and not on the ignore list
        if((llGetInventoryPermMask(itemname, MASK_OWNER) & PERM_COPY) && llListFindList(ignorelist, [itemname]) == -1) {
            if (i > 0) invlist += "|";
            invlist += itemname;
        }
    }
    
    return invlist;
}


integer sloodle_init()
{
    llSetText("Initialising...", <0.7, 0.7, 0.0>, 0.9);
    if ( (sloodleserverroot == "") || (pwd == "") ) {
        return 0;
    } else {
        return 1;
    }
}

default
{
    on_rez(integer param)
    {
        integer ready = sloodle_init();
        if (ready == 0) {
            state sloodle_wait_for_configuration;
        }
    }    
    state_entry()
    {     
        integer ready = sloodle_init();
        if (ready == 0) {
            state sloodle_wait_for_configuration;
        } else {
            state connecting;
        }
    }
}

state connecting
{
    state_entry()
    {
        // open an xmlrpc channel
        llSetText("Opening XMLRPC chanel...", <0.0,0.0,0.7>, 0.9);
        llOpenRemoteDataChannel();
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
    
    remote_data(integer type, key channel, key message_id, string sender, integer ival, string sval)
    {
        if (type == REMOTE_DATA_CHANNEL) { // channel created
            
            ch = channel;
            llSetText("Establishing connection with outside server...", <0.0,0.0,0.7>, 0.7);
            sloodle_debug("Opened XMLRPC channel "+(string)ch);
        
            // Get all available inventory
            sloodle_debug("Getting inventory...");
            string invlist = get_available_inventory();
            sloodle_debug("Inventory list = " + invlist);
        
            // Send the request
            sloodle_debug("Reporting to Moodle server...");
            llHTTPRequest( sloodleserverroot+linkerscript+"?sloodlepwd="+pwd+"&sloodlechannel="+(string)ch+"&sloodlecontents="+llEscapeURL(invlist), [], "");
        }
    }
    
    touch_start(integer num_detected)
    {
        // Make sure it's the owner who is touching this
        if (llDetectedKey(0) != llGetOwner()) return;
        // Start listening for the owner, and show a dialog of options
        llListen(SLOODLE_CHANNEL_AVATAR_SETTING, "", llGetOwner(), "Reconnect");
        llListen(SLOODLE_CHANNEL_AVATAR_SETTING, "", llGetOwner(), "Reset");
        llListen(SLOODLE_CHANNEL_AVATAR_SETTING, "", llGetOwner(), "Shutdown");
        llDialog(llGetOwner(), "Do you want to Reconnect, Reset, or Shutdown this distributor?", ["Reconnect","Reset","Shutdown"], SLOODLE_CHANNEL_AVATAR_SETTING);
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Make sure it's the owner on the correct channel
        if (id != llGetOwner() || channel != SLOODLE_CHANNEL_AVATAR_SETTING) return;
        // Check which message it is
        if (msg == "Reconnect") {
            state default;
        } else if (msg == "Reset") {
            llMessageLinked(LINK_SET, SLOODLE_OBJECT_DIALOG_CHANNEL, "do:reset", NULL_KEY);
            llResetScript();
        } else if (msg == "Shutdown") {
            state shutdown;
        }
    }
    
    http_response(key request_id, integer status, list metadata, string body)
    {
        //llOwnerSay("HTTP Response (" + (string)status + "): " + body);
        
        // Check that we got a proper response
        if (status >= 400) {
            llOwnerSay("ERROR - failed to connect: HTTP response gave status code " + (string)status);
            llSetText("Connection Failed.\nTouch me to Restart or Shutdown.", <1.0,0.0,0.0>, 0.9);
            return;
        }
        if (body == "") {
            llOwnerSay("ERROR - failed to connect: response had no body.");
            llSetText("Connection Failed.\nTouch me to Restart or Shutdown.", <1.0,0.0,0.0>, 0.9);
            return;
        }
        
        // Split the response at each line, then at each field
        list lines = llParseStringKeepNulls(body, ["\n"], []);
        list statusfields = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
        // The first item should be the status code
        integer statuscode = llList2Integer(statusfields, 0);
        
        // The status could should be positive if successful
        if (statuscode > 0) {
            llOwnerSay("Connected successfully.");
            state connected;
        } else {
            // Get the error message if one was given
            if (llGetListLength(lines) > 1) {
                string errmsg = llList2String(lines, 1);
                llOwnerSay("ERROR - failed to connect: (" + (string)statuscode + ") " + errmsg);
            } else {
                llOwnerSay("ERROR - failed to connect. (" + (string)statuscode + ")");
            }
            llSetText("Connection Failed.\nTouch me to Restart or Shutdown.", <1.0,0.0,0.0>, 0.9);
        }
    }
}

state connected
{
    state_entry()
    {
        llSetText("Connected.\n"+sloodleserverroot, <0.1,0.9,0.1>, 0.9);
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
    
    remote_data(integer type, key channel, key message_id, string sender, integer ival, string sval)
    {
        if (type == REMOTE_DATA_REQUEST) { // channel created
        
            sloodle_debug("Received XMLRPC request: " + sval);
        
            // Split the message by each line
            list lines = llParseStringKeepNulls(sval, ["\\n"], []);
            // Extract all fields of the status line
            list statusfields = llParseStringKeepNulls( llList2String(lines, 0), ["|"], [] );
            
            // Attempt to get the data fields
            list datafields = [];
            if (llGetListLength(lines) > 1) {
                datafields = llParseStringKeepNulls( llList2String(lines, 1), ["|"], [] );
            }
            
            // Was the status code successful?
            integer statuscode = llList2Integer(statusfields, 0);
            if (statuscode < 0) {
                sloodle_debug("Error given in status code: " + (string)statuscode);
                llRemoteDataReply(channel,NULL_KEY,"-1|DISTRIBUTOR\nError given in request",0);
                return;
            }
            
            // Make sure we have at least 1 field in the data line
            if (llGetListLength(datafields) < 1) {
                sloodle_debug("ERROR - no fields in data line");
                llRemoteDataReply(channel,NULL_KEY,"-1|DISTRIBUTOR\nNo fields in data line",0);
                return;
            }
            
            // What is the command in the data line?
            string cmd = llToUpper(llList2String(datafields, 0));
            if (cmd == "SENDOBJECT") {
                // Make sure we have 2 more items
                if (llGetListLength(datafields) < 3) {
                    sloodle_debug("ERROR - not enough fields in data line - expected 3.");
                    llRemoteDataReply(channel,NULL_KEY,"-1|DISTRIBUTOR\nNot enough fields in data line - expected 3.",0);
                    return;
                }
                // Extract both
                key targetavatar = llList2Key(datafields, 1);
                string objname = llList2String(datafields, 2);
                
                // Make sure we have the named object
                if (llGetInventoryType(objname) == INVENTORY_NONE) {
                    sloodle_debug("Object \"" + objname + "\" not found.");
                    llRemoteDataReply(channel,NULL_KEY,"-1|DISTRIBUTOR\nObject not found.",0);
                    return;
                }
                
                // Make sure we can find the identified avatar
                if (targetavatar == NULL_KEY || llGetOwnerKey(targetavatar) != targetavatar) {
                    sloodle_debug("Could not find identified avatar.");
                    llRemoteDataReply(channel,NULL_KEY,"-1|DISTRIBUTOR\nCould not find identified avatar.",0);
                    return;
                }
                
                
                // Attempt to give the object
                llGiveInventory(targetavatar, objname);
                // Send a success response
                llRemoteDataReply(channel,NULL_KEY,"1|DISTRIBUTOR\nSuccess.",0);
            }
        } 
        
    }
    
    touch_start(integer num_detected)
    {
        // Make sure it's the owner who is touching this
        if (llDetectedKey(0) != llGetOwner()) return;
        // Start listening for the owner, and show a dialog of options
        llListen(SLOODLE_CHANNEL_AVATAR_SETTING, "", llGetOwner(), "Reconnect");
        llListen(SLOODLE_CHANNEL_AVATAR_SETTING, "", llGetOwner(), "Reset");
        llListen(SLOODLE_CHANNEL_AVATAR_SETTING, "", llGetOwner(), "Shutdown");
        llDialog(llGetOwner(), "Do you want to Reconnect, Reset, or Shutdown this distributor?", ["Reconnect","Reset","Shutdown"], SLOODLE_CHANNEL_AVATAR_SETTING);
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Make sure it's the owner on the correct channel
        if (id != llGetOwner() || channel != SLOODLE_CHANNEL_AVATAR_SETTING) return;
        // Check which message it is
        if (msg == "Reconnect") {
            state default;
        } else if (msg == "Reset") {
            llMessageLinked(LINK_SET, SLOODLE_OBJECT_DIALOG_CHANNEL, "do:reset", NULL_KEY);
            llResetScript();
        } else if (msg == "Shutdown") {
            state shutdown;
        }
    }
}

state shutdown
{
    state_entry()
    {
        llSetText("Shutdown.\nTouch me to restart.", <0.5,0.5,0.5>, 1.0);
    }
    
    touch_start(integer num_detected)
    {
        // Make sure it's the owner who is touching this
        if (llDetectedKey(0) != llGetOwner()) return;
        // Start listening for the owner, and show a dialog of options
        llListen(SLOODLE_CHANNEL_AVATAR_SETTING, "", llGetOwner(), "Reconnect");
        llListen(SLOODLE_CHANNEL_AVATAR_SETTING, "", llGetOwner(), "Reset");
        llDialog(llGetOwner(), "Do you want to Reconnect or Reset this distributor?", ["Reconnect","Reset"], SLOODLE_CHANNEL_AVATAR_SETTING);
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Make sure it's the owner on the correct channel
        if (id != llGetOwner() || channel != SLOODLE_CHANNEL_AVATAR_SETTING) return;
        // Check which message it is
        if (msg == "Reconnect") {
            state default;
        } else if (msg == "Reset") {
            llMessageLinked(LINK_SET, SLOODLE_OBJECT_DIALOG_CHANNEL, "do:reset", NULL_KEY);
            llResetScript();
        }
    }
}
    
state sloodle_wait_for_configuration
{
    state_entry()
    {
        llSetText("Waiting for configuration...", <0.7, 0.7, 0.0>, 0.9);
        // Reset the values
        sloodleserverroot = "";
        pwd = "";
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
    
    link_message(integer sender_num, integer num, string str, key id)
    {
        sloodle_handle_command(str);

        if ( (sloodleserverroot != "") && (pwd != "") ) {
            state default;
        }
    }
}

