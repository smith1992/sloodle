// Sloodle quiz chair
// Allows SL users to take Moodle quizzes in-world
// Part of the Sloodle project (www.sloodle.org)
//
// Copyright (c) 2006-7 Sloodle (various contributors)
// Released under the GNU GPL
//
// Contributors:
//  Edmund Edgar - original design and implementation
//  Peter R. Bloomfield - updated to use new communications format (Sloodle 0.2)
//

string sloodleserverroot = ""; // this represents the top directory of the moodle installation
string pwd = ""; // This is the string the object needs to use when talking to the server to prove that it's authorized. It will be either a single, pre-defined string (with the old prim_password method) or a uuid of a master object combined with an arbitrary numerical code (with the master object authorization method)
integer sloodle_command_channel = -3857343; // an arbitrary channel the sloodle scripts will use to talk to each other. Doesn't atter what it is, as long as the same thing is set in the sloodle_slave script.
integer sloodle_courseid = 0; // The ID of the moodle course being used.

integer avatar_channel = 0; // the channel on which we talk to the avatar
integer dialog_channel = 352435; // the channel used by dialog boxes

integer SLOODLE_CHANNEL_AVATAR_IGNORE = -1639279999;

string sloodle_quiz_url = "/mod/sloodle/mod/quiz/sl_quiz_linker.php";

key populate_request_http_id = NULL_KEY;
integer qalistpopulated = 0;

// simple lists of question attributes, 1 per question.
list qtypes = []; // for each question, "multichoice" or "simple" // TODO: Check other types...
list qs = []; //["one","two","three"];
list as = []; //["ichi","ni","san"];
list qcodes = [];

// each question has multiple options.
// since we don't have multi-dimensional arrays, we'll stack up everything in an array for its type.
// for example, all question option text entries will be stacked up in qoptiontexts.
// so that we know which texts belong to which questions, we'll given each question one entry in the following lists:
list qoptionindexes = []; // where in qoptiontexts etc. the question's entries start.
list qoptioncounts = []; // how many entries in qoptiontexts etc. the question has.

list qoptiontexts = []; //["ichi","oink","san","fier","ni","hat","unko","poo","san"];
list qoptionfeedbacks = []; // ["Ichi - peachy","No, ink is the noise a pig makes"] etc.
list qoptionscores = []; // 1, -1, -0.5, etc.
list qoptioncodes = [];

integer doRepeat = 1; // whether we should run through the questions again when we're done
integer doDialog = 1;

integer numberOfQuestions = 0; //3;    
integer activeQuestion = -1; // index of question currently being asked - 0-based

integer quizid = 0;
integer timeup = 0;
string questionids;

// integer problemTimeout = 0; // sort this out later...

key sitter;
float lowestvector; 

