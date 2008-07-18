// Sloodle Classroom Access Controller
// Ensures that avatars are registered with the Moodle site, and shows them a list of courses they can take
// Part of the Sloodle project (www.sloodle.org)
//
// Copryight (c) 2006-7 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar - original design and implementation
//  Peter R. Bloomfield - updated to use new communications format (Sloodle 0.2)


// When someone enters (or for now, touches), authenticate them and show a list of course they're allowed to take.
// Only handles one user at a time for now...


string sloodleserverroot = ""; // "http://moodle.edochan.com";
string pwd = ""; //""; 

string sloodleregurlbase = "/mod/sloodle/login/sl_reg_linker.php";
string sloodleenrolbase = "/mod/sloodle/login/sl_enrol_linker.php";

integer object_dialog_channel = -3857343;
integer avatar_dialog_channel = 3857343;
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;
integer SLOODLE_CHANNEL_OBJECT_ACCESS_CHECKER_PERMIT = -1639270032;
// Channel used for requesting URL loading
integer SLOODLE_CHANNEL_OBJECT_LOAD_URL = -1639270041;

integer sloodle_courseid = 0; //3;

list avstates; // each avatar gets their own state saying where in the validation process they are.
list avuuids;
list avhttpids;

// These values indicate responses from handler functions
integer RESPONSE_FAIL = 0; // e.g. failed to authenticate user
integer RESPONSE_SUCCESS = 1; // e.g. successfully authenticated user
integer RESPONSE_ALREADY_DONE = 2; // e.g. user already authenticated


sloodle_debug(string msg)
{
     llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

require_sloodle_registration(integer avindex,key uuid)
{
    string sloodleregurl = sloodleserverroot + sloodleregurlbase + "?sloodlepwd=" + pwd;
    string url = sloodleregurl+"&sloodleavname="+llEscapeURL(llKey2Name(uuid))+"&sloodleuuid="+(string)uuid;
    sloodle_debug("Requesting login URL for "+llKey2Name(uuid));
    avhttpids = llListReplaceList(avhttpids,[llHTTPRequest(url,[],"")],avindex, avindex);
}

confirm_course_membership(integer avindex)
{
    key uuid = llList2Key(avuuids,avindex);
    string url = sloodleserverroot + sloodleenrolbase + "?sloodlepwd=" + pwd + "&sloodlemode=enrol&sloodlecourseid=" + (string)sloodle_courseid;
    url = url+"&sloodleavname="+llEscapeURL(llKey2Name(uuid))+"&sloodleuuid="+(string)uuid;

    avhttpids = llListReplaceList(avhttpids,[NULL_KEY],avindex, avindex);

    sloodle_debug("Requesting course membership URL for "+llKey2Name(uuid));
    avhttpids = llListReplaceList(avhttpids,[llHTTPRequest(url,[],"")],avindex, avindex);    
}

integer handle_authentication_response(string body) 
{
    // Split the response into separate lines, then extract the data fields
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    integer numlines = llGetListLength(lines);
    string dataline = "";
    if (numlines > 1) dataline = llList2String(lines, 1);
    key uuid = NULL_KEY;
    if (llGetListLength(statusfields) < 7) {
        sloodle_debug("WARNING: insufficient fields in status line to extract user key.");
    } else {
        uuid = llList2Key(statusfields, 6);
    }
    
    // Add the course id to the registration URL, to enable auto-forwarding to course enrolment
    dataline += "&sloodlecourseid=" + (string)sloodle_courseid;
    
    // Check the status code
    if (statuscode == 301) {
        // User is already fully registered - nothing to do
        //llDialog(uuid, "Your avatar is already registered with the Moodle site.", [], SLOODLE_CHANNEL_AVATAR_IGNORE);
        return RESPONSE_ALREADY_DONE;
        
    } else if (statuscode > 0) {
        // Looks like we've been successful
        //llLoadURL(uuid, "Follow this link to finish registering your avatar.", dataline);
        llWhisper(0, llKey2Name(uuid) + ", please use this URL to finished registering your avatar.");
        llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_LOAD_URL, dataline, uuid);
        return RESPONSE_SUCCESS;
    }
    
    // Something went wrong
    llWhisper(0, "(" + (string)statuscode + ") General error. " + dataline);
    llDialog(uuid, "Sorry " + llKey2Name(uuid) + ". An error occurred while trying to register your avatar.", [], SLOODLE_CHANNEL_AVATAR_IGNORE);
    return RESPONSE_FAIL;
}

