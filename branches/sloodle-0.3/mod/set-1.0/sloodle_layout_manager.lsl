// Sloodle Set layout manager script.
// Allows users in-world to use and manage layouts.
//
// Part of the Sloodle project (www.sloodle.org).
// Copyright (c) 2007-8 Sloodle
// Released under the GNU GPL v3
//
// Contributors:
//  Edmund Edgar
//  Peter R. Bloomfield
//

///// DATA /////

integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
string SLOODLE_LAYOUT_LINKER = "/mod/sloodle/mod/set-1.0/layout_linker.php";
string SLOODLE_EOF = "sloodleeof";

integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;

string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodleobjectaccessleveluse = 0; // Who can use this object?
integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)

integer isconfigured = FALSE; // Do we have all the configuration data we need?
integer eof = FALSE; // Have we reached the end of the configuration data?

list httplayoutbrowse = []; // Requests for a list of layouts
list httplayoutquery = []; // Requests for the contents of a layout
list httplayoutupdate = []; // Requests to update a layout

list cmddialog = []; // Alternating list of keys, timestamps and page numbers, indicating who activated a dialog, when, and which page they are on
list availablelayouts = []; // A list of names of layouts which are available on the current course

string currentlayout = ""; // Name of the currently loaded layout, if any

string MENU_BUTTON_NEXT_LOAD = ">";
string MENU_BUTTON_NEXT_SAVE = ">>";
string MENU_BUTTON_PREVIOUS_LOAD = "<";
string MENU_BUTTON_PREVIOUS_SAVE = "<<";
string MENU_BUTTON_LOAD = "A";
string MENU_BUTTON_SAVE = "B";
string MENU_BUTTON_SAVE_AS = "C";
string MENU_BUTTON_CANCEL = "X";


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
    else if (name == "set:sloodlepwd") {
        // The password may be a single prim password, or a UUID and a password
        if (value2 != "") sloodlepwd = value1 + "|" + value2;
        else sloodlepwd = value1;
        
    } else if (name == "set:sloodlecontrollerid") sloodlecontrollerid = (integer)value1;
    else if (name == "set:sloodleobjectaccessleveluse") sloodleobjectaccessleveluse = (integer)value1;
    else if (name == "set:sloodleserveraccesslevel") sloodleserveraccesslevel = (integer)value1;
    else if (name == SLOODLE_EOF) eof = TRUE;
    else if (name == "do:reset") llResetScript();
    
    return (sloodleserverroot != "" && sloodlepwd != "" && sloodlecontrollerid > 0);
}

// Checks if the given agent is permitted to control this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_ctrl(key id)
{
    // Only the owner can control this
    return (id == llGetOwner());
}

// Checks if the given agent is permitted to user this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_use(key id)
{
    // The owner can always use this
    if (id == llGetOwner()) return TRUE;
    
    // Check the access mode
    if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP) {
        return llSameGroup(id);
    } else if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC) {
        return TRUE;
    }
    return FALSE;
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


// Shows the given user a dialog of layouts, starting at the specified page.
// If "load" is TRUE, then each button label will be prefixed with "L" and a "load" dialog will be shown.
// Otherwise, each button label will be prefixed with "S" and a "save" dialog will be shown.
sloodle_show_layout_dialog(key id, integer page, integer load)
{
    // Determine our button label prefix
    string prefix = "S"; // S for Save
    if (load) prefix = "L"; // L for Load

    // Each dialog can display 12 buttons
    // However, we'll reserve the top row (buttons 10, 11, 12) for the next/previous buttons.
    // This leaves us with 9 others.

    // Check how many layouts we have
    integer numlayouts = llGetListLength(availablelayouts);
    if (numlayouts == 0) {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "layout:noneavailable", [llKey2Name(id)], NULL_KEY);
        return;
    }
    
    // How many pages are there?
    integer numpages = (integer)((float)numlayouts / 9.0) + 1;
    // If the requested page number is invalid, then cap it
    if (page < 0) page == 0;
    else if (page >= numpages) page = numpages - 1;
    
    // Build our list of item buttons (up to a maximum of 9)
    list buttonlabels = [];
    string buttondef = ""; // Indicates which button does what
    integer numbuttons = 0;
    integer layoutnum = 0;
    for (layoutnum = page * 9; layoutnum < numlayouts && numbuttons < 9; layoutnum++, numbuttons++) {
        // Add the button label (a number) and button definition
        buttonlabels += [prefix + (string)(layoutnum + 1)]; // Button labels are 1-based
        buttondef += (string)(layoutnum + 1) + " = " + llList2String(availablelayouts, layoutnum) + "\n";
    }
        
    // Add our page buttons if necessary
    if (page > 0) {
        if (load) buttonlabels += [MENU_BUTTON_PREVIOUS_LOAD];
        else buttonlabels += [MENU_BUTTON_PREVIOUS_SAVE];
    }
    if (page < (numpages - 1)) {
        if (load) buttonlabels += [MENU_BUTTON_NEXT_LOAD];
        else buttonlabels += [MENU_BUTTON_NEXT_SAVE];
    }
    
    // Display the appropriate menu
    if (load) sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG, [SLOODLE_CHANNEL_AVATAR_DIALOG] + buttonlabels, "layout:loadmenu", [buttondef], id);
    else sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG, [SLOODLE_CHANNEL_AVATAR_DIALOG] + buttonlabels, "layout:savemenu", [buttondef], id);
}

