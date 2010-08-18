// teamViewUpdate.lsl
/*********************************************
*  Copyrght (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
*  This script is part of the SLOODLE Project see http://sloodle.org
*
* teamViewUpdate.lsl
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
*
*/ 
integer ROW_CHANNEL;
string stringToPrint;
list lines;
integer numStudents;
integer totalPages;
list userDetails;
integer index;
integer index_teamScores;
string SCRIPT_NAME;
integer index_getClassList;
integer index_selectTeams;
integer DISPLAY_DATA                                                        =-774477; //every time the display is updated, data goes on this channel
integer WEB_UPDATE_CHANNEL                                        =-64000; // data we receive from an http request from httpIn handler
integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
integer SETTEXT_CHANNEL                                                =-776644;//hover text channel
integer SOUND_CHANNEL                                                     = -34000;//sound requests
integer DISPLAY_PAGE_NUMBER_STRING                            = 304000;//page number xy_text
integer XY_TITLE_CHANNEL                                                  = 600100;//title xy_text
integer XY_TEAM_CHANNEL                                                = -9110;//display xy_channel
integer XY_DETAILS_CHANNEL                                          = 700100;//instructional xy_text
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST     = -1928374651;//translation channel
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE     = -1928374652;//translation channel
integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
integer PRIM_PROPERTIES_CHANNEL                                =-870870;//setting highlights
integer DISPLAY_BOX_CHANNEL=-870881;
integer SLOODLE_CHANNEL_OBJECT_DIALOG                   = -3857343;//configuration channel
integer SET_COLOR_INDIVIDUAL                                       = 8888999;//row text color channel                                                                    
integer AWARD_DATA_CHANNEL                                        =890;
integer ANIM_CHANNEL                                                      =-77664251;//animation trigger channel
integer PAGE_SIZE=10; //can display only 10 users at once.
integer SET_ROW_COLOR= 8888999;
integer PLAYERNAME=0; //constant which defines a list postion our specific data is in to make code more readable
integer PLAYERPOINTS=1; //constant which defines a list postion our specific data is in to make code more readable
integer SLOTCHANNEL=2; //constant which defines a list postion our specific data is in to make code more readable
integer AVUUID=3; //constant which defines a list postion our specific data is in to make code more readable
integer MAX_XY_LETTER_SPACE=30;
list rows;
string authenticatedUser;
integer counter;
string senderUuid;
string statusLine;
string connected;
list awardGroups;
list courseGroups;
integer currentAwardId;
string current_grp_membership_group;//to keep track of which group we selected in group membership displaymode
integer current_grp_mbr_index; //keep track of which of group members we are viewing
list dataLines;
integer numGroups;
key owner;
string currentView;
list rows_teamScores;
list rows_getAwardGrps;
list rows_getAwardGrpMbrs;
list rows_selectTeams;
list rows_selectAward;
list rows_getClassList;
integer previousAwardId;
integer selectedAwardId=0;
integer currentIndex;
string currentGroup;
string sortMode="balance";
list pointMods=["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"]; //this is a list of values an owner can modify a users points to //["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000",
list modifyPointList; //this is a temp list that is used to store point modifications in.  When Save is pressed on a menu, these points are applied to the users point bank
integer modPoints;
string myUrl;
string displayData;
list facilitators;
integer SCOREBOARD_CHANNEL;
reinitialise()
        {
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", NULL_KEY);
            llResetScript();
}
// *************************************************** HOVER TEXT COLORS

vector     RED            = <0.77278,0.04391,0.00000>;//RED
vector     ORANGE = <0.87130,0.41303,0.00000>;//orange
vector     YELLOW         = <0.82192,0.86066,0.00000>;//YELLOW
vector     GREEN         = <0.12616,0.77712,0.00000>;//GREEN
vector     BLUE        = <0.00000,0.05804,0.98688>;//BLUE
vector     PINK         = <0.83635,0.00000,0.88019>;//INDIGO
vector     PURPLE = <0.39257,0.00000,0.71612>;//PURPLE
vector     WHITE        = <1.000,1.000,1.000>;//WHITE
vector     BLACK        = <0.000,0.000,0.000>;//BLACKvector     ORANGE = <0.87130, 0.41303, 0.00000>;//orange
list colors = [GREEN, YELLOW, ORANGE, BLUE, RED, PINK,PURPLE];
vector getVector(string vStr){
        vStr=llGetSubString(vStr, 1, llStringLength(vStr)-2);
        list vStrList= llParseString2List(vStr, [","], ["<",">"]);
        vector output= <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2)>;
        return output;
}//end getVector

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
integer DEBUG=FALSE;
debug(string str){
            if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==PRIM_MATERIAL_FLESH){
                llOwnerSay(llGetScriptName()+" " +str);
           }
        }
  integer debugCheck(){
            if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
                return TRUE;
            }
                else return FALSE;
            
        }
