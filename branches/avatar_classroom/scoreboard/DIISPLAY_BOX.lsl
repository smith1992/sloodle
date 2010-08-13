// LSL script generated: avatar_classroom.scoreboard.DIISPLAY_BOX.lslp Wed Aug 11 19:44:11 Pacific Daylight Time 2010

integer DISPLAY_BOX_CHANNEL = -870881;
vector WHITE = <1.0,1.0,1.0>;
vector GREEN = <0.0,1.04964,0.27035>;
vector RED = <0.92748,0.0,0.32245>;
integer myRow;
string myName;
integer myColumn;/*********************************************
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
* DISPLAY_box_properties
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
default {

    state_entry() {
        llSetColor(WHITE,ALL_SIDES);
        llSetAlpha(1,ALL_SIDES);
        llSetTexture("totallyclear",ALL_SIDES);
        (myName = llGetLinkName(llGetLinkNumber()));
        list data = llParseString2List(myName,[","],[]);
        (myRow = i(llList2String(data,0)));
        (myColumn = i(llList2String(data,1)));
    }

    
    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == DISPLAY_BOX_CHANNEL)) {
            list data = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(data,0));
            integer row = i(llList2String(data,1));
            integer column = i(llList2String(data,2));
            string power = s(llList2String(data,3));
            string color = s(llList2String(data,4));
            if ((row != myRow)) return;
            if ((column != myColumn)) return;
            if ((cmd == "HIGHLIGHT")) {
                if ((power == "ON")) {
                    if ((color == "GREEN")) llSetColor(GREEN,ALL_SIDES);
                    else  if ((color == "RED")) llSetColor(RED,ALL_SIDES);
                }
                else  {
                    llSetColor(WHITE,ALL_SIDES);
                }
            }
            else  if ((cmd == "TEXTURE")) {
                string texture = s(llList2String(data,3));
                llSetTexture(texture,1);
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
