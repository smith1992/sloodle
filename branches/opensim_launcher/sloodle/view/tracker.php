<?php
/**
* Defines a class for viewing the SLOODLE Second Life Tracker module in Moodle.
* Derived from the module view base class.
*
*/

/** The base module view class */
require_once(SLOODLE_DIRROOT.'/view/base/base_view_module.php');
/** The SLOODLE Session data structures */
require_once(SLOODLE_LIBROOT.'/sloodle_session.php');



/**
* Class for rendering a view of a Second Life Tracker module in Moodle.
* @package sloodle
*/
class sloodle_view_tracker extends sloodle_base_view_module
{

    /**
    * Constructor.
    */
    function sloodle_view_tracker()
    {
    }

    /**
    * Processes request data to determine which Second Life Tracker is being accessed.
    */
    function process_request()
    {
        // Process the basic data
        parent::process_request();
        // Nothing else to get just now
    }

    /**
    * Process any form data which has been submitted.
    */
    function process_form()
    {
    }

	/**
	* Get the students enroled in this course.
	* @return $userlist: A list with the users, empty if there are no users enroled
	*/
	function get_class_list()
    {
	        // Get all the users
            $fulluserlist = get_users(true, '','name');
            if (!$fulluserlist) $fulluserlist = array();
            $userlist = array();
            
            // Filter it down to members of the course
            foreach ($fulluserlist as $ful)
            {
                // Is this user on this course?
                if (has_capability('moodle/course:view', $this->course_context, $ful->id))
                {
                    // Copy it to our filtered list and exclude administrators
                    //if (!isadmin($ful->id)) // Not excluding admins, as it's easier to test this way
                        $userlist[] = $ful;
                }
            }
            return $userlist;
      
      }

