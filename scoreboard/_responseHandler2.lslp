//* response_handlers2.lsl
/*********************************************
*  Copyrght (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
*  This script is part of the SLOODLE Project see http://sloodle.org
*
* response_handlers2.lsl
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
integer index;
integer index_teamScores;
integer index_getClassList; 
integer index_selectTeams;
integer DISPLAY_DATA=-774477; //every time the display is updated, data goes on this channel      
integer DISPLAY_BOX_CHANNEL=-870881;                                                  
integer WEB_UPDATE_CHANNEL                                        =-64000; // data we receive from an http request from httpIn handler
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
vector ORANGE=<1.08262, 0.66319, 0.00000>;
vector BLACK=<0.00000, 0.00000, 0.00000>;
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
integer SCOREBOARD_CHANNEL=-1;
// *************************************************** HOVER TEXT COLORS
vector     RED            = <0.77278, 0.04391, 0.00000>;//RED
vector     YELLOW         = <0.82192, 0.86066, 0.00000>;//YELLOW
vector     GREEN         = <0.12616, 0.77712, 0.00000>;//GREEN
vector     PINK         = <0.83635, 0.00000, 0.88019>;//INDIGO
vector     WHITE        = <1.000, 1.000, 1.000>;//WHITE
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
  integer debugCheck(){
            if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==4){
                return TRUE;
            }
                else return FALSE;
            
        }
        debug(string str){
            if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==PRIM_MATERIAL_FLESH){
                llOwnerSay(llGetScriptName()+" " +str);
           }
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
left(string str){
                     llMessageLinked(LINK_SET, XY_TITLE_CHANNEL,str,NULL_KEY);
    }

/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer( integer min, integer max ){
  return min + (integer)( llFrand( max - min + 1 ) );
}
/***********************************************
*  displayModMenu(string userName,integer userPoints, integer row_channel)
*  Is used to display a dialog menu so owner can modify the points awarded 
***********************************************/
displayModMenu(string name,string userPoints, string row_channel,key avKey){
                     integer points=i(userPoints);       
                     integer channel = i(row_channel);     
                     string userName   = s(name);       
                     integer rowNum =  channel-ROW_CHANNEL;
                     key av_key= k(avKey);
                     //llSay(0,"++++++++++++++ points"+(string)points+" channel "+(string)channel+" username: "+userName+" rowNum: "+(string)rowNum+ "avKey: "+(string)av_key);
                     modPoints = points + llList2Integer(modifyPointList,rowNum);
                     if (modPoints <0) modPoints=0;   
                     if (isFacilitator(llKey2Name(k(avKey)))==FALSE) return;                                                      
                     llDialog(k(avKey)," -~~~ Modify iPoints awarded: "+(string)userPoints+" ~~~-\n"+userName+"\nModify Points to: "+(string) modPoints, ["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"] , channel);
}
/***********************************************
*  makeTransaction(string userName,integer userPoints, integer row_channel)
*  makes a transaction for the user to the current award
******************************************************/
makeTransaction(string avname,key avuuid,integer points){    
            //plugin:awards refers to awards.php in sloodle/mod/hq-1.0/plugins/awards.php              
            //send the plugin function request via the plugin_channel
            authenticatedUser= "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
            llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "awards->addTransaction"+authenticatedUser+"&sloodlemoduleid="+(string)currentAwardId+"&sourceuuid="+(string)llGetOwner()+"&avuuid="+(string)avuuid+"&avname="+llEscapeURL(llKey2Name(avuuid))+"&amount="+(string)points+"&currency=Credits&details="+llEscapeURL("Game Points,"+llKey2Name(avuuid) ), NULL_KEY);     
}

