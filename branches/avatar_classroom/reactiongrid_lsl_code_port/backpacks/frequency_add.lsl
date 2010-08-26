// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.backpacks.frequency_add.lslp Thu Aug 26 00:03:49 Pacific Daylight Time 2010

//llMessageLinked(LINK_THIS, UI_CHANNEL, "FREQUENCY ADD|"+(string)ITEM_FREQUENCY_ADD,NULL_KEY);
//llMessageLinked(LINK_THIS, UI_CHANNEL, "FREQUENCY RESET|"+(string)ITEM_FREQUENCY_RESET,NULL_KEY);
integer UI_CHANNEL = 89997;
integer NEXT_ADD;
integer TIME_LEFT;
integer SET_TEXT_CHANNEL = -988812;
default {

    state_entry() {
    }

    link_message(integer sender_num,integer num,string str,key id) {
        list cmdList = llParseString2List(str,["|"],[]);
        string cmd = llList2String(cmdList,0);
        integer NEXT_ADD = llList2Integer(cmdList,1);
        (TIME_LEFT = NEXT_ADD);
        if ((cmd == "FREQUENCY ADD")) llSetTimerEvent(1);
    }

    timer() {
        llMessageLinked(LINK_THIS,SET_TEXT_CHANNEL,("Time Left until next add: " + ((string)(TIME_LEFT--))),NULL_KEY);
        if ((TIME_LEFT <= 0)) {
            llMessageLinked(LINK_SET,UI_CHANNEL,"FREQUENCY ADD TIMER EVENT",NULL_KEY);
            (TIME_LEFT = NEXT_ADD);
        }
    }

    changed(integer change) {
        if ((change == CHANGED_INVENTORY)) {
            llResetScript();
        }
    }
}
