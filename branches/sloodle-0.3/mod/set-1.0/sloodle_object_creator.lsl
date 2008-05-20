// Sloodle Set object creation script.
// Handles the dispensor box's inventory and allows users to rez it.
//
// Part of the Sloodle project (www.sloodle.org).
// Copyright (c) 2007-8 Sloodle
// Released under the GNU GPL v3
//
// Contributors:
//  Edmund Edgar
//  Peter R. Bloomfield


///// DATA /////

integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
string SLOODLE_AUTH_LINKER = "/mod/sloodle/classroom/auth_object_linker.php";
string SLOODLE_EOF = "sloodleeof";

string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
string sloodlecoursename_full = "";
integer sloodleobjectaccessleveluse = 0; // Who can use this object?
integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?
integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)

integer isconfigured = FALSE; // Do we have all the configuration data we need?
integer eof = FALSE; // Have we reached the end of the configuration data?

key httpcheckcourse = NULL_KEY;
list cmddialog = []; // Alternating list of keys, timestamps and page numbers, indicating who activated a dialog, when, and which page they are on
list inventory = []; // A list of names of inventory items available for rezzing (copyable objects)

list autorez_names = []; // List of names of items to autorez
list autorez_pos = []; // Autorez positions
list autorez_rot = []; // Autorez rotations

string MENU_BUTTON_PREVIOUS = "<<";
string MENU_BUTTON_NEXT = ">>";

vector rez_pos = <1.0,1.0,1.0>; // Where we will rez objects, relative to this one
string rez_object = ""; // Name of the object we will rez
integer rez_password = 0; // Password of the object we have just rezzed
key rez_http = NULL_KEY; // HTTP request to authorise the object we have just rezzed
key rez_id = NULL_KEY; // UUID of the object we have just rezzed
key rez_user = NULL_KEY; // UUID of the agent requesting the object to be rezzed


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
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params), keyval);
}

///// ----------- /////


///// FUNCTIONS /////

// Send debug info
sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// Generate a random integer password
integer sloodle_random_password()
{
    return (100000 + (integer)llFrand(999899999.0));
}

// Configure by receiving a linked message from another script in the object
// Returns TRUE if the object has all the data it needs
integer sloodle_handle_command(string str) 
{
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    
    if (numbits > 1) value1 = llList2String(bits,1);
    if (numbits > 2) value2 = llList2String(bits,2);
    
    if (name == "set:sloodleserverroot") sloodleserverroot = value1;
    if (name == "set:sloodlecoursename_full") sloodlecoursename_full = value1;
    else if (name == "set:sloodlepwd") {
        // The password may be a single prim password, or a UUID and a password
        if (value2 != "") sloodlepwd = value1 + "|" + value2;
        else sloodlepwd = value1;
        
    } else if (name == "set:sloodlecontrollerid") sloodlecontrollerid = (integer)value1;
    else if (name == "set:sloodleobjectaccessleveluse") sloodleobjectaccessleveluse = (integer)value1;
    else if (name == "set:sloodleobjectaccesslevelctrl") sloodleobjectaccesslevelctrl = (integer)value1;
    else if (name == "set:sloodleserveraccesslevel") sloodleserveraccesslevel = (integer)value1;
    else if (name == SLOODLE_EOF) eof = TRUE;
    else if (name == "do:reset") llResetScript();
    
    return (sloodleserverroot != "" && sloodlepwd != "" && sloodlecontrollerid > 0);
}

// Checks if the given agent is permitted to user this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_use(key id)
{
    // Currently only the owner for this object
    return (id == llGetOwner());
}

// Checks if the given agent is permitted to control this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_ctrl(key id)
{
    // Currently only the owner for this object
    return (id == llGetOwner());
}

// Add the given agent to our command dialog list
sloodle_add_cmd_dialog(key id, integer page)
{
    // Does the person already exist?
    integer pos = llListFindList(cmddialog, [id]);
    if (pos < 0) {
        // No - add the agent to the end
        cmddialog += [id, llGetUnixTime(), page];
    } else {
        // Yes - update the time
        cmddialog = llListReplaceList(cmddialog, [llGetUnixTime(), page], pos + 1, pos + 2);
    }
}

// Get the number of the page the current user is on in the dialogs
// (Returns 0 if they are not found)
integer sloodle_get_cmd_dialog_page(key id)
{
    // Does the person exist in the list?
    integer pos = llListFindList(cmddialog, [id]);
    if (pos >= 0) {
        // Yes - get the page number
        return llList2Integer(cmddialog, pos + 2);
    }
    return 0;
}

// Remove the given agent from our command dialog list
sloodle_remove_cmd_dialog(key id)
{
    // Is the person in the list?
    integer pos = llListFindList(cmddialog, [id]);
    if (pos >= 0) {
        // Yes - remove them and their timestamp
        cmddialog = llDeleteSubList(cmddialog, pos, pos + 2);
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
            cmddialog = llDeleteSubList(cmddialog, i, i + 2);
        } else {
            // No - advance to the next
            i += 3;
        }
    }
}


