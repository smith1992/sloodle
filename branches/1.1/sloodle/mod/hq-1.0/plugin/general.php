<?php
/**
* Defines a plugin class for the SLOODLE hq -
* 
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
*
* @contributer Paul G. Preibisch - aka Fire Centaur 
* 
*/


class sloodle_hq_plugin_general {

  
     
     /**********************************************************
     * getSloodleObjects will return all the distributers in this course
     * 
     */
     
      function getSloodleObjects($data){
        global $sloodle;
        $data=$sloodle->request->optional_param('data'); 
        $bits = explode("|", $data);
        $type =  getFieldData($bits[0]);
        
        $index = getFieldData($bits[1]);
        $itemsPerPage=getFieldData($bits[2]);
        $dataLine="";
        $counter = 0;
        //$cmid=$sloodle->request->required_param('sloodlemoduleid'); 
        //$sCourseObj = new sloodleCourseObj($cmid);  
        $courseId = $sloodle->course->get_course_id();
        
        if ($type!="null"){
         $sloodleObjects= get_records_select('sloodle','course='.$courseId.' AND type=\''.$type.'\'');   
        }//endif
        else {
           $sloodleObjects= get_records_select('sloodle','course='.$courseId); 
        }//end else
        
        if ($sloodleObjects){
            $sloodle->response->set_status_code(1);          //line 0 
            $sloodle->response->set_status_descriptor('OK'); //line 0
            $sloodle->response->add_data_line("INDEX:".$index); 
            $sloodle->response->add_data_line("#OBJS:".count($sloodleObjects)); 
            foreach($sloodleObjects as $obj){                
                 if (($counter>=($index*$itemsPerPage))&&($counter<($index*$itemsPerPage+$itemsPerPage))){        
                    $sloodle->response->add_data_line("ID:".$obj->id."|NAME:".$obj->name);
                 }//endif 
            }//foreach
      }else { //if ($awars) - no awards
          $sloodle->response->set_status_code(-901100);    //no objects of type specified for this course
          $sloodle->response->set_status_descriptor('HQ'); //line 0
      }
    } //getSloodleObjects($data)
     
}//class
?>