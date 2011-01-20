/**********************************************************************************************
*  sloodle_auto_enrol.lsl
*  Copyright (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*
*  This script can be added as an autoenrol button, so administrators and teachers can enable / disable 
*  auto enrol functionality from within Second Life 
*
*  as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
* 
*  This script is part of the SLOODLE Project see http://sloodle.org
*
*  Copyright
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
/**********************************************************************************************/

//gets a vector from a string
vector     RED            = <0.77278,0.04391,0.00000>;//RED
vector     ORANGE = <0.87130,0.41303,0.00000>;//orange
vector     YELLOW         = <0.82192,0.86066,0.00000>;//YELLOW
vector     GREEN         = <0.12616,0.77712,0.00000>;//GREEN
vector     BLUE        = <0.00000,0.05804,0.98688>;//BLUE
vector     PINK         = <0.83635,0.00000,0.88019>;//INDIGO
vector     PURPLE = <0.39257,0.00000,0.71612>;//PURPLE
vector     WHITE        = <1.000,1.000,1.000>;//WHITE
vector     BLACK        = <0.000,0.000,0.000>;//BLACKvector     ORANGE = <0.87130, 0.41303, 0.00000>;//orange 
key sitter;
integer USE_DID_NOT_HAVE_PERMISSION_TO_ACCESS_RESOURCE_REQUESTED = -331;
integer AVATAR_NOT_ENROLLED= -321;

integer counter=0;
integer TIME_LIMIT=7;
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
integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
integer SLOODLE_CHANNEL_OBJECT_DIALOG                   = -3857343;//configuration channel
/***********************************************
*  isFacilitator()
*  |-->is this person's name in the access notecard
***********************************************/
integer isFacilitator(string avName){
    if (llListFindList(facilitators, [llStringTrim(llToLower(avName),STRING_TRIM)])==-1) return FALSE; else return TRUE;
}

