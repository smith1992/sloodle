// Sloodle quiz chair
// Allows SL users to take Moodle quizzes in-world
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-8 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar
//  Peter R. Bloomfield
//


integer doRepeat = 1; // whether we should run through the questions again when we're done
integer doDialog = 1; // whether we should ask the questions using dialog rather than chat
integer doPlaySound = 1; // whether we should play sound
integer doRandomize = 1; // whether we should ask the questions in random order

string sloodleserverroot = "";
integer sloodlecontrollerid = 0;
string sloodlepwd = "";
integer sloodlemoduleid = 0;
integer sloodleobjectaccessleveluse = 0; // Who can use this object?
integer sloodleobjectaccesslevelctrl = 0; // Who can control this object?
integer sloodleserveraccesslevel = 0; // Who can use the server resource? (Value passed straight back to Moodle)

integer isconfigured = FALSE; // Do we have all the configuration data we need?
integer eof = FALSE; // Have we reached the end of the configuration data?

integer avatar_channel = 0; // the channel on which we talk to the avatar
integer dialog_channel = 352435; // the channel used by dialog boxes

integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343; // an arbitrary channel the sloodle scripts will use to talk to each other. Doesn't atter what it is, as long as the same thing is set in the sloodle_slave script. 
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;

integer SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC = 0;
integer SLOODLE_OBJECT_ACCESS_LEVEL_OWNER = 1;
integer SLOODLE_OBJECT_ACCESS_LEVEL_GROUP = 2;

string SLOODLE_OBJECT_TYPE = "quiz-1.0";
string SLOODLE_EOF = "sloodleeof";

string sloodle_quiz_url = "/mod/sloodle/mod/quiz-1.0/linker.php";

key populate_request_http_id = NULL_KEY; 
integer request_timeout = 20; // Wait this long before giving up and requesting a question again.
integer populate_request_timestamp = -1;
integer is_waiting_for_active_question = 0;

list item_ids = [];

// We'll always keep one question ahead, so there'll be up to two questions alive at any time: 
// 1) the question we are about to ask or have just asked
// 2) the question we are storing up to ask next

integer qitem_current = -1;
integer qid_current = -1;
string qtype_current = "";
string qtext_current = ""; // What is 2+2?
list qoptionids_current = [];
list qoptiontexts_current = []; // 5, 4, 3.14159
list qoptionfeedbacks_current = []; // ["No, it's 4 - trying to be on the safe side?","Correct!","Getting too clever - that's pi."]
list qoptionscores_current = []; // [-1, 1, -0.5]

integer qitem_next = -1; // the questions in the order we ask them
integer qid_next = -1; // the question id in the moodle database
string qtype_next = ""; // question type - currently only multichoice is supported
string qtext_next = ""; // the text of the question
list qoptionids_next = [];
list qoptiontexts_next = []; //["ichi","oink","san","fier","ni","hat","unko","poo","san"];
list qoptionfeedbacks_next = []; // ["Ichi - peachy","No, ink is the noise a pig makes"] etc.
list qoptionscores_next = []; // 1, -1, -0.5, etc.

integer number_of_questions = -1; //3;    
integer active_question = -1; // index of question currently being asked - 0-based

integer quizid = 0;
integer timeup = 0;

string questionids;

key sitter;

float lowestvector; 

integer listener_id;


///// FUNCTIONS /////

sloodle_debug(string msg)
{
    llMessageLinked(LINK_THIS, DEBUG_CHANNEL, msg, NULL_KEY);
}

