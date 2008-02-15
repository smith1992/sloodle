// Sloodle course enrolment booth
// Allows SL users to enrol in Moodle courses
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-7 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar - original design and implementation
//  Peter R. Bloomfield - updated to use new communications format (Sloodle 0.2)
//

// When someone enters (or for now, touches), check if they are enrolled in the course. If not, provide them an enrolment link.
// Only handles one user at a time for now...


string sloodleserverroot = ""; // "http://moodle.edochan.com";
string pwd = ""; //""; 

string sloodleenrolbase = "/mod/sloodle/login/sl_enrol_linker.php";

integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;

integer object_dialog_channel = -3857343;
integer avatar_dialog_channel = 3857343;

integer sloodle_courseid = 0; //3;

key httpid = NULL_KEY;
key avuuid = NULL_KEY;
 
sloodle_debug(string msg) 
{
     llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

confirm_course_membership()
{
    string url = sloodleserverroot + sloodleenrolbase + "?sloodledebug=false&sloodlepwd=" + pwd + "&sloodlemode=enrol&sloodlecourseid=" + (string)sloodle_courseid;
    url = url+"&sloodleavname="+llEscapeURL(llKey2Name(avuuid))+"&sloodleuuid="+(string)avuuid;
    httpid = llHTTPRequest(url,[],"");
    sloodle_debug("Requesting course membership URL for "+llKey2Name(avuuid));    
}

integer handle_course_membership_confirmation_response(string body) 
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
    // Process any side effects
    list sideeffects = [];
    if (llGetListLength(statusfields) >= 3) {
        sideeffects = llParseString2List(llList2String(statusfields,2), [","], []);
    }
    
    // Check the status code
    if (statuscode == 401) {
        // User is already enrolled - nothing to do
        llDialog(uuid, "You are already enrolled in this course.", [], SLOODLE_CHANNEL_AVATAR_IGNORE);
        return 1;
            
    } else if (statuscode > 0) {
        // Looks like we've been successful
        string msg = "";
        // Check if auto-registration occurred
        if (llListFindList(sideeffects, ["322"]) >= 0) {
            msg += "A new Moodle account was automatically created for you. ";
        }
        msg += "Please follow this link to enrol in this course.";
        llLoadURL(uuid, msg, dataline);
        return 1;
    } else if (statuscode == -321) {
        // User is not registered
        llDialog(uuid, "Your avatar is not yet registered with the Moodle site. Please use a Registration Booth first.", [], SLOODLE_CHANNEL_AVATAR_IGNORE);
        return 0;
    }
        
    
    // Something went wrong
    llWhisper(0, "(" + (string)statuscode + ") General error. " + dataline);
    llDialog(uuid, "Sorry " + llKey2Name(uuid) + ". You are not allowed to enrol in this course.", [], SLOODLE_CHANNEL_AVATAR_IGNORE);
    
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

    if ( (sloodleserverroot != "") && (pwd != "") && (sloodle_courseid != 0) ) {
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

        avuuid = llDetectedKey(0);
                
        confirm_course_membership();
          
     }

    http_response(key request_id, integer status, list metadata, string body) {
        sloodle_debug("HTTP response: "+body);
        if(status < 400) {
            if (httpid == request_id) {
                integer responseresult = handle_course_membership_confirmation_response(body);
                avuuid = NULL_KEY;       
            } else {
                sloodle_debug("Ignoring out-of-sequence http id "+(string)request_id);
            }
        }
    } 
}

state sloodle_wait_for_configuration
{
    link_message(integer sender_num, integer num, string str, key id) {
        
        // Ignore debug messages
        if (num == DEBUG_CHANNEL) return;
        
        sloodle_handle_command(str);
    }
}
