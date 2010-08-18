integer SLOODLE_CHANNEL_OBJECT_DIALOG = -3857343;
stars(){
	llParticleSystem([PSYS_PART_MAX_AGE,4.16,
PSYS_PART_FLAGS, 263,
PSYS_PART_START_COLOR, <0.97466, 0.94959, 0.10151>,
PSYS_PART_END_COLOR, <0.77446, 0.23138, 0.02765>,
PSYS_PART_START_SCALE,<1.33333, 1.32886, 0.00000>,
PSYS_PART_END_SCALE,<0.00000, 0.00000, 0.00000>,
PSYS_SRC_PATTERN, 2,
PSYS_SRC_BURST_RATE,1.64,
PSYS_SRC_ACCEL, <-0.02000, -0.05892, 0.00000>,
PSYS_SRC_BURST_PART_COUNT,25,
PSYS_SRC_BURST_RADIUS,0.00,
PSYS_SRC_BURST_SPEED_MIN,0.25,
PSYS_SRC_BURST_SPEED_MAX,0.38,
PSYS_SRC_ANGLE_BEGIN, 0.00,
PSYS_SRC_ANGLE_END, 0.00,
PSYS_SRC_OMEGA, <-0.01100, 0.00000, 0.00000>,
PSYS_SRC_MAX_AGE, 0.0,
PSYS_SRC_TEXTURE, "fe054e23-14c3-23cd-a06b-ccaf97c42ea5",
PSYS_PART_START_ALPHA, 1.00,
PSYS_PART_END_ALPHA, 0.00]);
}
string SOUND="ON";
playSound(string sound){
	if (SOUND=="ON")llTriggerSound(sound, 1.0);
}

default {
    state_entry() {
        //if (button == "panel") llMessageLinked(LINK_THIS, SLOODLE_CHANNEL_OBJECT_DIALOG, "do:regenrol|" + sloodleserverroot + "|" + (string)sloodlecontrollerid + "|" + sloodlepwd, id);
    }
    link_message(integer sender_num, integer chan, string str, key id) {
    	if (chan!=SLOODLE_CHANNEL_OBJECT_DIALOG) return;
    		        list cmdList = llParseString2List(str, ["|"], []);        
        			string cmd = llList2String(cmdList,0);
        			if (cmd=="do:regenrol"){
        				stars();
        				llSetColor(YELLOW, ALL_SIDES);
        				llTriggerSound("chimes", 1.0);
        				llSetTimerEvent(1);
        			}
    	
    }
    timer() {
    	llSetColor(WHITE, ALL_SIDES);
    	llSetTimerEvent(0);
    	llParticleSystem([]);
    }

}
