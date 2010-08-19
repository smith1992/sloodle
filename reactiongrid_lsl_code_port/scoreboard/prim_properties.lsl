// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.scoreboard.prim_properties.lslp Wed Aug 18 19:07:06 Pacific Daylight Time 2010

integer PRIM_PROPERTIES_CHANNEL = -870870;
vector WHITE = <1.0,1.0,1.0>;
integer myRow;
string myName;/*********************************************
*  Copyrght (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
* 
*
*  This script is part of the SLOODLE Project see http://sloodle.org
*  
*  This Script listens on PRIM_PROPERTIES_CHANNEL and turns highlight (alpha) of this prim on or off
*
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
*
*sloodle_prim_properties
*  
* 
*/ 
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
vector getVector(string vStr){
    (vStr = llGetSubString(vStr,1,(llStringLength(vStr) - 2)));
    list vStrList = llParseString2List(vStr,[","],["<",">"]);
    vector output = <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2)>;
    return output;
}

default {

    state_entry() {
        llSetColor(WHITE,ALL_SIDES);
        llSetAlpha(1,ALL_SIDES);
        llSetTexture("totallyclear",ALL_SIDES);
        (myName = llGetLinkName(llGetLinkNumber()));
        list data = llParseString2List(myName,[","],[]);
        (myRow = i(llList2String(data,0)));
    }

    
    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel != PRIM_PROPERTIES_CHANNEL)) return;
        list data = llParseString2List(str,["|"],[]);
        string cmd = s(llList2String(data,0));
        integer row = i(llList2String(data,1));
        string power = s(llList2String(data,2));
        vector color = getVector(llList2String(data,3));
        if ((cmd == "HIGHLIGHT")) {
            if ((row == myRow)) {
                if ((power == "ON")) {
                    llSetTexture("totallywhite",ALL_SIDES);
                    llSetColor(color,ALL_SIDES);
                }
                else  if ((power == "OFF")) {
                    llSetTexture("totallyclear",ALL_SIDES);
                    llSetColor(color,ALL_SIDES);
                }
            }
        }
    }

    /***********************************************
    *  changed event
    *  |-->Every time the inventory changes, reset the script
    *        
    ***********************************************/
    changed(integer change) {
        if ((change == CHANGED_INVENTORY)) {
            llResetScript();
        }
    }
}
