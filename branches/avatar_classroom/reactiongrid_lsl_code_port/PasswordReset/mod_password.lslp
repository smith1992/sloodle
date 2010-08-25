// Sloodle Password Reset object
// Allows avatar with auto-registered Moodle accounts to reset their Moodle password from in-world.
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2008 Sloodle
// Released under the GNU GPL v3
//
// Contributors:
//  Peter R. Bloomfield
//

integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
string SLOODLE_PWRESET_LINKER = "/mod/sloodle/mod/pwreset-1.0/linker.php";
string SLOODLE_EOF = "sloodleeof";

string SLOODLE_OBJECT_TYPE = "pwreset-1.0";

integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;
integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
string sloodleserverroot = "";
string sloodlecoursename_full = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodleobjectaccessleveluse = 0; // Who can use this object?
integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?
integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)

integer isconfigured = FALSE; // Do we have all the configuration data we need?
integer eof = FALSE; // Have we reached the end of the configuration data?

list avatars = []; // A list of avatars with pending requests
list httprequests = []; // A list of http request keys, corresponding to the "avatars" list
integer counter;
string hoverText;
string SOUND="ON";
playSound(string sound){
    if (SOUND=="ON")llTriggerSound(sound, 1.0);
}

/***********************************************************************************************
*  s()  k() i() and v() are used so that sending messages is more readable by humans.  
* Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All these functions do is strip off the text before the ":" char and return a string
***********************************************************************************************/
string s (string ss){
    return llList2String(llParseString2List(ss, [":"], []),1);
}//end function
key k (string kk){
    return llList2Key(llParseString2List(kk, [":"], []),1);
}//end function
integer i (string ii){
    return llList2Integer(llParseString2List(ii, [":"], []),1);
}//end function

vector     RED            = <0.77278,0.04391,0.00000>;//RED
vector     ORANGE = <0.87130,0.41303,0.00000>;//orange
vector     YELLOW         = <0.82192,0.86066,0.00000>;//YELLOW
vector     GREEN         = <0.12616,0.77712,0.00000>;//GREEN
vector     BLUE        = <0.00000,0.05804,0.98688>;//BLUE
vector     PINK         = <0.83635,0.00000,0.88019>;//INDIGO
vector     PURPLE = <0.39257,0.00000,0.71612>;//PURPLE
vector     WHITE        = <1.000,1.000,1.000>;//WHITE
vector     BLACK        = <0.000,0.000,0.000>;//BLACKvector     ORANGE = <0.87130, 0.41303, 0.00000>;//orange

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
    
    return (sloodleserverroot != "" && sloodlepwd != "" && sloodlecontrollerid > 0);
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

// Initiate a password reset
sloodle_password_reset(key av)
{
    // Start the request
    string body = "sloodlecontrollerid=" + (string)sloodlecontrollerid;
    body += "&sloodlepwd=" + sloodlepwd;
    body += "&sloodleuuid=" + (string)av;
    body += "&sloodleavname=" + llEscapeURL(llKey2Name(av));
    body += "&sloodleserveraccesslevel=" + (string)sloodleserveraccesslevel;
    key newhttp = llHTTPRequest(sloodleserverroot + SLOODLE_PWRESET_LINKER, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
    
    // Is the avatar already in the list?
    integer pos = llListFindList(avatars, [av]);
    if (pos >= 0) {
        // Yes - replace the entry
        httprequests = llListReplaceList(httprequests, [newhttp], pos, pos);
    } else {
        // No - add a new entry
        avatars += [av];
        httprequests += [newhttp];
    }
}

// Remove an avatar from the lists
sloodle_remove_password_reset(key av)
{
    // Check for the entry in the list
    integer pos = llListFindList(avatars, [av]);
    if (pos >= 0) {
        avatars = llDeleteSubList(avatars, pos, pos);
        httprequests = llDeleteSubList(httprequests, pos, pos);
    }
}


// Default state - waiting for configuration
default
{
    state_entry()
    {
        llSetTimerEvent(0.25);
        // Starting again with a new configuration
        llSetText("", <0.0,0.0,0.0>, 0.0);
        isconfigured = FALSE;
        eof = FALSE;
        // Reset our data
        sloodleserverroot = "";
        sloodlepwd = "";
        sloodlecontrollerid = 0;
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
            if (eof == TRUE) {
                if (isconfigured == TRUE) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configurationreceived", [], NULL_KEY, "");
                    llSetTimerEvent(0);
                    llSetText("", YELLOW, 1);
                    
                    state ready;
                } else {
                    // Got all configuration but, it's not complete... request reconfiguration
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configdatamissing", [], NULL_KEY, "");
                    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:reconfigure", NULL_KEY);
                    eof = FALSE;
                }
            }
        }
    }
      timer() {
      counter++;
      
      if (counter>20){
          hoverText="|";
          counter=0;
      }
      hoverText+="||||";
      llSetText(hoverText, YELLOW, 1.0);
      
  }
    
}

