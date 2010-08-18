 /*********************************************
*  Copyright (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
* 
*
*  This script is part of the SLOODLE Project see http://sloodle.org
*
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
*
* _click_handler.lsl 
* 
* PURPOSE
*  This script is part of the SLOODLE HQ.
*  click_handler detects button clicks and sends a linked message on the UI_CHANNEL indicating which button was pressed
*  
/**********************************************************************************************/
key owner;
// *************************************************** HOVER TEXT VARIABLES
integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
integer DEBUG=FALSE;
integer SETTEXT_CHANNEL                                                =-776644;//hover text channel
integer SOUND_CHANNEL                                                     = -34000;//sound requests
integer DISPLAY_PAGE_NUMBER_STRING                            = 304000;//page number xy_text
integer XY_TITLE_CHANNEL                                                  = 600100;//title xy_text
integer XY_TEXT_CHANNEL                                                = 100100;//display xy_channel
integer XY_DETAILS_CHANNEL                                          = 700100;//instructional xy_text
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST     = -1928374651;//translation channel
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE     = -1928374652;//translation channel
integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
integer PRIM_PROPERTIES_CHANNEL                                =-870870;//setting highlights
integer SLOODLE_CHANNEL_OBJECT_DIALOG                     = -3857343;//configuration channel
integer SET_COLOR_INDIVIDUAL                                        = 8888999;//row text color channel
integer ROW_CHANNEL;                                                                    
integer AWARD_DATA_CHANNEL                                        =890;
integer ANIM_CHANNEL                                                        =-77664251;//animation trigger channel
 integer DISPLAY_BOX_CHANNEL=-870881;
        reinitialise()
        {
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", NULL_KEY);
            llResetScript();
}
/***********************************************
*  clearHighlights -- makes sure all highlight rows are set to 0 alpha
***********************************************/



integer debugCheck(){
        if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
            return TRUE;
        }
            else return FALSE;
        
    }
    debug(string str){
        if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
            llOwnerSay(str);
        }
    }

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
    return llList2Vector(llParseString2List(vv, [":"], []),1);
}//end function
stars(){
   llParticleSystem([PSYS_PART_MAX_AGE,4.16,
PSYS_PART_FLAGS, 263,
PSYS_PART_START_COLOR, <0.97466, 0.94959, 0.10151>,
PSYS_PART_END_COLOR, <0.77446, 0.23138, 0.02765>,
PSYS_PART_START_SCALE,<1.33333, 1.32886, 0.00000>,
PSYS_PART_END_SCALE,<0.00000, 0.00000, 0.00000>,
PSYS_SRC_PATTERN, 2,
PSYS_SRC_BURST_RATE,1.64,
PSYS_SRC_ACCEL, <-0.02000, -0.05892, 0.00000>,
PSYS_SRC_BURST_PART_COUNT,43,
PSYS_SRC_BURST_RADIUS,0.43,
PSYS_SRC_BURST_SPEED_MIN,0.25,
PSYS_SRC_BURST_SPEED_MAX,0.38,
PSYS_SRC_ANGLE_BEGIN, 0.00,
PSYS_SRC_ANGLE_END, 0.00,
PSYS_SRC_OMEGA, <-0.01100, 0.00000, 0.00000>,
PSYS_SRC_MAX_AGE, 0.0,
PSYS_SRC_TEXTURE, "fe054e23-14c3-23cd-a06b-ccaf97c42ea5",
PSYS_PART_START_ALPHA, 1.00,
PSYS_PART_END_ALPHA, 0.00]);
}
string SOUND="ON";
playSound(string sound){
    if (SOUND=="ON")llTriggerSound(sound, 1.0);
}

/* &&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
*
*  default state
*  In this state we wait until the sloodle_api script in this object inits
*
* &&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&& */
 default{     
  on_rez(integer start_param) {
       llResetScript();
   }
     state_entry() {
         owner=llGetOwner();
     }
    
    touch_start(integer num_detected) {
        playSound("click");
        stars();
              llSetTimerEvent(0.25);
            //buttonName:name
            list buttonData = llParseString2List(llGetLinkName(llDetectedLinkNumber(0)),[","],[]);
            string buttonName=s(llList2String(buttonData,0));
            if (buttonName=="")buttonName=llList2String(buttonData,0);
            llMessageLinked(LINK_SET, UI_CHANNEL, "CMD:BUTTON PRESS|BUTTON:"+buttonName+"|AVUUID:"+(string)llDetectedKey(0),NULL_KEY);
            debug("CMD:BUTTON PRESS|BUTTON:"+buttonName+"|AVUUID:"+(string)llDetectedKey(0));
             
    }//end touch event
    /***********************************************
    *  changed event
    *  |-->Every time the inventory changes, reset the script
    *        
    ***********************************************/
    changed(integer change) {
     if (change ==CHANGED_INVENTORY){         
         llResetScript();
     }//endif
    }//end changed event  
    timer() {
        llSetTimerEvent(0);
        llParticleSystem([]);
    }
}//end ready state
