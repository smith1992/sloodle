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
* @see award
*
* @contributer Paul Preibisch - aka Fire Centaur 
*/

 /** SLOODLE course data structure */
global $CFG;   
require_once(SLOODLE_DIRROOT.'/view/base/base_view_module.php');   


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
          
         $this->courseContext = get_context_instance(CONTEXT_MODULE, $this->cm->id);
         
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
          $this->courseContext =get_context_instance_by_id((int)$this->cm->instance);
            //  get_records('context','instanceid',(int)$this->cm->instance);
          
        // Fetch the SLOODLE instance itself
        if (!$this->sloodleRec = get_record('sloodle', 'id', $this->cm->instance)) error('Failed to find SLOODLE module instance');
            
        //set sloodleId  (id of module instance)          
        $this->sloodleId= $this->cm->instance;  
      }     
      /**
        * Returns a list of avatars in the course
        * @param $userList an array of users of the site
        * @return array of table rows of avatars (userid,username,avname,uuid)
        */
      function getAvatarList($userList){
         $avList = array();
         if ($userList){
         foreach ($userList as $u){             
             $sloodledata = get_records('sloodle_users', 'userid', $u->id);   
             //only adds users who have a linked avatar
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
         }
         return $avList;
      }
       /**
        * Returns a list of groups in the course
        * @param $courseid This is the id of the course 
        * @return array of table rows
        */
      function get_groups($courseid){
         return  get_records('groups', 'id', $courseid);             
         
      } 
      
       /**
        * Returns sloodleid of the course
        * @return integer sloodle id
        */      
      function getSloodleId(){
        return $this->sloodleId;
      }  
      /**
        * Returns true or false if user is a teacher
        * @param $userid 
        * @return bool true or false if user is a teacher
        */
      function is_teacher($userid){
           $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
          
          if (has_capability('moodle/course:manageactivities',$context, $userid)) { 
              return true;
          }
          else return false;
          
          
      }
      /**
        * Returns a list of users in the course
        * @return array of table rows
        */
      function getUserList(){
            global $CFG;  
            
           //get all the users from the users table in the moodle database that are members in this class   
           $sql = "select u.*, ra.roleid from ".$CFG->prefix."role_assignments ra, ".$CFG->prefix."context con, ".$CFG->prefix."course c, ".$CFG->prefix."user u ";
           $sql .= " where ra.userid=u.id and ra.contextid=con.id and con.instanceid=c.id and c.id=".$this->cm->course;
           
           
           $fullUserList = get_records_sql($sql);          
           return $fullUserList;                          
      }
  
     /**
     * Returns a url of that points to a users moodle profile
     * @param $user user object
     * @return string url pointing to users moodle profile
     */      
     function get_moodleUserProfile($user){
         global $CFG;
        // Construct URLs to this user's Moodle and SLOODLE profile pages
        $url_moodleprofile = $CFG->wwwroot."/user/view.php?id={$user->id}&amp;course={$this->courseId}";
        return $url_moodleprofile;
     }
          /**
     * Returns a url of that points to a users sloodle profile
     * @param $user user object* @return string url pointing to users sloodle profile
     */ 
     function get_sloodleprofile($user){
         global $CFG;
        $url_sloodleprofile = SLOODLE_WWWROOT."/view.php?_type=user&amp;id={$user->id}&amp;course={$this->courseId}";        
        return $url_sloodleprofile;
     }
      /**
        * Searches sloodle_users for a users id based on uuid of avatar
        * @param $avuuid 
        * @return array sloodle_user table row
        */
     function get_user_by_uuid($avuuid){
         global $CFG;         
         $avuuid = sloodle_clean_for_db($avuuid);
         $rec=get_record_select('sloodle_users','uuid',$avuuid);
         if (empty($rec)) return null;       
         return $rec;
         
     }
  }
        
?>