integer handle_course_membership_confirmation_response(integer avindex, string body) 
{
    // Split the response into separate lines, then extract the data fields
    list lines = llParseStringKeepNulls(body, ["\n"], []);
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    integer numlines = llGetListLength(lines);
    string dataline = "";
    if (numlines > 1) dataline = llList2String(lines, 1);
    key uuid = NULL_KEY;
    if (llGetListLength(statusfields) < 7) {
        sloodle_debug("WARNING: insufficient fields in status line to extract user key.");
    } else {
        uuid = llList2Key(statusfields, 6);
    }
    
    // Check the status code
    if (statuscode == 401) {
        // User is already enrolled - nothing to do
        llDialog(uuid, "Your registration and enrollment have been confirmed. Please touch again to enter.", [], SLOODLE_CHANNEL_AVATAR_IGNORE);
        avstates = llListReplaceList(avstates,["enrolled"],avindex,avindex);
        return RESPONSE_ALREADY_DONE;
        
    } else if (statuscode > 0) {
        // Looks like we've been successful
        key uuid = llList2Key(statusfields, 6);
        //llLoadURL(uuid, "Please follow this link to enrol in this course.", dataline);
        llWhisper(0, llKey2Name(uuid) + ", please use this URL to enrol in this Moodle course.");
        llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_LOAD_URL, dataline, uuid);
        return RESPONSE_SUCCESS;
    }
    
    // Something went wrong
    llWhisper(0, "(" + (string)statuscode + ") General error. " + dataline);
    llDialog(uuid, "Sorry " + llKey2Name(uuid) + ". You are not allowed to enrol in this course.", [], SLOODLE_CHANNEL_AVATAR_IGNORE);
    
    return RESPONSE_FAIL;
}

integer send_permit_message(key avuuid)
{
    llSay(0, "Permitted...");
    llMessageLinked(LINK_SET,SLOODLE_CHANNEL_OBJECT_ACCESS_CHECKER_PERMIT,"do:permitentry|"+(string)avuuid,NULL_KEY);
    return 1;
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

    if ( (sloodleserverroot != "") && (pwd != "") && (sloodle_courseid != 0) ) {
        state default;
    }
}



default 
{
    on_rez(integer param)
    {
        if ( (sloodleserverroot == "") || (pwd == "") || (sloodle_courseid == 0) ) {
        state sloodle_wait_for_configuration;
        }
    }
    state_entry()
    {
        if ( (sloodleserverroot == "") || (pwd == "") || (sloodle_courseid == 0) ) {
        state sloodle_wait_for_configuration;
        }
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
            sloodle_debug("checking registration for index "+(string)avindex+" and key "+(string)llDetectedKey(0));
            require_sloodle_registration(avindex,llDetectedKey(0));  
        } else {
            sloodle_debug("have previously seen "+llDetectedName(0)+" at index "+(string)avindex);
            // Is the user enrolled?
            if (llList2String(avstates, avindex) == "enrolled") {
                sloodle_debug("Permitting entry...");
                send_permit_message(llList2Key(avuuids, avindex));
            } else {
                avstates = llListReplaceList(avstates,["authentication"],avindex,avindex);
                avhttpids = llListReplaceList(avhttpids,[NULL_KEY],avindex,avindex);
                sloodle_debug("checking registration for index "+(string)avindex+" and key "+(string)llDetectedKey(0));
                require_sloodle_registration(avindex,llDetectedKey(0));  
            }
        }
        
     }

    http_response(key request_id, integer status, list metadata, string body) {
        sloodle_debug("handing request "+body);
        if(status < 400) {
            integer avindex = llListFindList(avhttpids, [request_id]);
            if(avindex != -1) {
                string avstate = llList2String(avstates, avindex);
                sloodle_debug("avstate is "+avstate+" for uuid index "+(string)avindex);
                if (avstate == "authentication") {
                    integer resp = handle_authentication_response(body);
                    // Only need to enrol if user was already authenticated
                    if (resp == RESPONSE_ALREADY_DONE) {
                        avstates = llListReplaceList(avstates,["course_confirmation"],avindex,avindex);
                        confirm_course_membership(avindex);
                    }
                } else if (avstate == "course_confirmation") {
                    integer allowed = handle_course_membership_confirmation_response(avindex, body);
                }         
            } else {
                sloodle_debug("Could not find http id "+(string)request_id);
            }
        }
    }                
}

state sloodle_wait_for_configuration
{
    link_message(integer sender_num, integer num, string str, key id) {
        
        // Ignore debug messages
        if(num == DEBUG_CHANNEL) return;
        
        sloodle_handle_command(str);
    }
}
