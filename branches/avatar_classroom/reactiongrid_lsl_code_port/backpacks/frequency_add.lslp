     /*
    *  Sloodle Backpack frequency add
    *  Copyright 2010 B3DMULTITECH.COM
    *  Paul Preibisch 
    *  fire@b3dmultitech.com
    *
    *  Released under the GNU GPL 3.0
    *  This script can be used in your scripts, but you must include this copyright header 
    *  as per the GPL Licence
    *  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
    *
    */  
    vector  RED            = <0.77278,0.04391,0.00000>;//RED
vector  ORANGE = <0.87130,0.41303,0.00000>;//orange
vector  YELLOW         = <0.82192,0.86066,0.00000>;//YELLOW
vector  GREEN         = <0.12616,0.77712,0.00000>;//GREEN
vector  BLUE        = <0.00000,0.05804,0.98688>;//BLUE
vector  PINK         = <0.83635,0.00000,0.88019>;//INDIGO
vector  PURPLE = <0.39257,0.00000,0.71612>;//PURPLE
vector  WHITE        = <1.000,1.000,1.000>;//WHITE
vector  BLACK        = <0.000,0.000,0.000>;//BLACK
//llMessageLinked(LINK_THIS, UI_CHANNEL, "FREQUENCY ADD|"+(string)ITEM_FREQUENCY_ADD,NULL_KEY);
//llMessageLinked(LINK_THIS, UI_CHANNEL, "FREQUENCY RESET|"+(string)ITEM_FREQUENCY_RESET,NULL_KEY);
integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
integer NEXT_ADD;
integer TIME_LEFT;
integer SET_TEXT_CHANNEL=-988812;
default {
    state_entry() {
    
    }
    link_message(integer sender_num, integer num, string str, key id) {
        list cmdList = llParseString2List(str, ["|"], []);
        string cmd = llList2String(cmdList,0);
        integer NEXT_ADD = llList2Integer(cmdList,1);
        TIME_LEFT = NEXT_ADD;
        if (cmd=="FREQUENCY ADD") llSetTimerEvent(1);
    }
    timer() {
        llMessageLinked(LINK_THIS, SET_TEXT_CHANNEL, "Time Left until next add: "+(string)TIME_LEFT--, NULL_KEY);
        if (TIME_LEFT<=0){
            llMessageLinked(LINK_SET, UI_CHANNEL, "FREQUENCY ADD TIMER EVENT", NULL_KEY);
            TIME_LEFT=NEXT_ADD;
        }
        
    }
    changed(integer change) {
            if (change== CHANGED_INVENTORY) { // and it was a link change
               
             llResetScript();
            }//endif
        }
}
