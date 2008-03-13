// Sloodle Object Selector
// Allows avatars in-world to select objects to be distributed to them
// (Does not require registration on the Moodle site)
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2007 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Peter R. Bloomfield - original design and implementation
//

// This script will interact with the "sl_distrib/sl_send_object_linker.php" script.
// When touched, it will request a list of available objects from the server.
// It will display this list to the avatar, allowing them to select which object they want distributed.
// Another request will be made to initiate the distribution.
//

// LIMITATIONS:
// - Only supports a single user at a time.
// - Only shows 12 objects (the number of buttons which can fit on a single dialog)


///// DATA /////

// Sloodle settings
string sloodleserverroot = "";
string sloodlepwd = "";
string linkerscript = "/mod/sloodle/sl_distrib/sl_send_object_linker.php";

// Sloodle communication channels
integer SLOODLE_OBJECT_DIALOG_CHANNEL = -3857343;
integer SLOODLE_CHANNEL_AVATAR_SETTING = 1;
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;

// UUID and name of the agent currently using this tool
key currentuseruuid = NULL_KEY;
string currentusername = "";
// Keys of HTTP requests
key httpqueryrequest = NULL_KEY; // Query for a list of available objects
key httpsendobjectrequest = NULL_KEY; // Query to initiate the sending of an object
// List of available objects
list availableobjects = [];
integer selectedobjectnum = 0; // Which object has been selected?

// Timeout values (seconds)
float TIMEOUT_HTTP = 16.0;
float TIMEOUT_USER_DIALOG = 20.0;


///// FUNCTIONS /////

// Send a debug message to the linked debug script (if there is one)
sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// Configure by receiving a linked message from another script in the object
sloodle_handle_command(string str)
{
    list bits = llParseString2List(str,["|"],[]);
    string name = llList2String(bits,0);
    string value = llList2String(bits,1);
    if (name == "set:sloodleserverroot") {
        sloodleserverroot = value;
    } else if (name == "set:pwd") {
        sloodlepwd = value;
        if (llGetListLength(bits) == 3) {
            sloodlepwd = sloodlepwd + "|" + llList2String(bits,2);
        }
    }     
}

// Handle a server's response to our query of available objects
handle_query_response(string body)
{
    // Reset our object list
    availableobjects = [];
    // Make sure the response was not empty
    if (body == "") {
        llSay(0, "ERROR: empty HTTP response body.");
        return;
    }
    
    // Split the response into lines, and extract the status fields
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    integer numlines = llGetListLength(lines);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    
    // Was an error reported?
    if (statuscode <= 0) {
        string errmsg = "";
        if (numlines > 1) errmsg = llList2String(lines, 1);
        llSay(0, "ERROR ("+(string)statuscode+"): " + errmsg);
        return;
    }
    
    // Everything seems fine
    // Each line should contain the name of an object - get them
    integer i = 1;
    for (i = 1; i < numlines; i++) {
        // Make sure the line is not empty, then store it
        string curline = llList2String(lines, i);
        if (curline != "") availableobjects += [curline];
    }
}

// Show a menu of available objects to the current user
show_object_menu()
{
    // Limit the number of objects to the maximum number of buttons in a dialog
    list showobjects = [];
    integer numobjects = llGetListLength(availableobjects);
    if (numobjects > 12) {
        showobjects = llList2List(availableobjects, 0, 11);
        numobjects = 12;
    } else {
        showobjects = availableobjects;
    }
    
    // Do nothing if there are no objects to select
    if (numobjects == 0) {
        sloodle_debug("ERROR: no objects to select in \"show_object_menu()\".");
        return;
    }
    
    // We need to prepare a dialog message which shows each object
    string dlgmsg = "Please select the object you would like:\n";
    integer i = 0;
    // We also need a list of numbered buttons
    list btns = [];
    for (i = 0; i < numobjects; i++) {
        dlgmsg += "\n" + (string)i + " = " + llList2String(availableobjects, i);
        btns += [(string)i];
    }
    
    // Listen for the user, and display the dialog
    llListen(SLOODLE_CHANNEL_AVATAR_SETTING, "", currentuseruuid, "");
    llDialog(currentuseruuid, dlgmsg, btns, SLOODLE_CHANNEL_AVATAR_SETTING);
}

// Handle the response from a "sendobject" query
handle_send_response(string body)
{
    // Reset our object list
    availableobjects = [];
    // Make sure the response was not empty
    if (body == "") {
        llSay(0, "ERROR: empty HTTP response body.");
        return;
    }
    
    // Split the response into lines, and extract the status fields
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    integer numlines = llGetListLength(lines);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    
    // There should be a single data line or error message
    string dataline = "";
    if (numlines > 1) dataline = llList2String(lines, 1);
    
    // Was an error reported?
    if (statuscode <= 0) {
        llSay(0, "ERROR ("+(string)statuscode+"): " + dataline);
        llDialog(currentuseruuid, "An error was reported while trying to send you the object.", ["OK"], SLOODLE_CHANNEL_AVATAR_IGNORE);
        return;
    }
    
    // Must have been successful
    llSay(0, "Successfully distributed object.");
}


///// STATES /////

// Uninitialised - waiting for configuration
default
{
    state_entry()
    {
        llSetText("Waiting for configuration...", <1.0,0.5,0.0>, 0.9);
        // Reset our values
        sloodleserverroot = "";
        sloodlepwd = "";
    }
    
    on_rez(integer start_param)
    {
        llResetScript();
    }
    
    link_message(integer sender_num, integer num, string str, key id)
    {
        // Ignore debug messages
        if (num == DEBUG_CHANNEL) return;
    
        sloodle_handle_command(str);

        // If have all our settings, then move on
        if ((sloodleserverroot != "") && (sloodlepwd != "")) {
            state ready;
        }
    }
}

