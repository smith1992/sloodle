 /*********************************************
*  Copyright (c) 2009 Paul Preibisch
*  Released under the GNU GPL 3.0
*  This script can be used in your scripts, but you must include this copyright header as per the GPL Licence
*  For more information about GPL 3.0 - see: http://www.gnu.org/copyleft/gpl.html
* 
*  quiz_handler.lsl 
*  This script is part of the SLOODLE Project see http://sloodle.org
*
*  Copyright:
*  Paul G. Preibisch (Fire Centaur in SL)
*  fire@b3dMultiTech.com  
* 
*
/**********************************************************************************************/
integer PAGE_SIZE=10; //amount of data rows to return
integer currentAwardId=-1;//current award id
string   currentAwardName;
integer previousQuizId;
list rows_selectQuiz;
string owner; //owner of the script
integer myQuizId=-1;
string myQuizName;
integer gameid;
integer XY_GAMEID_CHANNEL = 900100;
integer XY_QUIZ_CHANNEL=-1800100;

integer index=0;//the current row index we are viewing
// *************************************************** HOVER TEXT COLORS
vector     RED            = <0.77278, 0.04391, 0.00000>;//RED
vector     YELLOW         = <0.82192, 0.86066, 0.00000>;//YELLOW
vector     GREEN         = <0.12616, 0.77712, 0.00000>;//GREEN
vector     PINK         = <0.83635, 0.00000, 0.88019>;//INDIGO
vector     WHITE        = <1.000, 1.000, 1.000>;//WHITE
// *************************************************** HOVER TEXT VARIABLES
integer PLUGIN_RESPONSE_CHANNEL                                =998822; //sloodle_api.lsl responses
integer PLUGIN_CHANNEL                                                    =998821;//sloodle_api requests
integer XY_TEAM_CHANNEL                                                = -9110;//display xy_channel
integer SETTEXT_CHANNEL                                                =-776644;//hover text channel
integer SOUND_CHANNEL                                                     = -34000;//sound requests
integer DISPLAY_PAGE_NUMBER_STRING                            = 304000;//page number xy_text
integer XY_TITLE_CHANNEL                                                  = 600100;//title xy_text
integer XY_TEXT_CHANNEL                                                = 100100;//display xy_channel
integer XY_DETAILS_CHANNEL                                          = 700100;//instructional xy_text
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST     = -1928374651;//translation channel
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE     = -1928374652;//translation channel
integer DISPLAY_BOX_CHANNEL=-870881;
integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
integer PRIM_PROPERTIES_CHANNEL                                =-870870;//setting highlights
integer DISPLAY_DATA                                                        =-774477; //every time the display is updated, data goes on this channel
integer SLOODLE_CHANNEL_OBJECT_DIALOG                     = -3857343;//configuration channel
integer SET_COLOR_INDIVIDUAL                                        = 8888999;//row text color channel
integer ROW_CHANNEL;                                                                    
integer AWARD_DATA_CHANNEL                                        =890;
integer ANIM_CHANNEL                                                        =-77664251;//animation trigger channel
// *************************************************** TRANSLATION OUTPUT METHODS
string SLOODLE_TRANSLATE_HOVER_TEXT_BASIC = "hovertextbasic";
string  SLOODLE_TRANSLATE_LINK = "link";             // No output parameters - simply returns the translation on SLOODLE_TRANSLATION_RESPONSE link message channel
string  SLOODLE_TRANSLATE_SAY = "say";               // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_WHISPER = "whisper";       // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_SHOUT = "shout";           // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_REGION_SAY = "regionsay";  // 1 output parameter: chat channel number
string  SLOODLE_TRANSLATE_OWNER_SAY = "ownersay";    // No output parameters
string  SLOODLE_TRANSLATE_DIALOG = "dialog";         // Recipient avatar should be identified in link message keyval. At least 2 output parameters: first the channel number for the dialog, and then 1 to 12 button label strings.
string  SLOODLE_TRANSLATE_LOAD_URL = "loadurl";      // Recipient avatar should be identified in link message keyval. 1 output parameter giving URL to load.
string  SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";  // 2 output parameters: colour <r,g,b>, and alpha value
string  SLOODLE_TRANSLATE_IM = "instantmessage";     // Recipient avatar should be identified in link message keyval. No output parameters.
integer ADMIN_CHANNEL =82;  //used for dialog messages during setup
string ownerKey;
// *************************************************** LISTS TO HOLD FIELD VALUES OF DATAROW RECORD SETS
// *************************************************** AUTHENTICATION CONSTANTS
integer MENU_CHANNEL;
string   response;   //string used for linked_message stings
integer counter;//used with for loops
string dialogMessage;//used with dialogs
string currentTab;//currently selected tab
string currentSubMenuButton="s0";//last menu button pressed
integer drawerRight=-1;
integer drawerLeft=-1;
string currentSubMenuButton_studentsTab;
string currentSubMenuButton_groupsTab;
string currentSubMenuButton_prizesTab;
string currentSubMenuButton_configTab;
string myUrl;
list facilitators;
string SLOODLE_EOF = "sloodleeof";
string authenticatedUser;
list viewData;
string currentView;
string displayData;
integer selectedQuizId;


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
    for (c=0;c<9;c++){
        llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:"+(string)(c)+"|POWER:OFF|COLOR:"+(string)GREEN,NULL_KEY);
        llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,"CMD:TEXTURE|row:"+(string)c+"|col:0|TEXTURE:totallyclear",NULL_KEY);
           //llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,"CMD:TEXTURE|row:"+(string)counter+"|col:0|TEXTURE:n",NULL_KEY);
    } //endfor
}//end clearHighlights()

