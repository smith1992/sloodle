vector start_pos;
rotation start_rot;

default {
    
    state_entry() {
        start_pos = llGetPos();
        start_rot = llGetRot();
        llSetTimerEvent(1);
    }
    
    //moving_start() {
    //    start = llGetPos();
    //   // llSleep(2.0); // this prevents the end event from getting triggered earlier than we want
   // }
        
    //moving_end() {
    //    vector to = llGetPos();        
    //    llSay(232323, (string)(start - to));
    //    llSay(0, (string)(start - to));        
    //    start = to;
   // }
   

   //  touch_start(integer n) {   
    //    vector to_pos = llGetPos();
    //    rotation to_rot = llGetRot(); 
 //       if ( (to_pos == start_pos) && (start_rot == to_rot) ) {
//            llSetTimerEvent(2.5);             
//            return;
//        }
        
       // llOwnerSay( "rotated by "+ (string)llRot2Euler( to_rot / start_rot ) );

       // llSay(232323, (string)(start_pos - to_pos) + "|" + (string)(to_rot / start_rot) + "|" + (string)to_pos);
      //  start_pos = to_pos;
       // start_rot = to_rot;
   // }   
   
    timer() {
        vector to_pos = llGetPos();
        rotation to_rot = llGetRot(); 
        if ( (to_pos == start_pos) && (start_rot == to_rot) ) {
            llSetTimerEvent(2.5);
            return;
        }
        
       // llOwnerSay( "rotated by "+ (string)llRot2Euler(  to_rot / start_rot ) );

        llSay(232323, (string)(start_pos - to_pos) + "|" + (string)(to_rot / start_rot) + "|" + (string)to_pos);
        start_pos = to_pos;
        start_rot = to_rot;
    }
}
