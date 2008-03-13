// Sloodle Registration Booth
// When an avatar touches the panel, the booth will make a request to the Sloodle site.
// If the avatar is already registered, nothing more needs done.
// Otherwise the site will create an empty Sloodle registration, and return an authentication URL.
// The user follows that URL and logs-in to authenticate their avatar.
//
// Part of the Sloodle project (www.sloodle.org)
// Copyright (c) 2007 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar - original design and implementation
//  Peter R. Bloomfield - updated for new communications format
//


string sloodleserverroot = "";
string pwd = "";

string sloodleregurlbase = "/mod/sloodle/login/sl_reg_linker.php";
//string sloodlewelcomebase = "/mod/sloodle/login/sl_welcome_reg.php";//<-- no longer needed

integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;

integer object_dialog_channel = -3857343;
integer avatar_dialog_channel = 3857343;

integer sloodle_courseid = 0; //3;

list avstates; // each avatar gets their own state saying where in the validation process they are.
list avuuids;
list avhttpids;


// These are various status codes which might be returned
integer SLOODLE_STATUS_OK = 1;
integer SLOODLE_STATUS_USER_ALREADY_REGISTERED = 301;
integer SLOODLE_ERROR_USER_AUTH_ERROR = -301;
integer SLOODLE_ERROR_USER_AUTH_NEED_MORE_INFO = -311;
integer SLOODLE_ERROR_OBJECT_AUTH = -201;
integer SLOODLE_ERROR_OBJECT_AUTH_NEED_MORE_INFO = -212;
integer SLOODLE_ERROR_OBJECT_AUTH_FAILED = -213;

// Channel used for requesting URL loading
integer SLOODLE_CHANNEL_OBJECT_LOAD_URL = -1639270041;


 
sloodle_debug(string msg)
{
     llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

require_sloodle_registration(integer avindex,key uuid)
{
    string sloodleregurl = sloodleserverroot + sloodleregurlbase + "?sloodlepwd=" + pwd;
    string url = sloodleregurl+"&sloodleavname="+llEscapeURL(llKey2Name(uuid))+"&sloodleuuid="+(string)uuid;
    sloodle_debug("Requesting login URL for "+llDetectedName(0));
    avhttpids = llListReplaceList(avhttpids,[llHTTPRequest(url,[],"")],avindex, avindex);
}

// Handle the response from the registration booth linker script
// It should have a status line, and possible also a line of data
integer handle_authentication_response(string body) 
{
    // Split the response into lines
    list lines = llParseStringKeepNulls(body,["\n"],[]);
    integer numlines = llGetListLength(lines);
    // Get the status line as a list of pipe-delimited items
    if (numlines < 1) {
        sloodle_debug("ERROR: number of lines in response: " + (string)numlines);
        return 0;
    }
    list statusline = llParseStringKeepNulls(llList2String(lines, 0), ["|"], []);
    sloodle_debug("Status Line: " + llList2CSV(statusline));
    // Get the first data line
    string dataline = "";
    if (numlines >= 2) dataline = llList2String(lines, 1);
    
    // Split the status line into our separate parts
    integer statuscode = llList2Integer(statusline,0);
    // We need to know which avatar this related to
    // It should be the 7th item in the status line
    key uuid = llList2Key(statusline, 6);
    
    // Check for common status codes
    if(statuscode == SLOODLE_STATUS_USER_ALREADY_REGISTERED) {
        sloodle_debug("User already fully registered.");
        llDialog(uuid, "Thank you " + llKey2Name(uuid) + ". Your avatar is already fully authenticated with the Moodle site.", ["OK"], SLOODLE_CHANNEL_AVATAR_IGNORE);
        return -1;
    } else if (statuscode == SLOODLE_STATUS_OK) {
        // Success - inform the user of the authentication URL
        sloodle_debug("Showing URL to user " + llKey2Name(uuid) + "(" + (string)uuid + ")");
        //llLoadURL(uuid, "Please use this URL to login into Moodle", dataline);
        llWhisper(0, llKey2Name(uuid) + ", please use this URL to login to Moodle.");
        llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_LOAD_URL, dataline, uuid);
        return 1;
    }
    
     // Something probably went wrong.
     sloodle_debug("An error occurred. Reporting back to the user...");
     llDialog(uuid, "A problem occured.\n\nStatus code " + (string)statuscode + ".\nFurther information: " + dataline, ["OK"], SLOODLE_CHANNEL_AVATAR_IGNORE);
    return 0;
}

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
        } else if (name == "set:sloodle_courseid") {
            sloodle_courseid = (integer)value;
        }
    
    //llWhisper(0,"DEBUG: "+sloodleserverroot+"/"+pwd+"/"+(string)sloodle_courseid);

    if ( (sloodleserverroot != "") && (pwd != "") ) {
        state default;
    }
}

sloodle_init()
{
    //llWhisper(0,"initializing");    
    if ( (sloodleserverroot == "") || (pwd == "") ) {
        state sloodle_wait_for_configuration;
    }
    //llVolumeDetect(TRUE); 
}

default 
{
    on_rez(integer param)
    {
        sloodle_init();
    }
    state_entry()
    {
        sloodle_init();
    }    
    touch_start(integer total_number)
    {
        sloodle_debug("total_number is "+(string)total_number);        
        sloodle_debug("Detected avatar "+llDetectedName(0));
        // see if we're already working with them
        integer avindex = llListFindList(avuuids, [llDetectedKey(0)]);
        if (avindex == -1) {
            sloodle_debug("first time we've seen "+llDetectedName(0));
            avuuids = avuuids + [llDetectedKey(0)];
            avindex = llListFindList(avuuids, [llDetectedKey(0)]);
            avstates = avstates + ["authentication"];
            avhttpids = avhttpids + [NULL_KEY];
        } else {
            sloodle_debug("have previously seen "+llDetectedName(0)+" at index "+(string)avindex);
            llListReplaceList(avstates,["authentication"],avindex,avindex);
            llListReplaceList(avhttpids,[NULL_KEY],avindex,avindex);
        }
        sloodle_debug("checking registration for index "+(string)avindex+" and key "+(string)llDetectedKey(0));
        require_sloodle_registration(avindex,llDetectedKey(0));  
     }

    http_response(key request_id, integer status, list metadata, string body) {
        sloodle_debug("handing request "+body);
        if(status < 400) {
            integer avindex = llListFindList(avhttpids, [request_id]);
            if(avindex != -1) {
                string avstate = llList2String(avstates, avindex);
                sloodle_debug("avstate is "+avstate+" for uuid index "+(string)avindex);
                if (avstate == "authentication") {
                    integer ok = handle_authentication_response(body);
                    // Don't need this stuff... all responses are done in the function above
                    //if (ok == 1) {
                    //    avstates = llListReplaceList(avstates,["registered"],avindex,avindex);
                    //    key uuid = llList2Key(avuuids,avindex);            
                    //    llDialog(uuid,"You have been registered in Sloodle.",["OK"],SLOODLE_CHANNEL_AVATAR_IGNORE);
                    //} else if (ok == -1) {
                    //    key uuid = llList2Key(avuuids,avindex);            
                    //    llDialog(uuid,"You were already registered in Sloodle.",["OK"],SLOODLE_CHANNEL_AVATAR_IGNORE);                        
                    //}
                }          
            } else {
                sloodle_debug("Could not find http id "+(string)request_id);
            }
        } else {
            llWhisper(0, "An error occurred while trying to communicate with the server.");
        }
    }                
}

state sloodle_wait_for_configuration
{
    link_message(integer sender_num, integer num, string str, key id) {
        sloodle_handle_command(str);
    }
}

