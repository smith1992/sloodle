// Sloodle Glossary (for Sloodle 0.3)
// Allows users in-world to search a Moodle glossary.
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-8 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Jeremy Kemp
//  Peter R. Bloomfield
//

integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
string SLOODLE_CHAT_LINKER = "/mod/sloodle/mod/glossary-1.0/linker.php";
string SLOODLE_EOF = "sloodleeof";

string SLOODLE_OBJECT_TYPE = "glossary-1.0";

integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;

string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodlemoduleid = 0;
integer sloodleobjectaccessleveluse = 0; // Who can use this object?
integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)
integer sloodlepartialmatches = 1;
integer sloodlesearchaliases = 0;
integer sloodlesearchdefinitions = 0;
integer sloodleidletimeout = 120; // How many seconds before automatic idle timeout? (0 means don't timeout)
string sloodleglossaryname = ""; // Name of the glossary

integer isconfigured = FALSE; // Do we have all the configuration data we need?
integer eof = FALSE; // Have we reached the end of the configuration data?

key httpquery = NULL_KEY; // Request used to send/receive chat

string SLOODLE_METAGLOSS_COMMAND = "/def "; // The prefix for chat messages
string searchterm = ""; // The term to be searched


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
    else if (name == "set:sloodleobjectaccessleveluse") sloodleobjectaccessleveluse = (integer)value1;
    else if (name == "set:sloodleobjectaccesslevelctrl") sloodleobjectaccesslevelctrl = (integer)value1;
    else if (name == "set:sloodleserveraccesslevel") sloodleserveraccesslevel = (integer)value1;
    else if (name == "set:sloodlepartialmatches") sloodlepartialmatches = (integer)value1;
    else if (name == "set:sloodlesearchaliases") sloodlesearchaliases = (integer)value1;
    else if (name == "set:sloodlesearchdefinitions") sloodlesearchdefinitions = (integer)value1;
    else if (name == "set:sloodleidletimeout") sloodleidletimeout = (integer)value1;
    else if (name == SLOODLE_EOF) eof = TRUE;
    
    return (sloodleserverroot != "" && sloodlepwd != "" && sloodlecontrollerid > 0 && sloodlemoduleid > 0);
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


///// STATES /////

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
        sloodlelistentoobjects = 0;
        sloodleobjectaccessleveluse = 0;
        sloodleserveraccesslevel = 0;
        sloodlepartialmatches = 1;
        sloodlesearchaliases = 0;
        sloodlesearchdefinitions = 0;
        sloodleidletimeout = 120;
        sloodleglossaryname = "";
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
                state check_glossary;
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


// If necessary, check the name of the glossary
state check_glossary
{
    state_entry()
    {
        // Lookup the glossary name
        //... (temp):
        sloodleglossaryname = "(unknown)";
        state ready;
    }
}


// Ready for definition requests
state ready
{
    on_rez( integer param)
    {
        state default;
    }    
    
    state_entry()
    {
        // Update the hover text
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT, [<1.0,0.0,0.0>, 0.9], "metagloss:ready", [sloodleglossaryname, SLOODLE_METAGLOSS_COMMAND], NULL_KEY);
        // Listen for chat messages
        llListen(0, "", NULL_KEY, "");
    
        // We may need to de-activate after a period of idle time
        llSetTimerEvent(0.0);
        if (sloodleidletimeout > 0) llSetTimerEvent((float)sloodleidletimeout);
    }
    
    state_exit()
    {
        llSetTimerEvent(0.0);
    }
    
    listen( integer channel, string name, key id, string message)
    {
        // Check the channel
        if (channel == 0) {
            // Check use of this object
            if (sloodle_check_access_use(id) == FALSE) return;
            // Is this a definition request?
            if (llSubStringIndex(message, SLOODLE_METAGLOSS_COMMAND) != 0) return;
    
            // Store the term to be searched and search it
            searchterm = llGetSubString(message, llStringLength(SLOODLE_METAGLOSS_COMMAND), -1);
            state search;
            return;
        }
    }

    timer()
    {
        // Shutdown due to idle timeout
        state shutdown;
    }
    
}

state shutdown
{
    state_entry()
    {
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT, [<0.5,0.1,0.1>, 0.6], "metagloss:idle", [sloodleglossaryname], NULL_KEY);
    }
    
    touch_start(integer num_detected)
    {
        // Go through each toucher
        integer i = 0;
        key id = NULL_KEY;
        for (; i < num_detected; i++) {
            id = llDetectedKey(i);
            // Does this user have permission to use this object?
            if (sloodle_access_check_use(id)) {
                state ready;
            } else {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "nopermission:use", [], NULL_KEY);
            }
        }
    }
}

state search
{
    state_entry()
    {
        
    }
}