/***********************************************
*  isFacilitator()
*  |-->is this person's name in the access notecard
***********************************************/
integer isFacilitator(string avName){
    if (llListFindList(facilitators, [llStringTrim(llToLower(avName),STRING_TRIM)])==-1) return FALSE; else return TRUE;
}

        
/***********************************************
*  clearHighlights -- makes sure all highlight rows are set to 0 alpha
***********************************************/
clearHighlights(){
    integer c;
    for (c=0;c<=9;c++){
        llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:"+(string)(c)+"|POWER:OFF|COLOR:GREEN",NULL_KEY);
          
        llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,"CMD:TEXTURE|row:"+(string)(c)+"|col:0|TEXTURE:totallyclear",NULL_KEY);
    } 
}
/****************************************************************************************************
* center(string str) displays text on the title bar 
****************************************************************************************************/
center(string str){
    integer len = llStringLength(str);
    string spaces="                    ";
    integer numSpacesForMargin= (20-len)/2;
    string margin = llGetSubString(spaces, 0, numSpacesForMargin);
    string stringToPrint = margin+str+margin;    
    llMessageLinked(LINK_SET, XY_TITLE_CHANNEL,stringToPrint,NULL_KEY);
}

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer( integer min, integer max ){
  return min + (integer)( llFrand( max - min + 1 ) );
}

centerDetails(string str){
                 integer len = llStringLength(str);
                string spaces="                    ";
                integer numSpacesForMargin= (30-len)/2;
                string margin = llGetSubString(spaces, 0, numSpacesForMargin);
                string stringToPrint = margin+str+margin;
                llMessageLinked(LINK_SET, XY_DETAILS_CHANNEL, stringToPrint, NULL_KEY);  
}
string SLOODLE_EOF = "sloodleeof";
 string sloodleserverroot;
integer sloodlecontrollerid;
string sloodlecoursename_short;
string sloodlecoursename_full;
integer sloodleid;
string scoreboardname;
string currentAwardName;
 integer sloodle_handle_command(string str) {         
     if (str=="do:requestconfig"){
            llResetScript();
        }  
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
                currentAwardName=value2;
               
               centerDetails(currentAwardName);
                
                scoreboardname= value2;  
               
            }
            else        
            if (str == SLOODLE_EOF) {
                
                return TRUE;
                
            }
        return FALSE;
    }