// Ready to receive requests for password resets
state ready
{
    on_rez( integer param)
    {
        state default;
    }    
    
    state_entry()
    {
        // Display status
        if (sloodleserveraccesslevel == 2) {
            sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT, [<0.0,1.0,0.0>, 0.9], "pwresetready:course", [sloodleserverroot, sloodlecoursename_full], NULL_KEY, "pwreset");
        } else if (sloodleserveraccesslevel == 3) {
            sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT, [<0.0,1.0,0.0>, 0.9], "pwresetready:staff", [sloodleserverroot, sloodlecoursename_full], NULL_KEY, "pwreset");
        } else {
            sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT, [<0.0,1.0,0.0>, 0.9], "pwresetready:site", [sloodleserverroot], NULL_KEY, "pwreset");
        }
    }
    
    state_exit()
    {
        // Clear status tet
        llSetText("", <0.0,0.0,0.0>, 0.0);
    }
        
   
     link_message(integer sender_num, integer chan, string str, key id) {
        if (chan==SLOODLE_CHANNEL_OBJECT_DIALOG){
            if (str=="do:requestconfig") llResetScript();
        }else
        if (chan==UI_CHANNEL){
            list cmdList = llParseString2List(str, ["|"], []);        
            string cmd = s(llList2String(cmdList,0));
            string button= s(llList2String(cmdList,1));
            key userKey = k(llList2String(cmdList,2));
            if (cmd=="BUTTON PRESS"){
                if (button=="Reset"||button=="Cancel") return;
                  if (sloodle_check_access_use(userKey) == FALSE) {
                    llWhisper(0, "Sorry " + llKey2Name(userKey)+ ". You do not have permission to control this object.");
                    return;
                }//check_access
                // Do the password reset
                llSetTimerEvent(0.25);
                sloodle_password_reset(userKey);
            }//button
        }//channel
     }//linked
    http_response(key id, integer status, list meta, string body)
    {
        llSetTimerEvent(0);
                    llSetText("", YELLOW, 1);
        // Ignore any unexpected responses
        integer pos = llListFindList(httprequests, [id]);
        if (pos < 0) return;
        // Extract the data
        key av = llList2Key(avatars, pos);
        string name = llKey2Name(av);
        sloodle_remove_password_reset(av);
        
        // Check the response status
        if (status != 200) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "httperror:code", [status], NULL_KEY, "");
            return;
        }
        
        // Check the response body
        if (body == "") {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "httpempty", [], NULL_KEY, "");
            return;
        }
        
        // Parse the response
        list lines = llParseStringKeepNulls(body, ["\n"], []);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
        integer statuscode = (integer)llList2String(statusfields, 0);
        
        // Check the status code
        if (statuscode == -341) {
            // User cannot have their password reset in-world
            sloodle_translation_request(SLOODLE_TRANSLATE_IM, [], "pwreseterror:hasemail", [name, sloodleserverroot], av, "pwreset");
            playSound("resetpassword");
            sloodle_debug("ERROR reported in response: " + body);
            return;
            
        } else if (statuscode == -331) {
            // User cannot use this device
            sloodle_translation_request(SLOODLE_TRANSLATE_IM, [], "nopermission:use", [name], av, "");
            return;
            
        } else if (statuscode <= 0) {
            // Error occurred
            sloodle_translation_request(SLOODLE_TRANSLATE_IM, [], "pwreseterror:failed:code", [name, statuscode], av, "pwreset");
            sloodle_debug("ERROR reported in response: " + body);
            return;
        }
        
        // Check that there are enough lines
        if (numlines < 2) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "badresponseformat", [], NULL_KEY, "");
            sloodle_debug("ERROR: not enough lines in response: " + body);
            return;
        }
        
        // Attempt to parse the data line
        list datafields = llParseStringKeepNulls(llList2String(lines, 1), ["|"], []);
        if (llGetListLength(datafields) < 2) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "badresponseformat", [], NULL_KEY, "");
            sloodle_debug("ERROR: not enough data fields in response: " + body);
            return;
        }
        
        // Extract the data items
        string username = llList2String(datafields, 0);
        string password = llList2String(datafields, 1);
        
        // Inform the user of their new password
        
        sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG, ["ok"],  "pwreset:success", [name, sloodleserverroot, username, password], av, "pwreset");
    }
      timer() {
      counter++;
      
      if (counter>20){
          hoverText="|";
          counter=0;
      }
      llSetText(hoverText+="||||", YELLOW, 1.0);
      
  }
}
