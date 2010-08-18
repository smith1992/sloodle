// LSL script generated: avatar_classroom.secondlife_port.regEnrol.mod_regenrol.lslp Tue Aug 17 22:11:03 Pacific Daylight Time 2010
// Sloodle Registration Booth (for Sloodle 0.3)
// Allows users to touch a panel to do manual registration of their avatar.
//
// Copyright (c) 2007-8 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar
//  Peter R. Bloomfield
//
string SOUND = "ON";
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
string SLOODLE_EOF = "sloodleeof";

integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;

string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodleobjectaccessleveluse = 0;
integer UI_CHANNEL = 89997;
integer isconfigured = FALSE;
integer eof = FALSE;

///// TRANSLATION /////

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
string SLOODLE_TRANSLATE_SAY = "say";
playSound(string sound){
    if ((SOUND == "ON")) llTriggerSound(sound,1.0);
}
debug(string str){
    if ((llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0) == 4)) {
        llOwnerSay((((((llGetScriptName() + " ") + " freemem: ") + ((string)llGetFreeMemory())) + " ==== ") + str));
    }
}

// Send a translation request link message
sloodle_translation_request(string output_method,list output_params,string string_name,list string_params,key keyval,string batch){
    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_TRANSLATION_REQUEST,((((((((output_method + "|") + llList2CSV(output_params)) + "|") + string_name) + "|") + llList2CSV(string_params)) + "|") + batch),keyval);
}
/***********************************************************************************************
*  s()  k() i() and v() are used so that sending messages is more readable by humans.  
* Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All these functions do is strip off the text before the ":" char and return a string
***********************************************************************************************/
string s(string ss){
    return llList2String(llParseString2List(ss,[":"],[]),1);
}
key k(string kk){
    return llList2Key(llParseString2List(kk,[":"],[]),1);
}

// Configure by receiving a linked message from another script in the object
// Returns TRUE if the object has all the data it needs
integer sloodle_handle_command(string str){
    if ((str == "do:requestconfig")) llResetScript();
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
    else  if ((name == "set:sloodleobjectaccessleveluse")) (sloodleobjectaccessleveluse = ((integer)value1));
    else  if ((name == SLOODLE_EOF)) (eof = TRUE);
    return (((sloodleserverroot != "") && (sloodlepwd != "")) && (sloodlecontrollerid > 0));
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

    on_rez(integer param) {
        llResetScript();
    }

    state_entry() {
        llTriggerSound("STARTINGUP",1.0);
        (isconfigured = FALSE);
        (eof = FALSE);
        (sloodleserverroot = "");
        (sloodlepwd = "");
        (sloodlecontrollerid = 0);
        (sloodleobjectaccessleveluse = 0);
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
                    state ready;
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

state ready {

    on_rez(integer param) {
        llResetScript();
    }

    state_entry() {
        llTriggerSound("loadingcomplete",1.0);
    }

         link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            string button = s(llList2String(cmdList,1));
            key userKey = k(llList2String(cmdList,2));
            if ((cmd == "BUTTON PRESS")) {
                if (((button == "Reset") || (button == "Cancel"))) return;
                if (sloodle_check_access_use(userKey)) {
                    if ((button == "panel")) {
                        llMessageLinked(LINK_SET,SLOODLE_CHANNEL_OBJECT_DIALOG,((((("do:regenrol|" + sloodleserverroot) + "|") + ((string)sloodlecontrollerid)) + "|") + sloodlepwd),userKey);
                        playSound("standby");
                    }
                    debug((((((("panel pressed " + "do:regenrol|") + sloodleserverroot) + "|") + ((string)sloodlecontrollerid)) + "|") + sloodlepwd));
                }
                else  {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"nopermission:use",[llKey2Name(id)],NULL_KEY,"");
                }
            }
        }
        else  if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            list lines = llParseString2List(str,["\n"],[]);
            integer numlines = llGetListLength(lines);
            integer ii = 0;
            for ((ii = 0); (ii < numlines); (ii++)) {
                (isconfigured = sloodle_handle_command(llList2String(lines,ii)));
            }
        }
    }
}
