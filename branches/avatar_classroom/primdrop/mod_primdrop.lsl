// LSL script generated: avatar_classroom.primdrop.mod_primdrop.lslp Wed Aug 11 19:44:11 Pacific Daylight Time 2010
// Sloodle PrimDrop (for Sloodle 0.3)
// Allows students to submit SL objects as Moodle assignments.
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2007-8 Sloodle
// Released under the GNU GPL
//
// Contributors:
//  Jeremy Kemp
//  Peter R. Bloomfield
//  Paul Preibisch (Fire Centaur in SL)


/// DATA ///

// Sloodle constants
integer SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST = -1828374651;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer SLOODLE_CHANNEL_AVATAR_DIALOG = 1001;
string SLOODLE_PRIMDROP_LINKER = "/mod/sloodle/mod/primdrop-1.0/linker.php";
string SLOODLE_ASSIGNMENT_VIEW = "/mod/assignment/view.php";
string SLOODLE_EOF = "sloodleeof";
integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;
integer UI_CHANNEL = 89997;
vector YELLOW = <0.82192,0.86066,0.0>;
string hoverText = "";
integer counter = 0;


integer SLOODLE_CHANNEL_PRIMDROP_INVENTORY = -1639270071;
string PRIMDROP_RECEIVE_DROP = "do:receivedrop";
string PRIMDROP_CANCEL_DROP = "do:canceldrop";
string PRIMDROP_FINISHED_DROP = "set:droppedobject";


// Configuration settings
string sloodleserverroot = "";
string sloodlepwd = "";
integer sloodlecontrollerid = 0;
integer sloodlemoduleid = 0;
integer sloodleobjectaccessleveluse = 0;
integer sloodleobjectaccesslevelctrl = 0;
integer sloodleserveraccesslevel = 0;

// Configuration status
integer isconfigured = FALSE;
integer eof = FALSE;

// This defines who is currently dropping or rezzing an item
key current_user = NULL_KEY;
// The name of the object which is being submitted
string submit_obj = "";

// HTTP request keys
key httpcheck = NULL_KEY;
key httpsubmit = NULL_KEY;

// Assignment information
string assignmentname = "";
string assignmentsummary = "";

// Alternating list of keys, timestamps and page numbers, indicating who activated a dialog and when
list cmddialog = [];

// Menu button labels
string MENU_BUTTON_CANCEL = "0";
string MENU_BUTTON_SUMMARY = "1";
string MENU_BUTTON_SUBMIT = "2";
string MENU_BUTTON_ONLINE = "3";
string MENU_BUTTON_TAKE_ALL = "4";

// List of button labels ('cos otherwise the compiler runs out of memory!)
list teacherbuttons = [MENU_BUTTON_SUBMIT,MENU_BUTTON_ONLINE,MENU_BUTTON_TAKE_ALL,MENU_BUTTON_CANCEL,MENU_BUTTON_SUMMARY];
list userbuttons = [MENU_BUTTON_SUMMARY,MENU_BUTTON_SUBMIT,MENU_BUTTON_ONLINE,MENU_BUTTON_CANCEL];

///// TRANSLATION /////

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;

// Translation output methods
string SLOODLE_TRANSLATE_SAY = "say";
string SLOODLE_TRANSLATE_DIALOG = "dialog";
string SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";
string SLOODLE_TRANSLATE_IM = "instantmessage";

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

/// FUNCTIONS ///
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

sloodle_debug(string msg){
    llMessageLinked(LINK_THIS,DEBUG_CHANNEL,msg,NULL_KEY);
}

