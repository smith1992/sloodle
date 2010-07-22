// LSL script generated: _SLOODLE_HOUSE.buzzer.scoreboardrow.lslp Thu Jul 22 00:58:49 Pacific Daylight Time 2010

integer UI_CHANNEL = 89997;
list commandList;
string command;
vector color;
integer rowId;
integer myRow;
integer myCell;
integer myType;
integer SET_ROW_COLOR = 8888999;
vector BLACK = <0,0,0>;
vector ORANGE = <0.91574,0.6892199999999999,0.0>;
/***********************************************
*  s()  used so that sending of linked messages is more readable by humans.  Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added instead: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All this function does is strip off the text before the ":" char and return a string
***********************************************/
string s(string ss){
    return llList2String(llParseString2List(ss,[":"],[]),1);
}
/***********************************************
*  i()  used so that sending of linked messages is more readable by humans.  Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added instead: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All this function does is strip off the text before the ":" char and return an integer
***********************************************/

integer i(string ii){
    return llList2Integer(llParseString2List(ii,[":"],[]),1);
}
/***********************************************
*  v()  used so that sending of linked messages is more readable by humans.  Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added instead: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All this function does is strip off the text before the ":" char and return an integer
***********************************************/
vector v(string vv){
    return llList2Vector(llParseString2List(vv,[":"],[]),1);
}

default {

    
    state_entry() {
        list dataList = llParseString2List(llGetObjectName(),[","],[]);
        (myCell = i(llList2String(dataList,0)));
        (myRow = i(llList2String(dataList,1)));
        (myType = i(llList2String(dataList,2)));
        llSetLinkPrimitiveParams(llGetLinkNumber(),[PRIM_FULLBRIGHT,ALL_SIDES,TRUE]);
    }

    touch_start(integer num_detected) {
        llMessageLinked(LINK_SET,UI_CHANNEL,((("COMMAND:DISPLAY MENU|ROW:" + ((string)myRow)) + "|AVUUID:") + ((string)llDetectedKey(0))),NULL_KEY);
        llSay(0,((("COMMAND:DISPLAY MENU|ROW:" + ((string)myRow)) + "|AVUUID:") + ((string)llDetectedKey(0))));
        llMessageLinked(LINK_SET,SET_ROW_COLOR,((("COMMAND:SET COLOR|ROW:" + ((string)myRow)) + "|COLOR:") + ((string)ORANGE)),NULL_KEY);
    }

    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SET_ROW_COLOR)) {
            (commandList = llParseString2List(str,["|"],[]));
            (command = s(llList2String(commandList,0)));
            (rowId = i(llList2String(commandList,1)));
            (color = v(llList2String(commandList,2)));
            if ((command == "SET COLOR")) {
                llSetTimerEvent(2.0);
                if ((rowId == myRow)) {
                    llSetColor(ORANGE,ALL_SIDES);
                }
            }
        }
    }

    timer() {
        llSetColor(BLACK,ALL_SIDES);
        llSetTimerEvent(0.0);
    }
}
