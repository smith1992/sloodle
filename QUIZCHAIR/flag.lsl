// LSL script generated: _SLOODLE_HOUSE.QUIZCHAIR.flag.lslp Thu Jul 22 02:03:58 Pacific Daylight Time 2010

integer GROUP_CHANNEL = -91123421;
vector WHITE = <1.0,1.0,1.0>;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;vector getVector(string vStr){
    (vStr = llGetSubString(vStr,1,(llStringLength(vStr) - 2)));
    list vStrList = llParseString2List(vStr,[","],["<",">"]);
    vector output = <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2)>;
    return output;
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

 sloodle_handle_command(string str){
    if (((str == "do:requestconfig") || (str == "do:reset"))) llResetScript();
}
default {

    state_entry() {
        llSetColor(WHITE,ALL_SIDES);
    }

     link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            sloodle_handle_command(str);
        }
        if ((channel == GROUP_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            if ((cmd == "set color")) {
                llSetColor(getVector(llList2String(cmdList,1)),ALL_SIDES);
            }
        }
    }

changed(integer change) {
        if (((change == CHANGED_LINK) || (change == CHANGED_INVENTORY))) {
            llSetColor(WHITE,ALL_SIDES);
            llResetScript();
        }
    }
}