populate_qa_list(string response) 
{
    //llWhisper(0,"Got response from server:"+response);
    
    if (qalistpopulated == 1) { // already populated
        return 1;
    }
    
    
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
        
    integer i = 1;
    integer highestqoptionindex = -1;

    qoptionindexes = []; 
    qoptioncounts = []; 

    qoptiontexts = []; 
    qoptionfeedbacks = []; 
    qoptionscores = [];
    qoptioncodes = [];

    // llWhisper(0,"handling response" + response);
    for (i = 1; i < numlines; i++) {

        string thislinestr = llList2String(lines, i);
        //llWhisper(0,thislinestr);
        list thisline = llParseString2List(thislinestr,["|"],[]);
        string rowtype = llList2String( thisline, 0 );
        string thisqtype = "";

        if ( rowtype == "quiz" ) {
            quizid = llList2Integer( thisline, 4 ); 
        } else if ( rowtype == "question" ) { // column 1 says what kind of data it is...

            thisqtype = llList2String( thisline, 7 ); // multichoice or ???
            numberOfQuestions = numberOfQuestions + 1;
                        
            //llWhisper(0,"Adding question from line "+thislinestr);
            qs = qs + [llList2String(thisline, 4)];
            qtypes = qtypes + [thisqtype];

            // if this is the first qoptionindex, set it to 0.
            // if there are already some set, add the index of the previous record to its count.
            if ( llGetListLength(qoptionindexes) == 0 ) {
                qoptionindexes = [0];
                qoptioncounts = [0];
            } else {
                integer lastIndex = llList2Integer(qoptionindexes, -1);
                integer lastCount = llList2Integer(qoptioncounts, -1);
                qoptionindexes = qoptionindexes + [(lastIndex+lastCount)];
                qoptioncounts = qoptioncounts + [0];                
            }
            
            // TODO: for single-choice questions, add to as
            as = as + [''];
            qcodes = qcodes + [llList2Integer(thisline,1)];
            
            if (questionids != "") {
                questionids = questionids+",";
            }
            questionids = questionids + (string)[llList2Integer(thisline,1)];
                        
        } else if ( rowtype == "questionoption" ) {
            
            // if it's the first time we've seen a question option for this question, 
            qoptioncodes = qoptioncodes + [llList2Integer(thisline, 2)];
            qoptiontexts = qoptiontexts + [llList2String(thisline, 4)];
            qoptionfeedbacks = qoptionfeedbacks + [llList2String(thisline, 6)];
            qoptionscores = qoptionscores + [llList2Integer(thisline, 5)];
            
            // increment the last qoptioncounts record
            integer oldqoc = llList2Integer(qoptioncounts, -1);
            list newqocl = [oldqoc+1];
            qoptioncounts = llListReplaceList(qoptioncounts, newqocl, -1, -1);
            
        }
    }    

    lines = [];
    qalistpopulated = 1;    
    llWhisper(0,"Question list loaded. Starting test...");
    next_question();
}

request_question_list()
{
   // llWhisper(0,"Using sloodle server root "+sloodleserverroot);
    string url = sloodleserverroot + sloodle_quiz_url + "?sloodlepwd=" + pwd + "&sloodleavname="+llEscapeURL(llKey2Name(sitter)) + "&courseid=" + (string)sloodle_courseid;
   // llWhisper(0,"Reqesting url "+url);
    populate_request_http_id = llHTTPRequest(url,[],"");
}

notify_server(string qtype, integer questioncode, integer responsecode)
{
    string url = sloodleserverroot + sloodle_quiz_url + "?sloodlepwd=" + pwd + "&sloodleavname="+llEscapeURL(llKey2Name(sitter)) + "&q=" + (string)quizid+"&resp"+(string)questioncode+"_="+(string)responsecode+"&questionids="+questionids+"&resp"+(string)questioncode+"_submit=Submit&timeup="+(string)timeup+"&action=notify";
    //llWhisper(0,"Reqesting url "+url);
    llHTTPRequest(url,[],"");    
}

ask_question() 
{
    
//    llDialog(llDetectedKey(0), "What do you want to do?", MENU_MAIN, avatar_channel); 
    
    string question = llList2String(qs, activeQuestion);
   
    integer thisOpIndex = llList2Integer(qoptionindexes, activeQuestion);    
    integer thisOpCount = llList2Integer(qoptioncounts, activeQuestion);
    integer lastIndex = thisOpIndex + thisOpCount - 1;
    
    list thisqoptiontexts = llList2List(qoptiontexts, thisOpIndex, lastIndex);
   
    // llDialog(sitter, "Indexes: " + llDumpList2String(qoptionindexes,"+") + "\nCounts: " + llDumpList2String(qoptioncounts,"+") + "\nTexts: " + llDumpList2String(qoptiontexts,"+"), [], dialog_channel );      
    if (doDialog == 1) {
        llDialog(sitter, question, thisqoptiontexts, dialog_channel);
        llListen(dialog_channel, "", sitter, ""); // listen for dialog answers (from multiple users)
    } else {
        llWhisper(avatar_channel, "");
        llWhisper(avatar_channel, question);
     
        integer x;
        for (x=0; x < llGetListLength(thisqoptiontexts); x++) {
            llWhisper(0, " - " + llList2String(thisqoptiontexts, x) );   
        }
        llListen(avatar_channel,"",NULL_KEY,"");    
    }
}

