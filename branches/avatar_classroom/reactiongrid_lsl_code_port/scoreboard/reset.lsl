// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.scoreboard.reset.lslp Wed Aug 18 19:07:06 Pacific Daylight Time 2010
//reset
//gets a vector from a string
vector RED = <0.77278,4.391e-2,0.0>;
vector YELLOW = <0.82192,0.86066,0.0>;
vector WHITE = <1.0,1.0,1.0>;
key sitter;
integer counter = 0;
integer TIME_LIMIT = 10;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer UI_CHANNEL = 89997;
list facilitators;
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
string s(string ss){
    return llList2String(llParseString2List(ss,[":"],[]),1);
}
key k(string kk){
    return llList2Key(llParseString2List(kk,[":"],[]),1);
}
default {

	on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
        llSetText("",RED,1.0);
        llSetTexture("btn_reset",4);
        llSetObjectName("btn:Reset");
        (facilitators += llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM));
    }

    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) llResetScript();
        }
        if ((channel == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            if ((cmd == "sitter")) (sitter = id);
            if (((cmd == "BUTTON PRESS") && (s(llList2String(cmdList,1)) == "Reset"))) {
                key userKey = k(llList2String(cmdList,2));
                if (((isFacilitator(llKey2Name(userKey)) == FALSE) && (userKey != sitter))) {
                    llInstantMessage(userKey,((("Sorry, " + llKey2Name(userKey)) + " but you are not a facilitator, facilitators are: ") + llList2CSV(facilitators)));
                    return;
                }
                llTriggerSound("click",1.0);
                llSetTexture("btn_cancel",4);
                llSetTimerEvent(30);
                llSetObjectName("btn:Cancel");
                llSetTimerEvent(1);
            }
            else  if (((cmd == "BUTTON PRESS") && (s(llList2String(cmdList,1)) == "Cancel"))) {
                key userKey = k(llList2String(cmdList,2));
                if ((isFacilitator(llKey2Name(userKey)) == FALSE)) {
                    llInstantMessage(userKey,((("Sorry, " + llKey2Name(userKey)) + " but you are not a facilitator, facilitators are: ") + llList2CSV(facilitators)));
                    return;
                }
                llSetColor(WHITE,ALL_SIDES);
                llTriggerSound("snd_canceled",1.0);
                llSetTexture("btn_reset",4);
                llSetText("",RED,1.0);
                llSetTimerEvent(0);
                llSetObjectName("btn:Reset");
                (counter = 0);
            }
        }
    }

  timer() {
        (counter++);
        vector color;
        if ((llGetColor(4) == YELLOW)) (color = RED);
        else  (color = YELLOW);
        llSetText((("(" + ((string)(TIME_LIMIT - counter))) + ")"),color,1.0);
        llSetColor(color,ALL_SIDES);
        if ((counter >= TIME_LIMIT)) {
            llSetTimerEvent(0.0);
            llSetTexture("btn_reset",4);
            llSetObjectName("btn:Reset");
            llSetText("",RED,1.0);
            llSetColor(WHITE,ALL_SIDES);
            llMessageLinked(LINK_SET,SLOODLE_CHANNEL_OBJECT_DIALOG,"do:requestconfig",NULL_KEY);
            (counter = 0);
        }
        else  if (((TIME_LIMIT - counter) < 5)) llTriggerSound("beepbeep",1.0);
        else  llTriggerSound("TICK",1.0);
    }

  changed(integer change) {
        if ((change == CHANGED_INVENTORY)) {
            llSetTexture("btn_reset",4);
            llSetObjectName("btn:Reset");
            llResetScript();
        }
    }
}
