integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
poofer(key texture){
	llParticleSystem([PSYS_PART_MAX_AGE,1.98,
	PSYS_PART_FLAGS, 259,
	PSYS_PART_START_COLOR, <0.98715, 1.00000, 0.93575>,
	PSYS_PART_END_COLOR, <0.88738, 1.00000, 0.93243>,
	PSYS_PART_START_SCALE,<0.75290, 0.82027, 0.00000>,
	PSYS_PART_END_SCALE,<0.20276, 0.19653, 0.00000>,
	PSYS_SRC_PATTERN, 2,
	PSYS_SRC_BURST_RATE,0.65,
	PSYS_SRC_ACCEL, <0.00000, 0.00000, 0.50267>,
	PSYS_SRC_BURST_PART_COUNT,2,
	PSYS_SRC_BURST_RADIUS,0.63,
	PSYS_SRC_BURST_SPEED_MIN,0.01,
	PSYS_SRC_BURST_SPEED_MAX,0.00,
	PSYS_SRC_ANGLE_BEGIN, 3.14,
	PSYS_SRC_ANGLE_END, 0.00,
	PSYS_SRC_OMEGA, <0.00000, 0.94445, -1.82174>,
	PSYS_SRC_MAX_AGE, 0.0,
	PSYS_SRC_TEXTURE, texture,
	PSYS_PART_START_ALPHA, 1.00,
	PSYS_PART_END_ALPHA, 0.20]);
	}
//gets a vector from a string
	vector getVector(string vStr){
	        vStr=llGetSubString(vStr, 1, llStringLength(vStr)-2);
	        list vStrList= llParseString2List(vStr, [","], ["<",">"]);
	        vector output= <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2)>;
	        return output;
	}//end getVector
	rotation getRot(string vStr){
	        vStr=llGetSubString(vStr, 1, llStringLength(vStr)-2);
	        list vStrList= llParseString2List(vStr, [","], ["<",">"]);
	        rotation output= <llList2Float(vStrList,0),llList2Float(vStrList,1),llList2Float(vStrList,2),llList2Float(vStrList,3)>;
	        return output;
	}//end getRot
	/***********************************************************************************************
	*  s()  k() i() and v() are used so that sending messages is more readable by humans.  
	* Ie: instead of sending a linked message as
	*  GETDATA|50091bcd-d86d-3749-c8a2-055842b33484 
	*  Context is added with a tag: COMMAND:GETDATA|PLAYERUUID:50091bcd-d86d-3749-c8a2-055842b33484
	*  All these functions do is strip off the text before the ":" char and return a string
	***********************************************************************************************/
	string s (list ss,integer indx){
	    return llList2String(llParseString2List(llList2String(ss,indx), [":"], []),1);
	}//end function
	key k (string kk){
	    return llList2Key(llParseString2List(kk, [":"], []),1);
	}//end function
	integer i (string ii){
	    return llList2Integer(llParseString2List(ii, [":"], []),1);
	}//end function
	vector v (string vv){
	    integer p = llSubStringIndex(vv, ":");
	    string vString = llGetSubString(vv, p+1, llStringLength(vv));
	    return getVector(vString);
	}//end function
	rotation r (string rr){
	    integer p = llSubStringIndex(rr, ":");
	    string rString = llGetSubString(rr, p+1, llStringLength(rr));
	    return getRot(rString);
	}//end function
default {
    state_entry() {
        llParticleSystem([]);
    }
    link_message(integer sender_num, integer chan, string str, key id) {
    	if (chan==UI_CHANNEL){
    		 list cmdList = llParseString2List(str, ["|"], []);        
			string cmd = s(cmdList,0);
			if (cmd=="POOFER"){
				llSetTimerEvent(3);
				poofer(s(cmdList,1));
			}    
    	}else
    	if (chan == SLOODLE_CHANNEL_OBJECT_DIALOG) {
                     if (str=="do:requestconfig"){
                         llResetScript();
                     }
                 }
    }
    timer() {
    	llSetTimerEvent(0);
    	llParticleSystem([]);
    }
      changed(integer change) {
     if (change ==CHANGED_INVENTORY){         
         llResetScript();
     }//end if
    }//end changed
}
