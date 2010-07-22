// LSL script generated: _SLOODLE_HOUSE.QUIZCHAIR.poofer.lslp Thu Jul 22 02:04:05 Pacific Daylight Time 2010
integer UI_CHANNEL = 89997;
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
poofer(key texture){
    llParticleSystem([PSYS_PART_MAX_AGE,1.98,PSYS_PART_FLAGS,259,PSYS_PART_START_COLOR,<0.98715,1.0,0.93575>,PSYS_PART_END_COLOR,<0.8873800000000001,1.0,0.93243>,PSYS_PART_START_SCALE,<0.7529,0.82027,0.0>,PSYS_PART_END_SCALE,<0.20276,0.19653,0.0>,PSYS_SRC_PATTERN,2,PSYS_SRC_BURST_RATE,0.65,PSYS_SRC_ACCEL,<0.0,0.0,0.50267>,PSYS_SRC_BURST_PART_COUNT,2,PSYS_SRC_BURST_RADIUS,0.63,PSYS_SRC_BURST_SPEED_MIN,1.0e-2,PSYS_SRC_BURST_SPEED_MAX,0.0,PSYS_SRC_ANGLE_BEGIN,3.14,PSYS_SRC_ANGLE_END,0.0,PSYS_SRC_OMEGA,<0.0,0.94445,(-1.8217400000000001)>,PSYS_SRC_MAX_AGE,0.0,PSYS_SRC_TEXTURE,texture,PSYS_PART_START_ALPHA,1.0,PSYS_PART_END_ALPHA,0.2]);
}
	/***********************************************************************************************
	*  s()  k() i() and v() are used so that sending messages is more readable by humans.  
	* Ie: instead of sending a linked message as
	*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
	*  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
	*  All these functions do is strip off the text before the ":" char and return a string
	***********************************************************************************************/
	string s(list ss,integer indx){
    return llList2String(llParseString2List(llList2String(ss,indx),[":"],[]),1);
}
default {

    state_entry() {
        llParticleSystem([]);
    }

    link_message(integer sender_num,integer chan,string str,key id) {
        if ((chan == UI_CHANNEL)) {
            list cmdList = llParseString2List(str,["|"],[]);
            string cmd = s(cmdList,0);
            if ((cmd == "POOFER")) {
                llSetTimerEvent(3);
                poofer(s(cmdList,1));
            }
        }
        else  if ((chan == SLOODLE_CHANNEL_OBJECT_DIALOG)) {
            if ((str == "do:requestconfig")) {
                llResetScript();
            }
        }
    }

    timer() {
        llSetTimerEvent(0);
        llParticleSystem([]);
    }

      changed(integer change) {
        if ((change == CHANGED_INVENTORY)) {
            llResetScript();
        }
    }
}