    /**
    * Render the view of the SecondLife Tracker.
    */
    function render()
    { 
   		global $CFG, $USER;   
   		
   		$session = new SloodleSession(false);
        $tracker = new SloodleModuleTracker($session);
        // Load data of the current Second Life Tracker
        if (!$tracker->load($this->cm->id)) return false;
     
	    $this->courseid = $this->course->id; 
        $sloodleid=$this->sloodle->id;
 
        print('<h3 style="color:black;text-align:center;">'.get_string('secondlifetracker:activity','sloodle')).'</h3> '; 
		
		// if ths user is the teacher/admin show the link to create a new template
		//&cm = get_record('course_modules','course', $this->courseid);
        //$module_context = get_context_instance(CONTEXT_MODULE,&cm->id);
		if (has_capability('moodle/course:manageactivities', $this->module_context))
        {
		 //echo "<h4 style=\"text-align:center;\"><a href=\"".SLOODLE_WWWROOT."/mod/tracker-1.0/teleport.php?id={$this->cm->id}\" target=\"_blank\">".get_string('tracker:launchtemplate', 'sloodle')."</a></h4>";
		 echo "<h4 style=\"text-align:center;\"><a href=\"".SLOODLE_WWWROOT."/mod/tracker-1.0/template_manager.php?id={$this->cm->id}\">".get_string('tracker:managetemplate', 'sloodle')."</a></h4>";
		}
        echo "<h4 style=\"text-align:center;\"><a href=\"".SLOODLE_WWWROOT."/mod/tracker-1.0/teleport.php?id={$this->cm->id}\" target=\"_blank\">".get_string('tracker:launchopensim', 'sloodle')."</a></h4>";

        // Check if some kind of action has been requested, used if tasks has been reset
        $action = optional_param('action', '', PARAM_TEXT);

        //Obtain the users in this course  
        $userlist =$this->get_class_list();  
          
        if ($userlist) {          
                     
            $sloodletable = new stdClass();
            
            // Create column headers for html table
            $sloodletable->head = array(    get_string('user', 'sloodle'),
                                            get_string('avatar', 'sloodle'),
                                        );
            // Set alignment of table cells                                        
            $sloodletable->align = array('center', 'center');
            // Set size of table cells
            $sloodletable->size = array('50%', '50%');
            
            // Check if our start is past the end of our results
            if (empty($this->start) || $this->start >= count($userlist)) $this->start = 0;
            
            $maxperpage = 20; // Maximum number of students per page
		    $resultnum = 0;
            $resultsdisplayed = 0;
            
            foreach ($userlist as $u) {
            
            	// A student only can see his own tracker ($USER->id == $u->id). The admin can see all the students' activity (isadmin($USER->id)==TRUE)
            	if ((!isadmin($USER->id))and($USER->id != $u->id)) continue;
            	
            	// This variable will contain the avatar identifier, necessary to search in the "sloodle_activity_tracker" DB table.
            	$avatarid = '';
            	
            	// These variables will be used to obtain the overall percentage of tasks completed
            	$tasks = 0;
            	$completed = 0;
                
                // Only display this result if it is after our starting result number
                if ($resultnum >= $this->start) {
                    
                    // Reset the line's content
                    $line = array();
                    
                    // Construct URLs to this user's Moodle and SLOODLE profile pages
                    $url_moodleprofile = $CFG->wwwroot."/user/view.php?id={$u->id}&amp;course={$this->courseid}";
                    $url_sloodleprofile = SLOODLE_WWWROOT."/view.php?_type=user&amp;id={$u->id}&amp;course={$this->courseid}";                                   
                    // Add the Moodle name
                    $line[0] = "<a href=\"{$url_moodleprofile}\">{$u->firstname} {$u->lastname}</a>";                    
                    
                    // Get the Sloodle data for this Moodle user
                    $sloodledata = get_records('sloodle_users', 'userid', $u->id, 'uuid, avname');
                    
                   // Initialize our search index    
                    /////////////////////////////////////////////////////////////////////////$dateIndex=false;
                    
                    if ($sloodledata)
                    {
                    
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
                            $avatarid = $sd->uuid;           
                        }
                        
                        // Add the avatar name(s) to the line
                        $line[1] = "<a href=\"{$url_sloodleprofile}\">{$avnames}</a>";   
                                             
                    } else {
                        // The query failed - if we are showing only Sloodle-enabled users, then skip the rest
                        if (!empty($this->sloodleonly) && $this->sloodleonly) continue;
                        $line[1] = get_string('secondlifetracker:noavatar','sloodle');                       
                    }
                    $sloodletable->data[0] = $line;  
                    $resultsdisplayed++;
                }
                
                print_table($sloodletable);
               
               // Has a delete objects action been requested?
               if ($action == 'delete_objects') {

                  // Go through each request parameter
                  foreach ($_REQUEST as $name => $val) {
                    // Is this a delete objects request?
                    if ($val != 'true') continue;
                    $parts = explode('_', $name);
                    if (count($parts) == 2 && $parts[0] == 'sloodledeleteobj') {
                        // Only delete the object if it belongs to this controller
                        delete_records('sloodle_activity_tracker', 'trackerid', $this->cm->id,'id', (int)$parts[1]);                        
                    }
                  }
                }

				//Now all the tasks in the Tracker are displayed
				echo "<div style=\"text-align:center;\">\n";
        		echo '<h3>'.get_string('secondlifetasks','sloodle').'</h3>';
        		
        		// Get all the tasks for this Tracker, ordered by "taskname"
       		 	$recs = get_records('sloodle_activity_tool', 'trackerid', $this->cm->id, 'taskname');
            
            	if (is_array($recs) && count($recs) > 0)
                {
                	
                	$objects_table = new stdClass();
                	
                	// Create column headers for html table
                	$objects_table->head = array(get_string('objectname','sloodle'),get_string('secondlifeobjdesc','sloodle'),get_string('secondlifelevelcompl','sloodle'),'Date','');
                	// Set alignment of table cells 
                	$objects_table->align = array('left', 'left', 'centre', 'centre', 'centre');
                	
                	foreach ($recs as $obj) {
                 	    // Skip this object if it has no type information
                 	    if (empty($obj->type)) continue;
                   	    
                  	    //Has this user completed the task?
                  	    $act = get_record('sloodle_activity_tracker','avuuid',$avatarid,'objuuid',$obj->uuid,'trackerid',$this->cm->id);
                  	    
                  	    //Yes. Activity completed
						if (!empty($act)){
						    $timezone = $act->timeupdated - 3600;          
						    $date = date("F j, Y, g:i a", $timezone);   
						    
						    // Only the admin can reset tasks  
						    if (isadmin($USER->id)){   		
                   	 			$objects_table->data[] = array('<span style="text-align:center;color:blue">'.$obj->taskname.'</a>', $obj->description.'<a href="'.SLOODLE_WWWROOT.'/mod/tracker-1.0/teleport.php?id='.$this->cm->id.'" target="_blank">OpenSim</a>', '<span style="text-align:center;color:green">'.get_string('secondlifetracker:completed','sloodle').'</span><br>',$date,"<input type=\"checkbox\" name=\"sloodledeleteobj_{$act->id}\" value=\"true\" /");
                   	 		}
                   	 		else {
                   	 			$objects_table->data[] = array('<span style="text-align:center;color:blue">'.$obj->taskname.'</a>', $obj->description.'<a href="'.SLOODLE_WWWROOT.'/mod/tracker-1.0/teleport.php?id='.$this->cm->id.'" target="_blank">OpenSim</a>', '<span style="text-align:center;color:green">'.get_string('secondlifetracker:completed','sloodle').'</span><br>',$date,' - ');
                            }
                   	 		$tasks += 1;
                   	 		$completed += 1;
                		}
                		//No. Activity not completed
                		else {
                   	 		$objects_table->data[] = array('<span style="text-align:center;color:blue">'.$obj->taskname.'</a>', $obj->description.'<a href="'.SLOODLE_WWWROOT.'/mod/tracker-1.0/teleport.php?id='.$this->cm->id.'" target="_blank">OpenSim</a>', '<span style="text-align:center;color:red">'.get_string('secondlifetracker:notcompleted','sloodle').'</span><br>',' - ',' - ');
                   	 		$tasks += 1;
                		}
                		
                		//Overall percentage of tasks completed?
                   		$div = bcdiv($completed,$tasks,3);
                		$overall = $div*100;                		
                	}    
                	
                	// If is the admin, show the reset button
                	if (isadmin($USER->id)){
                		echo '<form action="" method="POST">';
                    	echo '<input type="hidden" name="id" value="'.$this->cm->id.'"/>';
                   		echo '<input type="hidden" name="action" value="delete_objects"/>';
                   		
                   		print_table($objects_table);
                   		echo '<h3>Overall percentage of tasks completed:'.$overall.'%</h3>';
                   		echo '<input type="submit" value="'.get_string('deletetask','sloodle').'"/>';
            		}
                	else
                	{
                		print_table($objects_table);
                   		echo '<h3>Overall percentage of tasks completed:'.$overall.'%</h3>';
                   	}
            	}
            	//No tasks in the Tracker
            	else {
                	echo '<span style="text-align:center;color:red">'.'No tasks found'.'</span><br>';
            	}
            	echo '<p>&nbsp;</p>';
        		echo "</div>\n";
                                
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
    	}
    	//No users enroled in the course
    	else {
    		echo "<div style=\"text-align:center;\">\n";
            echo '<span style="color:red">'.get_string('tracker:nousers','sloodle').'</span><br>';
        }
    }   
}

?>