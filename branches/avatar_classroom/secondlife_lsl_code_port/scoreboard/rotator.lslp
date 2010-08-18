integer PLUGIN_CHANNEL=998821; //channel api commands come from  
integer PLUGIN_RESPONSE_CHANNEL=998822; //channel the api responds on

integer NUM_API_SCRIPTS=3;
integer counter=0;
debug(string str){
            if (llList2Integer(llGetPrimitiveParams([PRIM_MATERIAL]),0)==PRIM_MATERIAL_FLESH){
                llOwnerSay(llGetScriptName()+" " +str);
           }
        }
default {
    state_entry() {
       
    }
    link_message(integer sender_num, integer channel, string str, key id) {
        if (channel==PLUGIN_CHANNEL){
            
            llMessageLinked(LINK_SET, PLUGIN_CHANNEL+10+counter++, str, id);
            if (counter>NUM_API_SCRIPTS) counter=0;
            
        }
    }
}
