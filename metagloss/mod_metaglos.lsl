// LSL script generated: _SLOODLE_HOUSE.metagloss.mod_metaglos.lslp Thu Jul 22 00:58:49 Pacific Daylight Time 2010
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
//  Paul Preibisch (Fire Centaur in SL)
integer SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST = -1828374651;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
string SLOODLE_GLOSSARY_LINKER = "/mod/sloodle/mod/glossary-1.0/linker.php";
string SLOODLE_EOF = "sloodleeof";

integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;

string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodlemoduleid = 0;
integer sloodleobjectaccessleveluse = 0;
integer sloodleserveraccesslevel = 0;
integer sloodlepartialmatches = 1;
integer sloodlesearchaliases = 0;
integer sloodlesearchdefinitions = 0;
integer sloodleidletimeout = 120;
string sloodleglossaryname = "";
vector YELLOW = <0.82192,0.86066,0.0>;

integer isconfigured = FALSE;
integer eof = FALSE;

key httpcheck = NULL_KEY;
key httpsearch = NULL_KEY;
float HTTP_TIMEOUT = 10.0;

string SLOODLE_METAGLOSS_COMMAND = "/def ";
string searchterm = "";
key searcheruuid = NULL_KEY;


///// TRANSLATION /////

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
string SLOODLE_TRANSLATE_SAY = "say";
string SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";
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

sloodle_reset(){
    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"resetting",[],NULL_KEY,"");
    llMessageLinked(LINK_SET,SLOODLE_CHANNEL_OBJECT_DIALOG,"do:reset",NULL_KEY);
    llResetScript();
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
    else  if ((name == "set:sloodlemoduleid")) (sloodlemoduleid = ((integer)value1));
    else  if ((name == "set:sloodleobjectaccessleveluse")) (sloodleobjectaccessleveluse = ((integer)value1));
    else  if ((name == "set:sloodleserveraccesslevel")) (sloodleserveraccesslevel = ((integer)value1));
    else  if ((name == "set:sloodlepartialmatches")) (sloodlepartialmatches = ((integer)value1));
    else  if ((name == "set:sloodlesearchaliases")) (sloodlesearchaliases = ((integer)value1));
    else  if ((name == "set:sloodlesearchdefinitions")) (sloodlesearchdefinitions = ((integer)value1));
    else  if ((name == "set:sloodleidletimeout")) (sloodleidletimeout = ((integer)value1));
    else  if ((name == SLOODLE_EOF)) (eof = TRUE);
    return ((((sloodleserverroot != "") && (sloodlepwd != "")) && (sloodlecontrollerid > 0)) && (sloodlemoduleid > 0));
}

// Checks if the given agent is permitted to user this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_use(key id){
    if ((sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP)) {
        return llSameGroup(id);
    }
    else  if ((sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC)) {
        return TRUE;
    }
    return (id == llGetOwner());
}


///// STATES /////

// Default state - waiting for configuration
default {

    state_entry() {
        llSetText("Starting Up",YELLOW,1.0);
        llTriggerSound("STARTINGUP",1.0);
        (isconfigured = FALSE);
        (eof = FALSE);
        (sloodleserverroot = "");
        (sloodlepwd = "");
        (sloodlecontrollerid = 0);
        (sloodlemoduleid = 0);
        (sloodleobjectaccessleveluse = 0);
        (sloodleserveraccesslevel = 0);
        (sloodlepartialmatches = 1);
        (sloodlesearchaliases = 0);
        (sloodlesearchdefinitions = 0);
        (sloodleidletimeout = 120);
        (sloodleglossaryname = "");
    }

    
    link_message(integer sender_num,integer num,string str,key id) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            list lines = llParseString2List(str,["\n"],[]);
            integer numlines = llGetListLength(lines);
            integer i = 0;
            for (; (i < numlines); (i++)) {
                (isconfigured = sloodle_handle_command(llList2String(lines,i)));
            }
            if ((eof == TRUE)) {
                if ((isconfigured == TRUE)) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"configurationreceived",[],NULL_KEY,"");
                    state check_glossary;
                }
                else  {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"configdatamissing",[],NULL_KEY,"");
                    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_OBJECT_DIALOG,"do:reconfigure",NULL_KEY);
                    (eof = FALSE);
                }
            }
        }
    }
}


// If necessary, check the name of the glossary
state check_glossary {

    on_rez(integer par) {
        state default;
    }

    
    link_message(integer sender_num,integer num,string str,key id) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:reset")) llResetScript();
            if ((str == "do:requestconfig")) llResetScript();
        }
    }


    state_entry() {
        (sloodleglossaryname = "");
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<0.0,1.0,0.0>,0.8],"metagloss:checking",[],NULL_KEY,"metagloss");
        (httpcheck = llHTTPRequest((((((((sloodleserverroot + SLOODLE_GLOSSARY_LINKER) + "?sloodlecontrollerid=") + ((string)sloodlecontrollerid)) + "&sloodlepwd=") + sloodlepwd) + "&sloodlemoduleid=") + ((string)sloodlemoduleid)),[HTTP_METHOD,"GET"],""));
        llSetTimerEvent(0.0);
        llSetTimerEvent(HTTP_TIMEOUT);
    }

    
    state_exit() {
        llSetTimerEvent(0.0);
    }

    
    timer() {
        llSetTimerEvent(0.0);
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httptimeout",[],NULL_KEY,"");
        llSleep(0.1);
        sloodle_reset();
    }

    
    http_response(key id,integer status,list meta,string body) {
        if ((id != httpcheck)) return;
        if ((status != 200)) {
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,status);
            sloodle_reset();
            return;
        }
        list lines = llParseStringKeepNulls(body,["\n"],[]);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
        integer statuscode = ((integer)llList2String(statusfields,0));
        if ((statuscode <= 0)) {
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,statuscode);
            sloodle_reset();
            return;
        }
        if ((numlines < 2)) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"badresponseformat",[],NULL_KEY,"");
            sloodle_reset();
            return;
        }
        (sloodleglossaryname = llList2String(lines,1));
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"metagloss:checkok",[sloodleglossaryname],NULL_KEY,"metagloss");
        state ready;
    }
}