// Configure by receiving a linked message from another script in the object
// Returns TRUE if the object has all the data it needs
integer sloodle_handle_command(string str) 
{
    list bits = llParseString2List(str,["|"],[]);
    integer numbits = llGetListLength(bits);
    string name = llList2String(bits,0);
    string value1 = "";
    string value2 = "";
    
    if (numbits > 1) value1 = llList2String(bits,1);
    if (numbits > 2) value2 = llList2String(bits,2);
    
    if (name == "set:sloodleserverroot") sloodleserverroot = value1;
    else if (name == "set:sloodlepwd") {
        // The password may be a single prim password, or a UUID and a password
        if (value2 != "") sloodlepwd = value1 + "|" + value2;
        else sloodlepwd = value1;
        
    } else if (name == "set:sloodlecontrollerid") sloodlecontrollerid = (integer)value1;
    else if (name == "set:sloodlemoduleid") sloodlemoduleid = (integer)value1;
    else if (name == "set:sloodleobjectaccessleveluse") sloodleobjectaccessleveluse = (integer)value1;
    else if (name == "set:sloodleserveraccesslevel") sloodleserveraccesslevel = (integer)value1;
    else if (name == "set:sloodlerepeat") doRepeat = (integer)value1;
    else if (name == "set:sloodlerandomize") doRandomize = (integer)value1;
    else if (name == "set:sloodledialog") doDialog = (integer)value1;
    else if (name == "set:sloodleplaysound") doPlaySound = (integer)value1;
    else if (name == SLOODLE_EOF) eof = TRUE;
    
    return (sloodleserverroot != "" && sloodlepwd != "" && sloodlecontrollerid > 0 && sloodlemoduleid > 0);
}

// Checks if the given agent is permitted to user this object
// Returns TRUE if so, or FALSE if not
integer sloodle_check_access_use(key id)
{
    // Check the access mode
    if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_GROUP) {
        return llSameGroup(id);
    } else if (sloodleobjectaccessleveluse == SLOODLE_OBJECT_ACCESS_LEVEL_PUBLIC) {
        return TRUE;
    }
    
    // Assume it's owner mode
    return (id == llGetOwner());
}

integer request_has_timed_out()
{
    if ( ( llGetUnixTime() - request_timeout ) > populate_request_timestamp ) {
        return 1;
    } else {
        return 0;
    }
}

populate_qa_list(string response) 
{
    //llWhisper(0,"Got response from server:"+response);
    
    // Split the response into several lines
    list lines = llParseStringKeepNulls(response, ["\n"], []);
    integer numlines = llGetListLength(lines);
    response = "";
    list statusfields = llParseStringKeepNulls(llList2String(lines,0), ["|"], []);
    integer statuscode = llList2Integer(statusfields, 0);
    
    // Was it an error code?
    if (statuscode <= 0) {
        // Check if an error message was reported
        string errmsg = "";
        if (numlines > 1) errmsg = llList2String(lines, 1);
        llSay(0, "Quiz error ("+(string)statuscode+"): "+errmsg);
        return 0;
    }

    integer loadedqitem = -1;

    // llWhisper(0,"handling response" + response);
    integer i;
    for (i = 1; i < numlines; i++) {

        string thislinestr = llList2String(lines, i);
        //llWhisper(0,thislinestr);
        list thisline = llParseString2List(thislinestr,["|"],[]);
        string rowtype = llList2String( thisline, 0 ); 
        string thisqtype = "";

        if ( rowtype == "quiz" ) {
            
            quizid = llList2Integer( thisline, 4 );        
            number_of_questions = llList2Integer( thisline, 5 );  
            
            populate_item_ids(number_of_questions);
            
        } else if ( rowtype == "question" ) { // column 1 says what kind of data it is...

            loadedqitem = llList2Integer( thisline, 1);
            
            if ( (is_waiting_for_active_question == 1) && (loadedqitem == llList2Integer(item_ids,(active_question-1) ) )){
        
                qid_current = llList2Integer( thisline, 2);
                qitem_current = llList2Integer( thisline, 1);    
    
                qtext_current = "";
                qoptiontexts_current = []; 
                qoptionfeedbacks_current = []; 
                qoptionscores_current = []; 
                qoptionids_current = [];

                qtype_current = llList2String( thisline, 7 ); // multichoice or ???
                qtext_current = llList2String(thisline, 4); 
                qid_current = llList2Integer(thisline,1);
                        
            } else if  (loadedqitem == (llList2Integer(item_ids,(active_question+1-1))) ){
                
                qid_next = llList2Integer( thisline, 2);
                qitem_next = llList2Integer( thisline, 1);    
    
                qtext_next = "";
                qoptiontexts_next = []; 
                qoptionfeedbacks_next = []; 
                qoptionscores_next = []; 
                qoptionids_next = [];

                qtype_next = llList2String( thisline, 7 ); // multichoice or ???
                qtext_next = llList2String(thisline, 4); 
                qid_next = llList2Integer(thisline,1);                                
                
            } else {
             
                //llSay(0,"ignoring out-of-order question");
                      
            }
                                                  
        } else if ( rowtype == "questionoption" ) {
            
            if ( (is_waiting_for_active_question == 1) && (loadedqitem == llList2Integer(item_ids,(active_question-1))) ) {            
            
                // if it's the first time we've seen a question option for this question, 
                qoptionids_current = qoptionids_current + [llList2Integer(thisline, 2)];
                qoptiontexts_current = qoptiontexts_current + [llList2String(thisline, 4)];
                qoptionfeedbacks_current = qoptionfeedbacks_current + [llList2String(thisline, 6)];
                qoptionscores_current = qoptionscores_current + [llList2Integer(thisline, 5)];
            
            } else if  (loadedqitem == (llList2Integer(item_ids,(active_question+1-1)))) {

                // if it's the first time we've seen a question option for this question, 
                qoptionids_next = qoptionids_next + [llList2Integer(thisline, 2)];
                qoptiontexts_next = qoptiontexts_next + [llList2String(thisline, 4)];
                qoptionfeedbacks_next = qoptionfeedbacks_next + [llList2String(thisline, 6)];
                qoptionscores_next = qoptionscores_next + [llList2Integer(thisline, 5)];

            } else {
                 
                //llSay(0,"ignoring option for loadedqitem "+(string)loadedqitem+" while on active question "+(string)active_question+ " - is_waiting_for_active_question is " + (string)is_waiting_for_active_question);
            }
                        
        }
    }    

    lines = [];
    //llWhisper(0,"Question loaded...");
    
    // If we're waiting for a question to ask, call ask_or_fetch_question again - it will see if it's now able to ask the question.
    // If we're not waiting, we can just stop here - the question we loaded will be held in reserve until the student answers the current question.
    if (is_waiting_for_active_question == 1) {
        
            ask_or_fetch_question();
        
    } 
    
}

