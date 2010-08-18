// LSL script generated: avatar_classroom.secondlife_port.scoreboard.rotator.lslp Tue Aug 17 22:11:15 Pacific Daylight Time 2010
integer PLUGIN_CHANNEL = 998821;

integer NUM_API_SCRIPTS = 3;
integer counter = 0;
default {

    state_entry() {
    }

    link_message(integer sender_num,integer channel,string str,key id) {
        if ((channel == PLUGIN_CHANNEL)) {
            llMessageLinked(LINK_SET,((PLUGIN_CHANNEL + 10) + (counter++)),str,id);
            if ((counter > NUM_API_SCRIPTS)) (counter = 0);
        }
    }
}
