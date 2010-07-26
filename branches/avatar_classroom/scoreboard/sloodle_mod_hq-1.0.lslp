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
* sloodle_mod_hq-1.0.lsl   
*  
* PURPOSE
*  This script is part of the SLOODLE HQ.
* This script initializes the Sloodle Awards
*  
* beep sound from http://www.freesound.org/samplesViewSingle.php?id=12906
* Creative Commons Sampling Plus 1.0 License. see http://creativecommons.org/licenses/sampling+/1.0/
/**********************************************************************************************/
key owner;
// *************************************************** HOVER TEXT VARIABLES
integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
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
string myUrl;
integer currentAwardId;
string previousUrl;
integer previousAwardId;
integer DISPLAY_BOX_CHANNEL=-870881;
// *************************************************** HOVER TEXT COLORS
vector     RED            = <0.77278, 0.04391, 0.00000>;//RED
vector     YELLOW         = <0.82192, 0.86066, 0.00000>;//YELLOW
vector     GREEN         = <0.12616, 0.77712, 0.00000>;//GREEN
vector     PINK         = <0.83635, 0.00000, 0.88019>;//INDIGO
vector     WHITE        = <1.000, 1.000, 1.000>;//WHITE
string currentAwardName ;
/***********************************************
*  clearHighlights -- makes sure all highlight rows are set to 0 alpha
***********************************************/
clearHighlights(){
    integer c;
    for (c=0;c<9;c++){
        llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:"+(string)(c)+"|POWER:OFF|COLOR:GREEN",NULL_KEY);           
        llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,"CMD:TEXTURE|row:"+(string)(c)+"|col:0|TEXTURE:totallyclear",NULL_KEY);
    } 
}
integer DEBUG=FALSE;
debug(string s){
 if (DEBUG==TRUE) llOwnerSay((string)llGetFreeMemory()+" "+llGetScriptName()+" "+ s);
   s="";
}

string SLOODLE_EOF = "sloodleeof";
string sloodleserverroot;
integer sloodlecontrollerid;
string sloodlecoursename_short;
string sloodlecoursename_full;
integer sloodleid;
string scoreboardname;
list facilitators;

 integer sloodle_handle_command(string str) {         
        list bits = llParseString2List(str,["|"],[]);
        integer numbits = llGetListLength(bits);
        string name = llList2String(bits,0);
        string value1 = "";
        string value2 = "";
        if (numbits > 1) value1 = llList2String(bits,1);
        if (numbits > 2) value2 = llList2String(bits,2);
        if (name == "facilitator")facilitators+=llStringTrim(llToLower(value1),STRING_TRIM);else
        if (name =="set:sloodleserverroot") sloodleserverroot= value1; else
        if (name =="set:sloodlecontrollerid") sloodlecontrollerid= (integer)value1; else 
        if (name =="set:sloodlecoursename_short") sloodlecoursename_short= value1; else
        if (name =="set:sloodlecoursename_full") sloodlecoursename_full= value1; else
        if (name =="set:sloodleid") {
            sloodleid= (integer)value1; 
            currentAwardId=sloodleid;    
        }
        else 
        if (name =="set:sloodleid") scoreboardname= value2; else
        if (name == SLOODLE_EOF) return TRUE;
        return FALSE;

    }
/***********************************************
*  clear()
*  |-->clears the xy display
***********************************************/ 
clear(){
        string blanks="";
        integer counter;
        for (counter=0;counter<300;counter++){
            blanks+=" ";    
        }
        llMessageLinked(LINK_SET, DISPLAY_PAGE_NUMBER_STRING, "          ", "0");
        llMessageLinked(LINK_SET, XY_TITLE_CHANNEL, "                              ", "0");
        llMessageLinked(LINK_SET, XY_DETAILS_CHANNEL, "                              ", "0");        
        llMessageLinked(LINK_SET, XY_TEXT_CHANNEL, blanks, "0");
        blanks="";
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


/* &&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&
*
*  default state
*  In this state we wait until the rest of the scripts in this object init
*
* &&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&&& */
 default{
     //on_rez event - Reset Script to ensure proper defaults on rez
    on_rez(integer start_param) {
        llResetScript();       
    }

     state_entry() {
         //clear highlighted rows
         clearHighlights();
         //clear scoreboard;
         clear();      
         //set owner
         owner = llGetOwner();
     }
    link_message(integer sender_num, integer channel, string str, key id) {
        list dataLines=llParseString2List(str, ["\n"],[]);
        list cmdLine = llParseString2List(str, ["|"],[]);
        string cmd=s(llList2String(cmdLine,0));
        list bits = llParseString2List(str,["|"],[]); 
        integer numbits = llGetListLength(bits); //count number of parameters on each line
        string name = llList2String(bits,0);
        string value1 = "";
        string value2 = "";
        if (channel == SLOODLE_CHANNEL_OBJECT_DIALOG) {           
               if (sloodle_handle_command(str)==TRUE) state ready;
        }//endif
    }//end linked_message event
}//end default state

 
state ready{
    //on_rez event - Reset Script to ensure proper defaults on rez
    on_rez(integer start_param) {
        llResetScript();       
    }

    state_entry() {
        
        //llMessageLinked(LINK_SET, UI_CHANNEL, "COMMAND:AWARD SELECTED|AWARDID:"+(string)currentAwardId+"|NAME:"+currentAwardName, NULL_KEY);        
        //send message to _click_handler.lsl and order it to start listening for clicks
        
        
        
    }//end state_entry
    link_message(integer sender_num, integer channel, string str, key id) {
        if (channel==UI_CHANNEL){
            list dataBits = llParseString2List(str,["|"],[]);
            string command = s(llList2String(dataBits,0));
            if(command=="RESET"){
                llInstantMessage(owner,"Releasing http-in url...");
                llReleaseURL(myUrl);
                  llResetScript();
              }//endif command=="RESET"      
            }//UI_CHANNEL  
    }//linked message
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
}//end ready state