/***********************************************************************************************
*  s()  k() i() and v() are used so that sending messages is more readable by humans.  
* Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All these functions do is strip off the text before the ":" char and return a string
***********************************************************************************************/
string s (list ss,integer indx){
   return llList2String(llParseString2List(llList2String(ss,indx), [":"], []),1);
}//end function
key k (list kk, integer indx){
   return llList2Key(llParseString2List(llList2String(kk,indx), [":"], []),1);
}//end function
integer i (list ii, integer indx){
   return llList2Integer(llParseString2List(llList2String(ii,indx), [":"], []),1);
}//end function
vector v (list vv, integer indx){
   integer p = llSubStringIndex(llList2String(vv,indx), ":");
   string vString = llGetSubString(llList2String(vv,indx), p+1, llStringLength(llList2String(vv,indx)));
   return getVector(vString);
}//end function
rotation r (list rr, integer indx){
   integer p = llSubStringIndex(llList2String(rr,indx), ":");
   string rString = llGetSubString(llList2String(rr,indx), p+1, llStringLength(llList2String(rr,indx)));
   return getRot(rString);
}//end function
integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
string SLOODLE_EOF = "sloodleeof";
string sloodleserverroot;
integer sloodlecontrollerid;
integer PLUGIN_RESPONSE_CHANNEL=998822; //channel the api responds on
string sloodlecoursename_short;
string sloodlecoursename_full;
integer sloodleid;
string scoreboardname;
integer currentAwardId;
string currentAwardName;
list facilitators;
integer readyCounter=0;
string hoverText;
integer sloodle_handle_command(string str) {  
     if (str == SLOODLE_EOF) return TRUE;       
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
            currentAwardName=value2;
            currentAwardId=sloodleid;    
        }
        else 
        if (name =="set:sloodleid") scoreboardname= value2; 
        
         return FALSE;
}
default{
    on_rez(integer start_param) {
        llResetScript();
    }
    state_entry() {
        llSetText("Loading", YELLOW, 1.0);
        llSetTimerEvent(0.25);;
    }
    link_message(integer sender_num, integer chan, string str, key id) {
        if (chan==SLOODLE_CHANNEL_OBJECT_DIALOG){
           if (sloodle_handle_command(str)==TRUE) state ready;
        }
    }
 timer() {
      counter++;
      
      if (counter>20){
          hoverText="|";
          counter=0;
      }
      hoverText+="||||";
      llSetText(hoverText, YELLOW, 1.0);
      
  }
}
state ready {
    on_rez(integer start_param) {
        llResetScript();
    }
    state_entry() {
        llSetTimerEvent(0);
        hoverText="";
        llSetText("", YELLOW, 1.0);
        llSetText("", RED, 1.0);
        llSetTexture("_blank", 4);
        llSetObjectDesc("btn:check_enrol");
        facilitators+=llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM);
        string authenticatedUser = "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
        llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "course->checkAutoEnrolSettings"+authenticatedUser, NULL_KEY);
    }
    touch_start(integer num_detected) {
        if (isFacilitator(llDetectedName(0))==FALSE) {
                            llSay(0,"Sorry, "+ llDetectedName(0)+" but you are not a facilitator, facilitators are: "+llList2CSV(facilitators));
                            return;
        }
        llTriggerSound("click", 1.0);//
        string desc = llGetObjectDesc();
       //turn autoenrol on
        if (desc=="btn:btn_autoenrol_on"){
            string authenticatedUser = "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
            llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "course->changeSettings"+authenticatedUser+"&var=autoenrol&setting=on", NULL_KEY);
            llSetTimerEvent(0.25);;
        }//button
        else
        //turn autoenrol off
        if (desc=="btn:btn_autoenrol_off"){
            llSetTimerEvent(0.25);;
            string authenticatedUser = "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
            llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "course->changeSettings"+authenticatedUser+"&var=autoenrol&setting=off", NULL_KEY);
        }//button
    }
    link_message(integer sender_num, integer channel, string str, key id) {
        if (channel==SLOODLE_CHANNEL_OBJECT_DIALOG){
             if (str=="do:requestconfig")llResetScript();
            }//endif SLOODLE_CHANNEL_OBJECT_DIALOG
        else
        if (channel==PLUGIN_RESPONSE_CHANNEL){
            llSetTimerEvent(0);
            hoverText="";
            llSetText("", YELLOW, 1.0);
            list dataLines = llParseStringKeepNulls(str,["\n"],[]);           
            //get status code
            list statusLine =llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
            integer status =llList2Integer(statusLine,0);
            string descripter = llList2String(statusLine,1);
            list sideEffects =llParseString2List(llList2String(statusLine,2), [","], []);
            string response =llList2String(statusLine,3);
            integer timeSent=llList2Integer(statusLine,4);
            integer timeRecvt=llList2Integer(statusLine,5);
            key uuidSent= llList2Key(statusLine,6);
            list cmdList = llParseString2List(str, ["|"], []);
            if (status ==AVATAR_NOT_ENROLLED){
            	state reset;
            }
            if (response=="course->checkAutoEnrolSettings"){
                integer autoEnrol;
                integer autoReg;
                if (status==-515){
                    llSetTexture("error", 4);
                    llLoadURL(llGetOwner(), "Auto Enrol is not enabled for this Site. Please change.",sloodleserverroot+"/admin/settings.php?section=modsettingsloodle");
                    llSetObjectDesc("btn:btn_autoenrol_on");
                }else
                if (status==-516){
                    llSetTexture("error", 4);
                    llLoadURL(llGetOwner(), "Auto Registration is not enabled for this Site. Please change.",sloodleserverroot+"/admin/settings.php?section=modsettingsloodle");
                    llSetObjectDesc("btn:btn_autoenrol_on");
                }
                if (s(dataLines,2)=="FALSE") {
                    autoEnrol=FALSE;
                    llSetTexture("btn_autoenrol_off", 4);
                     llSetObjectDesc("btn:btn_autoenrol_on");
                } else
                if (s(dataLines,2)=="TRUE") {
                    autoEnrol=TRUE;
                    llSetTexture("btn_autoenrol_on", 4);
                    llSetObjectDesc("btn:btn_autoenrol_off");
                }
                //if (s(dataLines,3)=="FALSE") autoReg=FALSE; else
                //if (s(dataLines,3)=="TRUE") autoReg=TRUE;  
            }
            else
            if (response=="course->changeSettings"){
                integer autoEnrol;
                integer autoReg;
                if (s(dataLines,2)=="FALSE") {
                    autoEnrol=FALSE;
                    llSetTexture("btn_autoenrol_off", 4);
                    llSetObjectDesc("btn:btn_autoenrol_on");
                } else
                if (s(dataLines,2)=="TRUE") {
                    autoEnrol=TRUE;
                    llSetTexture("btn_autoenrol_on", 4);
                    llSetObjectDesc("btn:btn_autoenrol_off");
                }
                //if (s(dataLines,3)=="FALSE") autoReg=FALSE; else
                //if (s(dataLines,3)=="TRUE") autoReg=TRUE;  
            }
        }
        
        
  }//link
  timer() {
      counter++;
      
      if (counter>20){
          hoverText="|";
          counter=0;
      }
            hoverText+="||||";
      llSetText(hoverText, YELLOW, 1.0);
      
  }
  changed(integer change) { // something changed
            if (change== CHANGED_INVENTORY) { // and it was a link change
                   llSetTexture("_blank", 4); 
                   llSetObjectDesc("btn:check_enrol");
                 llResetScript();
            }//endif
    }//change
}//default
state reset{

state_entry() {
	llSetText("Error: Not connected. Authorization error.", RED, 1.0);
	llSetTexture("error", 4);	
}
	touch_start(integer num_detected) {
		llSay(0,"Resetting");
		llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig",NULL_KEY);
		state default;	
	}

}