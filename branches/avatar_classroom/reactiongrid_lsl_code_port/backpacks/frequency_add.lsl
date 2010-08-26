// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.backpacks.frequency_add.lslp Wed Aug 25 16:31:55 Pacific Daylight Time 2010
//llMessageLinked(LINK_THIS, UI_CHANNEL, "FREQUENCY ADD|"+(string)ITEM_FREQUENCY_ADD,NULL_KEY);
//llMessageLinked(LINK_THIS, UI_CHANNEL, "FREQUENCY RESET|"+(string)ITEM_FREQUENCY_RESET,NULL_KEY);
integer UI_CHANNEL = 89997;
default {

    state_entry() {
    }

    link_message(integer sender_num,integer num,string str,key id) {
        list cmdList = llParseString2List(str,["|"],[]);
        string cmd = llList2String(cmdList,0);
        integer time = llList2Integer(cmdList,1);
        if ((cmd == "FREQUENCY ADD")) llSetTimerEvent(time);
    }

    timer() {
        llMessageLinked(LINK_THIS,UI_CHANNEL,"FREQUENCY ADD TIMER EVENT",NULL_KEY);
    }

    changed(integer change) {
        if ((change == CHANGED_INVENTORY)) {
            llResetScript();
        }
    }
}