// Ready for definition requests
state ready {

    on_rez(integer param) {
        state default;
    }

    
    link_message(integer sender_num,integer num,string str,key id) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:reset")) llResetScript();
            if ((str == "do:requestconfig")) llResetScript();
        }
    }

    
    state_entry() {
        llTriggerSound("loadingcomplete",1.0);
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<1.0,0.0,0.0>,0.9],"metagloss:ready",[sloodleglossaryname,SLOODLE_METAGLOSS_COMMAND],NULL_KEY,"metagloss");
        llListen(0,"",NULL_KEY,"");
        llSetTimerEvent(0.0);
        if ((sloodleidletimeout > 0)) llSetTimerEvent(((float)sloodleidletimeout));
    }

    
    state_exit() {
        llSetTimerEvent(0.0);
    }

    
    listen(integer channel,string name,key id,string message) {
        if ((channel == 0)) {
            if ((sloodle_check_access_use(id) == FALSE)) return;
            if ((llSubStringIndex(message,SLOODLE_METAGLOSS_COMMAND) != 0)) return;
            (searchterm = llGetSubString(message,llStringLength(SLOODLE_METAGLOSS_COMMAND),(-1)));
            (searcheruuid = id);
            state search;
            return;
        }
    }


    timer() {
        state shutdown;
    }
}

state shutdown {

    on_rez(integer par) {
        state default;
    }

    
    link_message(integer sender_num,integer num,string str,key id) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:reset")) llResetScript();
            if ((str == "do:requestconfig")) llResetScript();
        }
    }

    
    state_entry() {
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<0.5,0.1,0.1>,0.6],"metagloss:idle",[sloodleglossaryname],NULL_KEY,"metagloss");
    }

    
    touch_start(integer num_detected) {
        integer i = 0;
        key id = NULL_KEY;
        for (; (i < num_detected); (i++)) {
            (id = llDetectedKey(i));
            if (sloodle_check_access_use(id)) {
                state ready;
            }
            else  {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"nopermission:use",[],NULL_KEY,"");
            }
        }
    }
}

state search {

    on_rez(integer par) {
        state default;
    }

    
    link_message(integer sender_num,integer num,string str,key id) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:reset")) llResetScript();
            if ((str == "do:requestconfig")) llResetScript();
        }
    }


    state_entry() {
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<1.0,0.5,0.0>,0.9],"metagloss:searching",[sloodleglossaryname],NULL_KEY,"metagloss");
        string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
        (body += ("&sloodlepwd=" + sloodlepwd));
        (body += ("&sloodlemoduleid=" + ((string)sloodlemoduleid)));
        (body += ("&sloodleuuid=" + ((string)searcheruuid)));
        (body += ("&sloodleavname=" + llEscapeURL(llKey2Name(searcheruuid))));
        (body += ("&sloodleserveraccesslevel=" + ((string)sloodleserveraccesslevel)));
        (body += ("&sloodleterm=" + searchterm));
        (body += ("&sloodlepartialmatches=" + ((string)sloodlepartialmatches)));
        (body += ("&sloodlesearchaliases=" + ((string)sloodlesearchaliases)));
        (body += ("&sloodlesearchdefinitions=" + ((string)sloodlesearchdefinitions)));
        if ((searcheruuid != llGetOwnerKey(searcheruuid))) {
            (body += "&sloodleisobject=true");
        }
        (httpsearch = llHTTPRequest((sloodleserverroot + SLOODLE_GLOSSARY_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body));
        llSetTimerEvent(0.0);
        llSetTimerEvent(HTTP_TIMEOUT);
    }

    
    state_exit() {
        llSetTimerEvent(0.0);
    }

    
    timer() {
        llSetTimerEvent(0.0);
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httptimeout",[],NULL_KEY,"");
        state ready;
    }

    
    http_response(key id,integer status,list meta,string body) {
        if ((id != httpsearch)) return;
        if ((status != 200)) {
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,status);
            sloodle_reset();
            return;
        }
        list lines = llParseStringKeepNulls(body,["\n"],[]);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
        integer statuscode = ((integer)llList2String(statusfields,0));
        if ((statuscode <= 0)) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"servererror",[statuscode],NULL_KEY,"");
            sloodle_reset();
            return;
        }
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"metagloss:numdefs",[searchterm,(numlines - 1)],NULL_KEY,"metagloss");
        integer defnum = 1;
        list fields = [];
        for (; (defnum < numlines); (defnum++)) {
            (fields = llParseStringKeepNulls(llList2String(lines,defnum),["|"],[]));
            if ((llGetListLength(fields) >= 2)) {
                llSay(0,((llList2String(fields,0) + " = ") + llList2String(fields,1)));
            }
            else  {
                llSay(0,llList2String(fields,0));
            }
        }
        state ready;
    }
}
