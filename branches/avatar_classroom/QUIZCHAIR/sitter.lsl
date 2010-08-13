// LSL script generated: avatar_classroom.QUIZCHAIR.sitter.lslp Wed Aug 11 19:44:11 Pacific Daylight Time 2010
integer UI_CHANNEL = 89997;
default {

    state_entry() {
        llOwnerSay("Hello Scripter");
    }

changed(integer change) {
        if ((change == CHANGED_LINK)) {
            llSay(0,("\n\n\n\n\n\n\nevent was: " + ((string)change)));
            if ((change == CHANGED_LINK)) {
                llMessageLinked(LINK_SET,UI_CHANNEL,"CMD:sitter",llGetLinkKey(8));
            }
        }
    }
}