sloodle_reset(){
    llSetText("",<0.0,0.0,0.0>,0.0);
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
    else  if ((name == "set:sloodleobjectaccesslevelctrl")) (sloodleobjectaccesslevelctrl = ((integer)value1));
    else  if ((name == "set:sloodleserveraccesslevel")) (sloodleserveraccesslevel = ((integer)value1));
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

// Does the object have valid permissions?
// Returns TRUE if so, or FALSE otherwise
integer valid_perms(string obj){
    integer perms_owner = llGetInventoryPermMask(obj,MASK_OWNER);
    integer perms_next = llGetInventoryPermMask(obj,MASK_NEXT);
    return (!((((perms_owner & PERM_COPY) && (perms_owner & PERM_TRANSFER)) && (perms_next & PERM_COPY)) && (perms_next & PERM_TRANSFER)));
}

// Returns a list of all inventory
list get_inventory(integer type){
    list inv = [];
    integer num = llGetInventoryNumber(type);
    integer i = 0;
    for (; (i < num); (i++)) {
        if ((llGetInventoryName(type,i) != "sloodle_config")) (inv += [llGetInventoryName(type,i)]);
    }
    return inv;
}

// Checks and validates an inventory drop.
// Returns TRUE if it is OK, or FALSE if not.
integer sloodle_check_drop(){
    sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<1.0,0.0,0.0>,0.9],"assignment:checkingitem",[],NULL_KEY,"assignment");
    if (((llGetInventoryType(submit_obj) == INVENTORY_NONE) || (submit_obj == ""))) {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:submissionerror",[],NULL_KEY,"assignment");
        return FALSE;
    }
    if ((llGetInventoryType(submit_obj) == INVENTORY_SCRIPT)) {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:objectsonly",[],NULL_KEY,"assignment");
        llRemoveInventory(submit_obj);
        return FALSE;
    }
    key obj_id = llGetInventoryKey(submit_obj);
    key obj_creator = llGetInventoryCreator(submit_obj);
    if ((obj_creator != current_user)) {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:creatoronly",[],NULL_KEY,"assignment");
        llRemoveInventory(submit_obj);
        return FALSE;
    }
    if (valid_perms(submit_obj)) {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:invalidperms",[],NULL_KEY,"assignment");
        llRemoveInventory(submit_obj);
        return FALSE;
    }
    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:itemok",[submit_obj,llKey2Name(current_user)],NULL_KEY,"assignment");
    return TRUE;
}


/// STATES ///

// Default state - waiting for configuration
default {

    state_entry() {
        llSetTimerEvent(0.25);
        llTriggerSound("STARTINGUP",1.0);
        llSetText("Starting Up",YELLOW,1.0);
        (isconfigured = FALSE);
        (eof = FALSE);
        (sloodleserverroot = "");
        (sloodlepwd = "");
        (sloodlecontrollerid = 0);
        (sloodlemoduleid = 0);
        (sloodleobjectaccessleveluse = 0);
        (sloodleobjectaccesslevelctrl = 0);
        (sloodleserveraccesslevel = 0);
    }

    
    link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            list lines = llParseString2List(str,["\n"],[]);
            integer numlines = llGetListLength(lines);
            integer i = 0;
            for (; (i < numlines); (i++)) {
                (isconfigured = sloodle_handle_command(llList2String(lines,i)));
            }
            if ((eof == TRUE)) {
                if ((isconfigured == TRUE)) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"configurationreceived",[],NULL_KEY,"");
                    state check_assignment;
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

// Checking that the assignment is accessible
state check_assignment {

    state_entry() {
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<0.0,1.0,0.0>,0.9],"assignment:checking",[],NULL_KEY,"assignment");
        string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
        (body += ("&sloodlepwd=" + sloodlepwd));
        (body += ("&sloodlemoduleid=" + ((string)sloodlemoduleid)));
        (httpcheck = llHTTPRequest((sloodleserverroot + SLOODLE_PRIMDROP_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body));
        llSetTimerEvent(0.0);
        llSetTimerEvent(8.0);
    }

    
    state_exit() {
        llSetTimerEvent(0.0);
        (httpcheck = NULL_KEY);
        llSetText("",<0.0,0.0,0.0>,0.0);
    }

     link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
    }

    timer() {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httptimeout",[],NULL_KEY,"");
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"resetting",[],NULL_KEY,"");
        sloodle_reset();
    }

    
    http_response(key id,integer status,list meta,string body) {
        if ((id != httpcheck)) return;
        (httpcheck = NULL_KEY);
        if ((status != 200)) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httperror:code",[status],NULL_KEY,"");
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"resetting",[],NULL_KEY,"");
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,status);
            sloodle_reset();
            return;
        }
        if ((body == "")) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httpempty",[],NULL_KEY,"");
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"resetting",[],NULL_KEY,"");
            sloodle_reset();
            return;
        }
        list lines = llParseStringKeepNulls(body,["\n"],[]);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
        integer statuscode = llList2Integer(statusfields,0);
        if ((statuscode == (-601))) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:connectionfailed",[],NULL_KEY,"assignment");
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"resetting",[],NULL_KEY,"");
            sloodle_reset();
            return;
        }
        else  if ((statuscode <= 0)) {
            if ((numlines > 1)) {
                string errmsg = llList2String(lines,1);
                sloodle_debug(((("ERROR " + ((string)statuscode)) + ": ") + errmsg));
            }
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,statuscode);
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"resetting",[],NULL_KEY,"");
            sloodle_reset();
            return;
        }
        (assignmentname = llList2String(lines,1));
        (assignmentsummary = llList2String(lines,2));
        llSay(0,assignmentsummary);
        state ready;
    }

    
    on_rez(integer param) {
        state default;
    }
}


