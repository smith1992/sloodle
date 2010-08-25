// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.vendingmachine.sloodle_layout_object.lslp Wed Aug 25 13:52:43 Pacific Daylight Time 2010
// Sloodle object layout script.
// Allows individual objects to store themselves in a Sloodle layout.
//
// Part of the Sloodle project (www.sloodle.org)
// Copyright (c) 2008 Sloodle
// Released under the GNU GPL v3
//
// Contributors:
//  Peter R. Bloomfield
//  Edmund Edgar
//  Paul Preibisch - Fire Centaur in SL
///// DATA /////
integer SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST = -1828374651;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
string SLOODLE_LAYOUT_LINKER = "/mod/sloodle/mod/set-1.0/layout_linker.php";
string SLOODLE_EOF = "sloodleeof";

string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
key sloodlemyrezzer = NULL_KEY;

integer isconfigured = FALSE;
integer eof = FALSE;

key httpstore = NULL_KEY;

integer attemptnum = 0;
integer attemptmax = 2;

float DELAY_MIN = 0.0;
float DELAY_RANGE = 3.5;

key useruuid = NULL_KEY;
string layoutname = "";
vector layoutpos = <0.0,0.0,0.0>;


///// TRANSLATION /////

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
string SLOODLE_TRANSLATE_SAY = "say";

// Send a translation request link message
sloodle_translation_request(string output_method,list output_params,string string_name,list string_params,key keyval,string batch){
    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_TRANSLATION_REQUEST,((((((((output_method + "|") + llList2CSV(output_params)) + "|") + string_name) + "|") + llList2CSV(string_params)) + "|") + batch),keyval);
}

///// ----------- /////


///// FUNCTIONS /////
/******************************************************************************************************************************
* sloodle_error_code - 
* Author: Paul Preibisch
* Description - This function sends a linked message on the SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST channel
* The error_messages script hears this, translates the status code and sends an instant message to the avuuid
* Params: method - SLOODLE_TRANSLATE_SAY, SLOODLE_TRANSLATE_IM etc
* Params:  avuuid - this is the avatar UUID to that an instant message with the translated error code will be sent to
* Params: status code - the status code of the error as on our wiki: http://slisweb.sjsu.edu/sl/index.php/Sloodle_status_codes
*******************************************************************************************************************************/
sloodle_error_code(string method,key avuuid,integer statuscode){
    llMessageLinked(LINK_SET,SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST,((((method + "|") + ((string)avuuid)) + "|") + ((string)statuscode)),NULL_KEY);
}

// Send debug info
sloodle_debug(string msg){
    llMessageLinked(LINK_THIS,DEBUG_CHANNEL,msg,NULL_KEY);
}

// Configure by receiving a linked message from another script in the object
// Returns TRUE if the object has all the data it needs
integer sloodle_handle_command(string str){
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    if ((numbits > 1)) (value1 = llList2String(bits,1));
    if ((numbits > 2)) (value2 = llList2String(bits,2));
    if ((name == "set:sloodleserverroot")) (sloodleserverroot = value1);
    else  if ((name == "set:sloodlepwd")) {
        if ((value2 != "")) (sloodlepwd = ((value1 + "|") + value2));
        else  (sloodlepwd = value1);
    }
    else  if ((name == "set:sloodlecontrollerid")) (sloodlecontrollerid = ((integer)value1));
    else  if ((name == "set:sloodlemyrezzer")) (sloodlemyrezzer = ((key)value1));
    else  if ((name == SLOODLE_EOF)) (eof = TRUE);
    else  if ((name == "do:reset")) llResetScript();
    return (((sloodleserverroot != "") && (sloodlepwd != "")) && (sloodlecontrollerid > 0));
}

// Generate a random delay time
float random_delay(){
    return (DELAY_MIN + llFrand(DELAY_RANGE));
}


///// STATES /////

default {

    state_entry() {
        (isconfigured = FALSE);
        (eof = FALSE);
        (sloodleserverroot = "");
        (sloodlepwd = "");
        (sloodlecontrollerid = 0);
    }

    
    link_message(integer sender_num,integer num,string str,key id) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            list lines = llParseString2List(str,["\n"],[]);
            integer numlines = llGetListLength(lines);
            integer i;
            for ((i = 0); (i < numlines); (i++)) {
                (isconfigured = sloodle_handle_command(llList2String(lines,i)));
            }
            if (((eof == TRUE) && (isconfigured == TRUE))) {
                state ready;
            }
        }
    }
}

