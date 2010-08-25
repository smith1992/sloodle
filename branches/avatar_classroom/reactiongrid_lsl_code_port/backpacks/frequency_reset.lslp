//llMessageLinked(LINK_THIS, UI_CHANNEL, "FREQUENCY ADD|"+(string)ITEM_FREQUENCY_ADD,NULL_KEY);
//llMessageLinked(LINK_THIS, UI_CHANNEL, "FREQUENCY RESET|"+(string)ITEM_FREQUENCY_RESET,NULL_KEY);
integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
default {
    state_entry() {
     
    }
    link_message(integer sender_num, integer num, string str, key id) {
    	list cmdList = llParseString2List(str, ["|"], []);
    	string cmd = llList2String(cmdList,0);
    	integer time = llList2Integer(cmdList,1);
    	if (cmd=="FREQUENCY RESET") llSetTimerEvent(time);
    }
    timer() {
    	llMessageLinked(LINK_THIS, UI_CHANNEL, "FREQUENCY RESET TIMER EVENT", NULL_KEY);
    }
    changed(integer change) {
            if (change== CHANGED_INVENTORY) { // and it was a link change
               
             llResetScript();
            }//endif
        }
}