// Ready to be used
state ready {

    state_entry() {
        llTriggerSound("loadingcomplete",1.0);
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<1.0,0.5,0.0>,1.0],"assignment:ready",[assignmentname],NULL_KEY,"assignment");
        llListen(SLOODLE_CHANNEL_AVATAR_DIALOG,"",NULL_KEY,"");
        llSetTimerEvent(0.0);
        llSetTimerEvent(12.0);
        (current_user = NULL_KEY);
    }

    
    state_exit() {
        llSetTimerEvent(0.0);
        llSetText("",<0.0,0.0,0.0>,0.0);
    }

    
   
    link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
        if ((chan == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            string button = s(llList2String(cmdList,1));
            integer level;
            if ((cmd == "BUTTON PRESS")) {
                if (((button == "Reset") || (button == "Cancel"))) return;
                key userKey = k(llList2String(cmdList,2));
                if (sloodle_check_access_ctrl(userKey)) (level = 2);
                else  if (sloodle_check_access_use(userKey)) (level = 1);
                if ((level == 2)) {
                    sloodle_add_cmd_dialog(userKey);
                    sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG,([SLOODLE_CHANNEL_AVATAR_DIALOG] + teacherbuttons),"assignment:primdropteachermenu",[0,1,2,3,4],userKey,"assignment");
                }
                else  if ((level == 1)) {
                    sloodle_add_cmd_dialog(userKey);
                    sloodle_translation_request(SLOODLE_TRANSLATE_DIALOG,([SLOODLE_CHANNEL_AVATAR_DIALOG] + userbuttons),"assignment:primdropmenu",[0,1,2,3],userKey,"assignment");
                }
                else  {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"nopermission:use",[userKey],NULL_KEY,"");
                }
            }
        }
    }

    listen(integer channel,string name,key id,string msg) {
        if ((channel == SLOODLE_CHANNEL_AVATAR_DIALOG)) {
            if ((llGetOwnerKey(id) != id)) return;
            if ((llListFindList(cmddialog,[id]) < 0)) return;
            sloodle_remove_cmd_dialog(id);
            if ((!sloodle_check_access_use(id))) {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"nopermission:use",[name],NULL_KEY,"");
                return;
            }
            integer canctrl = sloodle_check_access_ctrl(id);
            (current_user = id);
            if ((msg == MENU_BUTTON_CANCEL)) {
                return;
            }
            else  if ((msg == MENU_BUTTON_SUMMARY)) {
                llSay(0,assignmentsummary);
            }
            else  if ((msg == MENU_BUTTON_SUBMIT)) {
                state drop;
            }
            else  if ((msg == MENU_BUTTON_ONLINE)) {
                llLoadURL(id,assignmentname,(((sloodleserverroot + SLOODLE_ASSIGNMENT_VIEW) + "?id=") + ((string)sloodlemoduleid)));
            }
            else  if (((msg == MENU_BUTTON_TAKE_ALL) && canctrl)) {
                list inv;
                (inv += get_inventory(INVENTORY_ANIMATION));
                (inv += get_inventory(INVENTORY_GESTURE));
                (inv += get_inventory(INVENTORY_LANDMARK));
                (inv += get_inventory(INVENTORY_NOTECARD));
                (inv += get_inventory(INVENTORY_OBJECT));
                if ((inv == [])) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_IM,[],"assignment:nosubmissions",[],id,"assignment");
                }
                else  {
                    llGiveInventoryList(id,assignmentname,inv);
                    sloodle_translation_request(SLOODLE_TRANSLATE_IM,[],"assignment:allgiven",[assignmentname],id,"assignment");
                }
            }
        }
    }

    
    on_rez(integer param) {
        state default;
    }
}