state ready {

    state_entry() {
        (useruuid = NULL_KEY);
        (layoutname = "");
        llListen(SLOODLE_CHANNEL_OBJECT_DIALOG,"",sloodlemyrezzer,"");
    }

    
    listen(integer channel,string name,key id,string msg) {
        if ((sloodlemyrezzer == NULL_KEY)) return;
        if ((channel != SLOODLE_CHANNEL_OBJECT_DIALOG)) return;
        if ((llGetOwnerKey(id) != llGetOwner())) return;
        list fields = llParseStringKeepNulls(msg,["|"],[]);
        integer numfields = llGetListLength(fields);
        if ((numfields < 4)) return;
        string cmd = llList2String(fields,0);
        key rezzer = ((key)llList2String(fields,1));
        (useruuid = ((key)llList2String(fields,2)));
        vector rezzerpos = ((vector)llList2String(fields,3));
        (layoutname = llList2String(fields,4));
        if ((cmd != "do:storelayout")) return;
        if (((rezzer != sloodlemyrezzer) || (sloodlemyrezzer == NULL_KEY))) return;
        if ((useruuid == NULL_KEY)) return;
        if ((layoutname == "")) return;
        (layoutpos = (llGetPos() - rezzerpos));
        if ((llVecMag(layoutpos) > 10.0)) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"layout:toofar",[],NULL_KEY,"");
            return;
        }
        (attemptnum = 0);
        state store_layout;
    }

    
    link_message(integer sender_num,integer num,string sval,key kval) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sval == "do:reset")) {
                llResetScript();
                return;
            }
        }
    }
}

// This is a dummy state... it can be used to start the store process again
state store_layout {

    state_entry() {
        state delay;
    }

    
    link_message(integer sender_num,integer num,string sval,key kval) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sval == "do:reset")) {
                llResetScript();
                return;
            }
        }
    }
}

// Wait for a delay before actually sending the request
state delay {

    state_entry() {
        llSetTimerEvent(random_delay());
    }

    
    state_exit() {
        llSetTimerEvent(0.0);
    }

    
    timer() {
        llSetTimerEvent(0.0);
        (attemptnum += 1);
        state request;
    }

    
    link_message(integer sender_num,integer num,string sval,key kval) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sval == "do:reset")) {
                llResetScript();
                return;
            }
        }
    }
}

// Send a request to store the object data
state request {

    state_entry() {
        string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
        (body += ("&sloodlepwd=" + sloodlepwd));
        (body += ("&sloodlelayoutname=" + layoutname));
        (body += ("&sloodleuuid=" + ((string)useruuid)));
        (body += ((((("&sloodlelayoutentries=" + llGetObjectName()) + "|") + ((string)layoutpos)) + "|") + ((string)llGetRot())));
        (body += "&sloodleadd=true");
        (httpstore = llHTTPRequest((sloodleserverroot + SLOODLE_LAYOUT_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body));
    }

    
    state_exit() {
        llSetTimerEvent(0.0);
    }

    
    timer() {
        llSetTimerEvent(0.0);
        (httpstore = NULL_KEY);
        state failed;
    }

    
    http_response(key id,integer status,list meta,string body) {
        if ((id != httpstore)) return;
        llSetTimerEvent(0.0);
        (httpstore = NULL_KEY);
        if ((status != 200)) {
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,status);
            state failed;
            return;
        }
        if ((body == "")) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httpempty",[],NULL_KEY,"");
            state failed;
            return;
        }
        list lines = llParseStringKeepNulls(body,["\n"],[]);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
        integer statuscode = ((integer)llList2String(statusfields,0));
        if ((statuscode <= 0)) {
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,statuscode);
            sloodle_debug(("HTTP response: " + body));
            state failed;
            return;
        }
        state success;
    }

    
    link_message(integer sender_num,integer num,string sval,key kval) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sval == "do:reset")) {
                llResetScript();
                return;
            }
        }
    }
}

// Update failed... try again?
state failed {

    state_entry() {
        if ((attemptnum < attemptmax)) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"layout:failedretrying",[],NULL_KEY,"");
            state store_layout;
        }
        else  {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"layout:failedaborting",[],NULL_KEY,"");
            state ready;
        }
    }

    
    link_message(integer sender_num,integer num,string sval,key kval) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sval == "do:reset")) {
                llResetScript();
                return;
            }
        }
    }
}

// Update successful
state success {

    state_entry() {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"layout:stored",[],NULL_KEY,"");
        state ready;
    }

    
    link_message(integer sender_num,integer num,string sval,key kval) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sval == "do:reset")) {
                llResetScript();
                return;
            }
        }
    }
}
