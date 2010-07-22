integer UI_CHANNEL                                                            =89997;//UI Channel - main channel
default {
    state_entry() {
        llOwnerSay("Hello Scripter");
    }    
changed(integer change) {
        if (change ==CHANGED_LINK){                       
                llSay(0,"\n\n\n\n\n\n\nevent was: "+(string)change);
                  if (change ==CHANGED_LINK){
                      llMessageLinked(LINK_SET, UI_CHANNEL, "CMD:sitter", llGetLinkKey(8));                       
                  }//changelink
         }//if
     }//linked
}