// Shows the given user a dialog of objects, starting at the specified page
sloodle_show_object_dialog(key id, integer page)
{
    // Each dialog can display 12 buttons
    // However, we'll reserve the top row (buttons 10, 11, 12) for the next/previous buttons.
    // This leaves use with 9 others.

    // Check how many objects we have
    integer numobjects = llGetListLength(inventory);
    if (numobjects == 0) {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "sloodleset:noobjects", [llKey2Name(id)], NULL_KEY);
        return;
    }
    
    // How many pages are there?
    integer numpages = (integer)((float)numobjects / 9.0) + 1;
    // If the requested page number is invalid, then cap it
    if (page < 0) page == 0;
    else if (page >= numpages) page = numpages - 1;
    
    // Build our list of item buttons (up to a maximum of 9)
    list buttonlabels = [];
    string buttondef = ""; // Indicates which button does what
    integer numbuttons = 0;
    integer itemnum = 0;
    for (itemnum = page * 9; itemnum < numobjects && numbuttons < 9; itemnum++, numbuttons++) {
        // Add the button label (a number) and button definition
        buttonlabels += [(string)(itemnum + 1)]; // Button labels are 1-based
        buttondef += (string)(itemnum + 1) + " = " + llList2String(inventory, itemnum) + "\n";
    }
    
    // Add our page buttons if necessary
    if (page > 0) buttonlabels += [MENU_BUTTON_PREVIOUS];
    if (page < (numpages - 1)) buttonlabels += [MENU_BUTTON_NEXT];
    
    // Display the basic object menu
    sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG, [SLOODLE_CHANNEL_AVATAR_DIALOG] + buttonlabels, "sloodleset:objectmenu", [buttondef], id);
}

// Update our inventory list
update_inventory()
{
    // We're going to build a string of all copyable inventory objects
    inventory = [];
    integer numitems = llGetInventoryNumber(INVENTORY_OBJECT);
    string itemname = "";
    
    // Go through each item
    integer i = 0;
    for (i = 0; i < numitems; i++) {
        // Get the name of this item
        itemname = llGetInventoryName(INVENTORY_OBJECT, i);
        // Make sure it's copyable
        if (llGetInventoryPermMask(itemname, MASK_OWNER) & PERM_COPY) {
            inventory += [itemname];
        }
    }
}

// Rez the named inventory item
sloodle_rez_inventory(string name, integer password)
{
    // Check that the item exists
    if (llGetInventoryType(name) != INVENTORY_OBJECT) return;
    // Attempt to rez the item
    llRezObject(name, llGetPos() + rez_pos, ZERO_VECTOR, ZERO_ROTATION, password);
}


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
        sloodlecoursename_full = "";
        sloodleobjectaccessleveluse = 0;
        sloodleserveraccesslevel = 0;
    }
    
    link_message(integer sender_num, integer num, string str, key id)
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
            if (eof == TRUE && isconfigured == TRUE) {
                state ready;
            }
        }  
    }
    
    touch_start(integer num_detected)
    {
        // Can the user use this object
        if (sloodle_check_access_use(llDetectedKey(0))) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "notconfiguredyet", [llDetectedName(0)], NULL_KEY);
        } else {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "nopermission:use", [llDetectedName(0)], NULL_KEY);
        }
    }
}

state ready
{
    state_entry()
    {
        // Listen for dialog commands
        llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", NULL_KEY, "");
        // Regularly purge expired dialogs
        llSetTimerEvent(12.0);
        
        // Check our inventory
        update_inventory();
    }
    
    timer()
    {
        sloodle_purge_cmd_dialog();
    }
    
    changed(integer change)
    {
        if ((change & CHANGED_INVENTORY) == CHANGED_INVENTORY) update_inventory();
    }
    
    touch_start(integer num_detected)
    {
        // Go through each toucher
        integer i = 0;
        key id = NULL_KEY;
        for (; i < num_detected; i++) {
            id = llDetectedKey(0);
            // Make sure the user is allowed to use this object
            if (sloodle_check_access_use(id) || sloodle_check_access_ctrl(id)) {
                // Show a menu of objects
                sloodle_show_object_dialog(id, 0);
                sloodle_add_cmd_dialog(id, 0);
            } else {
                // Inform the user of the problem
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "nopermission:use", [llKey2Name(id)], NULL_KEY);
            }
        }
    }
    
    link_message(integer sender_num, integer num, string str, key id)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Is this a reset command?
            if (str == "do:reset") {
                llResetScript();
                return;
            }
            
            // Parse the command
            list lines = llParseString2List(str, ["\n"], []);
            integer numlines = llGetListLength(lines);
            string cmd = llList2String(lines, 0);
            
            // Are we being asked to rez items?
            // (ignore it if we have some auto-rezzing pending)
            if (cmd == "do:rez" && autorez_names == []) {
                autorez_pos = [];
                autorez_rot = [];
                // Go through each other line
                integer i = 1;
                list fields = [];
                for (; i < numlines; i++) {
                    // Extract the fields
                    fields = llParseString2List(llList2String(lines, i), ["|"], []);
                    if (llGetListLength(fields) >= 3) {
                        autorez_names += [llList2String(fields, 0, 0)];
                        autorez_pos += [(vector)llList2String(fields, 1, 1)];
                        autorez_rot += [(vector)llList2String(fields, 2, 2)];
                    }
                }
            }
        }  
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check what channel it is
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Ignore the message if the user is not on our list
            if (llListFindList(cmddialog, [id]) == -1) return;
            // Find out what the current page number is
            integer page = sloodle_get_cmd_dialog_page(id);
        
            // Check what message is
            if (msg == MENU_BUTTON_NEXT) {
                // Show the next menu of objects
                sloodle_show_object_dialog(id, page + 1);
                sloodle_add_cmd_dialog(id, page + 1);
                
            } else if (msg == MENU_BUTTON_PREVIOUS) {
                // Show the previous menu of objects
                sloodle_show_object_dialog(id, page - 1);
                sloodle_add_cmd_dialog(id, page - 1);
                
            } else {
                // Treat the message as a number (objects are numbered from 1)
                integer objnum = (integer)msg;
                if (objnum > 0 && objnum <= llGetListLength(inventory)) {
                    // Attempt to rez the specified item
                    rez_object = llList2String(inventory, objnum - 1);
                    rez_user = id;
                    state rezzing;
                }
                sloodle_remove_cmd_dialog(id);
            }
        }
    }
}


