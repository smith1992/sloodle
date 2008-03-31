// Sloodle quiz chair
// Allows SL users to take Moodle quizzes in-world
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-8 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar - original design and implementation
//  Peter R. Bloomfield - updated to use new communications format (Sloodle 0.2)
//


integer doRepeat = 1; // whether we should run through the questions again when we're done
integer doDialog = 1; // whether we should ask the questions using dialog rather than chat
integer doPlaySound = 1; // whether we should play sound
integer doRandomize = 1; // whether we should ask the questions in random order

string sloodleserverroot = ""; // this represents the top directory of the moodle installation
string pwd = ""; // This is the string the object needs to use when talking to the server to prove that it's authorized. It will be either a single, pre-defined string (with the old prim_password method) or a uuid of a master object combined with an arbitrary numerical code (with the master object authorization method)
integer sloodle_command_channel = -3857343; // an arbitrary channel the sloodle scripts will use to talk to each other. Doesn't atter what it is, as long as the same thing is set in the sloodle_slave script.
integer sloodle_courseid = 0; // The ID of the moodle course being used.

integer avatar_channel = 0; // the channel on which we talk to the avatar
integer dialog_channel = 352435; // the channel used by dialog boxes
 
integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;

string sloodle_quiz_url = "/mod/sloodle/mod/quiz/sl_quiz_linker_single.php";

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
   // llWhisper(0,"Using sloodle server root "+sloodleserverroot);
    string url = sloodleserverroot + sloodle_quiz_url + "?sloodlepwd=" + pwd + "&sloodleavname="+llEscapeURL(llKey2Name(sitter)) + "&courseid=" + (string)sloodle_courseid + "&ltq=" + (string)limittoquestion;
    llWhisper(0,"Reqesting url "+url);
    populate_request_http_id = llHTTPRequest(url,[],"");
    llSetTimerEvent(request_timeout);

    
}

notify_server(string qtype, integer questioncode, integer responsecode)
{
    
    string url = sloodleserverroot + sloodle_quiz_url + "?sloodlepwd=" + pwd + "&sloodleavname="+llEscapeURL(llKey2Name(sitter)) + "&q=" + (string)quizid+"&resp"+(string)questioncode+"_="+(string)responsecode+"&questionids="+questionids+"&resp"+(string)questioncode+"_submit=Submit&timeup="+(string)timeup+"&action=notify";
    //llWhisper(0,"Reqesting url "+url);
    llHTTPRequest(url,[],"");    
    
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

sloodle_handle_command(string str) 
{
    //llWhisper(0,"handling command "+str);    
    list bits = llParseString2List(str,["|"],[]);
        string name = llList2String(bits,0);
        string value = llList2String(bits,1);
        if (name == "set:sloodleserverroot") {
            sloodleserverroot = value;
        } else if (name == "set:pwd") {
            pwd = value;
            if (llGetListLength(bits) == 3) {
                pwd = pwd + "|" + llList2String(bits,2);
            }
        } else if (name == "set:sloodle_courseid") {
            sloodle_courseid = (integer)value;
        } else if (name == "set:id") {
            quizid = (integer)value;
        }
    

    //llWhisper(0,"DEBUG: "+sloodleserverroot+"/"+pwd+"/"+(string)sloodle_courseid);
    // TODO: Start setting quizid here as well - we'll need to wait for it...
    if ( (sloodleserverroot != "") && (pwd != "") && (sloodle_courseid != 0) ) {
        state default;
    }
}

sloodle_init()
{
    //llWhisper(0,"initializing");    
    if ( (sloodleserverroot == "") || (pwd == "") || (sloodle_courseid == 0) ) {
        state sloodle_wait_for_configuration;
    }
}


default
{
    on_rez(integer param)
    {
        sloodle_init();
    }
    state_entry()
    {
        sloodle_init();
        //llSay(0, "Hello, Avatar!");
        //request_question_list();
        //vector eul = <0,270,0>; //45 degrees around the z-axis, in Euler form
        //eul *= DEG_TO_RAD; //convert to radians
        //rotation quat = llEuler2Rot(eul); //convert to quaternion
        //llSitTarget(<0, 0, -1.5>, quat); // for dragon
        llSitTarget(<0,0,.5>, ZERO_ROTATION);
        llSetSitText("Ride");
    }

    touch_start(integer total_number)
    {
        //llWhisper(0,"Sloodle Quiz Starting...");
        //next_question();

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
                
            } else {
                if (sitter != llAvatarOnSitTarget()) { // new sitter
                
                    sitter = llAvatarOnSitTarget();
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

state sloodle_wait_for_configuration
{
    state_entry() {
        //llWhisper(0,"waiting for command");
    }
    link_message(integer sender_num, integer num, string str, key id) {
        //llWhisper(0,"got message "+(string)sender_num+str);
       // if ( (sender_num == LINK_THIS) && (num == sloodle_command_channel) ){
            sloodle_handle_command(str);
        //}   
    }
}

