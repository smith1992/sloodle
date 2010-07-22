// LSL script generated: _SLOODLE_HOUSE.utility_buttons.auto_enrol.lslp Thu Jul 22 02:03:23 Pacific Daylight Time 2010
//reset
//gets a vector from a string
vector RED = <0.77278,4.391e-2,0.0>;
vector YELLOW = <0.82192,0.86066,0.0>;
integer counter = 0;
integer PLUGIN_CHANNEL = 998821;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
string SLOODLE_EOF = "sloodleeof";
string sloodleserverroot;
integer sloodlecontrollerid;
integer PLUGIN_RESPONSE_CHANNEL = 998822;
string sloodlecoursename_short;
string sloodlecoursename_full;
integer sloodleid;
string scoreboardname;
integer currentAwardId;
string currentAwardName;
list facilitators;
string hoverText;
/***********************************************
*  isFacilitator()
*  |-->is this person's name in the access notecard
***********************************************/
integer isFacilitator(string avName){
    if ((llListFindList(facilitators,[llStringTrim(llToLower(avName),STRING_TRIM)]) == (-1))) return FALSE;
    else  return TRUE;
}

/***********************************************************************************************
*  s()  k() i() and v() are used so that sending messages is more readable by humans.  
* Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All these functions do is strip off the text before the ":" char and return a string
***********************************************************************************************/
string s(list ss,integer indx){
    return llList2String(llParseString2List(llList2String(ss,indx),[":"],[]),1);
}
integer sloodle_handle_command(string str){
    if ((str == SLOODLE_EOF)) return TRUE;
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    if ((numbits > 1)) (value1 = llList2String(bits,1));
    if ((numbits > 2)) (value2 = llList2String(bits,2));
    if ((name == "facilitator")) (facilitators += llStringTrim(llToLower(value1),STRING_TRIM));
    else  if ((name == "set:sloodleserverroot")) (sloodleserverroot = value1);
    else  if ((name == "set:sloodlecontrollerid")) (sloodlecontrollerid = ((integer)value1));
    else  if ((name == "set:sloodlecoursename_short")) (sloodlecoursename_short = value1);
    else  if ((name == "set:sloodlecoursename_full")) (sloodlecoursename_full = value1);
    else  if ((name == "set:sloodleid")) {
        (sloodleid = ((integer)value1));
        (currentAwardName = value2);
        (currentAwardId = sloodleid);
    }
    else  if ((name == "set:sloodleid")) (scoreboardname = value2);
    return FALSE;
}
default {

    on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
        llSetText("Loading",YELLOW,1.0);
        llSetTimerEvent(0.2);
        
    }

    link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((sloodle_handle_command(str) == TRUE)) state ready;
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

    on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
        llSetTimerEvent(0);
        (hoverText = "");
        llSetText("",YELLOW,1.0);
        llSetText("",RED,1.0);
        llSetTexture("_blank",4);
        llSetObjectDesc("btn:check_enrol");
        (facilitators += llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM));
        string authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner())));
        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,("course->checkAutoEnrolSettings" + authenticatedUser),NULL_KEY);
    }

    touch_start(integer num_detected) {
        if ((isFacilitator(llDetectedName(0)) == FALSE)) {
            llSay(0,((("Sorry, " + llDetectedName(0)) + " but you are not a facilitator, facilitators are: ") + llList2CSV(facilitators)));
            return;
        }
        llTriggerSound("click",1.0);
        string desc = llGetObjectDesc();
        if ((desc == "btn:btn_autoenrol_on")) {
            string authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner())));
            llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(("course->changeSettings" + authenticatedUser) + "&var=autoenrol&setting=on"),NULL_KEY);
            llSetTimerEvent(0.25);
            
        }
        else  if ((desc == "btn:btn_autoenrol_off")) {
            llSetTimerEvent(0.25);
            
            string authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner())));
            llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(("course->changeSettings" + authenticatedUser) + "&var=autoenrol&setting=off"),NULL_KEY);
        }
    }

    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
        else  if ((channel == PLUGIN_RESPONSE_CHANNEL)) {
            llSetTimerEvent(0);
            (hoverText = "");
            llSetText("",YELLOW,1.0);
            list dataLines = llParseStringKeepNulls(str,["\n"],[]);
            list statusLine = llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
            integer status = llList2Integer(statusLine,0);
            string descripter = llList2String(statusLine,1);
            list sideEffects = llParseString2List(llList2String(statusLine,2),[","],[]);
            string response = llList2String(statusLine,3);
            integer timeSent = llList2Integer(statusLine,4);
            integer timeRecvt = llList2Integer(statusLine,5);
            key uuidSent = llList2Key(statusLine,6);
            list cmdList = llParseString2List(str,["|"],[]);
            if ((response == "course->checkAutoEnrolSettings")) {
                integer autoEnrol;
                integer autoReg;
                if ((status == (-515))) {
                    llSetTexture("error",4);
                    llLoadURL(llGetOwner(),"Auto Enrol is not enabled for this Site. Please change.",(sloodleserverroot + "/admin/settings.php?section=modsettingsloodle"));
                    llSetObjectDesc("btn:btn_autoenrol_on");
                }
                else  if ((status == (-516))) {
                    llSetTexture("error",4);
                    llLoadURL(llGetOwner(),"Auto Registration is not enabled for this Site. Please change.",(sloodleserverroot + "/admin/settings.php?section=modsettingsloodle"));
                    llSetObjectDesc("btn:btn_autoenrol_on");
                }
                if ((s(dataLines,2) == "FALSE")) {
                    (autoEnrol = FALSE);
                    llSetTexture("btn_autoenrol_off",4);
                    llSetObjectDesc("btn:btn_autoenrol_on");
                }
                else  if ((s(dataLines,2) == "TRUE")) {
                    (autoEnrol = TRUE);
                    llSetTexture("btn_autoenrol_on",4);
                    llSetObjectDesc("btn:btn_autoenrol_off");
                }
            }
            else  if ((response == "course->changeSettings")) {
                integer autoEnrol;
                integer autoReg;
                if ((s(dataLines,2) == "FALSE")) {
                    (autoEnrol = FALSE);
                    llSetTexture("btn_autoenrol_off",4);
                    llSetObjectDesc("btn:btn_autoenrol_on");
                }
                else  if ((s(dataLines,2) == "TRUE")) {
                    (autoEnrol = TRUE);
                    llSetTexture("btn_autoenrol_on",4);
                    llSetObjectDesc("btn:btn_autoenrol_off");
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

  changed(integer change) {
        if ((change == CHANGED_INVENTORY)) {
            llSetTexture("_blank",4);
            llSetObjectDesc("btn:check_enrol");
            llResetScript();
        }
    }
}
