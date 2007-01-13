// 0.3 - adding in auto-update
//     - rewriting some code, to simplify things and allow for more control via buttons
// 0.2 - uses Edmund Earp's authentication data
//      - No longer asks user to set ID in notecard, but user
//        must be authenticated to use this successfully
// 0.1 - based on sloodle chat 0.72. DL

//----------
// WE USE A NOTECARD NOW
// edit these to put in your moodle name as it appears in moodle chat room,
// and place your moodle ID number at the end of line 2, where '52' is just not
// you also need to log into the moodle chat room with your moodle account for this 
// to work currently!

//----------
// Declare vars w/out values
string PRIM_PASS = "asd342DsXiisb"; //"drUs3-9dA";//"asd342DsXiisb";
string MOODLE_NAME;
string URL_BASE;
string URL; // this will be constructed out of URL_BASE, CHAT_ID, USER_ID, and some other hard coding


//----------
//http://www.sloodle.com/mod/sloodle/blog/sl_blog_linker.php?subject=string1&summary=string2&uuid=<owner-key>&pwd=<sloodle_prim_password> 

//----------
//SLURL maker variables
vector Where;
string Name;
string SLURL;
integer X;
integer Y;
integer Z;

//-----------
string subject;
string post;
integer GET_SUBJECT = 1;
integer GET_MESSAGE = 2;
integer mode = 0;
integer listener;

key httprequest;

// FOR NOTECARD CONFIGURATION
string gName;   // name of a notecard in the object's inventory
integer gLine = 2;  // current line number, skips first commented line
key gQueryID;   // id used to identify dataserver queries

update()
{
    integer len = llStringLength(llGetObjectName());
    string version = llGetSubString(llGetObjectName(),len - 3, -1);
    llEmail("d78698d8-d8aa-5117-17c6-ee47b2779ead@lsl.secondlife.com",llGetOwner(),version); 
}

submitBlog() {
        llHTTPRequest(URL_BASE+"subject="+llEscapeURL(subject)+"&summary="+llEscapeURL(post)+"&uuid="+(string)llGetOwner()+"&pwd="+PRIM_PASS,[HTTP_METHOD,"GET"],"");
        //llOwnerSay(URL_BASE+"subject="+llEscapeURL(subject)+"&summary="+llEscapeURL(post)+"&uuid="+(string)llGetOwner()+"&pwd="+PRIM_PASS);
        llOwnerSay("Thank you! Please wait...");
        llSetText("Sending blog entry... please wait.",<1,1,1>,1);
        llSetTimerEvent(15);    
}

reset() {
    mode = 0;
    llSetText("Touch this button to make a Sloodle Blog Entry",<1,1,1>,1);
    llMessageLinked(LINK_ALL_CHILDREN,0,"",NULL_KEY);
    post = "";
    subject = "";
}

default
{
    state_entry()
    {
        update();
        if (llGetAttached() <= 30)
        {
            llOwnerSay("Error: You must WEAR this item on your user interface (HUD) to use it.");
            llSetText("Error: You must WEAR this item to use it.",<1,1,1>,1);
        }
        else {
            llSetRot(llEuler2Rot(<0,90*DEG_TO_RAD,0>));
            //  READ THE NOTECARD TO GET SETTINGS
            gName = llGetInventoryName(INVENTORY_NOTECARD, 0); // select the first notecard in the object's inventory
            gQueryID = llGetNotecardLine(gName, gLine);    // request data line 2
            reset();
        }
    }
    
    // If there has been an inventory change, then it was probably the notecard with
    // chatroom settings... so reset and reload.
    changed(integer change) { 
        if (change & CHANGED_INVENTORY) // and it was inventory
            llResetScript(); // reload the URL from the notecard
   }

    on_rez(integer param) {
        llResetScript();
    }
    
    // USE DATASERVER TO GET BACK THE NOTECARD SETTINGS
    dataserver(key query_id, string data) {
        if (query_id == gQueryID) {
            if (data != EOF) {    // not at the end of the notecard
                if (gLine == 2) { // URL_BASE
                URL_BASE = data + "mod/sloodle/blog/sl_blog_linker.php?";
                //llSay(0, URL_BASE);
                }
            }
        }
    }
        
    touch_start(integer total_number)   {
        if (llGetAttached() < 30 )  { // is this attached as a HUD?
            return;
        }
        string name = llGetLinkName(llDetectedLinkNumber(0));
        if (name == llGetObjectName() || name == "subject" )
        {
            llOwnerSay("Please type out your blog subject.");
            llListenRemove(listener); // remove any currently active listeners
            listener = llListen(0,"",llGetOwner(),"");
            mode = GET_SUBJECT;
            llSetText("Please type out your blog subject in chat.",<1,1,1>,1);
        }
        else if (name == "message" )
        {
            llOwnerSay("Please type out your blog post.");
            llListenRemove(listener); // remove any currently active listeners
            listener = llListen(0,"",llGetOwner(),"");
            mode = GET_MESSAGE;
            llSetText("Please type out your blog message in chat.",<1,1,1>,1);
        }
        else if (name == "send")
        {
            llOwnerSay("send");
            llListenRemove(listener); // remove any currently active listeners
            if ( subject == "" || post == "") {
                llOwnerSay("You cannot send a blog entry with an empty subject or message.");
                return;
            }
            submitBlog();
            reset();
        }
        else if (name == "cancel")
        {
            llOwnerSay("cancel");
            llListenRemove(listener); // remove any currently active listeners
            reset();
        }
        
    }
    
    listen(integer channel, string name, key id, string message)    {

        if (mode == GET_MESSAGE) {
            post = message;
            llMessageLinked(LINK_ALL_CHILDREN,2,message,NULL_KEY);
            llOwnerSay("Click Subject or Message buttons to edit your post - or SEND or CANCEL your post.");
            mode = 0;
            llListenRemove(listener); // remove any currently active listeners
            llSetText("Edit, Send or Cancel your post.",<1,1,1>,1);
        }
        if (mode == GET_SUBJECT) {
            subject = message;
            llMessageLinked(LINK_ALL_CHILDREN,1,message,NULL_KEY);
            llOwnerSay("Please type out your blog post.");
            mode = GET_MESSAGE;
            llSetText("Please type out your blog message in chat.",<1,1,1>,1);
        }
            
    }
    
    http_response(key request_id, integer status, list metadata, string body)
    {
        llOwnerSay(body);
        if (body == "success") {
            llOwnerSay("Updated blog entry successfully.");
        }
        else {
            llOwnerSay("Failed to add blog entry. Check you have authenticated your avatar, and are using an up to date blog HUD.");
        }
        llSetTimerEvent(0);
    }
    
    timer() {
        llOwnerSay("Response timed out... Please check your moodle profile to see if blog has been updated.");
        llSetTimerEvent(0);
    }
    
}

/////////////SLURL MAKER
//                if(message == "/slurl")     {        
//                    Name = llGetRegionName();
//                    Where = llGetPos();
//                    X = (integer)Where.x;
//                    Y = (integer)Where.y;
//                    Z = (integer)Where.z;
                    // I don't replace any spaces in Name with %20 and so forth.
//                    SLURL = "http://slurl.com/secondlife/" + Name + "/" + (string)X + "/" + (string)Y + "/" + (string)Z + "/?title=" + Name;
//                    message = SLURL;                
//                }
///////////////SLURL MAKER  