/***************************************************
*  SLOODLE TRANSLATION
*  @see: http://slisweb.sjsu.edu/sl/index.php/Translating_Sloodle_objects_in_Second_Life#Sloodle_LSL_Translation_Code
****************************************************/
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}

string leftAlign(string s,integer len){            
            integer marginLen= (integer)(len-llStringLength((string)s));
            integer j;
            string spaces="";
            for (j=0;j<len+1;++j){
                spaces+=" ";    
            }//for
            string margin = llGetSubString(spaces, 0, marginLen-1);
            string text = (string)(s)+margin;
            return text;
}//left align
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
*  clear()
*  |-->clears the xy display
***********************************************/ 
clear(){
        string blanks="";
        for (counter=0;counter<300;counter++){
            blanks+=" ";    
        }
        llMessageLinked(LINK_SET, DISPLAY_PAGE_NUMBER_STRING, "          ", "0");
        llMessageLinked(LINK_SET, XY_TITLE_CHANNEL, "                              ", "0");
        llMessageLinked(LINK_SET, XY_DETAILS_CHANNEL, "                              ", "0");        
        llMessageLinked(LINK_SET, XY_TEXT_CHANNEL, blanks, "0");
        blanks="";
}
/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer( integer min, integer max )
{
  return min + (integer)( llFrand( max - min + 1 ) );
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
        if (name == "set:quizmoduleid") myQuizId= (integer)value1;else 
        if (name == "set:quizname") myQuizName= (string)value1;else
        if (name =="set:sloodlecoursename_short") sloodlecoursename_short= value1; else
        if (name =="set:sloodlecoursename_full") sloodlecoursename_full= value1; else
        if (name =="set:sloodleid") {
            sloodleid= (integer)value1; 
            currentAwardName=value2;
            currentAwardId=sloodleid;    
        }
        else 
        if (name =="set:sloodleid") scoreboardname= value2; else
        if (name == SLOODLE_EOF) return TRUE;
         return FALSE;
    }

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
     
         owner=llGetOwner();
       
         MENU_CHANNEL=random_integer(-231111,-200);
         
           facilitators+=llStringTrim(llToLower(llKey2Name(llGetOwner())),STRING_TRIM);
    }

