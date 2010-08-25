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
    //teleport cript created by Morgam Biedermann
    vector posnow;
    integer SLOODLE_CHANNEL_OBJECT_DIALOG                   = -3857343;//configuration channel
    vector stapos;
    rotation rotnow;
    integer MYCHANNEL=-1;
    integer TIMEOUT=30;
    integer currentFloor;
    
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
    
                 
    teleports(vector dest){
        if(llGetPos() != startPos)    {
            llSetPos(startPos);
            teleports(startPos);
            llOwnerSay("teleporting to: "+(string)dest);
        }
    }
    vector pos;
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
    rotation startRot;
    integer debugCheck(){
        if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
            return TRUE;
        }
        else return FALSE;
}
debug(string str){
        if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
            llOwnerSay(llGetScriptName()+" "+" freemem: "+(string)llGetFreeMemory()+ " ==== "+str);
        }
}
  
    integer curFloor=1;
    list getMenu(){
        integer j;
        list tmpList=[];
        for (j=llGetListLength(menuFloors)-1;j>=0;j--){
            if (j!=curFloor-1)
                tmpList+=llList2String(menuFloors,j);
        }
        return tmpList;
    }
    warp(vector pos)
{
    list rules;
    integer num = llRound(llVecDist(llGetPos(),pos)/10)+1;
    integer x;
    for(x=0; x<num; ++x){
         rules+=[PRIM_POSITION,pos];
    }
    llSetPrimitiveParams(rules);
    
            llUnSit(llAvatarOnSitTarget());
           
      
    
}

    
    transport(vector dest, rotation rot,string fName,integer fNum,integer RESET){
        
                    
                        if (curFloor!=1){
                            llSetTimerEvent(TIMEOUT);
                        }
                        //creative commons license - source: http://www.freesound.org/samplesViewSingle.php?id=54522
                        llTriggerSound("elevator2",1.0);//elevator sound - filename: elevator2
                        curFloor=fNum;//set current floor;
                        llSay(0,"Moving to "+fName);
                       
                        if (fNum!=1){
                            //llGetRot() * llEuler2Rot(<0,0,100> * DEG_TO_RAD)
                            llSetRot(rot);
                              warp(<0,1.3,-1>*rot+dest);
                             
                        }else{
                            if (RESET==TRUE){
                                llSetRot(startRot);
                                warp(startPos);
                            }else{
                                llSetRot(startRot);
                                warp(<0,1.3,-1>*startRot+dest);
                            }
                        }
    }
    default{
            on_rez(integer start_param) {
            llResetScript();
        }
        state_entry() {
            llTriggerSound("startingup", 1.0);//startingup complete
            llSetText("Waiting for configuration", YELLOW, 1.0);
        }
           link_message(integer sender_num, integer chan, string str, key id) {
                   if (chan==SLOODLE_CHANNEL_OBJECT_DIALOG){
                       list cmdList = llParseString2List(str, ["|"], []);        
                    string cmd = llStringTrim(llList2String(cmdList,0),STRING_TRIM);
                       if (cmd=="elevator_channel"){
                           MYCHANNEL=llList2Integer(cmdList,1);
                           debug("my chan is: "+(string)MYCHANNEL);
                           state ready;
                       }
                    else if (str=="do:requestconfig") llResetScript();
                   }
                   else
                   if (chan==UI_CHANNEL){
                       list cmdList = llParseString2List(str, ["|"], []);        
                       string cmd = s(cmdList,0);
                    key userKey = k(cmdList,2);
                       if (cmd=="BUTTON PRESS"){
                            llDialog(userKey, strSit, menuFloors, MENU_CHANNEL);
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
            llSetText("Online", PINK, 1.0);
            menuFloors=["Floor 1","Floor 2"];
            llSitTarget(<0.0, 0.0, 1.0>, llEuler2Rot(<0,0,180>)); //The vector's components must not all be set to 0 for effect to take place
            llListen(MYCHANNEL, "", "", "");       
            debug("Listening to: "+(string)MYCHANNEL);
            startRot=llGetRot(); 
            startPos = llGetPos();            
            MENU_CHANNEL=random_integer(-3000,-33000);
            llListen(MENU_CHANNEL,"","","");
            llSetText("Sit for Menu", GREEN, 1.0);
            
        }
         changed(integer change) { // something changed
            if (change | CHANGED_LINK) { // and it was a link change
                key sitter = llAvatarOnSitTarget() ;            
                if (sitter!= NULL_KEY) { // somebody is sitting on me
                    llDialog(sitter, strSit, getMenu(), MENU_CHANNEL);
                
                }
            }
                // If the inventory is changed, and we have a Sloodle config notecard, then use it to re-initialise
                        if (change & CHANGED_INVENTORY) {
                            // If the current notecard is not the same as the one we read most recently, then reset
                               llResetScript();
                        }//if
            
        }
       link_message(integer sender_num, integer chan, string str, key id) {
                  if (chan==SLOODLE_CHANNEL_OBJECT_DIALOG){
                      if (str=="do:requestconfig") llResetScript();
                  }
               if (chan==UI_CHANNEL){
                       list cmdList = llParseString2List(str, ["|"], []);        
                    string cmd = s(cmdList,0);
                    key userKey = k(cmdList,2);
                   if (cmd=="BUTTON PRESS"){
                        llDialog(userKey, strSit, menuFloors, MENU_CHANNEL);
                   }
               }
       
       }
        listen(integer channel, string name, key id, string message) {
               if (channel==MYCHANNEL){
                if (name=="receiver1"|name=="receiver2"|name=="receiver3"){
                    debug("got command: "+message);
                    list cmdList = llParseString2List(message, ["|"], []);        
                    string cmd = s(cmdList,0);
                    if (cmd=="GO TO FLOOR"){
                        
                        llSetTimerEvent(TIMEOUT);
                        string fName = s(cmdList,1);
                        integer fNum= i(cmdList,2);
                        currentFloor=fNum;                    
                        vector dest = v(cmdList,3);                    
                        rotation rot= r(cmdList,4);
                        string avKey= k(cmdList,5);
                        if (avKey==llGetOwner()){
                            transport(dest,rot,fName,fNum,FALSE);
                        }//avkey
                    }    //gotofloor        
                }//receiver
               }//channel
               else
               if (channel==MENU_CHANNEL){
                if (llListFindList(menuFloors,[message])!=-1){
                    llShout(MYCHANNEL,"CMD:GET FLOOR|FLOOR:"+message+"|AVKEY:"+(string)llGetOwner());
                }//if
           }//channel               
        }//listen
        timer() {
            if (currentFloor!=1)
             llShout(MYCHANNEL,"CMD:GET FLOOR|FLOOR:Floor 1|AVKEY:"+(string)llGetOwner());
             llSetTimerEvent(0);
        }
    }
