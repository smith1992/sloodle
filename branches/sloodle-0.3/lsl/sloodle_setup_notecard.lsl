// Sloodle configuration notecard reader
// Reads a configuration notecard and transmits the data via link messages to other scripts
// If the notecard changes, then it automatically resets.
//
// Part of the Sloodle project (www.sloodle.org)
// Copyright (c) 2007-8 Sloodle
// Released under the GNU GPL v3
//
// Contributors:
//  Edmund Edgar
//  Peter R. Bloomfield


integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
string SLOODLE_CONFIG_NOTECARD = "sloodle_config";
string SLOODLE_EOF = "sloodleeof";

key sloodle_notecard_key = NULL_KEY;
integer sloodle_notecard_line = 0;

string COMMENT_PREFIX = "//";

key latestnotecard = NULL_KEY; // The most recently read notecard


sloodle_tell_other_scripts(string msg)
{
    sloodle_debug("notecard sending message to other scripts: "+msg);
    llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, msg, NULL_KEY);   
}

sloodle_debug(string msg)
{
    //llWhisper(0,msg);
}

sloodle_handle_command(string str) 
{
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    
    string name = llList2String(bits,0);
    string value = "";
    if (numbits >= 2) llList2String(bits,1);

    // Check the command
    if (name == "do:reset") {
        // Reset
        sloodle_debug("Resetting configuration notecard reader");
        llResetScript();
    } else if (name == "do:requestconfig") {
        // Configuration request
        sloodle_start_reading_notecard();
    }
}

sloodle_start_reading_notecard()
{
    if (llGetInventoryType(SLOODLE_CONFIG_NOTECARD) == INVENTORY_NOTECARD) {
        sloodle_debug("starting reading notecard");
        sloodle_notecard_line = 0;
        sloodle_notecard_key = llGetNotecardLine("sloodle_config", 0); // read the first line. The dataserver event will get the next one.
        latestnotecard = llGetInventoryKey(SLOODLE_CONFIG_NOTECARD);
    } else {
        sloodle_debug("No notecard called "+SLOODLE_CONFIG_NOTECARD+" found - skipping notecard configuration");
        latestnotecard = NULL_KEY;
    }
}


default 
{
    on_rez(integer start_param)
    {
        llResetScript();
    }
    
    state_entry() 
    {
        sloodle_start_reading_notecard();
    }
    
    dataserver(key requested, string data)
    {
        if ( requested == sloodle_notecard_key )  // make sure we are getting the data we want
        {
            sloodle_notecard_key = NULL_KEY;
            if ( data != EOF )
            {
                // If this is a comment line, then do not forward it
                string trimmeddata = llStringTrim(data, STRING_TRIM_HEAD);
                if (llSubStringIndex(trimmeddata, COMMENT_PREFIX) == -1) sloodle_tell_other_scripts(data);
                // Advance to the next line
                sloodle_notecard_line++;
                sloodle_notecard_key = llGetNotecardLine("sloodle_config",sloodle_notecard_line);
            } else {
                // This is the end of the configuration data
                sloodle_tell_other_scripts(SLOODLE_EOF);
            }
        }
    }
    
    link_message(integer sender_num, integer num, string str, key id) {
        // Ignore debug messages
        if (num == DEBUG_CHANNEL) return;
        // Is this an object dialog message?
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            sloodle_handle_command(str);
        }
    }
    
    changed(integer change) {
        // If the inventory is changed, and we have a Sloodle config notecard, then use it to re-initialise
        if (change & CHANGED_INVENTORY && llGetInventoryType(SLOODLE_CONFIG_NOTECARD) == INVENTORY_NOTECARD) {
            // If the current notecard is not the same as the one we read most recently, then reset
            if (llGetInventoryKey(SLOODLE_CONFIG_NOTECARD) != latestnotecard) llResetScript();
        }
    }
}
