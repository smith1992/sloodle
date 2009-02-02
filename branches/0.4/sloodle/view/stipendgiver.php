<?php
/**
* Defines a class for viewing the SLOODLE Distributor module in Moodle.
* Derived from the module view base class.
*
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
*
* @contributor Peter R. Bloomfield
* @contributer Paul Preibisch - aka Fire Centaur 
*/

/** The base module view class */
require_once(SLOODLE_DIRROOT.'/view/base/base_view_module.php');
       
 /** SLOODLE course data structure */
require_once(SLOODLE_LIBROOT.'/course.php');
/** Sloodle Session code. */
require_once(SLOODLE_LIBROOT.'/sloodle_session.php');
/** General Sloodle functionality. */
require_once(SLOODLE_LIBROOT.'/general.php');



/**
* Class for rendering a view of a Distributor module in Moodle.
* @package sloodle
*/
class sloodle_view_stipendgiver extends sloodle_base_view_module
{
    /**
    * SLOODLE data about a Distributor, retrieved directly from the database (table: sloodle_distributor)
    * @var object
    * @access private
    */
    var $stipend = false;

    /**
    * Integer ID of the course which is being accessed.
    * @var integer
    * @access private
    */
    var $courseid = 0;
    
    /**
    * The VLE course object, retrieved directly from database.
    * @var object
    * @access private
    */
    var $course = 0;

    /**
    * SLOODLE course object, retrieved directly from database.
    * @var object
    * @access private
    */
    var $sloodle_course = null;
    
    /**
    * The search string for users where appropriate.
    * @var string
    * @access private
    */
    var $searchstr = '';

    /**
    * Moodle permissions context for the current course.
    * @var object
    * @access private
    */
   
    var $sloodleonly = false;
    
    /**
    * URL for accessing the current course.
    * @var string
    * @access private
    */
    var $courseurl = '';
    
    /**
    * Short name of the current course.
    * @var string
    * @access private
    */
    var $courseshortname = '';
    
    /**
    * Full name of the current course.
    * @var string
    * @access private
    */
    var $coursefullname = '';
    
    /**
    * The result number to start displaying from
    * @var integer
    * @access private
    */
    var $start = 0;  
    /**
    * Constructor.
    */
    function sloodle_base_view_module()
    {
    }
     /**
    * Check and process the request parameters.
    */
    function process_request()
    {
        global $CFG, $USER;
    
        $id = required_param('id', PARAM_INT);
        if (!$this->cm = get_coursemodule_from_id('sloodle', $id)) error('Course module ID was incorrect.');
        // Fetch the course data
        if (!$this->course = get_record('course', 'id', $this->cm->course)) error('Failed to retrieve course.');
        $this->sloodle_course = new SloodleCourse();
        if (!$this->sloodle_course->load($this->course)) error(get_string('failedcourseload', 'sloodle'));

        // Fetch the SLOODLE instance itself
        if (!$this->sloodle = get_record('sloodle', 'id', $this->cm->instance)) error('Failed to find SLOODLE module instance');
        $this->start = optional_param('start', 0, PARAM_INT);
        if ($this->start < 0) $this->start = 0;
    }
    /**
    * Process any form data which has been submitted.
    */
    function process_form()
    {
    }

      /**
    * Override the base_view_module print_header for formatting reasons 
    */
    