// Waiting for an object to be dropped
state drop {

    state_entry() {
        llMessageLinked(LINK_SET,SLOODLE_CHANNEL_PRIMDROP_INVENTORY,PRIMDROP_RECEIVE_DROP,NULL_KEY);
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<0.0,0.5,1.0>,1.0],"assignment:waitingforsubmission",[llKey2Name(current_user)],NULL_KEY,"assignment");
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:dropsubmission",[llKey2Name(current_user)],NULL_KEY,"assignment");
        llSetTimerEvent(0.0);
        llSetTimerEvent(60.0);
    }

    
    state_exit() {
        llSetTimerEvent(0.0);
    }

    
    timer() {
        llSetTimerEvent(0.0);
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:submittimeout",[llKey2Name(current_user)],NULL_KEY,"assignment");
        llMessageLinked(LINK_SET,SLOODLE_CHANNEL_PRIMDROP_INVENTORY,PRIMDROP_CANCEL_DROP,NULL_KEY);
        state ready;
    }

    
    link_message(integer sender_num,integer chan,string str,key kval) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
        if ((chan == SLOODLE_CHANNEL_PRIMDROP_INVENTORY)) {
            list parts = llParseStringKeepNulls(str,["|"],[]);
            if ((llGetListLength(parts) < 2)) return;
            string cmd = llList2String(parts,0);
            string val = llList2String(parts,1);
            if ((cmd == PRIMDROP_FINISHED_DROP)) {
                llSetTimerEvent(0.0);
                (submit_obj = val);
                if (sloodle_check_drop()) state submitting;
                else  state ready;
            }
        }
    }

    
    on_rez(integer param) {
        state default;
    }
}

// Submitting an object which was dropped
state submitting {

    state_entry() {
        if ((submit_obj == "")) {
            llSay(0,"ERROR: no object to submit.");
            state ready;
            return;
        }
        sloodle_translation_request(SLOODLE_TRANSLATE_HOVER_TEXT,[<1.0,0.0,0.0>,0.9],"assignment:submitting",[llKey2Name(current_user)],NULL_KEY,"assignment");
        string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
        (body += ("&sloodlepwd=" + sloodlepwd));
        (body += ("&sloodlemoduleid=" + ((string)sloodlemoduleid)));
        (body += ("&sloodleuuid=" + ((string)current_user)));
        (body += ("&sloodleavname=" + llEscapeURL(llKey2Name(current_user))));
        (body += ("&sloodleserveraccesslevel=" + ((string)sloodleserveraccesslevel)));
        (body += ("&sloodleobjname=" + submit_obj));
        (body += ("&sloodleprimcount=" + ((string)llGetObjectPrimCount(llGetInventoryKey(submit_obj)))));
        (body += ("&sloodleprimdropname=" + llGetObjectName()));
        (body += ("&sloodleprimdropuuid=" + ((string)llGetKey())));
        (body += ("&sloodleregion=" + llGetRegionName()));
        (body += ("&sloodlepos=" + ((string)llGetPos())));
        (httpsubmit = llHTTPRequest((sloodleserverroot + SLOODLE_PRIMDROP_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body));
        llSetTimerEvent(0.0);
        llSetTimerEvent(8.0);
    }

     link_message(integer sender_num,integer chan,string str,key kval) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
    }

    state_exit() {
        llSetTimerEvent(0.0);
        (httpsubmit = NULL_KEY);
        llSetText("",<0.0,0.0,0.0>,0.0);
    }

    
    timer() {
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httptimeout",[],NULL_KEY,"");
        state ready;
    }

    
    http_response(key id,integer status,list meta,string body) {
        if ((id != httpsubmit)) return;
        (httpsubmit = NULL_KEY);
        if ((status != 200)) {
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,status);
            state ready;
            return;
        }
        if ((body == "")) {
            sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"httpempty",[],NULL_KEY,"");
            state ready;
            return;
        }
        list lines = llParseStringKeepNulls(body,["\n"],[]);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
        integer statuscode = llList2Integer(statusfields,0);
        string current_user_name = llKey2Name(current_user);
        if ((statuscode < 0)) {
            if ((statuscode == (-10201))) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:nopermission",[current_user_name],NULL_KEY,"assignment");
            else  if ((statuscode == (-10202))) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:early",[current_user_name],NULL_KEY,"assignment");
            else  if ((statuscode == (-10203))) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:late",[current_user_name],NULL_KEY,"assignment");
            else  if ((statuscode == (-10205))) sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:noresubmit",[current_user_name],NULL_KEY,"assignment");
            else  {
                sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:submissionfailed",[current_user_name,statuscode],NULL_KEY,"assignment");
                sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,statuscode);
            }
            if ((numlines > 1)) {
                string errmsg = llList2String(lines,1);
                sloodle_debug(((("ERROR " + ((string)statuscode)) + ": ") + errmsg));
            }
            state ready;
            return;
        }
        sloodle_translation_request(SLOODLE_TRANSLATE_SAY,[0],"assignment:submissionok",[current_user_name],NULL_KEY,"assignment");
        state ready;
    }

    
    on_rez(integer param) {
        state default;
    }
}