repeat_question() 
{
    ask_question();   
}

next_question() 
{
    
    activeQuestion++;
    ask_question();

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
    llWhisper(avatar_channel, "done");      
    move_to_start(); 
    if (doRepeat == 1) {
        activeQuestion = -1;
        next_question();   
    }
    
}

handle_response(string message) {
    
    integer scorechange = 0;
    string feedback = "";
        
    string thisqtype = llList2String( qtypes, activeQuestion );
    if (thisqtype == "multichoice") {

        // get the options
        integer thisOpIndex = llList2Integer(qoptionindexes, activeQuestion);
        integer thisOpCount = llList2Integer(qoptioncounts, activeQuestion);
        integer lastIndex = thisOpIndex + thisOpCount;
        
        list thisqoptiontexts = llList2List(qoptiontexts, thisOpIndex, lastIndex);
        list thisqoptionfeedbacks = llList2List(qoptionfeedbacks, thisOpIndex, lastIndex);
        list thisqoptionscores = llList2List(qoptionscores, thisOpIndex, lastIndex);
        
        integer x;
        integer answerfound = 0;
        for (x=0; x < llGetListLength(thisqoptiontexts); x++) {
            string thisqot = llList2String(thisqoptiontexts, x);
            //llWhisper(0, "Checking " + message + " against - " + thisqot );
            // TODO: accept either the answer itself or a digit 
            if (message == thisqot) {
                answerfound = 1;
                feedback = llList2String(thisqoptionfeedbacks, x);
                scorechange = llList2Integer(thisqoptionscores, x);

                list thisqoptioncodes = llList2List(qoptioncodes, thisOpIndex, lastIndex);
                notify_server( thisqtype, llList2Integer(qcodes,activeQuestion) , llList2Integer(thisqoptioncodes, x) );
            }
        }
        if (answerfound == 0) {
            llWhisper(0, "Your choice was not in the list of available choices. Please try again.");
            repeat_question();
            return 1;   
        }
    
        
    
    } else {

        string answer = llList2String( as, activeQuestion );   
        if (message == answer) {
            feedback = "correct";
        } else {
            feedback = "wrong";            
        }

    }
    llSay(0,feedback);
    //llDialog(sitter, feedback, ["Next"],SLOODLE_CHANNEL_AVATAR_IGNORE);
    move_vertical(scorechange);
    //llWhisper(0,"moving vertical "+(string)scorechange);
    
    if (numberOfQuestions > activeQuestion+1) {
        next_question();    
    } else {
        process_done();         
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
        vector eul = <0,270,0>; //45 degrees around the z-axis, in Euler form
        eul *= DEG_TO_RAD; //convert to radians
        rotation quat = llEuler2Rot(eul); //convert to quaternion
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
        
        handle_response(message);

    }

    http_response(key request_id, integer status, list metadata, string body) {

        // only on request success
        if (request_id == populate_request_http_id) {
            
            if(status == 200) {
                //llWhisper(0,"got body"+body);
                populate_qa_list(body);
            }
         
        }

    }
    
    changed(integer change) { // something changed

        if (change & CHANGED_LINK) { // and it was a link change
            //llSleep(0.5); // llUnSit works better with this delay
            if (llAvatarOnSitTarget() != NULL_KEY) { // somebody is sitting on me
                if (sitter != llAvatarOnSitTarget()) { // new sitter
                    sitter = llAvatarOnSitTarget();
                    vector thispos = llGetPos();
                    lowestvector = (float)thispos.z; //llGround ( ZERO_VECTOR );
                    move_to_start();
                    qalistpopulated = 0;
                    request_question_list(); // load question list
                    llWhisper(0,"Loading questions for "+llKey2Name(llAvatarOnSitTarget()));            
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