// In this state, we are rezzing a new object
state rezzing
{
    state_entry()
    {
        // Display an appropriate caption
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT, [<0.0,0.0,1.0>,1.0], "rezzingobject", [rez_object], NULL_KEY);
        // Generate a password, and rez the object
        rez_password = sloodle_random_password();
        sloodle_rez_inventory(rez_object, rez_password);
        
        // Timeout after a while if the object doesn't get rezzed
        llSetTimerEvent(8.0);
        
    }
    
    timer()
    {
        llSetTimerEvent(0.0);
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "reztimeout", [rez_object], NULL_KEY);
        state ready;
    }
    
    state_exit()
    {
        llSetText("", <0.0,0.0,0.0>, 0.0);
    }
    
    object_rez(key id)
    {
        // Reset our timeout for the authorisation
        llSetTimerEvent(10.0);
        rez_id = id;
        // Start authorising the object
        string body = "sloodlecontrollerid=" + (string)sloodlecontrollerid;
        body += "&sloodlepwd=" + sloodlepwd;
        body += "&sloodleobjuuid=" + (string)id;
        body += "&sloodleobjname=" + rez_object;
        body += "&sloodleobjpwd=" + (string)rez_password;
        body += "&sloodleuuid=" + (string)rez_user;
        body += "&sloodleavname=" + llKey2Name(rez_user);
        
        rez_http = llHTTPRequest(sloodleserverroot + SLOODLE_AUTH_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
    }
    
    link_message(integer sender_num, integer num, string str, key id)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // What was the message?
            if (str == "do:reset") llResetScript();
        }  
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        // Ignore this response if it is not expected
        if (id != rez_http) return;
        llSetTimerEvent(0.0);
        rez_http = NULL_KEY;
        
        // Check the HTTP status
        if (status != 200) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "httperror:code", [status], NULL_KEY);
            state ready;
            return;
        }
        
        // Check the body of the response
        if (body == "") {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "httpempty", [], NULL_KEY);
            state ready;
            return;
        }
        
        // Parse the response
        list lines = llParseStringKeepNulls(body, ["\n"], []);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
        integer statuscode = (integer)llList2String(statusfields, 0);
        
        // Make sure we have enough data
        if (numlines < 2) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "badresponseformat", [], NULL_KEY);
            sloodle_debug("HTTP response: " + body);
            state ready;
            return;
        }
                
        // Did an error occur?
        if (statuscode == -331) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "nopermission:authobject", [llKey2Name(rez_user)], NULL_KEY);
            state ready;
            return;
        } else if (statuscode <= 0) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "servererror", [statuscode], NULL_KEY);
            sloodle_debug("HTTP response: " + body);
            state ready;
            return;
        }
        
        // Extract the authorisation ID
        string authid = llList2String(lines, 1);
        // Determine the root key of the object
        key rootkey = llGetLinkKey(1); // Gets the root prim of a link set
        if (rootkey == NULL_KEY) rootkey = llGetLinkKey(0); // Gets the only prim if there is only one
        
        // Everything must be OK... send the data to the object. Format:
        //  sloodle_init|<target-uuid>|<moodle-address>|<authid>
        llSay(SLOODLE_CHANNEL_OBJECT_DIALOG, "sloodle_init|" + (string)rootkey + "|" + (string)rez_id + "|" + sloodleserverroot + "|" + authid);
        state ready;
    }
}
