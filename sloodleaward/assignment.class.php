<?php
    // This file is part of the Sloodle project (www.sloodle.org)
    
    /**
    * This file defines the "Sloodle Object" assignment sub-type.
    * It allows students to submit 3d objects in Second Life as Moodle assignments.
    *
    * @package sloodleprimdrop
    * @copyright Copyright (c) 2008 Sloodle (various contributors)
    * @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
    *
    * @contributor (various Moodle guys!)
    * @contributor Peter R. Bloomfield
    */

/** Attempt to include the Sloodle configuration. */
require_once($CFG->dirroot.'/mod/sloodle/sl_config.php');
/** Include the general Sloodle functions. */
require_once($CFG->dirroot.'/mod/sloodle/lib/general.php');


/** Include the base assignment class, if necessary. */
require_once($CFG->dirroot.'/mod/assignment/lib.php');

/**
 * Extend the base assignment class for assignments where you submit an SL object in-world.
 * This has been modified from the "assignment_online" type.
 * @package sloodle
 */
class assignment_sloodleaward extends assignment_base {

    var $is_loaded=false;
    function assignment_sloodleaward($cmid=0, $assignment=NULL, $cm=NULL, $course=NULL) {
        parent::assignment_base($cmid, $assignment, $cm, $course);
        $this->type = 'sloodleaward';
    }

    function view() {
        
        // Bring in the global user data
        global $USER,$CFG;

        // Check that this user can view assignments
        $context = get_context_instance(CONTEXT_MODULE, $this->cm->id);
        require_capability('mod/assignment:view', $context);
        
        // Fetch the submission data
        $submission = $this->get_submission();
        $sloodle_submission = new assignment_sloodleaward_submission();
        $sloodle_submission->load_submission($submission);

        $this->view_header();
       
        // Display a text summary of the submission
        print('<div style="text-align:center;">');
            print_simple_box_start('left', '70%', '', 0, 'generalbox', 'online'); 
            
           
             echo '<table align="left" width="100%"><tr><td>';         
             echo '<img src="'.SLOODLE_WWWROOT.'/lib/media/award.gif" align="left" alt="Sloodle Award Image" border="0" style="margin: 5; m">';
             echo '</td><td style="vertical-align:text-top" align="left" width=100%>';
             
             print('<div style="text-align:left;">');

            // print('<div style="font-style:oblique;"')
            
            print('<h3>Sloodle Awards</h3>');
            print ($this->view_intro());
            ?>
            <!--  
            The Golden Man image is licenced under the Creative Commons License here:
            http://creativecommons.org/licenses/by-sa/3.0/
            And was created by: LuMaxArt Linkware Image www.lumaxart.com/
            -->
            <?php
            print('<br><br><a href="'.$CFG->wwwroot.'/mod/assignment/submissions.php?id='.$this->cm->id.'">'.get_string('awards:viewgradesassociated','sloodle').'</a><br>');
             
             print('</div>');
             echo '</td></tr><tr align="center"><td colspan=2>';
              print('<div style="text-align:center;">');
            $this->view_dates();  
             print('</div>');
             echo '</td></tr></table>';

            $sloodle_submission->text_summary(false);
            print_simple_box_end();
        print('</div>');
        
        
        $this->view_feedback();
        $this->view_footer();
    }

    /*
     * Display the assignment dates
     */
    function view_dates() {
        // Bring in the global user and configuration data
        global $USER, $CFG;

        // Make sure the time available and time due dates are set
        if (!$this->assignment->timeavailable && !$this->assignment->timedue) {
            return;
        }
        // Start a display box
         
        print_simple_box_start('center', '', '', 0, 'generalbox', 'dates');
        echo '<div style="align:center;"><table align="center" width=100%>';
        // Display the time the assignment is available from
        if ($this->assignment->timeavailable) {
            echo '<tr ><td class="c0">'.get_string('availabledate','assignment').':&nbsp&nbsp</td>';
            echo '<td class="c1" >   '.userdate($this->assignment->timeavailable).'</td></tr>';
        }
        // Display the time the assignment is due by
        if ($this->assignment->timedue) {
            echo '<tr><td class="c0">'.get_string('duedate','assignment').':&nbsp&nbsp</td>';
            echo '<td class="c1">   '.userdate($this->assignment->timedue).'</td></tr>';
        }
        // Is there a submission by this user?
        $submission = $this->get_submission($USER->id);
        if ($submission) {
            // Convert the submission to a Sloodle assignment object
            $sloodle_submission = new assignment_sloodleaward_submission();
            $sloodle_submission->load_submission($submission);
        
            // Display the date it was last updated
            echo '<tr><td class="c0">'.get_string('lastedited').':</td></tr>';
            echo '    <td class="c1">'.userdate($submission->timemodified);
            
          
        }
        echo '</table></div>';
        print_simple_box_end();
         
    }

