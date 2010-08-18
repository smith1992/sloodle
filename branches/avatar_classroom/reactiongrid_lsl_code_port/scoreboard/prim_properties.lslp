/*********************************************
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
string s (string ss){
    return llList2String(llParseString2List(ss, [":"], []),1);
}
/***********************************************
*  k()  used so that sending of linked messages is more readable by humans.  Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added instead: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All this function does is strip off the text before the ":" char and return a key
***********************************************/
key k (string kk){
    return llList2Key(llParseString2List(kk, [":"], []),1);
}
/***********************************************
*  i()  used so that sending of linked messages is more readable by humans.  Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added instead: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All this function does is strip off the text before the ":" char and return an integer
***********************************************/

integer i (string ii){
    return llList2Integer(llParseString2List(ii, [":"], []),1);
}

/***********************************************
*  v()  used so that sending of linked messages is more readable by humans.  Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added instead: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All this function does is strip off the text before the ":" char and return an integer
***********************************************/

vector v (string vv){
    return llList2Vector(llParseString2List(vv, [":"], []),1);
}
integer PRIM_PROPERTIES_CHANNEL=-870870;
vector WHITE=<1.000,1.000,1.000>;
vector GREEN =<0.00000, 1.04964, 0.27035>;
vector RED= <0.92748, 0.00000, 0.32245>;
integer myRow;
string myName;
vector getVector(string vStr){
        vStr=llGetSubString(vStr, 1, llStringLength(vStr)-2);
        list vStrList= llParseString2List(vStr, [","], ["<",">"]);
        vector output= <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2)>;
        return output;
}//end getVector

default {
    state_entry() {
        llSetColor(WHITE, ALL_SIDES);        
        llSetAlpha(1, ALL_SIDES);
        llSetTexture("totallyclear", ALL_SIDES);
        //row:0
        myName = llGetLinkName(llGetLinkNumber());
        list data = llParseString2List(myName, [","], []); //parse the message into a list
        myRow=i(llList2String(data,0));
    }
    
    link_message(integer sender_num, integer channel, string str, key id) {
            if (channel!=PRIM_PROPERTIES_CHANNEL)return;
                //llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:row#|POWER:ON/OFF|COLOR:RED/GREEN",NULL_KEY);
//                llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:"+(string)counter+"|POWER:ON",NULL_KEY);
                list data = llParseString2List(str, ["|"], []); //parse the message into a list
                string cmd = s(llList2String(data,0));
                integer row = i(llList2String(data,1));
                string power = s(llList2String(data,2));
                vector color=getVector(llList2String(data,3));              
                if (cmd=="HIGHLIGHT"){
                    if (row==myRow){

                            if (power=="ON"){               
                                  llSetTexture("totallywhite", ALL_SIDES);
                                llSetColor(color, ALL_SIDES);
                            }else
                            if (power=="OFF"){               
                                  llSetTexture("totallyclear", ALL_SIDES);
                                llSetColor(color, ALL_SIDES);
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
         if (change ==CHANGED_INVENTORY){         
             llResetScript();
         }
     }
}