string SLOODLE_EOF = "sloodleeof";
string sloodleserverroot;
integer sloodlecontrollerid;
string sloodlecoursename_short;
string sloodlecoursename_full;
integer sloodleid;
string scoreboardname;

 integer sloodle_handle_command(string str) {
     if (str=="do:requestconfig")llResetScript();         
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
default{    
//on_rez event - Reset Script to ensure proper defaults on rez
    on_rez(integer start_param) {
        llResetScript();       
    }
 
    state_entry() {
         owner=llGetOwner();
         //llMessageLinked(LINK_SET, UI_CHANNEL, "CMD:REGISTER VIEW|INDEX:0|TOTALITEMS:0|COMMAND:cmd{index}|CHAN:channel",NULL_KEY);
         //create a random ROW_CHANNEL index within a range of 5,000,000 numbers - to avoid conflicts with other scoreboards
          //this user channel will accept messages from the owner when the owner clicks on a scoreboard row        
          ROW_CHANNEL=random_integer(-2483000,-3483000);
          integer c=0;
         //listen to all userchannels so we can detect scoreboard row clicks          
         for (c=0;c<10;c++){
            llListen(ROW_CHANNEL+c, "", "", "");  
         }//endfor
         //initialize tempory storage for row calculations          
         modifyPointList=[0,0,0,0,0,0,0,0,0,0];      
         //add the owner to the facilitators list
         ////llStringTrim(llToLower(avName),STRING_TRIM)] 
          facilitators+=llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM);      
    }
    
    link_message(integer sender_num, integer channel, string str, key id) {      

             if (channel==SLOODLE_CHANNEL_OBJECT_DIALOG){
                sloodle_handle_command(str);
            }//endif SLOODLE_CHANNEL_OBJECT_DIALOG
            else
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
                 /*********************************
                 * Capture current button                 
                 *********************************/                 
                 if (command=="SET CURRENT BUTTON"){ 
                     currentView= s(llList2String(dataBits,2));
                 }//endif
                 else  
                /*********************************
                 * Capture UPDATE ARROWS                 
                 *********************************/                            
                 if (command=="UPDATE ARROWS"){
                     currentView=s(llList2String(dataBits,1));
                     currentIndex = i(llList2String(dataBits,2));

                 }//endif
                 else
                /*********************************
                 * Capture current group                 
                 *********************************/                            
                 if (command=="SET CURRENT GROUP"){
                     currentGroup=s(llList2String(dataBits,1));
                 }//endif
                 else
                 /***********************************************************************************
                 * Capture DISPLAY MENU - scoreboard row clicks
                 ***********************************************************************************/                              
                 if (command=="DISPLAY MENU"){                     
                    //since the XY Display board can be used to display different lists besides the user list, we must first check which displayMode is current.                         
                    integer rowNum =i(llList2String(dataBits,1));                 
                    key av= k(llList2String(dataBits,2));
                    //make sure it was the owner who clicked on the row
                    if (isFacilitator(llKey2Name(av))){
                        authenticatedUser= "&sloodleuuid="+(string)av+"&sloodleavname="+llEscapeURL(llKey2Name(av));               
                       if (currentView=="Top Scores"||currentView=="Sort by Name"){
                           
                     //the rowNum is the row that was clicked on            
                             rowNum =i(llList2String(dataBits,1));                 
                             //each row number consists of: username,points,channel,uuid
                             list user = llList2List(rows_getClassList, rowNum* 4, rowNum* 4+ 3);  
                             //llSay(0,"-******************************"+llList2CSV(user));
                             //llSay(0,"-******************************"+llList2CSV(rows_getClassList));   
                             //1120, 2580438, 31ce8c0c-618e-400b-927e-1b5603d028fa, Jock Bing
                             //the user list above has 3 elements:  avName,avPoints,Channel 
                             //Each row will communicate on a separate link message channel.  We will do this to identify which user
                             //a menu dialog update corresponds to.  We have to use this approach because the menu buttons used to update
                             //a users ipoint balance are numeric - ie: 100,500,1000 etc.
                             //therefore when our listen event receives a message with the value "500" we have to somehow tie that information
                             //to a particular user.  We can do this by querying the channel, and then checking our userList to see which slot
                             //the channel has been saved on, and then, in turn, discover which user the update pertains to.                   
                             debug("NAME:"+llList2String(user,0)+" POINTS:"+llList2String(user,1)+ " CHANNEL:"+llList2String(user,2)+" AVKEY:"+llList2String(user,3));
                             displayModMenu("NAME:"+llList2String(user,0),"POINTS:"+llList2String(user,1),"CHANNEL:"+llList2String(user,2),"AVKEY:"+(string)av);
                             user=[];
                             dataBits=[];  
                             command="";                              
                    }//endif currentView
                    
                   }//av!=owner
                   else llOwnerSay("******************************* Sorry, not a facilitator");
                 }//end command==DISPLAY MENU
             }//end UI_CHANNEL
             else 
            /*****************************************************************
            * Handle the PLUGIN_RESPONSE_CHANNEL     
            *****************************************************************/           
            if (channel==PLUGIN_RESPONSE_CHANNEL){
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
                
                authenticatedUser= "&sloodleuuid="+(string)authUserUuid+"&sloodleavname="+llEscapeURL(llKey2Name(authUserUuid));                             
               
            /*********************************
               * Handle the user|getPlayerScores  response 
               *********************************/                      
               if (response=="awards|getPlayerScores"){                    
                    llMessageLinked(LINK_SET,SETTEXT_CHANNEL,"DISPLAY::userUpdate display|STRING::                                   |COLOR::"+(string)PINK+"|ALPHA::1.0",NULL_KEY);
                    modifyPointList=[0,0,0,0,0,0,0,0,0,0];    
                    /*******************
                    * PARSE DATA
                    ********************/
                    index_getClassList= i(llList2String(dataLines,2));
                    
                    //get number of students
                    numStudents = i(llList2String(dataLines,3));
                    //getClassList returns upto 10 rows of users
                   
                    list userLines = llList2List(dataLines, 4, llGetListLength(dataLines)-1);       
					if (status==80002){
                                 stringToPrint="No players have joined the     game yet.";
                       //now send this text to the xy_prims
                   llMessageLinked(LINK_SET, XY_TEXT_CHANNEL,stringToPrint, NULL_KEY);            
                   //send displayDataStr
                   llMessageLinked(LINK_SET, DISPLAY_DATA, displayData,NULL_KEY);      
                         }
                         else                  
                        if (currentView=="Top Scores"||currentView=="Sort by Name"){
                            
                                left("Top Scores");

                            //update arrows
                            llMessageLinked(LINK_SET, UI_CHANNEL, "COMMAND:UPDATE ARROWS|VIEW:"+currentView+"|INDEX:"+(string)index_getClassList+"|TOTALITEMS:"+(string)numStudents, NULL_KEY);
                            //*********************************
                            // PRINT USER LIST
                            //*********************************
                            //initialize our printstring                  
                            string stringToPrint="";
                            /*User data will be in this format
                            * UUID:uuid|AVNAME:avname|BALANCE:balance|DEBITS:debits
                            */
                            integer len = llGetListLength(userLines);
                             displayData="CURRENT VIEW:"+currentView+"\n";   
                             rows_getClassList=[];
                            for (counter=0;counter<len;counter++){
                                 list user = llParseString2List(llList2String(userLines,counter),["|"],[]);        
                                //extrapolate user data                                                                            
                                integer userPoints=i(llList2String(user,2));
                                string userName=s(llList2String(user,0));
                                key userKey = k(llList2String(user,1));                        
                                // To keep track of users and points internally, we use a strided LIST called "Rows"
                                //buildDisplayDataStr
                                displayData+=(string)userKey+"|"+userName+"|"+(string)userPoints;
                                if (counter!=len)displayData+="\n";                            
                                //row 0 [username][userPoints][row_channel][uuid]
                                //row 1 [username][userPoints][row_channel][uuid]
                                //row 2 [username][userPoints][row_channel][uuid]
                                //...
                                //row 9 [username][userPoints][row_channel][uuid]
                                // 
                                //so, we will replace each row with the updated data that was received from MOODLE
                                //You'll notice we are replacing i*4 to i*4+4 because our data consists of 4 fields username,userpoints,row_channel,uuid
                               //the only confusing field to understand may be row_channel.  I have specified a row_channel, because each row must talk on a seperate channel
                               //when using dialogs  
                               
                               rows_getClassList=llListReplaceList(rows_getClassList, [userName,userPoints,ROW_CHANNEL+counter,llStringTrim(userKey,STRING_TRIM)],counter*4,counter*4+4);
                               //concatinate string if it is over 20 chars so it fits the display
                               if (llStringLength(userName)>20){
                                   userName = llGetSubString(userName, 0, 19);
                               }//endif
                               //To right align points, we must count how many characters are used by the text printed on one row, then compute how many spaces we need 
                               integer spaceLen=MAX_XY_LETTER_SPACE - (llStringLength    ((string)((index_getClassList+counter+1)))+2+llStringLength(userName)+llStringLength((string)userPoints));
                               //calculate length of the index, the bracket, and username         
                               string text=(string)(index_getClassList+counter+1)+") "+userName;
                              //here we add the number of spaces by grabbing a chunk of spaces from the string of spaces below below and appending it to text
                              text+=llGetSubString("                              ", 0, spaceLen-1) + (string)userPoints;
                              //now add the stringToPrint to the end. This will effectively placed the correct number of spaces in between the name, and the points
                              stringToPrint+=text;   
                              text="";
                           }//endfor                   
                             //now send this text to the xy_prims
                  llMessageLinked(LINK_SET, XY_TEXT_CHANNEL,stringToPrint, NULL_KEY);            
                   //send displayDataStr
                   llMessageLinked(LINK_SET, DISPLAY_DATA, displayData,NULL_KEY);      
                     }//end  if (currentView=="Top Scores"||currentView=="Sort by Name")
                   
                   stringToPrint="";
                 return;
               }// user|getClassList 
               else 
               /*********************************
               * Handle the awards|makeTransaction response 
               *********************************/
               if (response=="awards|makeTransaction"){
                    //get user who initiated the request                                       
                    key sourceUuid =  k(llList2String(dataLines,2));
                    //get avUuid of avatar to make transaction for
                    key avUuid =   k(llList2String(dataLines,3));
                    //get avName
                    string avName = s(llList2String(dataLines,4));
                    //get points
                    integer points = i(llList2String(dataLines,5));
                    //search our rows for user which was updated
                    integer rowNum = llListFindList(rows_getClassList, [avName])/4;
                    //get the rowChannel of the user        
                    integer rowChannel = ROW_CHANNEL+rowNum;
                    if (rowNum!=-1){
                        //update our rows list with new point data            
                        //name,points,channel,avkey
                        rows_getClassList =  llListReplaceList(rows_getClassList,[avName,points,rowChannel,avUuid], rowNum*4,rowNum*4+3);
                    }  //endif                                                          
         }//awards|makeTransaction response
        }//channel!=PLUGIN_RESPONSE_CHANNEL
    }//linked message event
     listen(integer channel, string name, key id, string str) {   
           if ((channel >=ROW_CHANNEL) && (channel <= ROW_CHANNEL+10)){  
               if (isFacilitator(llKey2Name(id))==FALSE) return;
                    integer rowNum = channel - ROW_CHANNEL;
                    //now using this rowNum, we can reach into our rows list, and retrieve user specific data
                            
                    //Now determine if a number was pressed, or the (~~ SAVE ~~) Button was pressed
                    if (llListFindList(["-5","-10","-100","-500","-1000","+5","+10","+100","+500","+1000"],[str])!=-1){                            
                        //now just modify the value.            
                        //modify points
                        list user = llList2List(rows_getClassList, rowNum* 4, rowNum* 4 + 3);       
                        string avKey = llList2String(user,3);
                        integer currentPoints = llList2Integer(user,1);
                        makeTransaction(llKey2Name(avKey),avKey,(integer)str);     
                        //modifyPointList= llListReplaceList(modifyPointList,[newAmount], rowNum, rowNum);
                        //displayModMenu("NAME:"+llList2String(user, 0),"POINTS:"+llList2String(user, 1),"CHANNEL:"+llList2String(user,2),"AVKEY:"+avKey);//display mod menu again
                        user=[];
                    }//findlist             
                    
                   
              }// ((channel >=ROW_CHANNEL) && (channel <= ROW_CHANNEL+10))
    }
}//default state
