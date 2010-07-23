<?php
// This file is part of the Sloodle project (www.sloodle.org)
/**
* Defines a class to render a view of SLOODLE course information.
* Class is inherited from the base view class.
*
* @package sloodle
* @copyright Copyright (c) 2008 Sloodle (various contributors)
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
*
* @contributor Peter R. Bloomfield
* @contributor Paul Preibisch
*
*/ 

/** The base view class */
require_once(SLOODLE_DIRROOT.'/view/base/base_view.php');
/** SLOODLE logs data structure */
require_once(SLOODLE_LIBROOT.'/course.php');
require_once(SLOODLE_LIBROOT.'/currency.php');    

/**
* Class for rendering a view of SLOODLE course information.
* @package sloodle
*/
class sloodle_view_backpack extends sloodle_base_view
{
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
    
    var $sloodle_currency = null;
    /**
    * Constructor.
    */
    function sloodle_view_backpack()
    {
         
    }

    /**
    * Check the request parameters to see which course was specified.
    */
    function process_request()
    {
        $id = required_param('id', PARAM_INT);
        if (!$this->course = get_record('course', 'id', $id)) error('Could not find course.');
        $this->sloodle_course = new SloodleCourse();
        if (!$this->sloodle_course->load($this->course)) error(get_string('failedcourseload', 'sloodle'));
       
    }

    /**
    * Check that the user is logged-in and has permission to alter course settings.
    */
    function check_permission()
    {
        // Ensure the user logs in
        require_login($this->course->id);
        if (isguestuser()) error(get_string('noguestaccess', 'sloodle'));
        add_to_log($this->course->id, 'course', 'view sloodle data', '', "{$this->course->id}");

        // Ensure the user is allowed to update information on this course
        $this->course_context = get_context_instance(CONTEXT_COURSE, $this->course->id);
        require_capability('moodle/course:update', $this->course_context);
    }

    /**
    * Print the course settings page header.
    */
    function print_header()
    {
        global $CFG;
        $navigation = "<a href=\"{$CFG->wwwroot}/mod/sloodle/view_backpack.php?id={$this->course->id}\">".get_string('backpack:view', 'sloodle')."</a>";
        print_header_simple(get_string('backpack','sloodle'), "", $navigation, "", "", true, '', navmenu($this->course));
    }


    /**
    * Render the view of the module or feature.
    * This MUST be overridden to provide functionality.
    */
    function render()
    {
        
        global $CFG;
        global $sloodle;
        $id = required_param('id', PARAM_INT);
        $currentCurrency = optional_param('currentCurrency',"Credits");
        $currentUser= optional_param('currentUser',"ALL");
        
        //get enrolled users
            echo "<form action=\"{$_SERVER["PHP_SELF"]}\" method=\"post\" >";
            echo "<input type=\"hidden\" name=\"id\" value=\"".$id."\">";
            echo "<input type=\"hidden\" name=\"_type\" value=\"backpack\">";
            $contextid = get_context_instance(CONTEXT_COURSE,$this->course->id);
            
            $enrolledUsers = $this->sloodle_course->get_enrolled_users();
            $sloodle_currency= new SloodleCurrency();
           $cTypes=    $sloodle_currency->get_currency_types();
                
        
        // Display info about Sloodle course configuration
        echo "<h1 style=\"text-align:center;\">".get_string('backpack:view','sloodle')."</h1>\n"; 
        echo "<h2 style=\"text-align:center;\">(".get_string('course').": \"<a href=\"{$CFG->wwwroot}/course/view.php?id={$this->course->id}\">".$this->sloodle_course->get_full_name()."</a>\")</h2>";
       print_box_start();
         echo "<select name=\"currentCurrency\"    onchange=\"this.form.submit()\" value=\"Sumbit\">";
            foreach ($cTypes as $ct){
                if ($ct->name==$currentCurrency)$selectStr="selected"; else $selectStr="";
            echo "<option value=\"{$ct->name}\" {$selectStr}>{$ct->name} {$ct->units}</option>";
        }            
        echo '</select>';
        
        $students = $this->sloodle_course->get_enrolled_users();
                 echo "<select name=\"currentUser\"    onchange=\"this.form.submit()\" value=\"Sumbit\">";
                 if ($currentUser =="ALL")$selectStr="selected";
                 
                 echo "<option value=\"ALL\" {$selectStr}>ALL</option>";
            foreach ($students as $s){
                if ($s->avname==$currentUser)$selectStr="selected"; else $selectStr="";
            echo "<option value=\"{$s->avname}\" {$selectStr}>{$s->avname} / {$s->firstname} {$s->lastname}</option>";
        }    
        echo '</select>';
        print_box_end();
        //build menu for currency
        
        
        
      // print_box(get_string('logs:info','sloodle'), 'generalbox boxaligncenter boxwidthnormal');
        $sloodletable = new stdClass(); 
         $sloodletable->head = array(                         
             '<h4><div style="color:red;text-align:left;">'.get_string('backpack:avname', 'sloodle').'</h4>',
             '<h4><div style="color:red;text-align:left;">'.get_string('backpack:currency', 'sloodle').'</h4>',
             '<h4><div style="color:red;text-align:left;">'.get_string('backpack:units', 'sloodle').'</h4>',             
             '<h4><div style="color:green;text-align:right;">'.get_string('backpack:amount', 'sloodle').'</h4>');
              //set alignment of table cells                                        
            $sloodletable->align = array('left','left','left','right');
            $sloodletable->width="95%";
            //set size of table cells
            $sloodletable->size = array('25%','25%', '25%','25%');            
            if ($currentUser=="ALL"){
                
            }else{
               $trans = $sloodle_currency->get_transactions($currentUser,$currentCurrency);
            
            }
            
            foreach ($trans as $t){
                $trowData= Array();
                $trowData[]=$t->avname;  
                $trowData[]=$t->currency;  
                $trowData[]=$t->units;  
                $trowData[]=$t->amount;  
                $sloodletable->data[] = $trowData;     
            }
             
             echo '</form>';
        print_table($sloodletable); 
        
 
 
    }

    /**
    * Print the footer for this course.
    */
    function print_footer()
    {
        global $CFG;
        echo "<p style=\"text-align:center; margin-top:32px; font-size:90%;\"><a href=\"{$CFG->wwwroot}/course/view.php?id={$this->course->id}\">&lt;&lt;&lt; ".get_string('backtocoursepage','sloodle')."</a></h2>";
        print_footer($this->course);
    }

}


?>