    /**
    * Update the submission with the provided data.
    * @param int $userid Integer ID of the user making the submission
    * @param assignment_sloodleaward_submission $data A structure containing data about the Sloodle assignment submission
    * @return bool True if successful, or false otherwise.
    */
    function update_submission($userid, $data)
    {
        $submission = $this->get_submission($userid, true);

        $update = new object();
        $update->id           = $submission->id;
        $update->data1        = $data->data1;
        $update->grade      = $data->grade;
        $update->teacher      = $data->teacher;
        $update->timecreated  = $data->timecreated;
        $update->submissioncomment  = $data->submissioncomment;       
        $update->timemodified = time();

        return update_record('assignment_submissions', $update);
    }


    function print_student_answer($userid, $return=true){
        global $CFG;
        $text = '';
        
        if (!($submission = $this->get_submission($userid))) {
            $text = '';
        } else {
            // Output the Submission data
            $sloodle_submission = new assignment_sloodleaward_submission();
            $sloodle_submission->load_submission($submission);
            
            //$text = '<b>'.shorten_text(trim(strip_tags($sloodle_submission->obj_name)), 20).'</b><br>';
            
            $text = 'Sloodle Awards';
        }
        
        if ($return) return $text;
        echo $text;
    }
    
    
    function print_user_files($userid, $return=false) {
        global $CFG;
        if (!$submission = $this->get_submission($userid)) {
            return '';
        }
        
        // Construct a Sloodle submission object
        $sloodle_submission = new assignment_sloodleaward_submission();
        $sloodle_submission->load_submission($submission);

        
        // Display the text summary of this submission
        print_simple_box($sloodle_submission->text_summary(), 'center', '100%');
    }
    

    function preprocess_submission(&$submission) {
        
    }

    function setup_elements(&$mform) {
        global $CFG, $COURSE;

        $ynoptions = array( 0 => get_string('no'), 1 => get_string('yes'));

        $mform->addElement('select', 'resubmit', get_string("allowresubmit", "assignment"), $ynoptions);
        $mform->setHelpButton('resubmit', array('resubmit', get_string('allowresubmit', 'assignment'), 'assignment'));
        $mform->setDefault('resubmit', 0);

        $mform->addElement('select', 'emailteachers', get_string("emailteachers", "assignment"), $ynoptions);
        $mform->setHelpButton('emailteachers', array('emailteachers', get_string('emailteachers', 'assignment'), 'assignment'));
        $mform->setDefault('emailteachers', 0);
    }

}


/**
* Defines a submission for a Sloodle assignment.
* @package sloodle
*/
class assignment_sloodleaward_submission
{
        
    /**
    * Parses the data from a Submission database record object.
    * @var object $submission The submission database record object.
    */
    function load_submission($submission)
    {
        
        $this->is_loaded = true;
    }

    
    /**
    * Construct a text summary of this submission.
    * @param bool $return If TRUE (default) then the text will be submitted instead of printed.
    * @return string If parameter $return was TRUE, then it returns the string. Otherwise, an empty string.
    */
    function text_summary($return = true)
    {
        // Make sure something is loaded
        if (!$this->is_loaded) {
            $text = get_string('emptysubmission', 'assignment');
        } else {
           
        }
        
        if ($return) return $text;
            
        return '';
    }
}



?>