request_question( integer activeq )
{
    integer limittoquestion = llList2Integer(item_ids, activeq-1);
    
    string body = "sloodlecontrollerid=" + (string)sloodlecontrollerid;
    body += "&sloodlepwd=" + sloodlepwd;
    body += "&sloodlemoduleid=" + (string)sloodlemoduleid;
    body += "&sloodleuuid=" + (string)sitter;
    body += "&sloodleavname=" + llEscapeURL(llKey2Name(sitter));
    body += "&sloodleserveraccesslevel=" + (string)sloodleserveraccesslevel;
    body += "&ltq" + (string)limittoquestion;
    
    populate_request_http_id = llHTTPRequest(sloodleserverroot + sloodle_quiz_url, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
    
    llSetTimerEvent(0.0);
    llSetTimerEvent((float)request_timeout);

    
}

notify_server(string qtype, integer questioncode, integer responsecode)
{
    string body = "sloodlecontrollerid=" + (string)sloodlecontrollerid;
    body += "&sloodlepwd=" + sloodlepwd;
    body += "&q=" + (string)quizid;
    body += "&sloodleuuid=" + (string)sitter;
    body += "&sloodleavname=" + llEscapeURL(llKey2Name(sitter));
    body += "&sloodleserveraccesslevel=" + (string)sloodleserveraccesslevel;
    body += "&resp" + (string)questioncode + "_=" + (string)responsecode;
    body += "&questionids=" + questionids;
    body += "&resp" + (string)questioncode+"_submit=Submit";
    body += "&timeup=" + (string)timeup;
    body += "&action=notify";
    
    llHTTPRequest(sloodleserverroot + sloodle_quiz_url, [HTTP_METHOD, "POST", HTTP_MIMETYPE, "application/x-www-form-urlencoded"], body);
}

ask_or_fetch_question()
{
    
    if (llList2Integer(item_ids,active_question-1) == qitem_current) {        

        is_waiting_for_active_question = 0;
                    
        if ( llList2Integer(item_ids, (active_question+1-1) )  != qitem_next ) {
            
            if ( (active_question == 0) || (active_question <= number_of_questions) ) {
                request_question(active_question+1); // fetch the next question in advance so the student doesn't have to wait.
            }
            
        }

        ask_question();   
        
    } else if (llList2Integer(item_ids,(active_question-1)) == qitem_next) {

        qitem_current = qitem_next;
        qid_current = qid_next;
        qtext_current = qtext_next;
        qoptiontexts_current = qoptiontexts_next; 
        qoptionfeedbacks_current = qoptionfeedbacks_next; 
        qoptionscores_current = qoptionscores_next;

        qitem_next = -1;
        qid_next = -1;
        qtext_next = "";
        qoptiontexts_next = []; 
        qoptionfeedbacks_next = []; 
        qoptionscores_next = [];     
                    
        is_waiting_for_active_question = 0;
        
        request_question(active_question+1); // fetch the next question    
                        
        ask_question();
                                
    } else if (is_waiting_for_active_question == 0) { 
    
        // Don't have a question to ask. 
        // If we haven't requested it from the server, or we've requested it but it's timed out, request it  now.
        // When the question arrives, the function handling the response will call ask_question again.
        
        is_waiting_for_active_question = 1;
        
        request_question(active_question);
        
    } else {
        
        if (request_has_timed_out() == 1) {

            request_question(active_question);            
                                    
        } else {
            
            //llSay(0,"waiting for question "+(string)active_question+" from server");    
            
        }
                
    }
    
}

ask_question() 
{      
     //llSay(0,"asking question "+(string)active_question);
    if (doDialog == 1) {
        
        integer qi;
        list qdialogoptions = [];
        string qdialogtext = qtext_current + "\n";
        //llSay(0,"making buttons for options - has "+(string)llGetListLength(qoptiontexts_current)+" items");
        for (qi=1; qi<=llGetListLength(qoptiontexts_current); qi++) {
            qdialogtext = qdialogtext + (string)qi + " :" + llList2String(qoptiontexts_current, (qi-1)) + "\n";
            qdialogoptions = qdialogoptions + [(string)qi];
        }
        llDialog(sitter, qdialogtext, qdialogoptions, dialog_channel);
        listener_id = llListen(dialog_channel, "", sitter, ""); // listen for dialog answers (from multiple users)
        
    } else {
        
        llWhisper(avatar_channel, "");
        llWhisper(avatar_channel, qtext_current);
     
        integer x;
        for (x=0; x < llGetListLength(qoptiontexts_current); x++) {
            //llWhisper(0, " - " + llList2String(qoptiontexts_current, x) );   
        }
        llListen(avatar_channel,"",sitter,"");
        
    }
}


handle_answer(string message) {
    
    integer scorechange = 0;
    string feedback = "";
        
    //llSay(0,"handling answer "+message);
        
    if (qtype_current == "multichoice") {

        integer x = -1;

        x = (integer)message;
        if ( (x > 0) && (x <= llGetListLength(qoptionfeedbacks_current) ) ) { // check the response is a number in the list
                        
            feedback = llList2String(qoptionfeedbacks_current, x-1);
            scorechange = llList2Integer(qoptionscores_current, x-1);

            //llSay(0,"TODO: notify server");
            notify_server( qtype_current, llList2Integer(qoptionids_current,llList2Integer(item_ids,active_question-1)) , llList2Integer(qoptionids_current, x) );

        } else {
        
            llWhisper(0, "Your choice was not in the list of available choices. Please try again.");
            repeat_question();        
            
        }        
    
    } else {

        llSay(0,"Error: This object cannot handle quiz question of type " + qtype_current);

      //  string answer = llList2String( as, active_question );   
      //  if (message == answer) {
      //      feedback = "correct";
      //  } else {
      //      feedback = "wrong";            
      //  }

    }
    
    llSay(0,feedback);
    //llDialog(sitter, feedback, ["Next"],SLOODLE_CHANNEL_AVATAR_IGNORE);
    move_vertical(scorechange); 
    play_sound(scorechange);
    //llWhisper(0,"moving vertical "+(string)scorechange);
    
    next_question();    

}


repeat_question() 
{
    
    ask_question();   
    
}

next_question() 
{    
    
    if ( (active_question == 0) || (active_question < number_of_questions) ) {
        active_question++;        
        ask_or_fetch_question();        
    } else {    
        process_done();   
    }

}

play_sound(integer multiplier) {

    if (doPlaySound == 0) {
        return;
    }

    string sound_file;
    float volume;

    if (multiplier > 0) {
        
        sound_file = "Correct";
        
    } else {
        
        sound_file = "Incorrect";
        multiplier = multiplier * -1;
        
    }
    
    if (multiplier > 1) {
        volume = 1.0;
    } else {
        volume = (float)multiplier;
    }    
    
    llPlaySound(sound_file,multiplier);

}

move_vertical(integer multiplier) {
        
    //float lowestvector = llGround ( ZERO_VECTOR );
    integer lowest = (integer)lowestvector;
    integer range = 30;
    integer highest = lowest + range;;
    float increment = 0.5;
    vector position = llGetPos();
    position.z = position.z + (increment * multiplier);
    if (position.z < lowest) {
        position.z = lowest;
    }
    if (position.z > highest) {
        position.z = highest;
    }
    llSetPos(position);
}

move_to_start() {
    
    //integer lowest = (integer)lowestvector;
        
    //integer middle = lowest + 6;
    //integer increment = 1;
    //vector position = llGetPos();
    //position.z = middle;
    //llSetPos(position);    
}

process_done() 
{
    llWhisper(avatar_channel, "Quiz complete");      
    move_to_start(); 
    if (doRepeat == 1) {
        active_question = 0;
        next_question();   
        llWhisper(avatar_channel, "Repeating...");
    }
    
}

populate_item_ids(integer num_items)
{
    item_ids = [];
    integer r;
    for (r=1; r <= num_items; r++) {
        item_ids = item_ids + [r];
    }
    if (doRandomize == 1) {
        item_ids = llListRandomize(item_ids,1);
    }
}


///// TRANSLATION /////

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
integer SLOODLE_CHANNEL_TRANSLATION_RESPONSE = -1928374652;

// Translation output methods
string SLOODLE_TRANSLATE_LINK = "link";             // No output parameters - simply returns the translation on SLOODLE_TRANSLATION_RESPONSE link message channel
string SLOODLE_TRANSLATE_SAY = "say";               // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_WHISPER = "whisper";       // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_SHOUT = "shout";           // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_REGION_SAY = "regionsay";  // 1 output parameter: chat channel number
string SLOODLE_TRANSLATE_OWNER_SAY = "ownersay";    // No output parameters
string SLOODLE_TRANSLATE_DIALOG = "dialog";         // Recipient avatar should be identified in link message keyval. At least 2 output parameters: first the channel number for the dialog, and then 1 to 12 button label strings.
string SLOODLE_TRANSLATE_LOAD_URL = "loadurl";      // Recipient avatar should be identified in link message keyval. 1 output parameter giving URL to load.
string SLOODLE_TRANSLATE_HOVER_TEXT = "hovertext";  // 2 output parameters: colour <r,g,b>, and alpha value
string SLOODLE_TRANSLATE_IM = "instantmessage";     // Recipient avatar should be identified in link message keyval. No output parameters.

// Send a translation request link message
sloodle_translation_request(string output_method, list output_params, string string_name, list string_params, key keyval, string batch)
{
    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_TRANSLATION_REQUEST, output_method + "|" + llList2CSV(output_params) + "|" + string_name + "|" + llList2CSV(string_params) + "|" + batch, keyval);
}

