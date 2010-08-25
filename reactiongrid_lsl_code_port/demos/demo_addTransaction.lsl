// LSL script generated: avatar_classroom2.reactiongrid_lsl_code_port.demos.demo_addTransaction.lslp Wed Aug 25 13:52:43 Pacific Daylight Time 2010

integer PLUGIN_CHANNEL = 998821;
default {

    state_entry() {
    }

    touch_start(integer num_detected) {
        key owner = llGetOwner();
        string avname = llKey2Name(llGetOwner());
        string authenticatedUser = ((("&sloodleuuid=" + ((string)llGetOwner())) + "&sloodleavname=") + llEscapeURL(avname));
        string currency = llGetObjectDesc();
        if ((currency == "")) (currency = "Credits");
        llMessageLinked(LINK_SET,PLUGIN_CHANNEL,(((((((("awards->addTransaction" + authenticatedUser) + "&avname=") + llEscapeURL(avname)) + "&avuuid=") + ((string)llGetOwner())) + "&currency=") + llEscapeURL(currency)) + "&amount=30"),NULL_KEY);
    }
}