default{    
 //on_rez event - Reset Script to ensure proper defaults on rez
    on_rez(integer start_param) {
        llResetScript();       
    }
    state_entry() {
        integer c;
      for (c=0;c<=4;c++){
        llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:"+(string)(11+c)+"|POWER:ON|"+(string)WHITE,NULL_KEY);
         
        }
          llMessageLinked(LINK_SET, XY_TEAM_CHANNEL,"                                                                                       ", NULL_KEY);
        SCRIPT_NAME=llGetScriptName();
         owner=llGetOwner();
         //llMessageLinked(LINK_SET, UI_CHANNEL, "CMD:REGISTER VIEW|INDEX:0|TOTALITEMS:0|COMMAND:cmd{index}|CHAN:channel",NULL_KEY);
         //create a random ROW_CHANNEL index within a range of 5,000,000 numbers - to avoid conflicts with other scoreboards
          //this user channel will accept messages from the owner when the owner clicks on a scoreboard row        
          ROW_CHANNEL=random_integer(-2483000,3483000);

         //listen to all userchannels so we can detect scoreboard row clicks          
         for (c=0;c<4;c++){
            llListen(ROW_CHANNEL+c, "", "", "");  
         }//endfor
         //initialize tempory storage for row calculations          
         modifyPointList=[0,0,0,0,0,0,0,0,0,0];      
         //add the owner to the facilitators list 
         facilitators+=llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM);
    }
    
    link_message(integer sender_num, integer channel, string str, key id) {  
        
             if (channel==SLOODLE_CHANNEL_OBJECT_DIALOG){
                if (sloodle_handle_command(str)==TRUE) state go;         
             }
    }
}
state go{
    on_rez(integer start_param) {
        llResetScript();
    }
    state_entry() {
        currentView="Team Top Scores";
    }             
    
     link_message(integer sender_num, integer channel, string str, key id) {                          
                   if (channel==SLOODLE_CHANNEL_OBJECT_DIALOG){
                sloodle_handle_command(str);
            }//endif SLOODLE_CHANNEL_OBJECT_DIALOG
                  if (channel==PLUGIN_RESPONSE_CHANNEL){
                      if (str=="do:requestconfig") llResetScript();
                  }
            /*********************************
            * Handle the UI_CHANNEL 
            *********************************/
             if (channel==UI_CHANNEL){
                
                 list dataBits = llParseString2List(str,["|"],[]);
                 string command = s(llList2String(dataBits,0));
                 /*********************************
                 * Capture current award - this messageg gets fired when a new award has been chosen               
                 *********************************/                 
                 if(command=="AWARD SELECTED"){
                     currentAwardId=i(llList2String(dataBits,1));
                     //connect display to newly selected award                     
                 }//endif AWARD SELECTED
                 else
                 if(command=="GAMEID"){
                   index_teamScores=0;
                   authenticatedUser= "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
                    llMessageLinked(LINK_SET, PLUGIN_CHANNEL+5, "awards->getTeamPlayerScores&currency=Credits"+authenticatedUser+"&sloodlemoduleid="+(string)currentAwardId+"&index="+(string)index_teamScores+"&maxitems=4&sortmode=name", "http2");
                    
                 }else
                    /*********************************
                   * Capture UPDATE VIEW CLASS LIST 
                   *********************************/               
                 if (command=="UPDATE VIEW CLASS LIST"||command=="UPDATE DISPLAY"){
                     if (currentView =="Team Top Scores"){
                         //COMMAND:GETCLASSLIST|SORTMODE:"+sortMode    
                         authenticatedUser= "&sloodleuuid="+(string)owner+"&sloodleavname="+llEscapeURL(llKey2Name(owner));    
                         llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "awards->getTeamPlayerScores&currency=Credits"+authenticatedUser+"&sloodlemoduleid="+(string)currentAwardId+"&index="+(string)index_teamScores+"&maxitems=4&sortmode=name", "http2");   
                                    
                     }//end if
                 }//command

             }//end UI_CHANNEL
             else 
            /*****************************************************************
            * Handle the PLUGIN_RESPONSE_CHANNEL     
            *****************************************************************/        
     
            if (channel==PLUGIN_RESPONSE_CHANNEL+5||PLUGIN_RESPONSE_CHANNEL){
                dataLines = llParseStringKeepNulls(str,["\n"],[]);           
                //get status code
                list statusLine =llParseStringKeepNulls(llList2String(dataLines,0),["|"],[]);
                integer status =llList2Integer(statusLine,0);
                string descripter = llList2String(statusLine,1);
                key authUserUuid = llList2Key(statusLine,6);
                string response = s(llList2String(dataLines,1));
                index = i(llList2String(dataLines,2));                 
                integer totalGroups= i(llList2String(dataLines,3));
                string data = llList2String(dataLines,4);
                
              
                /*********************************
                * Get Team Scores response -
                * This is the response from requesting top scores 
                *********************************/
                if (response=="awards|getTeamPlayerScores"){
                    if (status==1){
                        list grpsData = llParseString2List(data, ["|"], []);
                        rows_teamScores=[];
                        //update arrows & page number
                        index_teamScores = index;
                        //llMessageLinked(LINK_SET, UI_CHANNEL, "COMMAND:UPDATE ARROWS|VIEW:"+currentView+"|INDEX:"+(string)index_teamScores+"|TOTALITEMS:"+(string)totalGroups,SCRIPT_NAME);
                        stringToPrint="";
                        //set up display data for the web
                        displayData="CURRENT VIEW:"+currentView;                        
                        for (counter=0;counter<totalGroups;counter++){
                            list grpData =llParseString2List(llList2String(grpsData,counter), [","], []); //parse the message into a list
                            string grpName =s(llList2String(grpData,0));
                            integer grpPoints = i(llList2String(grpData,1));                            
                            displayData +="\n"+grpName+"|"+(string)grpPoints;
                            llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:"+(string)(11+counter)+"|POWER:ON|"+(string)getVector(llList2String(colors,counter)),NULL_KEY);
                            //To right align points, we must count how many characters are used by the text printed on one row, then compute how many spaces we need 
                            integer spaceLen=MAX_XY_LETTER_SPACE - (llStringLength    ((string)((counter+1)))+2+llStringLength(grpName)+llStringLength((string)grpPoints)); 
                            string text=(string)(index+counter+1)+") "+grpName;
                            //here we add the number of spaces by grabbing a chunk of spaces from the string of spaces below below and appending it to text
                            text+=llGetSubString("                              ", 0, spaceLen-1) + (string)grpPoints;
                            //now add the stringToPrint to the end. This will effectively placed the correct number of spaces in between the name, and the points
                            rows_teamScores+=[]+grpName;    
                            stringToPrint+=text; 
                        }//for
                        //now send this text to the xy_prims
                      llMessageLinked(LINK_SET, XY_TEAM_CHANNEL,stringToPrint, NULL_KEY);
                                                stringToPrint="";
                    }//status==1    
                    else
                    if (status==-500700){
                        stringToPrint="No teams have been added yet. Please select teams first.";
                           //now send this text to the xy_prims
                        llMessageLinked(LINK_SET, XY_TEAM_CHANNEL,stringToPrint, NULL_KEY);
                       
                        stringToPrint="";
                    }                    
               }//response!="awards|getTeamPlayerScores"              
        }//channel!=PLUGIN_RESPONSE_CHANNEL
    }//linked message event     
}//default state