link_message(integer sender_num, integer channel, string str, key id) {
    /*
    * general handling of a button is
    * set current button
    * set highlight button
    * set title
    * set instructional text
    * execute
    */
     if (channel==SLOODLE_CHANNEL_OBJECT_DIALOG){
                sloodle_handle_command(str);
            }//endif SLOODLE_CHANNEL_OBJECT_DIALOG
     if (channel==PLUGIN_RESPONSE_CHANNEL){
                  list dataLines = llParseStringKeepNulls(str,["\n"],[]);           
                //get status code
                list statusLine =llParseString2List(llList2String(dataLines,0),["|"],[]);
                integer status =llList2Integer(statusLine,0);
                string descripter = llList2String(statusLine,1);
                string response = s(llList2String(dataLines,1));
                integer index = i(llList2String(dataLines,2)); 
                integer totalItems= i(llList2String(dataLines,3));
                list data = llList2List(dataLines, 4, llGetListLength(dataLines)-1);
                integer counter=0;            
                string stringToPrint;   
               
                if (response=="quiz|getQuizzes"){
                     left("Available Quizzes");
                    clearHighlights();      
                    currentView="Quizzes";                          
                        //update arrows & page number
                        rows_selectQuiz=[];
                        llMessageLinked(LINK_SET, UI_CHANNEL, "COMMAND:UPDATE ARROWS|VIEW:"+currentView+"|INDEX:"+(string)index+"|TOTALITEMS:"+(string)totalItems, NULL_KEY);
                        //initialize the string we will print on xy_text channel
                        stringToPrint="";
                        //get all of the award activities returned
                        integer len = llGetListLength(data);
                        displayData="CURRENT VIEW:"+currentView+"\n"; 
                        for (counter=0;counter<len;counter++){
                          
                                list quizData =llParseString2List(llList2String(data,counter), ["|"], []); //parse the message into a list
                                //get awardId from distributerData
                                integer quizId =i(llList2String(quizData,1));
                                string quizName =s(llList2String(quizData,0));
  
                                //highlight the currently selected distributer(if selected)
                                if (quizId==selectedQuizId){
                                    //highlight row
                                //    llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:"+(string)counter+"|POWER:ON|COLOR:"+(string)GREEN,NULL_KEY);
                                    llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,"CMD:TEXTURE|row:"+(string)counter+"|col:0|TEXTURE:yes",NULL_KEY);
                                }   else llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,"CMD:TEXTURE|row:"+(string)counter+"|col:0|TEXTURE:no",NULL_KEY);
                            //     llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:"+(string)counter+"|POWER:ON|COLOR:"+(string)WHITE,NULL_KEY);
                                displayData+="QUIZNAME:"+quizName;
                                if (counter!=len)displayData+="\n";
                                //add award to our rows
                                rows_selectQuiz+=[quizName,quizId];
                                //trim the length of the award name is to long for our display                                    
                                 if (llStringLength(quizName)>25){
                                     quizName= llGetSubString(quizName, 0, 24);
                                 }             
                                 //align text                                                           
                                 stringToPrint+= leftAlign((string)(index+counter+1)+") "+quizName,30);
                        }//for
                       /* if (myQuizId==-1){
                                list quizData =llParseString2List(llList2String(data,0), ["|"], []); //parse the message into a list
                                myQuizId =i(llList2String(quizData,1));
selectedQuizId=myQuizId;                                
                                myQuizName=s(llList2String(quizData,0));
                                //llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:"+(string)0+"|POWER:ON|"+(string)GREEN,NULL_KEY);
                                   llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,"CMD:TEXTURE|row:"+(string)0+"|col:0|TEXTURE:yes",NULL_KEY);
                                llMessageLinked(LINK_SET,UI_CHANNEL, "COMMAND:GOT QUIZ ID|QUIZID:"+(string)myQuizId+"|QUIZNAME:"+myQuizName, NULL_KEY);
                                
                        }
                        */
                        //now send this text to the xy_prims
                        llMessageLinked(LINK_SET, XY_TEXT_CHANNEL,stringToPrint, NULL_KEY);
                        llMessageLinked(LINK_SET, DISPLAY_DATA, displayData,NULL_KEY);                                                
                        stringToPrint="";
                }//response
          }//channel
          else
    if (channel==UI_CHANNEL){
        
        list dataBits = llParseString2List(str,["|"],[]);
        string command = s(llList2String(dataBits,0));
     
        //game id command comes from scoreboard_public
            if (command=="GAMEID"){
                gameid=i(llList2String(dataBits,1));
                myQuizName = s(llList2String(dataBits,2));
                myQuizId = i(llList2String(dataBits,3));
                selectedQuizId=myQuizId;
            }
            
          if(command=="RESET"){
              llResetScript();
          }//endif command=="RESET"        
          else
          //AWARD_SELECTED is passed on the UI_CHANNEL when a user selects an award on the xy_text display during the config stage
          if(command=="AWARD SELECTED"){
            currentAwardId=i(llList2String(dataBits,1));
            currentAwardName=s(llList2String(dataBits,2));
            myUrl = llList2String(dataBits,1);
          }//endif  AWARD SELECTED
          else          
        if (command=="BUTTON PRESS"){
            string button =s(llList2String(dataBits,1));
            key avuuid=k(llList2String(dataBits,2));            
            string avname = llKey2Name(avuuid);
            //                
            authenticatedUser = "&sloodleuuid="+(string)avuuid+"&sloodleavname="+llEscapeURL(llKey2Name(avuuid));
           if (button=="Quizzes"){
                   string authenticatedUser= "&sloodleuuid="+(string)llGetOwner()+"&sloodleavname="+llEscapeURL(llKey2Name(llGetOwner()));
                llMessageLinked(LINK_SET, PLUGIN_CHANNEL, "quiz->getQuizzes"+authenticatedUser+"&index=0"+"&maxitems="+(string)10, NULL_KEY); 
           }
      }//end if command==BUTTON_PRESS
       else
                  if (command=="SET VIEW"){
                     currentView= s(llList2String(dataBits,1));
                  }else
                  if (command=="SET CURRENT BUTTON"){ 
                     currentView= s(llList2String(dataBits,2));
                 }//endif
                 else  
                /*********************************
                 * Capture UPDATE ARROWS                 
                 *********************************/                            
                 if (command=="UPDATE ARROWS"){
                     currentView=s(llList2String(dataBits,1));
                  

                 }//endif
                  else
               if (command=="DISPLAY MENU"){//hapens when someone clicks on a display row
                    //since the XY Display board can be used to display different lists besides the user list, we must first check which displayMode is current.                         
                    integer rowNum =i(llList2String(dataBits,1));                 
                    key av= k(llList2String(dataBits,2));
                    string authenticatedUser= "&sloodleuuid="+(string)av+"&sloodleavname="+llEscapeURL(llKey2Name(av));
                    //make sure it was the owner who clicked on the row
                    debug(llList2CSV(facilitators));
                    if (isFacilitator(llKey2Name(av))){
                    /*****************************************************************    
                     * Select Award  - this is the handler when user is selecting an distributor activitiy                 
                     *****************************************************************/                        
                        if (currentView=="Quizzes"){
                            
                                     //save previous award
                                     previousQuizId = selectedQuizId;
                                     //clear all highlights
                                    clearHighlights();
                                    //highlight chosen distributer
                                    //llMessageLinked(LINK_SET,PRIM_PROPERTIES_CHANNEL,"COMMAND:HIGHLIGHT|ROW:"+(string)rowNum+"|POWER:ON|"+(string)GREEN,NULL_KEY);
                                    llMessageLinked(LINK_SET,DISPLAY_BOX_CHANNEL,"CMD:TEXTURE|row:"+(string)rowNum+"|col:0|TEXTURE:yes",NULL_KEY);
                                    //get awardId                                
                                    //debug("COMMAND:HIGHLIGHT|ROW:"+(string)rowNum+"|POWER:ON|"+(string)GREEN);
                                    myQuizId= llList2Integer(rows_selectQuiz,rowNum*2+1);
                                    selectedQuizId=myQuizId;
                                    myQuizName=llList2String(rows_selectQuiz,rowNum*2);
                                    //send award id back.
                                  llMessageLinked(LINK_SET, XY_QUIZ_CHANNEL, myQuizName, "0");
                                   llMessageLinked(LINK_SET,UI_CHANNEL,"COMMAND:GOT QUIZ ID|QUIZID:"+(string)myQuizId+"|QUIZNAME:"+myQuizName,NULL_KEY);
                                   //simulate button press
                                   llMessageLinked(LINK_SET,UI_CHANNEL, "CMD:BUTTON PRESS|BUTTON:Students Tab|UUID:"+(string)llGetOwner(),NULL_KEY);
                       }//currentView!="Select Award"
                     
  }//end if channel==UI_CHANNEL
 
}}}
    changed(integer change) {
     if (change ==CHANGED_INVENTORY){         
         llResetScript();
     }
    }
    
}