///// ----------- /////


///// STATES /////

default
{
    state_entry()
    {
        // Starting again with a new configuration
        isconfigured = FALSE;
        eof = FALSE;
        // Reset our data
        sloodleserverroot = "";
        sloodlepwd = "";
        sloodlecontrollerid = 0;
        sloodlemoduleid = 0;
        sloodleobjectaccessleveluse = 0;
        sloodleserveraccesslevel = 0;
        doRepeat = 1;
        doDialog = 1;
        doPlaySound = 1;
        doRandomize = 1;
    }
    
    link_message( integer sender_num, integer num, string str, key id)
    {
        // Check the channel
        if (num == SLOODLE_CHANNEL_OBJECT_DIALOG) {
            // Split the message into lines
            list lines = llParseString2List(str, ["\n"], []);
            integer numlines = llGetListLength(lines);
            integer i = 0;
            for (; i < numlines; i++) {
                isconfigured = sloodle_handle_command(llList2String(lines, i));
            }
            
            // If we've got all our data AND reached the end of the configuration data, then move on
            if (eof == TRUE) {
                if (isconfigured == TRUE) {
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configurationreceived", [], NULL_KEY, "");
                    state ready;
                } else {
                    // Go all configuration but, it's not complete... request reconfiguration
                    sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "configdatamissing", [], NULL_KEY, "");
                    llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:reconfigure", NULL_KEY);
                    eof = FALSE;
                }
            }
        }
    }
    
    touch_start(integer num_detected)
    {
        // Attempt to request a reconfiguration
        if (llDetectedKey(0) == llGetOwner()) {
            llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", NULL_KEY);
        }
    }
}


