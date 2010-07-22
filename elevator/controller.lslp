/*********************************************
*  Copyright (c) 2009 Paul Preibisch
*  fire@b3dMultiTech.com
* All Rights Reserved
*
* receiver.lsl
*
* This script calls an elevator
*
  
* 
* 
*/
/*********************************************
*  Copyright (c) 2009 Paul Preibisch
*  fire@b3dMultiTech.com
* All Rights Reserved
*
* elevator.lsl
*
* This script teleports the user when they sit on the object to an offset
* from the objects current location.
*
* To specify the offset, place a vector in the objects description  
* 
* 
*/
//Leave that here
//Script created by Morgam Biedermann

integer MYCHANNEL;



key     gSetupQueryId; //used for reading settings notecards
list    gInventoryList;//used for reading settings notecards
string  gSetupNotecardName="_config";//used for reading settings notecards
integer gSetupNotecardLine;//used for reading settings notecards
/***********************************************
*  getInventoryist()
*  used to read notecard settings
***********************************************/
list getInventoryList(){
    list       result = [];
    integer    n = llGetInventoryNumber(INVENTORY_ALL);
    integer    i = 0;
    while(i < n){
        result += llGetInventoryName(INVENTORY_ALL, i);
        ++i;
    }//while
    return result;
 }//list getInventoryList

/***********************************************
*  readSettingsNotecard()
*  |-->used to read notecard settings
***********************************************/
readSettingsNotecard(){
   gSetupNotecardLine = 0;
   gSetupQueryId = llGetNotecardLine(gSetupNotecardName,gSetupNotecardLine); 
}

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer( integer min, integer max ){
 return min + (integer)( llFrand( max - min + 1 ) );
}
vector     RED            = <0.77278, 0.04391, 0.00000>;//RED
vector     ORANGE = <0.87130, 0.41303, 0.00000>;//orange
vector     YELLOW         = <0.82192, 0.86066, 0.00000>;//YELLOW
vector     GREEN         = <0.12616, 0.77712, 0.00000>;//GREEN
vector     BLUE        = <0.00000, 0.05804, 0.98688>;//BLUE
vector     PINK         = <0.83635, 0.00000, 0.88019>;//INDIGO
vector     PURPLE = <0.39257, 0.00000, 0.71612>;//PURPLE
vector     WHITE        = <1.000, 1.000, 1.000>;//WHITE
vector     BLACK        = <0.000, 0.000, 0.000>;//BLACK
list tp;
integer MENU_CHANNEL;
list menuFloors;
list rot;
string strSit ="Please select a destination";
vector startPos;

string floorName;
//gets a vector from a string
vector getVector(string vStr){
        vStr=llGetSubString(vStr, 1, llStringLength(vStr)-2);
        list vStrList= llParseString2List(vStr, [","], ["<",">"]);
        vector output= <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2)>;
        return output;
}//end getVector
rotation getRot(string vStr){
        vStr=llGetSubString(vStr, 1, llStringLength(vStr)-2);
        list vStrList= llParseString2List(vStr, [","], ["<",">"]);
        rotation output= <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2),llList2Float(vStrList,3)>;
        return output;
}//end getRot
/***********************************************************************************************
*  s()  k() i() and v() are used so that sending messages is more readable by humans.  
* Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All these functions do is strip off the text before the ":" char and return a string
***********************************************************************************************/
string s (string ss){
    return llList2String(llParseString2List(ss, [":"], []),1);
}//end function
key k (string kk){
    return llList2Key(llParseString2List(kk, [":"], []),1);
}//end function
integer i (string ii){
    return llList2Integer(llParseString2List(ii, [":"], []),1);
}//end function
vector v (string vv){
    integer p = llSubStringIndex(vv, ":");
    string vString = llGetSubString(vv, p+1, llStringLength(vv));
    return getVector(vString);
}//end function
rotation r (string rr){
    integer p = llSubStringIndex(rr, ":");
    string rString = llGetSubString(rr, p+1, llStringLength(rr));
    return getRot(rString);
}//end function
integer floorNum;
integer SLOODLE_CHANNEL_OBJECT_DIALOG                   = -3857343;//configuration channel
 default{
            on_rez(integer start_param) {
            llResetScript();
        }
        state_entry() {
        	llSetText("Waiting for configuration", YELLOW, 1.0);
        	llTriggerSound("startingup", 1.0);//startingup complete
        }
       	link_message(integer sender_num, integer chan, string str, key id) {
       			if (chan==SLOODLE_CHANNEL_OBJECT_DIALOG){
       				list cmdList = llParseString2List(str, ["|"], []);        
        			string cmd = llStringTrim(llList2String(cmdList,0),STRING_TRIM);
       				if (cmd=="elevator_channel"){
       					MYCHANNEL=llList2Integer(cmdList,1);
       					state ready;
       				}
       			}
       	}
    }
    state ready{
    	on_rez(integer start_param) {
    		llResetScript();
    	}
  
    state_entry() {
    	llTriggerSound("loadingcomplete", 1.0);//loading complete
            string desc = llGetObjectDesc();
        list pDesc = llParseString2List(desc, [","], []);
        floorName= llList2String(pDesc,0);
        floorNum= llList2Integer(pDesc,1);
        llListen(MYCHANNEL, "elevator", "", "");
        llSetText(floorName+"\nTouch to Call Elevator", GREEN, 1.0);
      
    }

    touch_start(integer num_detected) {
      llShout(MYCHANNEL,"CMD:GO TO FLOOR|FLOOR:"+floorName+"|FLOORNUM:"+(string)floorNum+"|DEST:"+(string)llGetPos()+"|ROT:"+(string)llGetRot()+"|AVKEY:"+(string)llGetOwner());
      //creative commons license - source:http://www.freesound.org/samplesViewSingle.php?id=60344
     llTriggerSound("3b55d663-7d20-326e-c126-c9804acef383",1.0);//bell sound - filename: bell      
     llSay(0,"Calling elevator for "+(string)llDetectedName(0) +" to "+floorName);
      
    }
    listen(integer channel, string name, key id, string str) {
        
        list cmdList = llParseString2List(str, ["|"], []);        
        string cmd = s(llList2String(cmdList,0));
        if (cmd=="GET FLOOR"){
            if (s(llList2String(cmdList,1))==floorName){
                if (k(llList2String(cmdList,2))==llGetOwner())
                     llShout(MYCHANNEL,"CMD:GO TO FLOOR|FLOOR:"+floorName+"|FLOORNUM:"+(string)floorNum+"|DEST:"+(string)llGetPos()+"|ROT:"+(string)llGetRot()+"|AVKEY:"+(string)llGetOwner());
            }
        }
    }
    changed(integer change) {
            // If the inventory is changed, and we have a Sloodle config notecard, then use it to re-initialise
                    if (change & CHANGED_INVENTORY) {
                        // If the current notecard is not the same as the one we read most recently, then reset
                           llResetScript();
                    }//if
                 }
}
