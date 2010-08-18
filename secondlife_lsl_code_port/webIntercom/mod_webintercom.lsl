// LSL script generated: avatar_classroom.secondlife_port.webIntercom.mod_webintercom.lslp Tue Aug 17 22:11:25 Pacific Daylight Time 2010
// Sloodle WebIntercom
// Links in-world SL (text) chat with a Moodle chatroom
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-10 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Paul Andrews
//  Daniel Livingstone
//  Jeremy Kemp
//  Edmund Edgar
//  Peter R. Bloomfield
//  Paul Preibisch
//
integer SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST = -1828374651;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG;
string SLOODLE_CHAT_LINKER = "/mod/sloodle/mod/chat-1.0/linker.php";
string SLOODLE_EOF = "sloodleeof";
integer UI_CHANNEL = 89997;
integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;
string hoverText = "";
integer counter = 0;
vector YELLOW = <0.82192,0.86066,0.0>;

string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodlemoduleid = 0;
integer sloodlelistentoobjects = 0;
integer sloodleobjectaccessleveluse = 0;
integer sloodleobjectaccesslevelctrl = 0;
integer sloodleserveraccesslevel = 0;
integer sloodleautodeactivate = 1;

string SoundFile = "";
string MOODLE_NAME = "(SL)";
string MOODLE_NAME_OBJECT = "(SL-object)";

integer isconfigured = FALSE;
integer eof = FALSE;

integer listenctrl = 0;
list cmddialog = [];

list recordingkeys = [];
list recordingnames = [];

key httpchat = NULL_KEY;
integer message_id = 0;

float sensorrange = 30.0;
float sensorrate = 60.0;
integer nosensorcount = 0;
integer nosensormax = 2;


///// TRANSLATION /////

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
string SLOODLE_TRANSLATE_SAY = "say";
string SLOODLE_TRANSLATE_DIALOG = "dialog";
string SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";
debug(string str){
    if ((llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0) == 4)) {
        llOwnerSay(str);
    }
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
// Send a translation request link message
sloodle_translation_request(string output_method,list output_params,string string_name,list string_params,key keyval,string batch){
    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_TRANSLATION_REQUEST,((((((((output_method + "|") + llList2CSV(output_params)) + "|") + string_name) + "|") + llList2CSV(string_params)) + "|") + batch),keyval);
}

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer(integer min,integer max){
    return (min + ((integer)llFrand(((max - min) + 1))));
}
///// ----------- /////


///// FUNCTIONS /////

sloodle_error_code(string method,key avuuid,integer statuscode){
    llMessageLinked(LINK_SET,SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST,((((method + "|") + ((string)avuuid)) + "|") + ((string)statuscode)),NULL_KEY);
}

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
    else  if ((name == "set:sloodlemoduleid")) (sloodlemoduleid = ((integer)value1));
    else  if ((name == "set:sloodlelistentoobjects")) (sloodlelistentoobjects = ((integer)value1));
    else  if ((name == "set:sloodleobjectaccessleveluse")) (sloodleobjectaccessleveluse = ((integer)value1));
    else  if ((name == "set:sloodleobjectaccesslevelctrl")) (sloodleobjectaccesslevelctrl = ((integer)value1));
    else  if ((name == "set:sloodleserveraccesslevel")) (sloodleserveraccesslevel = ((integer)value1));
    else  if ((name == "set:sloodleautodeactivate")) (sloodleautodeactivate = ((integer)value1));
    else  if ((name == SLOODLE_EOF)) (eof = TRUE);
    return ((((sloodleserverroot != "") && (sloodlepwd != "")) && (sloodlecontrollerid > 0)) && (sloodlemoduleid > 0));
}

// Checks if the given agent is permitted to control this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_ctrl(key id){
    if ((sloodleobjectaccesslevelctrl == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP)) {
        return llSameGroup(id);
    }
    else  if ((sloodleobjectaccesslevelctrl == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC)) {
        return TRUE;
    }
    return (id == llGetOwner());
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

// Add the given agent to our command dialog list
sloodle_add_cmd_dialog(key id){
    integer pos = llListFindList(cmddialog,[id]);
    if ((pos < 0)) {
        (cmddialog += [id,llGetUnixTime()]);
    }
    else  {
        (cmddialog = llListReplaceList(cmddialog,[llGetUnixTime()],(pos + 1),(pos + 1)));
    }
}

// Remove the given agent from our command dialog list
sloodle_remove_cmd_dialog(key id){
    integer pos = llListFindList(cmddialog,[id]);
    if ((pos >= 0)) {
        (cmddialog = llDeleteSubList(cmddialog,pos,(pos + 1)));
    }
}