// Request an updated list of layouts on behalf of the specified user.
sloodle_update_layout_list(key id)
{
    // Start authorising the object
    string body = "sloodlecontrollerid=" + (string)sloodlecontrollerid;
    body += "&sloodlepwd=" + sloodlepwd;
    body += "&sloodleuuid=" + (string)id;
    body += "&sloodleavname=" + llKey2Name(id);
    body += "&sloodleserveraccesslevel=" + sloodleserveraccesslevel;
    
    key newhttp = llHTTPRequest(sloodleserverroot + SLOODLE_AUTH_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
    httplayoutbrowse += [newhttp];
}

// Display the command dialog to the specified user
sloodle_show_command_dialog(key id)
{
    // Use letters for this menu
    list btns = [MENU_BUTTON_LOAD, MENU_BUTTON_SAVE, MENU_BUTTON_SAVE_AS, MENU_BUTTON_CANCEL];
    sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG, [SLOODLE_CHANNEL_AVATAR_DIALOG] + btns, "layout:cmdmenu", btns, id);
}


///// STATES /////

// Waiting for configuration
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


// Ready to be used
state ready
{
    state_entry()
    {
        // Listen for any avatars controlling us
        llListen(SLOODLE_CHANNEL_AVATAR_DIALOG, "", NULL_KEY, "");
        // Regularly purge the list of dialogs
        llSetTimerEvent(12.0);
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
                // Update the internal list of layouts
                sloodle_update_layout_list(id);
                                
            } else {
                // Inform the user of the problem
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "nopermission:use", [llKey2Name(id)], NULL_KEY);
            }
        }
    }
    
    http_response(key id, integer status, list meta, string body)
    {
        //...
        
        // Show the command menu
        sloodle_show_command_dialog(id);
        sloodle_add_cmd_dialog(id, 0);
    }
    
    listen(integer channel, string name, key id, string msg)
    {
        // Check the channel
        if (channel == SLOODLE_CHANNEL_AVATAR_DIALOG) {
            // Ignore the message if the user is not on our list
            if (llListFindList(cmddialog, [id]) == -1) return;
            // Find out what the current page number is
            integer page = sloodle_get_cmd_dialog_page(id);
            
            // Check what message is
            if (msg == MENU_BUTTON_NEXT_LOAD) {
                // Show the next load menu of layouts
                sloodle_show_layout_dialog(id, page + 1, TRUE);
                sloodle_add_cmd_dialog(id, page + 1);
                
            } if (msg == MENU_BUTTON_NEXT_SAVE) {
                // Show the next save menu of layouts
                sloodle_show_layout_dialog(id, page + 1, FALSE);
                sloodle_add_cmd_dialog(id, page + 1);
                
            } else if (msg == MENU_BUTTON_PREVIOUS_LOAD) {
                // Show the previous load menu of layouts
                sloodle_show_layout_dialog(id, page - 1, TRUE);
                sloodle_add_cmd_dialog(id, page - 1);
                
            } else if (msg == MENU_BUTTON_PREVIOUS_SAVE) {
                // Show the previous load menu of layouts
                sloodle_show_layout_dialog(id, page - 1, FALSE);
                sloodle_add_cmd_dialog(id, page - 1);
                
            } else if (msg == MENU_BUTTON_LOAD) {
                // Display the loading dialog
                sloodle_show_layout_dialog(id, 0, TRUE);
                sloodle_add_cmd_dialog(id, 0);
                
            } else if (msg == MENU_BUTTON_SAVE) {
                // Do we currently have a layout loaded?
                if (currentlayout == "") {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "layout:nolayouttosave", [name], NULL_KEY);
                    sloodle_remove_cmd_dialog(id);
                } else {
                    // Show the save dialog
                    sloodle_show_layout_dialog(id, 0, FALSE);
                    sloodle_add_cmd_dialog(id, 0);
                }
                
            } else if (msg == MENU_BUTTON_SAVE_AS) {
                sloodle_remove_cmd_dialog(id);
                state save_as;
                
            } else if (msg == MENU_BUTTON_CANCEL) {
                sloodle_remove_cmd_dialog(id);
                
            } else if (llStringLength(msg) > 1) {
                // This might be a "Lx" or "Sx" message for Loading or Saving a profile.
                sloodle_remove_cmd_dialog(id);
                string code = llGetSubString(msg, 0, 0);
                if (code != "S" && code != "L") return;
                integer num = (integer)llGetSubString(msg, 1, -1);
                if (num < 1 || num > llGetListLength(availablelayouts)) return;
                
                // Attempt to load or save the given laout
                current_layout = llList2String(availablelayouts, num - 1);
                if (current_layout == "") return;
                if (code == "S") state save_layout;
                else if (code == "L") state load_layout;
            }
        }
    }
}

