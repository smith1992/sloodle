// SCRIPTING IN PROGRESS! :-)

// Sloodle PrimDrop (for Sloodle 0.3)
// Allows students to submit SL objects as Moodle assignments.
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2007-8 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Jeremy Kemp
//  Peter R. Bloomfield
//


/// DATA ///

// Sloodle constants
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
string SLOODLE_CHAT_LINKER = "/mod/sloodle/mod/chat-1.0/linker.php";
string SLOODLE_EOF = "sloodleeof";
integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;

string SLOODLE_OBJECT_TYPE = "primdrop-1.0";

// Configuration settings
string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodlemoduleid = 0;
integer sloodleobjectaccessleveluse = 0; // Who can use this object?
integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?
integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)

// Configuration status
integer isconfigured = FALSE; // Do we have all the configuration data we need?
integer eof = FALSE; // Have we reached the end of the configuration data?

// This defines who is currently dropping or rezzing an item
key current_user = NULL_KEY;

// These lists are used when determining what inventory has been added
list old_inventory = [];
list new_inventory = [];


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


/// FUNCTIONS ///

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
    else if (name == "set:sloodlemoduleid") sloodlemoduleid = (integer)value1;
    else if (name == "set:sloodlelistentoobjects") sloodlelistentoobjects = (integer)value1;
    else if (name == "set:sloodleobjectaccessleveluse") sloodleobjectaccessleveluse = (integer)value1;
    else if (name == "set:sloodleobjectaccesslevelctrl") sloodleobjectaccesslevelctrl = (integer)value1;
    else if (name == "set:sloodleserveraccesslevel") sloodleserveraccesslevel = (integer)value1;
    else if (name == "set:sloodleautodeactivate") sloodleautodeactivate = (integer)value1;
    else if (name == SLOODLE_EOF) eof = TRUE;
    
    return (sloodleserverroot != "" && sloodlepwd != "" && sloodlecontrollerid > 0 && sloodlemoduleid > 0);
}

// Checks if the given agent is permitted to control this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_ctrl(key id)
{
    // Check the access mode
    if (sloodleobjectaccesslevelctrl == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP) {
        return llSameGroup(id);
    } else if (sloodleobjectaccesslevelctrl == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC) {
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
    if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP) {
        return llSameGroup(id);
    } else if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC) {
        return TRUE;
    }
    
    // Assume it's owner mode
    return (id == llGetOwner());
}

// Does the object have valid permissions?
// Returns TRUE if so, or FALSE otherwise
integer valid_perms(string obj)
{
    integer perms_owner = llGetInventoryPermMask(obj, MASK_OWNER);
    integer perms_next = llGetInventoryPermMask(obj, MASK_NEXT);
    
    return (!((perms_owner & PERM_COPY) && (perms_owner & PERM_TRANSFER) && (perms_next & PERM_COPY) && (perms_next & PERM_TRANSFER)));
}

// Returns a list of all inventory (all types)
list get_inventory()
{
    list inv = [];
    integer num = llGetInventoryNumber(INVENTORY_ALL);
    integer i = 0;
    for (; i < num; i++) {
        inv += [llGetInventoryName(INVENTORY_ALL, i)];
    }
    
    return inv;
}

// Compares 2 lists
// Returns the first item on list1 that is not on list2
// Returns an empty string if nothing is found
string ListDiff(list list1, list list2) {
    integer i;

    for (i = 0; i < llGetListLength(list1); i++) {
        if (llListFindList(list2, llList2List(list1, i, i)) == -1) {
            return(llList2String(list1, i));
        }
    }
    return("");
}


/// STATES ///

// Default state - waiting for configuration
default
{
    state_entry()
    {
        // Starting again with a new configuration
        isconfigured = FALSE;
        eof = FALSE;
        // Reset our data
        sloodleserverroot = "";
        sloodlepwd = "";
        sloodlecontrollerid = 0;
        sloodlemoduleid = 0;
        sloodleobjectaccessleveluse = 0;
        sloodleobjectaccesslevelctrl = 0;
        sloodleserveraccesslevel = 0;
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
            if (eof == TRUE && isconfigured == TRUE) {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configurationreceived", [], NULL_KEY);
                state ready;
            }
        }
    }
    
    touch_start(integer num_detected)
    {
        // Attempt to request a reconfiguration
        if (llDetectedKey(0) == llGetOwner()) {
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", NULL_KEY);
        }
    }
}

// Checking that the assignment is accessible
state check_assignment
{
}


// Ready to be used
state ready
{
    state_entry()
    {
        llSetText("Ready", <1.0,1.0,1.0>, 1.0);
        llListen(0, "", NULL_KEY, "");
    }

    listen(integer channel, string name, key id, string msg)
    {
        if (channel == 0 && id == llGetOwner()) {
            if (msg == "DROP") {
                current_user = id;
                state drop;
                return;
            }
        }
    }
}

// Checking if the current user can submit objects to this assignment
state check_user_submit
{
}

// Checking if the current user can rez objects from this PrimDrop
state check_user_rez
{
}

// Waiting for an object to be dropped
state drop
{
    state_entry()
    {
        llSetText("Checking inventory...", <0.5,0.1,0.0>, 1.0);
        old_inventory = get_inventory();
        llSetText("Waiting for object from: " + llKey2Name(current_user), <0.0,0.6,0.0>, 1.0);
        llAllowInventoryDrop(TRUE);
        llSetTimerEvent(15.0);
    }
    
    state_exit()
    {
        llAllowInventoryDrop(FALSE);
        llSetText("", <0.0,0.6,0.0>, 1.0);
        llSetTimerEvent(0.0);
    }
    
    
    timer()
    {
        llSay(0, "Timeout");
        state ready;
    }
    
    changed(integer change)
    {
        if ((change & CHANGED_INVENTORY) || (change & CHANGED_ALLOWED_DROP)) {
            state check_drop;
        }
    }
}

// Checking an object which was dropped
state check_drop
{
    state_entry()
    {
        // Determine what our new object is
        llSetText("Checking object...", <0.5,0.3,0.0>, 1.0);
        new_inventory = get_inventory();
        string obj = ListDiff(new_inventory, old_inventory);
        
        // Make sure it exists
        if (llGetInventoryType(obj) == INVENTORY_NONE || obj == "") {
            llSay(0, "Error locating new object.");
            state ready;
            return;
        }
        
        // Make sure it is the correct type
        if (llGetInventoryType(obj) != INVENTORY_OBJECT) {
            llSay(0, "ERROR: only objects are accepted.");
            llRemoveInventory(obj);
            state ready;
            return;
        }

        // Determine the object ID and creator
        key obj_id = llGetInventoryKey(obj);
        key obj_creator = llGetInventoryCreator(obj);
        
        // Make sure the creator is the expected user
        if (obj_creator != current_user) {
            llSay(0, "Error: for security, object must be submitted by its creator.");
            llRemoveInventory(obj);
            state ready;
            return;
        }
        
        if (valid_perms(obj)) {
            llSay(0, "Object did not have valid permissions. Please ensure copy and transfer are enabled.");
            llRemoveInventory(obj);
            state ready;
            return;
        }
        
        llSay(0, "Object \"" + obj + "\" received successfully. Thank you " + llKey2Name(current_user));
        state submitting;
    }
    
    state_exit()
    {
        llSetText("", <0.0,0.6,0.0>, 1.0);
    }
}

// Submitting an object which was dropped
state submitting
{
}

// Rezzing an object
state rezzing
{
}