// Purge the command dialog list of old activity
sloodle_purge_cmd_dialog(){
    integer curtime = llGetUnixTime();
    integer i = 0;
    while ((i < llGetListLength(cmddialog))) {
        if (((curtime - llList2Integer(cmddialog,(i + 1))) > 12)) {
            (cmddialog = llDeleteSubList(cmddialog,i,(i + 1)));
        }
        else  {
            (i += 2);
        }
    }
}

// Start recording the specified agent
sloodle_start_recording_agent(key id){
    if ((llListFindList(recordingkeys,[id]) >= 0)) {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"webintercom:alreadyrecording",[llKey2Name(id)],NULL_KEY,"webintercom");
        return;
    }
    (recordingkeys += [id]);
    (recordingnames += [llKey2Name(id)]);
    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"webintercom:startedrecording",[llKey2Name(id)],NULL_KEY,"webintercom");
    sloodle_update_hover_text();
}

// Stop recording the specified agent
sloodle_stop_recording_agent(key id){
    integer pos = llListFindList(recordingkeys,[id]);
    if ((pos < 0)) {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"webintercom:notrecording",[llKey2Name(id)],NULL_KEY,"webintercom");
        return;
    }
    (recordingkeys = llDeleteSubList(recordingkeys,pos,pos));
    (recordingnames = llDeleteSubList(recordingnames,pos,pos));
    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"webintercom:stoppedrecording",[llKey2Name(id)],NULL_KEY,"webintercom");
    sloodle_update_hover_text();
}

// Is the specified agent currently being recorded?
// Returns TRUE if so, or FALSE otherwise
integer sloodle_is_recording_agent(key id){
    return (llListFindList(recordingkeys,[id]) >= 0);
}

// Update the hover text while logging
sloodle_update_hover_text(){
    string recordlist = llDumpList2String(recordingnames,"\n");
    if ((recordlist == "")) (recordlist = "-");
    sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<1.0,0.2,0.2>,1.0],"webintercom:recording",[recordlist],NULL_KEY,"webintercom");
}


// Default state - waiting for configuration
default {

    state_entry() {
        llSetTimerEvent(0.25);
        llTriggerSound("STARTINGUP",1.0);
        (SLOODLE_CHANNEL_AVATAR_DIALOG = random_integer((-40000),(-50000)));
        llSetTexture("sloodle_chat_off",ALL_SIDES);
        llSetText("",<0.0,0.0,0.0>,0.0);
        (isconfigured = FALSE);
        (eof = FALSE);
        (sloodleserverroot = "");
        (sloodlepwd = "");
        (sloodlecontrollerid = 0);
        (sloodlemoduleid = 0);
        (sloodlelistentoobjects = 0);
        (sloodleobjectaccessleveluse = 0);
        (sloodleobjectaccesslevelctrl = 0);
        (sloodleserveraccesslevel = 0);
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
                    llTriggerSound("loadingcomplete",1.0);
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

    timer() {
        (counter++);
        if ((counter > 20)) {
            (hoverText = "|");
            (counter = 0);
        }
        llSetText((hoverText += "||||"),YELLOW,1.0);
    }
}

state ready {

    on_rez(integer param) {
        state default;
    }

    
    state_entry() {
        (hoverText = "|");
        (counter = 0);
        llSetTimerEvent(0);
        llSetTexture("sloodle_chat_off",ALL_SIDES);
        (recordingkeys = []);
        (recordingnames = []);
        (cmddialog = []);
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<1.0,1.0,1.0>,1.0],"off",[],NULL_KEY,"");
        (SoundFile = llGetInventoryName(INVENTORY_SOUND,0));
    }

    
    state_exit() {
        llSetTimerEvent(0.0);
    }

        
    
    link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
        else  if ((chan == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            string button = s(llList2String(cmdList,1));
            key userKey = k(llList2String(cmdList,2));
            if ((cmd == "BUTTON PRESS")) {
                if (((button == "Reset") || (button == "Cancel"))) return;
                if ((sloodle_check_access_ctrl(userKey) == FALSE)) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"nopermission:ctrl",[llKey2Name(userKey)],NULL_KEY,"");
                    return;
                }
                else  debug(("str:" + str));
                llListenRemove(listenctrl);
                (listenctrl = llListen(SLOODLE_CHANNEL_AVATAR_DIALOG,"",userKey,""));
                sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG,[SLOODLE_CHANNEL_AVATAR_DIALOG,"0","1"],"webintercom:ctrlmenu",["0","1"],userKey,"webintercom");
                llSetTimerEvent(10.0);
            }
        }
    }

    listen(integer channel,string name,key id,string message) {
        if ((channel == SLOODLE_CHANNEL_AVATAR_DIALOG)) {
            if ((sloodle_check_access_ctrl(id) == FALSE)) return;
            if ((message == "1")) {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"webintercom:chatloggingon",[name],NULL_KEY,"webintercom");
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"webintercom:joinchat",[((sloodleserverroot + "/mod/chat/view.php?id=") + ((string)sloodlemoduleid))],NULL_KEY,"webintercom");
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"webintercom:touchtorecord",[],NULL_KEY,"webintercom");
                (recordingkeys = [id]);
                (recordingnames = [name]);
                state logging;
                return;
            }
        }
    }


    timer() {
        llSetTimerEvent(0.0);
        llListenRemove(listenctrl);
    }
}

