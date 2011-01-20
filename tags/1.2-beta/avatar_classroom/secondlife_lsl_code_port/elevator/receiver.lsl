// LSL script generated: avatar_classroom2.secondlife_lsl_code_port.elevator.receiver.lslp Wed Aug 25 13:52:43 Pacific Daylight Time 2010
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
vector YELLOW = <0.82192,0.86066,0.0>;
vector GREEN = <0.12616,0.77712,0.0>;

string floorName;
integer floorNum;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
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
key k(string kk){
    return llList2Key(llParseString2List(kk,[":"],[]),1);
}
debug(string str){
    if ((llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0) == 4)) {
        llOwnerSay((((((llGetScriptName() + " ") + " freemem: ") + ((string)llGetFreeMemory())) + " ==== ") + str));
    }
}
 default {

            on_rez(integer start_param) {
        llResetScript();
    }

        state_entry() {
        llSetText("Waiting for configuration",YELLOW,1.0);
        llTriggerSound("startingup",1.0);
    }

       	link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = llStringTrim(llList2String(cmdList,0),STRING_TRIM);
            if ((cmd == "elevator_channel")) {
                (MYCHANNEL = llList2Integer(cmdList,1));
                state ready;
            }
        }
    }
}
    state ready {

    	on_rez(integer start_param) {
        llResetScript();
    }

  
    state_entry() {
        llTriggerSound("loadingcomplete",1.0);
        string desc = llGetObjectDesc();
        list pDesc = llParseString2List(desc,[","],[]);
        (floorName = llList2String(pDesc,0));
        (floorNum = llList2Integer(pDesc,1));
        llListen(MYCHANNEL,"elevator","","");
        llSetText((floorName + "\nTouch to Call Elevator"),GREEN,1.0);
    }


    touch_start(integer num_detected) {
        llRegionSay(MYCHANNEL,((((((((("CMD:GO TO FLOOR|FLOOR:" + floorName) + "|FLOORNUM:") + ((string)floorNum)) + "|DEST:") + ((string)llGetPos())) + "|ROT:") + ((string)llGetRot())) + "|AVKEY:") + ((string)llGetOwner())));
        llTriggerSound("3b55d663-7d20-326e-c126-c9804acef383",1.0);
        llSay(0,((("Calling elevator for " + ((string)llDetectedName(0))) + " to ") + floorName));
        debug(((((((((((("CMD:GO TO FLOOR|FLOOR:" + floorName) + "|FLOORNUM:") + ((string)floorNum)) + "|DEST:") + ((string)llGetPos())) + "|ROT:") + ((string)llGetRot())) + "|AVKEY:") + ((string)llGetOwner())) + " on chan: ") + ((string)MYCHANNEL)));
    }

    listen(integer channel,string name,key id,string str) {
        list cmdList = llParseString2List(str,["|"],[]);
        string cmd = s(llList2String(cmdList,0));
        if ((cmd == "GET FLOOR")) {
            if ((s(llList2String(cmdList,1)) == floorName)) {
                if ((k(llList2String(cmdList,2)) == llGetOwner())) llShout(MYCHANNEL,((((((((("CMD:GO TO FLOOR|FLOOR:" + floorName) + "|FLOORNUM:") + ((string)floorNum)) + "|DEST:") + ((string)llGetPos())) + "|ROT:") + ((string)llGetRot())) + "|AVKEY:") + ((string)llGetOwner())));
                debug(((((((((("CMD:GO TO FLOOR|FLOOR:" + floorName) + "|FLOORNUM:") + ((string)floorNum)) + "|DEST:") + ((string)llGetPos())) + "|ROT:") + ((string)llGetRot())) + "|AVKEY:") + ((string)llGetOwner())));
            }
        }
    }

    changed(integer change) {
        if ((change & CHANGED_INVENTORY)) {
            llResetScript();
        }
    }
}