state ready
{
    on_rez(integer param)
    {
        state default;
    }
    
    state_entry()
    {
        //llSay(0, "Hello, Avatar!");
        //request_question_list();
        //vector eul = <0,270,0>; //45 degrees around the z-axis, in Euler form
        //eul *= DEG_TO_RAD; //convert to radians
        //rotation quat = llEuler2Rot(eul); //convert to quaternion
        //llSitTarget(<0, 0, -1.5>, quat); // for dragon
        llSitTarget(<0,0,.5>, ZERO_ROTATION);
        //llSetSitText("Ride");
    }

    touch_start(integer total_number)
    {
    }
    
    listen(integer channel, string name, key id, string message) {
        
        if (id == sitter) {
            handle_answer(message);
        }

    }
    
    timer() {
        
        // When we request a question, we set a timer in case the reply doesn't come.
        // If we hit a timeout, we'll send it back to ask_or_fetch_question(), which will carry on with the quiz if it's got a question to ask or re-send the request if it hasn't.
        if (is_waiting_for_active_question == 1) {
            
            //ask_or_fetch_question();
            
        }
        
    }

    http_response(key request_id, integer status, list metadata, string body) {

        // only on request success
        if (request_id == populate_request_http_id) {
            
            if(status == 200) {
                //llWhisper(0,"got body"+body);
                
                //llSay(0,"got questions, processing...");
                populate_qa_list(body);
            }
         
        }

    }
    
    changed(integer change) { // something changed

        if (change & CHANGED_LINK) { // and it was a link change
        
            llSleep(0.5); // Allegedly llUnSit works better with this delay
            
            if (llAvatarOnSitTarget() == NULL_KEY) { // sitter has gone
            
                sitter = NULL_KEY;
                llListenRemove(listener_id);
                is_waiting_for_active_question = 0;
                active_question = -1;
                item_ids = [];
                
                // Try resetting so that somebody else can use this device
                llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:requestconfig", NULL_KEY);
                state default;
                return;
                
            } else {
                if (sitter != llAvatarOnSitTarget()) { // new sitter
                
                    sitter = llAvatarOnSitTarget();
                    
                    // Make sure the given avatar is allowed to use this object
                    if (!sloodle_check_access_use(sitter)) {
                        sloodle_translation_request(SLOODLE_TRANSLATE_SAY, [0], "nopermission:use", [llKey2Name(sitter)], NULL_KEY, "");
                        llUnSit(sitter);
                        sitter = NULL_KEY;
                        return;
                    }
                    
                    vector thispos = llGetPos();
                    lowestvector = (float)thispos.z; //llGround ( ZERO_VECTOR );
                    move_to_start();
                    active_question = 0;
                    next_question(); // request first question
                    
                    llWhisper(0,"Starting quiz for "+llKey2Name(llAvatarOnSitTarget()));
                    
                }
            }
        }
    }
    
}