state logging {

    on_rez(integer param) {
        state default;
    }

    
    state_entry() {
        llSetTexture("sloodle_chat_on",ALL_SIDES);
        llListen(0,"",NULL_KEY,"");
        llListen(SLOODLE_CHANNEL_AVATAR_DIALOG,"",NULL_KEY,"");
        sloodle_update_hover_text();
        llSetTimerEvent(12.0);
        if ((sloodleautodeactivate != 0)) {
            llSensorRepeat("",NULL_KEY,AGENT,sensorrange,PI,sensorrate);
        }
        (nosensorcount = 0);
        string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
        (body += ("&sloodlepwd=" + sloodlepwd));
        (body += ("&sloodlemoduleid=" + ((string)sloodlemoduleid)));
        (body += ("&sloodleuuid=" + ((string)llGetKey())));
        (body += ("&sloodleavname=" + llEscapeURL(llGetObjectName())));
        (body += ("&sloodleserveraccesslevel=" + ((string)sloodleserveraccesslevel)));
        (body += ("&firstmessageid=" + ((string)(message_id + 1))));
        (body += (((("&sloodleisobject=true&message=" + MOODLE_NAME_OBJECT) + " ") + llList2String(recordingnames,0)) + " has activated this WebIntercom"));
        (httpchat = llHTTPRequest((sloodleserverroot + SLOODLE_CHAT_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body));
    }

    
    state_exit() {
        string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
        (body += ("&sloodlepwd=" + sloodlepwd));
        (body += ("&sloodlemoduleid=" + ((string)sloodlemoduleid)));
        (body += ("&sloodleuuid=" + ((string)llGetKey())));
        (body += ("&sloodleavname=" + llEscapeURL(llGetObjectName())));
        (body += ("&sloodleserveraccesslevel=" + ((string)sloodleserveraccesslevel)));
        (body += ("&firstmessageid=" + ((string)(message_id + 1))));
        (body += (("&sloodleisobject=true&message=" + MOODLE_NAME_OBJECT) + " WebIntercom deactivated"));
        (httpchat = llHTTPRequest((sloodleserverroot + SLOODLE_CHAT_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body));
    }

    
    
     link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
        else  if ((chan == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            string button = s(llList2String(cmdList,1));
            if ((cmd == "BUTTON PRESS")) {
                if (((button == "Reset") || (button == "Cancel"))) return;
                key userKey = k(llList2String(cmdList,2));
                integer canctrl = sloodle_check_access_ctrl(userKey);
                integer canuse = sloodle_check_access_use(userKey);
                if (canctrl) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG,[SLOODLE_CHANNEL_AVATAR_DIALOG,"0","1","2","3"],"webintercom:usectrlmenu",["0","1","2","3"],userKey,"webintercom");
                    sloodle_add_cmd_dialog(userKey);
                }
                else  if (canuse) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG,[SLOODLE_CHANNEL_AVATAR_DIALOG,"0","1","2"],"webintercom:usemenu",["0","1","2"],userKey,"webintercom");
                    sloodle_add_cmd_dialog(userKey);
                }
                else  {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"nopermission:use",[llKey2Name(userKey)],NULL_KEY,"webintercom");
                }
            }
        }
    }

    listen(integer channel,string name,key id,string message) {
        if ((channel == SLOODLE_CHANNEL_AVATAR_DIALOG)) {
            if ((llListFindList(cmddialog,[id]) < 0)) return;
            sloodle_remove_cmd_dialog(id);
            integer canctrl = sloodle_check_access_ctrl(id);
            integer canuse = sloodle_check_access_use(id);
            if ((message == "0")) {
                if ((!(canctrl || canuse))) return;
                sloodle_stop_recording_agent(id);
            }
            else  if ((message == "1")) {
                if ((!(canctrl || canuse))) return;
                sloodle_start_recording_agent(id);
            }
            else  if ((message == "2")) {
                if ((!(canctrl || canuse))) return;
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"webintercom:anouncechatroom",[((sloodleserverroot + "/mod/chat/view.php?id=") + ((string)sloodlemoduleid))],NULL_KEY,"webintercom");
            }
            else  if ((message == "3")) {
                if ((!canctrl)) return;
                state ready;
                return;
            }
        }
        else  if ((channel == 0)) {
            integer isavatar = FALSE;
            if ((llGetOwnerKey(id) == id)) {
                if ((!sloodle_is_recording_agent(id))) return;
                (isavatar = TRUE);
            }
            else  {
                if ((sloodlelistentoobjects == 0)) return;
            }
            if ((message == "/slurl")) {
                string region = llEscapeURL(llGetRegionName());
                vector vec = llGetPos();
                string posX = ((string)((integer)vec.x));
                string posY = ((string)((integer)vec.y));
                string posZ = ((string)((integer)vec.z));
                (message = ((((((((("http://slurl.com/secondlife/" + region) + "/") + posX) + "/") + posY) + "/") + posZ) + "/?title=") + region));
            }
            string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
            (body += ("&sloodlepwd=" + sloodlepwd));
            (body += ("&sloodlemoduleid=" + ((string)sloodlemoduleid)));
            (body += ("&sloodleuuid=" + ((string)id)));
            (body += ("&sloodleavname=" + llEscapeURL(name)));
            (body += ("&sloodleserveraccesslevel=" + ((string)sloodleserveraccesslevel)));
            (body += ("&firstmessageid=" + ((string)(message_id + 1))));
            if (isavatar) (body += (("&message=" + MOODLE_NAME) + " "));
            else  (body += (("&sloodleisobject=true&message=" + MOODLE_NAME_OBJECT) + " "));
            (body += ((name + ": ") + message));
            (httpchat = llHTTPRequest((sloodleserverroot + SLOODLE_CHAT_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body));
        }
    }

    
    timer() {
        if ((httpchat == NULL_KEY)) {
            string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
            (body += ("&sloodlepwd=" + sloodlepwd));
            (body += ("&sloodlemoduleid=" + ((string)sloodlemoduleid)));
            (body += ("&firstmessageid=" + ((string)(message_id + 1))));
            (httpchat = llHTTPRequest((sloodleserverroot + SLOODLE_CHAT_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body));
        }
        sloodle_purge_cmd_dialog();
    }

    
    http_response(key id,integer status,list meta,string body) {
        if ((id != httpchat)) return;
        (httpchat = NULL_KEY);
        if ((status != 200)) {
            sloodle_debug(("Failed HTTP response. Status: " + ((string)status)));
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,status);
            return;
        }
        if ((llStringLength(body) == 0)) return;
        sloodle_debug(("Receiving chat data:\n" + body));
        list lines = llParseStringKeepNulls(body,["\n"],[]);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
        integer statuscode = llList2Integer(statusfields,0);
        if ((statuscode <= 0)) {
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,statuscode);
            string msg = ("ERROR: linker script responded with status code " + ((string)statuscode));
            if ((numlines > 1)) {
                (msg += ("\n" + llList2String(lines,1)));
            }
            sloodle_debug(msg);
            return;
        }
        integer msgnum = 0;
        string name = "";
        string text = "";
        integer i = 1;
        for ((i = 1); (i < numlines); (i++)) {
            list fields = llParseStringKeepNulls(llList2String(lines,i),["|"],[]);
            if ((llGetListLength(fields) >= 3)) {
                (msgnum = llList2Integer(fields,0));
                (name = llList2String(fields,1));
                (text = llList2String(fields,2));
                if ((msgnum > message_id)) {
                    (message_id = msgnum);
                    if (((llSubStringIndex(text,MOODLE_NAME) != 0) && (llSubStringIndex(text,MOODLE_NAME_OBJECT) != 0))) {
                        if ((llSubStringIndex(text,"beep ") == 0)) {
                            llStopSound();
                            if ((SoundFile == "")) {
                                llPlaySound("34b0b9d8-306a-4930-b4cd-0299959bb9f4",1.0);
                            }
                            else  {
                                llPlaySound(SoundFile,1.0);
                            }
                        }
                        llSay(0,((name + ": ") + text));
                    }
                }
            }
        }
    }

    
    sensor(integer num_detected) {
        (nosensorcount = 0);
    }

    
    no_sensor() {
        if ((sloodleautodeactivate == 0)) return;
        if ((llGetAttached() > 0)) {
            (nosensorcount = 0);
        }
        else  {
            (nosensorcount++);
            if ((nosensorcount >= nosensormax)) {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"webintercom:autodeactivate",[],NULL_KEY,"webintercom");
                state ready;
                return;
            }
        }
    }
}
