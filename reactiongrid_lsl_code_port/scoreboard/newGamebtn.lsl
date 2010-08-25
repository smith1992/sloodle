// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.scoreboard.newGamebtn.lslp Wed Aug 25 13:52:43 Pacific Daylight Time 2010

//gets a vector from a string
vector RED = <0.77278,4.391e-2,0.0>;
vector YELLOW = <0.82192,0.86066,0.0>;
vector WHITE = <1.0,1.0,1.0>;
integer myQuizId;
string myQuizName;
                integer PLUGIN_RESPONSE_CHANNEL = 998822;
integer counter = 0;
integer TIME_LIMIT = 5;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
integer UI_CHANNEL = 89997;
string SLOODLE_EOF = "sloodleeof";
integer sloodleid;
integer currentAwardId;
string currentAwardName;
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
integer i(string ii){
    return llList2Integer(llParseString2List(ii,[":"],[]),1);
}
integer sloodle_handle_command(string str){
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    if ((numbits > 1)) (value1 = llList2String(bits,1));
    if ((numbits > 2)) (value2 = llList2String(bits,2));
    if ((name == "facilitator")) (facilitators += llStringTrim(llToLower(value1),STRING_TRIM));
    else  if ((name == "set:sloodleid")) {
        (sloodleid = ((integer)value1));
        (currentAwardId = sloodleid);
        (currentAwardName = value2);
    }
    else  if ((name == SLOODLE_EOF)) return TRUE;
    return FALSE;
}
default {

     on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
        llSetText("",RED,1.0);
        llSetTexture("btn_newgame",4);
        llSetObjectName("btn:New Game");
        (facilitators += llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM));
    }

    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            sloodle_handle_command(str);
        }
        else  if ((channel == PLUGIN_RESPONSE_CHANNEL)) {
            list dataLines = llParseString2List(str,["\n"],[]);
            string responseLine = s(llList2String(dataLines,1));
            list statusLine = llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
            integer status = llList2Integer(statusLine,0);
            if ((responseLine == "quiz|newQuizGame")) {
                (myQuizName = s(llList2String(dataLines,3)));
                (myQuizId = i(llList2String(dataLines,4)));
            }
        }
        if ((channel == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            if ((cmd == "GAMEID")) {
                (myQuizName = s(llList2String(cmdList,2)));
                (myQuizId = i(llList2String(cmdList,3)));
            }
            else  if ((cmd == "GOT QUIZ ID")) {
                (myQuizId = i(llList2String(cmdList,1)));
                (myQuizName = s(llList2String(cmdList,2)));
            }
            else  if (((cmd == "BUTTON PRESS") && (s(llList2String(cmdList,1)) == "New Game"))) {
                if ((isFacilitator(llKey2Name(k(llList2String(cmdList,2)))) == FALSE)) {
                    llSay(0,((("Sorry, " + llKey2Name(k(llList2String(cmdList,2)))) + " but you are not a facilitator, facilitators are: ") + llList2CSV(facilitators)));
                    return;
                }
                llTriggerSound("click",1.0);
                llSetTexture("btn_cancel",4);
                llSetTimerEvent(30);
                llSetObjectName("btn:Cancel");
                llSetTimerEvent(1);
            }
            else  if (((cmd == "BUTTON PRESS") && (s(llList2String(cmdList,1)) == "Cancel"))) {
                if ((isFacilitator(llKey2Name(k(llList2String(cmdList,2)))) == FALSE)) {
                    llSay(0,((("Sorry, " + llKey2Name(k(llList2String(cmdList,2)))) + " but you are not a facilitator ") + str));
                    return;
                }
                llSetColor(WHITE,ALL_SIDES);
                llTriggerSound("snd_canceled",1.0);
                llSetTexture("btn_newgame",4);
                llSetText("",RED,1.0);
                llSetTimerEvent(0);
                llSetObjectName("btn:New Game");
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
            llTriggerSound("confirmed",1.0);
            llSetTexture("btn_newgame",4);
            llSetObjectName("btn:New Game");
            llSetText("",RED,1.0);
            llSetColor(WHITE,ALL_SIDES);
            (counter = 0);
            string authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(llKey2Name(llGetOwner())));
            llMessageLinked(LINK_SET,UI_CHANNEL,"CMD:NEWGAME",NULL_KEY);
        }
        else  if (((TIME_LIMIT - counter) < 5)) llTriggerSound("beepbeep",1.0);
        else  llTriggerSound("TICK",1.0);
    }

  changed(integer change) {
        if ((change == CHANGED_INVENTORY)) {
            llSetTexture("btn_newgame",4);
            llSetObjectName("btn:New Game");
            llResetScript();
        }
    }
}
