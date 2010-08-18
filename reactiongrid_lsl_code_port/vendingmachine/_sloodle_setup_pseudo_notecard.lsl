// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.vendingmachine._sloodle_setup_pseudo_notecard.lslp Tue Aug 17 23:59:29 Pacific Daylight Time 2010
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
string API_URL = "http://api.avatarclassroom.com/api/api.php";
string AVATAR_CLASSROOM_PASSWORD = "128sdfKiweriojs012";
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
string SLOODLE_CONFIG_NOTECARD = "sloodle_config";
string SLOODLE_EOF = "sloodleeof";

string COMMENT_PREFIX = "//";

key latestnotecard = NULL_KEY;



key http_id;
debug(string str){
    if ((llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0) == 4)) {
        llOwnerSay(str);
    }
}
///// ----------- /////


///// FUNCTIONS /////


sloodle_tell_other_scripts(string msg){
    sloodle_debug(("pseudo notecard sending message to other scripts: " + msg));
    llMessageLinked(LINK_SET,SLOODLE_CHANNEL_OBJECT_DIALOG,msg,NULL_KEY);
}

sloodle_debug(string msg){
}

sloodle_handle_command(string str){
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value = "";
    if ((numbits >= 2)) llList2String(bits,1);
    if ((name == "do:reset")) {
        sloodle_debug("Resetting configuration notecard reader");
        llResetScript();
    }
    else  if ((name == "do:requestconfig")) {
        llResetScript();
    }
}


default {

    on_rez(integer start_param) {
        llResetScript();
    }

    
    state_entry() {
        llSleep(0.2);
        if ((llGetInventoryType(SLOODLE_CONFIG_NOTECARD) == INVENTORY_NOTECARD)) {
        }
        else  {
            sloodle_debug((("No notecard called " + SLOODLE_CONFIG_NOTECARD) + " found - trying instantclassroom API"));
            (latestnotecard = NULL_KEY);
            llSetTimerEvent(60.0);
            string body = ((((((((("owneruuid=" + ((string)llGetOwner())) + "&ownername=") + llKey2Name(llGetOwner())) + "&object_name=") + llGetObjectName()) + "&type=") + "pseudo_notecard") + "&password=") + AVATAR_CLASSROOM_PASSWORD);
            (http_id = llHTTPRequest(API_URL,[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body));
            debug(((API_URL + "?") + body));
        }
    }

    
    http_response(key request_id,integer ss,list metadata,string body) {
        list lines = llParseStringKeepNulls(body,["\n"],[]);
        integer status = ((integer)llList2String(lines,0));
        debug(body);
        if ((status > 0)) {
            integer i;
            for ((i = 1); (i < llGetListLength(lines)); (i++)) {
                string data = llList2String(lines,i);
                string trimmeddata = llStringTrim(data,STRING_TRIM_HEAD);
                if ((llSubStringIndex(trimmeddata,COMMENT_PREFIX) != 0)) {
                    sloodle_tell_other_scripts(trimmeddata);
                    llSleep(0.4);
                }
            }
        }
        llSleep(0.2);
        sloodle_tell_other_scripts(SLOODLE_EOF);
    }

    
    link_message(integer sender_num,integer num,string str,key id) {
        if ((num == DEBUG_CHANNEL)) return;
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            sloodle_handle_command(str);
        }
    }

    
    changed(integer change) {
        if ((change & CHANGED_INVENTORY)) {
            llResetScript();
        }
    }
}