// Ready for use - will respond to clicks
state ready
{
    state_entry()
    {
        llSetText("Ready. Touch me to select objects.\n"+sloodleserverroot, <0.0,1.0,0.0>, 1.0);
        currentuseruuid = NULL_KEY;
        currentusername = "";
    }
    
    touch_start(integer num_detected)
    {
        // Store the UUID of the user who touched us, and start processing
        currentuseruuid = llDetectedKey(0);
        currentusername = llKey2Name(currentuseruuid);
        state getobjects;
    }
}

// Get a list of available objects to show the user
state getobjects
{
    state_entry()
    {
        llSetTimerEvent(0.0);
        llSetText("Currently in use by " + currentusername + ".\nFetching list of available objects.", <1.0,0.0,0.0>, 0.9);
        // Make the request for a list of items
        httpqueryrequest = llHTTPRequest(sloodleserverroot + linkerscript + "?sloodlepwd="+sloodlepwd+"&sloodlecmd=query", [], "");
        llSetTimerEvent(TIMEOUT_HTTP);
    }
    
    on_rez(integer start_param)
    {
        state ready;
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Ignore irrelevant responses
        if (id != httpqueryrequest) return;
        sloodle_debug("HTTP Response ("+(string)status+"): " + body);
        llSetTimerEvent(0.0);
        httpqueryrequest = NULL_KEY;
        
        // Make sure the HTTP status was OK
        if (status != 200) {
            llDialog(currentuseruuid, "Sorry " + currentusername + ".\nI could not get a list of available objects.\n (HTTP Status: "+(string)status+")", ["OK"], SLOODLE_CHANNEL_AVATAR_IGNORE);
            state ready;
            return;
        }
     
        // Handle the response
        handle_query_response(body);
        // Make sure we got some items
        if (llGetListLength(availableobjects) == 0) {
            llDialog(currentuseruuid, "Sorry " + currentusername + ".\nThere are no objects available for distribution.", ["OK"], SLOODLE_CHANNEL_AVATAR_IGNORE);
            state ready;
        } else {
            state selectobject;
        }
    }
    
    timer()
    {
        llSetTimerEvent(0.0);
        llDialog(currentuseruuid, "Sorry " + currentusername + ".\nThe website was taking too long to respond. Please try again later.", ["OK"], SLOODLE_CHANNEL_AVATAR_IGNORE);
        state ready;
    }
}

// Show the user the list of objects, and let them select
state selectobject
{
    state_entry()
    {
        llSetTimerEvent(0.0);
        llSetText("Currently in use by " + currentusername + ".\nSelecting object for distribution.", <1.0,0.0,0.0>, 0.9);
        show_object_menu();
        llSetTimerEvent(TIMEOUT_USER_DIALOG);
    }
    
    on_rez(integer start_param)
    {
        state ready;
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Make sure this is on the correct channel, and from the correct avatar
        if (channel != SLOODLE_CHANNEL_AVATAR_SETTING || id != currentuseruuid) return;
        sloodle_debug("Listen: response = " + msg);
        // Determine which item number was requested
        selectedobjectnum = (integer)msg;
        // Attempt to send the object
        state sendobject;
    }
    
    timer()
    {
        llSetTimerEvent(0.0);
        llSay(0, "User was taking too long to respond. Timing-out.");
        state ready;
    }
}

// Send the specified object to the user
state sendobject
{
    state_entry()
    {
        // Make sure the selected object is valid
        if (selectedobjectnum < 0 || selectedobjectnum >= llGetListLength(availableobjects)) {
            llSay(0, "Invalid object selected. Reverting to 'ready' state.");
            state ready;
            return;
        }
        
        // Determine the object name
        string selectedobjectname = llList2String(availableobjects, selectedobjectnum);
    
        llSetTimerEvent(0.0);
        llSetText("Currently in use by " + currentusername + ".\nSending object to user...", <1.0,0.0,0.0>, 0.9);
        // Make the request to send the item
        httpsendobjectrequest = llHTTPRequest(sloodleserverroot + linkerscript + "?sloodlepwd="+sloodlepwd+"&sloodlecmd=sendobject"+"&sloodleuuid="+(string)currentuseruuid+"&sloodleobject="+llEscapeURL(selectedobjectname), [], "");
        llSetTimerEvent(TIMEOUT_HTTP);
    }
    
    on_rez(integer start_param)
    {
        state ready;
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Ignore irrelevant responses
        if (id != httpsendobjectrequest) return;
        sloodle_debug("HTTP Response ("+(string)status+"): " + body);
        llSetTimerEvent(0.0);
        httpsendobjectrequest = NULL_KEY;
        
        // Make sure the HTTP status was OK
        if (status != 200) {
            llDialog(currentuseruuid, "Sorry " + currentusername + ".\nI could not request an object to be sent to you.\n (HTTP Status: "+(string)status+")", ["OK"], SLOODLE_CHANNEL_AVATAR_IGNORE);
            state ready;
            return;
        }
     
        // Handle the response
        handle_send_response(body);
        state ready;
    }
    
    timer()
    {
        llSetTimerEvent(0.0);
        llDialog(currentuseruuid, "Sorry " + currentusername + ".\nThe website was taking too long to respond. Please try again later.", ["OK"], SLOODLE_CHANNEL_AVATAR_IGNORE);
        state ready;
    }
}

