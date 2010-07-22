/*********************************************
*  Copyright (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
*  This script is part of the Quiz Build Project for Skoolaborate
*
* chair_rezzer.lsl
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
* 
* 
*/


//set DEBUG=FALSE to stop debug output
integer DEBUG=TRUE;
integer MENU_CHANNEL;
string type;
integer chairId;
string listen_cmd;
list chairs;
list activeBlackChairs;
string sitter ;
integer placeinlist;
list listen_cmdList;
list activeWhiteChairs;
list activeWhiteChairSitters;
list activeBlackChairSitters;
list DEBUG_VARS;
integer DEBUG_CHAN=-32212;
debug(string debugVar,string s){
    llMessageLinked(LINK_SET, DEBUG_CHAN,"DEBUGVAR:"+debugVar+"%%script:"+llGetScriptName()+"%%mem:"+(string)llGetFreeMemory()+"%%"+s,NULL_KEY);
}
list list_cast(list in)
{
    list out;
    integer i = 0;
    integer l= llGetListLength(in);
    while(i < l)
    {
        string d= llStringTrim(llList2String(in,i),STRING_TRIM);
        if(d == "")out += "";
        else
        {
            if(llGetSubString(d,0,0) == "<")
            {
                if(llGetSubString(d,-1,-1) == ">")
                {
                    list s = llParseString2List(d,[","],[]);
                    integer sl= llGetListLength(s);
                    if(sl == 3)
                    {
                        out += (vector)d;
                        //jump end;
                    }else if(sl == 4)
                    {
                        out += (rotation)d;
                        //jump end;
                    }
                }
                //either malformed,or identified
                jump end;
            }
            if(llSubStringIndex(d,".") != -1)
            {
                out += (float)d;
            }else
            {
                integer lold = (integer)d;
                if((string)lold == d)out += lold;
                else
                {
                    key kold = (key)d;
                    if(kold)out += [kold];
                    else out += [d];
                }
            }
        }
        @end;
        i += 1;
    }
 
    return out;
}
/***********************************************
*  isChair(key id);
*  |-->checks if its a chair that we rezzed
***********************************************/
integer isChair(key id){
    if (llListFindList(chairs, [id] )==-1) return FALSE; else return TRUE;
}
list facilitators;
integer rezzed=0;
/***********************************************
*  isFacilitator()
*  |-->is this person's name in the access notecard
***********************************************/
integer isFacilitator(string avName){
    if (llListFindList(facilitators, [llStringTrim(llToLower(avName),STRING_TRIM)])==-1) return FALSE; else return TRUE;
}
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
string s (string ss){
    return llList2String(llParseString2List(ss, [":"], []),1);
}//end function
integer i (string ii){
    return llList2Integer(llParseString2List(ii, [":"], []),1);
}//end function
key _k (string kk){
    return llList2Key(llParseString2List(kk, [":"], []),1);
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

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer( integer min, integer max ){
  return min + (integer)( llFrand( max - min + 1 ) );
}
integer REZNUM;
integer NOTECARD_CHANNEL=-70000012;
string SERVER_URL;
string secret; //the key for md5
integer SERVER_CHANNEL; //the channel we will communicate on
key myKey;

vector     RED            = <0.77278, 0.04391, 0.00000>;//RED
vector     ORANGE = <0.87130, 0.41303, 0.00000>;//orange
vector     YELLOW         = <0.82192, 0.86066, 0.00000>;//YELLOW
vector     GREEN         = <0.12616, 0.77712, 0.00000>;//GREEN
vector     BLUE        = <0.00000, 0.05804, 0.98688>;//BLUE
vector     PINK         = <0.83635, 0.00000, 0.88019>;//INDIGO
vector     PURPLE = <0.39257, 0.00000, 0.71612>;//PURPLE
vector     WHITE        = <1.000, 1.000, 1.000>;//WHITE
vector     BLACK        = <0.000, 0.000, 0.000>;//BLACKvector     ORANGE = <0.87130, 0.41303, 0.00000>;//orange
integer whiteRezzedNum=0;
integer blackRezzedNum=0;

 /***********************************************
*  getInventoryist()
*  used to read notecard settings
***********************************************/
list getInventoryList()
    {
    list       result = [];
    integer    n = llGetInventoryNumber(INVENTORY_ALL);
    integer    i = 0;

    while(i < n)
    {
        result += llGetInventoryName(INVENTORY_ALL, i);
        ++i;
    }
    return result;
 }
 key kk (string kko){
    return llList2Key(llParseString2List(kko, [":"], []),1);
}//end function

key     gSetupQueryId; //used for reading settings notecards
list    gInventoryList;//used for reading settings notecards
string     gSetupNotecardName="0_config";//used for reading settings notecards
integer gSetupNotecardLine;//used for reading settings notecards
list signX;
list signY;
list signZ;
 integer signIndex=0;
integer counterX=1;
integer counterY=1;
integer counterZ=1;
string chairName;
integer counter;
vector  startPos;
    integer j;
        integer k;
integer target;
list path;
list gameChairs;
   //gets a vector from a string

    
/***********************************************
*  readSettingsNotecard()
*  |-->used to read notecard settings
***********************************************/
readSettingsNotecard()
{
   gSetupNotecardLine = 0;
   gSetupQueryId = llGetNotecardLine(gSetupNotecardName,gSetupNotecardLine); 
}

/***********************************************
*  random_float()
*  |-->Produces a random integer
***********************************************/ 
float random_float( float min, float max ){
 return min + ( llFrand( max - min + 1 ) );
}

init(){
  //initialize iterators
        integer ii=0;
        integer iii=0;
        whiteRezzedNum=0;
        blackRezzedNum=0; 
        chairs=[];   
        if (llGetObjectDesc()=="Black Chair")   {
            //kill all exisiting chairs
                llRegionSay(SERVER_CHANNEL, "CMD:die");
                llRegionSay(SERVER_CHANNEL, "CMD:die");    
                llRegionSay(SERVER_CHANNEL, "CMD:die");    
                llRegionSay(SERVER_CHANNEL, "CMD:die");    
                llRegionSay(SERVER_CHANNEL, "CMD:die");    
                llRegionSay(SERVER_CHANNEL, "CMD:die");    
                llRegionSay(SERVER_CHANNEL, "CMD:die");    
                llRegionSay(SERVER_CHANNEL, "CMD:die");
                llRezObject("Teacher Chair", llGetPos()+<0,4,3>,  ZERO_VECTOR, <-0.00000, -0.00000, -1.00000, 0.00000>,0);
        
        }          
            //rez chairs 
            string chairToRez = llGetObjectDesc();
            vector myPos = llGetPos();
                for (iii=0;iii<REZNUM;iii++){
                        llRezObject(chairToRez,myPos+<2,2,5>, ZERO_VECTOR, ZERO_ROTATION,0);
                        llSleep(2);
                }
}
default{


    on_rez(integer start_param) {
        llResetScript();    
    }

    state_entry() {
        llSetStatus(STATUS_PHYSICS|STATUS_PHANTOM , FALSE);
        debug("rezzer","Chair Rezzer is ready!");     
        llSetText("Ready", GREEN, 1.0);                 
         llListen(MENU_CHANNEL, "", llGetOwner(), "");
       //listen to group channel incase user must specify via dialog a group theyd like to choose
       startPos = llGetPos();
       counter=0;
       llSetText("Loading", RED, 1.0);
       readSettingsNotecard();
        facilitators+=llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM);
    }//end state_entry    
    dataserver(key queryId, string data){
         
        if(queryId == gSetupQueryId) {
            if(data != EOF){
                if (llGetSubString(data,0,1)=="/") return;//its a comment                
                list fieldData= llParseString2List(data, ["|"], []);               
                string field= llList2String(fieldData,0);                          
                  if (field=="channel") SERVER_CHANNEL=llList2Integer(fieldData ,1);else
                     if (field== "facilitator"){ 
                                facilitators+=llStringTrim(llToLower(llList2String(fieldData ,1)),STRING_TRIM);
                    }else
                    if (field=="path"){ 
                        path+=getVector(llList2String(fieldData,1));
                        llSetText("Reading Paths: "+(string)llGetListLength(path), GREEN, 1.0);
                    }else
                    if (field=="reznum") REZNUM= llList2Integer(fieldData,1);    
                gSetupQueryId = llGetNotecardLine(gSetupNotecardName,++gSetupNotecardLine); 
            }//endif data != EOF
            else  {
                if (SERVER_CHANNEL!=-1){
                    state go;
                }
            }   
        }//endif queryId
    }//end dataserver   
}
state go{
    state_entry() {
        llSetText("Touch to Rez Chairs", GREEN, 1.0);
        MENU_CHANNEL = random_integer(-322122,-992312);
        llListen(MENU_CHANNEL, "", "", "");
        llListen(SERVER_CHANNEL,"","","");
        llRegionSay(SERVER_CHANNEL, "ACTIVE CHAIRS|ALL|"+(string)llList2CSV(activeWhiteChairs)+"|"+(string)llList2CSV(activeWhiteChairSitters)+"|"+(string)llList2CSV(activeBlackChairs)+"|"+(string)llList2CSV(activeBlackChairSitters));
    }
    touch_start(integer num_detected) {
        
        string detectedName = llKey2Name(llDetectedKey(0));
        if (isFacilitator(detectedName)){
                llDialog(llDetectedKey(0), "Please choose a function", ["Rez All Pods","Rez Teacher","Rez Student", "Rez Instr."], MENU_CHANNEL);
                
     
      
                
        }else llInstantMessage(llDetectedKey(0), "Sorry you are not allowed to click this rezzer!");
      
    } 
   listen(integer channel, string name, key id, string str) {
            listen_cmdList = llParseString2List(str, ["|"], []);        
                listen_cmd = s(llList2String(listen_cmdList,0));
                 if (listen_cmd=="GET ACTIVE CHAIRS"){
                         if (llGetObjectName()=="White Rezzer") return;
                        llRegionSay(SERVER_CHANNEL, "CMD:ACTIVE CHAIRS|"+(string)id+"|"+(string)llList2CSV(activeWhiteChairs)+"|"+(string)llList2CSV(activeWhiteChairSitters)+"|"+(string)llList2CSV(activeBlackChairs)+"|"+(string)llList2CSV(activeBlackChairSitters));
                    }else
           if (isFacilitator(llKey2Name(id))){
               if (str=="Rez All Pods"){
                   init();
               }else
               if (str=="Rez Teacher"){
                   llRezObject("Teacher Chair", llGetPos()+<0,4,3>,  ZERO_VECTOR, <-0.00000, -0.00000, -1.00000, 0.00000>,0);
               }else
               if (str=="Rez Student"){
               llRezObject("Black Chair",<2,2,5>, ZERO_VECTOR, ZERO_ROTATION,0);
               }else
               if (str=="Rez Instr."){
               llRezObject("White Chair",<2,2,5>, ZERO_VECTOR, ZERO_ROTATION,0);
               }
            }
            else           
            //someone has sat on a chair - message comes from a chair                           
                            if (listen_cmd=="REGISTER POD SITTER"){                                
                                //CMD:REGISTER POD SITTER|CHAIRID:1|TYPE:Black Chair|SITTER:a3a48849-7228-4f82-92b1-7838e3853d4a

                                //      llRegionSay(SERVER_CHANNEL,"CMD:REGISTER POD SITTER|CHAIRID:"+(string)myChairId+"|TYPE:TYPE|SITTER:"+(string)llGetLinkKey(totalPrims+1));
                                chairId= i(llList2String(listen_cmdList,1));
                                type= s(llList2String(listen_cmdList,2));
                                sitter = llKey2Name(_k(llList2String(listen_cmdList,3)));
                                if (type == "White Chair"){                                                        
                                    activeWhiteChairs+=chairId;
                                    activeWhiteChairSitters+=sitter;
                                }else
                                if (type == "Black Chair"){
                                    activeBlackChairs+=chairId;
                                    activeBlackChairSitters+=sitter;
                                }
                            }else
        //someone has jumped off a chair - message comes from a chair                    
                            if (listen_cmd=="DEREGISTER POD SITTER"){
                                chairId= i(llList2String(listen_cmdList,1));
                                type= s(llList2String(listen_cmdList,2));
                                sitter = llKey2Name(s(llList2String(listen_cmdList,3)));
                                if (type == "White Chair"){
                                    activeWhiteChairs= list_cast(activeWhiteChairs);
                                    placeinlist = llListFindList(activeWhiteChairs , [chairId]);
                                    if (placeinlist != -1){
                                        activeWhiteChairs = llDeleteSubList(activeWhiteChairs , placeinlist, placeinlist);
                                        //find name
                                        activeWhiteChairSitters=llDeleteSubList(activeWhiteChairSitters, placeinlist, placeinlist);
                                    }                                    
                                }else
                                if (type == "Black Chair"){                           
                                    placeinlist = llListFindList(activeBlackChairs , [chairId]);
                                    activeBlackChairs = list_cast(activeWhiteChairs);        
                                    if (placeinlist != -1){
                                        activeBlackChairs = llDeleteSubList(activeBlackChairs , placeinlist, placeinlist);
                                        activeBlackChairSitters=llDeleteSubList(activeBlackChairSitters, placeinlist, placeinlist);
                                    }                                    
                                }
                            }//deregister
                
                     
   }
   object_rez(key id) {
       
       if (llKey2Name(id)=="White Chair"){
           llRemoteLoadScriptPin(id, "0_GAMECHAIR", 4452, TRUE, whiteRezzedNum++);
       }else
       if (llKey2Name(id)=="Black Chair"){
           llRemoteLoadScriptPin(id, "0_GAMECHAIR", 4452, TRUE, blackRezzedNum++);
           
       }else
       if (llKey2Name(id)=="Teacher Chair"){
           llRemoteLoadScriptPin(id, "0_GAMECHAIR", 4452, TRUE, 0);
       }      
       chairs+=id;
     
       
   }
   
    /***********************************************
    *  changed event
    *  |-->Every time the inventory changes, reset the script
    *        
    ***********************************************/
    changed(integer change) {
         if (change ==CHANGED_INVENTORY){         
             llResetScript();
         }//endif
     }//end changed state
}//endstate

