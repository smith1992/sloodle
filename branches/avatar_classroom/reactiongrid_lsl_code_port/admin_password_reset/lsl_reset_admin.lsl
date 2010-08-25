// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.admin_password_reset.lsl_reset_admin.lslp Wed Aug 25 13:52:43 Pacific Daylight Time 2010
key change_pass_id;

string TEMPLATE = "dev";
string API_URL = "http://api.avatarclassroom.com/api/api.php";
string AVATAR_CLASSROOM_PASSWORD = "128sdfKiweriojs012";
list HTTP_VARS = [HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"];
vector YELLOW = <0.82192,0.86066,0.0>;

string hoverText;
integer counter;
string SOUND = "ON";
integer INVALID_PASSWORD_ERROR = -20002;
integer LIST_SITES_NO_SITES_FOUND = 20101;
integer CREATE_SITE_OK = 20201;
integer CREATE_SITE_ALREADY_EXISTS = -20202;
integer CREATE_SITE_FAILED = -20203;
integer DESTROY_SITE_OK = 20301;
integer DESTROY_SITE_FAILED = -20302;
integer DESTROY_SITE_FAILED_SITE_NOT_FOUND = -20303;
integer DESTROY_SITE_FAILED_OWNER_MISMATCH = -20304;
integer DESTROY_SITE_MISSING_SITE_NAME = -20305;
integer PSEUDO_NOTECARD_SITE_NOT_FOUND = -20402;
integer PSEUDO_NOTECARD_FAILED = -20403;
string OWNER_INFO;
integer MENU_CHANNEL;
string sloodleserverroot;
key http_id;

integer UI_CHANNEL = 89997;
list sites;
 list siteBtns;
playSound(string sound){
    if ((SOUND == "ON")) llTriggerSound(sound,1.0);
}
/***********************************************
*  random_integer()
*  |-->Produces a random integer
***********************************************/ 
integer random_integer(integer min,integer max){
    return (min + ((integer)llFrand(((max - min) + 1))));
}
errorSound(string str){
    if ((llListFindList([20101,20201,20301,(-20002),(-20202),(-20203),(-20302),(-20303),(-20304),(-20305),(-20402),(-20403)],[((integer)str)]) != 0));
    if ((str == ((string)LIST_SITES_NO_SITES_FOUND))) (str = "Error: No avatar classrooms have been found on our server for the specified user.");
    if ((str == ((string)CREATE_SITE_OK))) (str = "Success: An Avatar classroom has successfully been created on our server.");
    if ((str == ((string)DESTROY_SITE_OK))) (str = "Success: We have successfully removed the Avatar Classroom on our server for the specified user.");
    if ((str == ((string)INVALID_PASSWORD_ERROR))) (str = "Error: Invalid password specified");
    if ((str == ((string)CREATE_SITE_ALREADY_EXISTS))) (str = "Error: Create Avatar Classroom Failed.  An Avatar Classroom already exists on our server for the specified user.");
    if ((str == ((string)CREATE_SITE_FAILED))) (str = "Error: Create Avatar Classroom Failed.");
    if ((str == ((string)DESTROY_SITE_FAILED))) (str = "Error: Destroy site failed.");
    if ((str == ((string)DESTROY_SITE_FAILED_SITE_NOT_FOUND))) (str = "Error: Destroy site failed, Avatar Classroom specified was not found on our");
    if ((str == ((string)DESTROY_SITE_FAILED_OWNER_MISMATCH))) (str = "Error: Destroy site failed, Avatar Classroom owner name mismatch.");
    if ((str == ((string)DESTROY_SITE_MISSING_SITE_NAME))) (str = "Error: Destroy site failed, Missing site name");
    if ((str == ((string)PSEUDO_NOTECARD_SITE_NOT_FOUND))) (str = "Error: Notecard site not found");
    if ((str == ((string)PSEUDO_NOTECARD_FAILED))) (str = "Error: Notecard failed");
}
 /***********************************************************************************************
*  s()  k() i() and v() are used so that sending messages is more readable by humans.  
* Ie: instead of sending a linked message as
*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
*  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
*  All these functions do is strip off the text before the ":" char and return a string
***********************************************************************************************/
string s(string ss){
    return llList2String(llParseString2List(ss,[":"],[]),1);
}
key k(string kk){
    return llList2Key(llParseString2List(kk,[":"],[]),1);
}


default {

    on_rez(integer start_param) {
        llResetScript();
    }

    state_entry() {
        llSetText("",YELLOW,1.0);
        llSetTimerEvent(0.25);
        (MENU_CHANNEL = random_integer((-30000),(-40000)));
        llListen(MENU_CHANNEL,"",llGetOwner(),"");
        (OWNER_INFO = ((("owneruuid=" + ((string)llGetOwner())) + "&ownername=") + llEscapeURL(llKey2Name(llGetOwner()))));
        string body = ((((((OWNER_INFO + "&template=") + TEMPLATE) + "&type=") + "list_sites") + "&password=") + AVATAR_CLASSROOM_PASSWORD);
        (http_id = llHTTPRequest(API_URL,HTTP_VARS,body));
    }

        link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(llList2String(cmdList,0));
            string button = s(llList2String(cmdList,1));
            key userKey = k(llList2String(cmdList,2));
            if ((cmd == "BUTTON PRESS")) {
                if ((userKey == llGetOwner())) {
                    string body = ((((((((((("owneruuid=" + ((string)llGetOwner())) + "&ownername=") + llEscapeURL(llList2String(sites,2))) + "&template=") + TEMPLATE) + "&sitename=") + llList2String(sites,2)) + "&type=") + "change_password") + "&password=") + AVATAR_CLASSROOM_PASSWORD);
                    (change_pass_id = llHTTPRequest(API_URL,HTTP_VARS,body));
                    playSound("resetadminpass");
                }
            }
        }
    }

   
 http_response(key request_id,integer status,list metadata,string body) {
        llSetTimerEvent(0);
        (hoverText = "");
        (counter = 0);
        llSetText("",YELLOW,1);
        list lines = llParseStringKeepNulls(body,["\n"],[]);
        integer statusLine = ((integer)llList2String(lines,0));
        if ((request_id == change_pass_id)) {
            if ((((integer)statusLine) == ((integer)20301))) {
                string password = llList2String(lines,1);
                string msg = ("Password Reset!\nNew Password: " + password);
                llDialog(llGetOwner(),msg,["OK"],(MENU_CHANNEL + 1));
                llOwnerSay(msg);
            }
        }
        else  if ((request_id == http_id)) {
            if ((statusLine < 0)) {
                errorSound(((string)statusLine));
            }
            else  if ((statusLine > 0)) {
                errorSound(((string)statusLine));
                integer i;
                string siteStr;
                (siteBtns = []);
                (sites = []);
                for ((i = 1); (i < llGetListLength(lines)); (i++)) {
                    string sitedataline = llList2String(lines,1);
                    list sitedata = llParseStringKeepNulls(sitedataline,["|"],[]);
                    (sloodleserverroot = llList2String(sitedata,6));
                    string sitename = llList2String(sitedata,1);
                    string user = llList2String(sitedata,8);
                    string pass = llList2String(sitedata,9);
                    (sites += [i,sloodleserverroot,sitename,user,pass]);
                    (siteStr += (("Site " + ((string)i)) + "\n"));
                    (siteBtns += ("Site " + ((string)i)));
                    llSetText(sitename,YELLOW,1.0);
                }
            }
        }
    }
}
