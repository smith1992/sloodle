// LSL script generated: avatar_classroom.secondlife_port.regEnrol.manual_reg.lslp Tue Aug 17 22:11:02 Pacific Daylight Time 2010

// Manual avatar registration and enrolment script.
// Will initiate manual (URL-based) avatar registration/enrolment in
//  response to link messages.
//
// Part of the Sloodle project (www.sloodle.org).
// Copyright (c) 2008 Sloodle
// Released under the GNU GPL v3
//
// Contributors:
//  Peter R. Bloomfield
//

// Can be supplied with 3 link messages for registration only, enrolment only, or both.
// The avatar in question should be identified in the key value for all 3.
// The string value should take on one of the following formats:
//
//  <cmd>|<sloodleserverroot>|<sloodlecontrollerid>|<prim_pwd>
//  <cmd>|<sloodleserverroot>|<sloodlecontrollerid>|<uuid>|<pwd>
//
// The <cmd> value should be one of the following:
//
//  do:reg = registration
//  do:enrol = enrolment
//  do:regenrol = registration & enrolment
//
string SOUND = "ON";
///// DATA /////
integer SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST = -1828374651;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
string SLOODLE_REG_LINKER = "/mod/sloodle/login/regenrol_linker.php";

list httpreqs = [];


///// TRANSLATION /////

// Link message channels
integer SLOODLE_CHANNEL_TRANSLATION_REQUEST = -1928374651;
string SLOODLE_TRANSLATE_SAY = "say";
string SLOODLE_TRANSLATE_LOAD_URL_PARALLEL = "loadurlpar";
string SLOODLE_TRANSLATE_IM = "instantmessage";
playSound(string sound){
    if ((SOUND == "ON")) llTriggerSound(sound,1.0);
}

// Send a translation request link message
sloodle_translation_request(string output_method,list output_params,string string_name,list string_params,key keyval,string batch){
    llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_TRANSLATION_REQUEST,((((((((output_method + "|") + llList2CSV(output_params)) + "|") + string_name) + "|") + llList2CSV(string_params)) + "|") + batch),keyval);
}

///// ----------- /////


///// FUNCTIONS /////
/******************************************************************************************************************************
* sloodle_error_code - 
* Author: Paul Preibisch
* Description - This function sends a linked message on the SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST channel
* The error_messages script hears this, translates the status code and sends an instant message to the avuuid
* Params: method - SLOODLE_TRANSLATE_SAY, SLOODLE_TRANSLATE_IM etc
* Params:  avuuid - this is the avatar UUID to that an instant message with the translated error code will be sent to
* Params: status code - the status code of the error as on our wiki: http://slisweb.sjsu.edu/sl/index.php/Sloodle_status_codes
*******************************************************************************************************************************/
sloodle_error_code(string method,key avuuid,integer statuscode){
    llMessageLinked(LINK_SET,SLOODLE_CHANNEL_ERROR_TRANSLATION_REQUEST,((((method + "|") + ((string)avuuid)) + "|") + ((string)statuscode)),NULL_KEY);
}


///// STATES /////

// Only a single state... no configuration needed
default {

    state_entry() {
    }

    
    link_message(integer sender_num,integer num,string sval,key kval) {
        if ((num == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if (((kval == NULL_KEY) || (kval != llGetOwnerKey(kval)))) return;
            list fields = llParseStringKeepNulls(sval,["|"],[]);
            integer numfields = llGetListLength(fields);
            if ((numfields < 4)) return;
            string cmd = llList2String(fields,0);
            if ((((cmd != "do:reg") && (cmd != "do:enrol")) && (cmd != "do:regenrol"))) return;
            string sloodlemode = llGetSubString(cmd,3,(-1));
            string sloodleserverroot = llList2String(fields,1);
            integer sloodlecontrollerid = ((integer)llList2String(fields,2));
            string sloodlepwd = llList2String(fields,3);
            if ((numfields > 4)) {
                (sloodlepwd += ("|" + llList2String(fields,4)));
            }
            sloodle_translation_request(SLOODLE_TRANSLATE_IM,[],("attempting" + sloodlemode),[],kval,"regenrol");
            string body = ("sloodlecontrollerid=" + ((string)sloodlecontrollerid));
            (body += ("&sloodlepwd=" + sloodlepwd));
            (body += ("&sloodleuuid=" + ((string)kval)));
            (body += ("&sloodleavname=" + llKey2Name(kval)));
            (body += ("&sloodlemode=" + sloodlemode));
            key newhttp = llHTTPRequest((sloodleserverroot + SLOODLE_REG_LINKER),[HTTP_METHOD,"POST",HTTP_MIMETYPE,"application/x-www-form-urlencoded"],body);
            (httpreqs += [newhttp,kval]);
        }
    }

    
    http_response(key id,integer status,list meta,string body) {
        integer pos = llListFindList(httpreqs,[id]);
        if ((pos < 0)) return;
        key av = llList2Key(httpreqs,(pos + 1));
        (httpreqs = llDeleteSubList(httpreqs,pos,(pos + 1)));
        if ((status != 200)) {
            sloodle_translation_request(SLOODLE_TRANSLATE_IM,[0],"httperror:code",[status],av,"");
            return;
        }
        list lines = llParseStringKeepNulls(body,["\n"],[]);
        integer numlines = llGetListLength(lines);
        list statusfields = llParseStringKeepNulls(llList2String(lines,0),["|"],[]);
        integer statuscode = ((integer)llList2String(statusfields,0));
        if ((statuscode == (-321))) {
            sloodle_translation_request(SLOODLE_TRANSLATE_IM,[0],"enrolfailed:notreg",[statuscode],av,"regenrol");
            return;
        }
        else  if ((statuscode == 301)) {
            sloodle_translation_request(SLOODLE_TRANSLATE_IM,[0],"alreadyauthenticated",[llKey2Name(av)],av,"regenrol");
            llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_OBJECT_DIALOG,"confirm:reg",av);
            return;
        }
        else  if ((statuscode == 401)) {
            sloodle_translation_request(SLOODLE_TRANSLATE_IM,[0],"alreadyenrolled",[llKey2Name(av)],av,"regenrol");
            playSound("alreadyenrolled");
            llMessageLinked(LINK_THIS,SLOODLE_CHANNEL_OBJECT_DIALOG,"confirm:enrol",av);
            return;
        }
        else  if ((statuscode <= 0)) {
            sloodle_error_code(SLOODLE_TRANSLATE_SAY,NULL_KEY,statuscode);
            return;
        }
        if ((numlines < 2)) {
            sloodle_translation_request(SLOODLE_TRANSLATE_IM,[0],"badresponseformat",[],av,"");
            return;
        }
        string url = llList2String(lines,1);
        sloodle_translation_request(SLOODLE_TRANSLATE_LOAD_URL_PARALLEL,[url],"regenrolurl",[],av,"regenrol");
    }
}
