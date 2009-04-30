<?php
/**
* sloodlecourseobject provides basic functionality for accessing information about
* the students, including a student list, and avatar list
*
* 
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
* @see http://slisweb.sjsu.edu/sl/index.php/Sloodle_Stipend_Giver
* @see iBank
*
* @contributer Paul Preibisch - aka Fire Centaur 
*/

 /** SLOODLE course data structure */
 
require_once(SLOODLE_DIRROOT.'/view/base/base_view_module.php');   
global $CFG; 

  class sloodleCourseObj{
      
      
      var $cm;
      
      var $courseRec;
      
      var $courseId;
      /**
      * URL for accessing the current course.
      * @var string
      * @access private
      */
      var $courseUrl = '';
      
      /**
      * Full name of the current course.
      * @var string
       * @access private
      */
      var $courseFullName = '';
  
      /**
      * Short name of the current course.
      * @var string
      * @access private
      */
      var $courseShortName = '';
      
      var $courseContext=null;
      
      var $sloodleCourseObject;
      
      var $sloodleRec;
      
      var $sloodleId;
      
      var $userList;
      
      var $avatarList;
   

           
      //constructor
      function sloodleCourseObj($id){
          
          if(!$this->cm = get_coursemodule_from_id('sloodle',$id)) error ('Course module ID was incorrect.');
          //coursemodule id
          
           
          // Course object
          if (!$this->courseRec = get_record('course', 'id', $this->cm->course)) error('Failed to retrieve course.');            
          
          //set course object
          $this->courseId = $this->cm->course;

          $this->courseFullName = $this->courseRec->fullname;
          $this->courseShortName = $this->courseRec->shortname;
          
         //set sloodle course object          
          $this->sloodleCourseObject = new SloodleCourse();
          
          $this->userList = $this->getUserList();
          $this->avatarList = $this->getAvatarList($this->userList);
          
        if (!$this->sloodleCourseObject->load($this->courseRec)) error(get_string('failedcourseload', 'sloodle'));
        //set course context
          $this->courseContext = get_record('context','instanceid',(int)$this->cm->instance);   
             
          
        // Fetch the SLOODLE instance itself
        if (!$this->sloodleRec = get_record('sloodle', 'id', $this->cm->instance)) error('Failed to find SLOODLE module instance');
            
        //set sloodleId  (id of module instance)          
        $this->sloodleId= $this->cm->instance;  

      
      
      }     
      
      function get_avatars($userid){
         return  get_records('sloodle_users', 'userid', $userid);   
          
      } 
      //returns a list of avatars in the class
      function getAvatarList($userList){
         $avList = array();
         
         foreach ($userList as $u){             
             $sloodledata = get_records('sloodle_users', 'userid', $u->id);   
             
             if ($sloodledata){
                foreach ($sloodledata as $sd){
                   $av = new stdClass(); 
                   $av->userid = $u->id;
                   $av->username = $u->username;
                   $av->avname = $sd->avname;
                   $av->uuid = $sd->uuid;
                   $avList[]=$av;
                }
             
             
             }
         }
         return $avList;
      }
      function getSloodleId(){
        return $this->sloodleId;
      }  
      function is_teacher($userid){
           $context = get_record('context','instanceid',$this->cm->id);         
          
          if (has_capability('moodle/course:manageactivities',$context, $userid)) { 
              return true;
          }
          else return false;
          
          
      }
      
      function getUserList(){
       
           //get all the users from the users table in the moodle database   
           $fullUserList = get_users(true, '');
            
           if (!$fullUserList) $fullUserList = array();
           $uList = array();
          
           
           // Filter it down to members of the course
           foreach ($fullUserList as $ful) {
                //get context of the course
                // Is this user on this course?
                                           
            
              $context = get_record('context','instanceid',$this->cm->id); 
             

                if (has_capability('moodle/course:view',$context, $ful->id)) {
                    

                    // Copy it to our filtered list and exclude administrators
               //  if (!isadmin($ful->id)){     
                 //now add sloodle data to ful array
                         $uList[]=$ful;
                 //  }
                
                    
                } 

           }
           //now add avatar data to each user record
            //sort user list either by avnames
          //  sort($uList,SORT_STRING);
           return $uList;                          
      }
  
   
     /**
     * setUserList set's the private userList array
     * @param $this->userList
     * @return null
     */   
     
     function setUserList($list){
        $this->userList = $list;
     }
     
     function get_moodleUserProfile($u){
         global $CFG;
        // Construct URLs to this user's Moodle and SLOODLE profile pages
        $url_moodleprofile = $CFG->wwwroot."/user/view.php?id={$u->id}&amp;course={$this->courseId}";
        return $url_moodleprofile;
     }
     
     function get_sloodleprofile($u){
         global $CFG;
        $url_sloodleprofile = SLOODLE_WWWROOT."/view.php?_type=user&amp;id={$u->id}&amp;course={$this->courseId}";        
        return $url_sloodleprofile;
     }
  }
        
?>
