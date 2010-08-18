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
string API_URL="http://api.avatarclassroom.com/api/api.php";
string AVATAR_CLASSROOM_PASSWORD="128sdfKiweriojs012";
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
string SLOODLE_CONFIG_NOTECARD = "sloodle_config";
string SLOODLE_EOF = "sloodleeof";

key sloodle_notecard_key = NULL_KEY;
integer sloodle_notecard_line = 0;

string COMMENT_PREFIX = "//";

key latestnotecard = NULL_KEY; // The most recently read notecard



key http_id;


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
string SLOODLE_TRANSLATE_LOAD_URL = "loadurl";      // Recipient avatar should be identified in link message keyval. 1 output parameter, containing the URL.
string SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";  // 2 output parameters: colour <r,g,b>, and alpha value

// Send a translation request link message
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}
integer debugCheck(){
    if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
        return TRUE;
    }
        else return FALSE;
    
}
debug(string str){
    if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
        llOwnerSay(str);
    }
}
///// ----------- /////


///// FUNCTIONS /////


sloodle_tell_other_scripts(string msg)
{
    sloodle_debug("pseudo notecard sending message to other scripts: "+msg);
    llMessageLinked(LINK_SET, SLOODLE_CHANNEL_OBJECT_DIALOG, msg, NULL_KEY);   
}

sloodle_debug(string msg)
{
    //llOwnerSay(msg);
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
        llResetScript();
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
        // Pause for a moment, in case all scripts were reset at the same time
        llSleep(0.2);
        // Go!

        if (llGetInventoryType(SLOODLE_CONFIG_NOTECARD) == INVENTORY_NOTECARD) {
            // Do nothing and let the regular notecard script handle this.
        } else {
            sloodle_debug("No notecard called "+SLOODLE_CONFIG_NOTECARD+" found - trying instantclassroom API");
            latestnotecard = NULL_KEY;
            
            llSetTimerEvent(60.0);                
            string body = "owneruuid=" + (string)llGetOwner() + "&ownername=" + llKey2Name(llGetOwner()) + "&object_name=" + llGetObjectName()+ "&type=" + "pseudo_notecard" + "&password=" + AVATAR_CLASSROOM_PASSWORD;
            http_id = llHTTPRequest(API_URL, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
debug(API_URL+"?"+body);
            
        }

    }    
    
    http_response(key request_id, integer ss, list metadata, string body) {
        list lines = llParseStringKeepNulls( body, ["\n"], []);
        integer status = (integer)llList2String(lines, 0);
debug(body);
        if (status > 0) {
            integer i;
            for( i=1; i<llGetListLength(lines); i++ ) {
                string data = llList2String(lines, i);
                string trimmeddata = llStringTrim(data, STRING_TRIM_HEAD);
                if (llSubStringIndex(trimmeddata, COMMENT_PREFIX) != 0) {
                    sloodle_tell_other_scripts(trimmeddata); 
                    llSleep(0.4);
                }
            }
        }
        llSleep(0.2);
        sloodle_tell_other_scripts(SLOODLE_EOF);        
    }
    
    link_message(integer sender_num, integer num, string str, key id) {
        // Ignore debug messages
        if (num == DEBUG_CHANNEL) return;
        // Is this an object dialog message?
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
       //     debug(str);
            sloodle_handle_command(str);
        }
    }
    
    changed(integer change) {
        // If the inventory is changed, and we have a Sloodle config notecard, then use it to re-initialise
        if (change & CHANGED_INVENTORY) {
            // If the current notecard is not the same as the one we read most recently, then reset
          llResetScript();
        }
    }
}