     function print_header()
    {
        global $CFG;

        // Offer the user an 'update' button if they are allowed to edit the module
        $editbuttons = '';
        if ($this->canedit) {
            $editbuttons = update_module_button($this->cm->id, $this->course->id, get_string('modulename', 'sloodle'));
        }
        // Display the header
        $navigation = "<a href=\"index.php?{$this->course->id}\">".get_string('modulenameplural','sloodle')."</a> ->";
        print_header_simple(format_string($this->sloodle->name), "", "{$navigation} ".format_string($this->sloodle->name, "", "", true, $editbuttons, navmenu($this->course, $this->cm)));

        // Display the module name
        $img = '<img src="'.$CFG->wwwroot.'/mod/sloodle/icon.gif" width="16" height="16" alt=""/> ';
        print_heading($img.$this->sloodle->name, 'center');
    
        // Display the module type and description
        $fulltypename = get_string("moduletype:{$this->sloodle->type}", 'sloodle');
        echo '<h4 style="text-align:center;">'.get_string('moduletype', 'sloodle').': '.$fulltypename;
        echo helpbutton("moduletype_{$this->sloodle->type}", $fulltypename, 'sloodle', true, false, '', true).'</h4>';
    
    
    }
    /**
    * Gets a list of students in the class
    */
      function get_class_list(){
            $fulluserlist = get_users(true, '');
            if (!$fulluserlist) $fulluserlist = array();
            $userlist = array();
            // Filter it down to members of the course
            foreach ($fulluserlist as $ful) {
                // Is this user on this course?
                if (has_capability('moodle/course:view', $this->course_context, $ful->id)) {
                    // Copy it to our filtered list and exclude administrators
                    if (!isadmin($ful->id))
                      $userlist[] = $ful;
                }
            }
            return $userlist;
      
      }
       

      
    /**
    * Render the view of the Stipend Giver.
    */
    function render()              
    {
        global $CFG, $USER;   
         $this->courseid = $this->course->id; 
         $sloodleid=$this->sloodle->id;
        // Fetch a list of all stipendgiver entries
        print_box_start('generalbox boxaligncenter boxwidthnarrow leftpara'); 
        print('<b style="color:Black;text-align:left;">'.get_string('sloodleobjectstipendgiver:description','sloodle').':</b> '.$this->sloodle->intro.'<br> ');  
        $stipendgiver = get_record('sloodle_stipendgiver', 'sloodleid', $sloodleid);
        print '<b style="color:green;text-align:left;">'.get_string('sloodleobjectstipendgiver:stipendisfor','sloodle').'</b>'. $stipendgiver->amount.'<br>';
        print '<b style="color:green;text-align:left;">'.get_string('sloodleobjectstipendgiver:purpose','sloodle').'</b>'. $stipendgiver->purpose;
        print_box_end();      
        // // ----------- // //
        // Fetch a list of all stipendgiver transactions
        print_box_start('generalbox boxaligncenter boxwidthnarrow leftpara'); 
        print('<h3 style="color:black;text-align:center;">'.get_string('sloodleobjectstipendgiver:transactions','sloodle')).'</h3> ';                                                                                         
    
        //now list the users in this course.   
        $userlist =$this->get_class_list();  
        //get the list of transactions
        $trans = get_records('sloodle_stipendgiver_trans', 'sloodleid', $this->cm->id, 'receiveruuid,date');
		
		if (!$trans) $trans = array(); // THIS IS IMPORTANT - if "get_records" fails, it returns FALSE, so the "foreach" below will fail with an error message -- PRB
		
        //create new array and only store uuid of transactions
        $alltrans=Array();
        //now create another array which will have an  identical size  and only put the withdrawl dates in it
        $dateWithdrawn=Array();
        //build arrays (will be used for searching later)
          foreach ($trans as $t){
            $alltrans[]= $t->receiveruuid;
            $dateWithdrawn[]= $t->date;
          } 
          
          
          //build a course user list
        if ($userlist) {
            
            
            $sloodletable = new stdClass();
            //create column headers for html table
            $sloodletable->head = array(    get_string('user', 'sloodle'),
                                            get_string('avatar', 'sloodle'),
                                            get_string('stipendgiver:alloted', 'sloodle'),
                                            get_string('stipendgiver:withdrawn', 'sloodle'),  
                                            get_string('stipendgiver:date', 'sloodle') 
                                        );
            //set alignment of table cells                                        
            $sloodletable->align = array('left', 'left','center','center','center');
            //set size of table cells
            $sloodletable->size = array('30%', '20%','10%','10%',"30%");
            
            // Check if our start is past the end of our results
            if ($this->start >= count($userlist)) $this->start = 0;
            
            // Go through each entry to add it to the table
            $resultnum = 0;
            $resultsdisplayed = 0;
            $maxperpage = 20;
            foreach ($userlist as $u) {
                // Only display this result if it is after our starting result number
                if ($resultnum >= $this->start) {
                    // Reset the line's content
                    $line = array();
                    
                    // Construct URLs to this user's Moodle and SLOODLE profile pages
                    $url_moodleprofile = $CFG->wwwroot."/user/view.php?id={$u->id}&amp;course={$this->courseid}";
                    $url_sloodleprofile = SLOODLE_WWWROOT."/view.php?_type=user&amp;id={$u->id}&amp;course={$this->courseid}";
                                   
                    // Add the Moodle name
                    $line[] = "<a href=\"{$url_moodleprofile}\">{$u->firstname} {$u->lastname}</a>";
                    
                    // Get the Sloodle data for this Moodle user
                    $sloodledata = get_records('sloodle_users', 'userid', $u->id .",uuid,avname");
                   //initialize our search index    
                    $dateIndex=false;
                    
                    if ($sloodledata) {
                        // Display all avatars names, if available
                        $avnames = '';
                        $firstentry = true;
                        foreach ($sloodledata as $sd) {
                           
                            // If this entry is empty, then skip it
                            if (empty($sd->avname) || ctype_space($sd->avname)) continue;
                            // Comma separated entries
                            if ($firstentry) $firstentry = false;
                            else $avnames .= ', ';
                            // Add the current name
                            $avnames .= $sd->avname;
                            //check to see if this student has withdrawn their stipend, and find the date
                             $dateIndex = array_search( $sd->uuid,$alltrans);
                                    
                           }
                              
                    
                        // Add the avatar name(s) to the line
                        $line[] = "<a href=\"{$url_sloodleprofile}\">{$avnames}</a>";
                       
                        
                        
                        
                    } else {
                        // The query failed - if we are showing only Sloodle-enabled users, then skip the rest
                        if ($this->sloodleonly) continue;
                        $line[] = get_string('stipendgiver:noavatar','sloodle');
                       
                    }
                        //write the stipend amount in the column
                        $line[]=$stipendgiver->amount;  
                        //write how much the student has withdrawn and the date
                            if ($dateIndex !==false) {
                                $line[] = $stipendgiver->amount; //print amount withdrawn
                                $line[] =  date("F, j, Y,g:i a",$dateWithdrawn[$dateIndex]); 
                            }
                            else
                        { 
                            //if student uuid does not exist in transactions, just write a 0 for amount, and an empty date field
                            $line[] = 0;
                            $line[] ='';
                        }
                    
                    //now add all the lines  (columns) to the output
                   $sloodletable->data[] = $line;  
                    $resultsdisplayed++;
                }
                
                // Have we displayed the maximum number of results for this page?
                $resultnum++;
                if ($resultsdisplayed >= $maxperpage) break;
            }
            $basicurl = SLOODLE_WWWROOT."/view.php?id={$this->cm->id}&amp;course={$this->courseid}";  
            // Construct the next/previous links
            $previousstart = max(0, $this->start - $maxperpage);
            $nextstart = $this->start + $maxperpage;
            $prevlink = null;
            $nextlink = null;
            if ($previousstart != $this->start) $prevlink = "<a href=\"{$basicurl}&amp;start={$previousstart}\" style=\"color:#0000ff;\">&lt;&lt;</a>&nbsp;&nbsp;";            
            if ($nextstart < count($userlist)) $nextlink = "<a href=\"{$basicurl}&amp;start={$nextstart}\" style=\"color:#0000ff;\">&gt;&gt;</a>";
            
            // Display the next/previous links, if we have at least one
            if (!empty($prevlink) || !empty($nextlink)) {
                echo '<p style="text-align:center; font-size:14pt;">';
                if (!empty($prevlink)) echo $prevlink;
                else echo '<span style="color:#777777;">&lt;&lt;</span>&nbsp;&nbsp;';
                if (!empty($nextlink)) echo $nextlink;
                else echo '<span style="color:#777777;">&gt;&gt;</span>&nbsp;&nbsp;';
                echo '</p>';
            }
                  
          
            
            // Display the table
            print_table($sloodletable);
            print_box_end();      
                
    }
    }

}


?>
