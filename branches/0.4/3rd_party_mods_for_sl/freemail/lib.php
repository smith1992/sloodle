<?php  // $Id: lib.php,v 1.4 2006/08/28 16:41:20 mark-nielsen Exp $
/**
* Freemail v1.1 with SL patch
*
* @package freemail
* @copyright Copyright (c) 2008 Serafim Panov
* @license http://www.gnu.org/licenses/gpl-3.0.html GNU GPL v3
* @author Serafim Panov
* 
*
*/

if (!$CFG->freemail_mail_user_name) {
    $CFG->freemail_mail_user_name = "mailuser";
} 

if (!$CFG->freemail_mail_user_pass) {
    $CFG->freemail_mail_user_pass = "mailpassword";
} 

if (!$CFG->freemail_mail_box_settinds) {
    $CFG->freemail_mail_box_settinds = "{mail.yourdomen.com:110/pop3/notls}INBOX";
} 

if (!$CFG->freemail_mail_emailadress) {
    $CFG->freemail_mail_emailadress = "mail@yourdomen.com";
} 

if (!$CFG->freemail_mail_maxcheck) {
    $CFG->freemail_mail_maxcheck = 10;
} 

if (!$CFG->freemail_mail_maxsize) {
    $CFG->freemail_mail_maxsize = 2097152;
} 

if (!$CFG->freemail_usepassword) {
    $CFG->freemail_usepassword = 0;
} 

if (!$CFG->freemail_subjectline) {
    $CFG->freemail_subjectline = 1;
} 

if (!$CFG->freemail_2bytes) {
    $CFG->freemail_2bytes = 1;
} 

if (!$CFG->freemail_mess_header) {
    $CFG->freemail_mess_header = "Hello!\r\r";
} 

if (!$CFG->freemail_mess_footer) {
    $CFG->freemail_mess_footer = "\r\r-----\r\r";
} 

if (!$CFG->freemail_mess_subject) {
    $CFG->freemail_mess_subject = "Moodle Robot";
} 

if (!$CFG->freemail_mess_001) {
    $CFG->freemail_mess_001 = "profile image was updated.";
} 

if (!$CFG->freemail_mess_002) {
    $CFG->freemail_mess_002 = "Email address isnt registered.";
} 

if (!$CFG->freemail_mess_003) {
    $CFG->freemail_mess_003 = "Incorrect password. We cannot change your profile image.";
} 

if (!$CFG->freemail_mess_004) {
    $CFG->freemail_mess_004 = "No commands were found in your mail.";
} 

if (!$CFG->freemail_mess_005) {
    $CFG->freemail_mess_005 = "Email message contained no image.";
} 

if (!$CFG->freemail_mess_006) {
    $CFG->freemail_mess_006 = "Incorrect image size.";
} 

if (!$CFG->freemail_mess_007) {
    $CFG->freemail_mess_007 = "Active commands:\r\r HELP - help by mail messages\r\r P - upload new image to your profile\r\r B - Add new blog entry\r    Comands: title, publish (site, draft, public)";
} 

if (!$CFG->freemail_mess_008) {
    $CFG->freemail_mess_008 = "Your blog entry was added.";
} 

if (!$CFG->freemail_mess_009) {
    $CFG->freemail_mess_009 = "Incorrect password. Blog entry not added.";
} 

if (!$CFG->freemail_mess_010) {
    $CFG->freemail_mess_010 = "Email message contain no attachment.";
} 

if (!$CFG->freemail_mess_011) {
    $CFG->freemail_mess_011 = "Item was added to your album.";
} 

if (!$CFG->freemail_mess_012) {
    $CFG->freemail_mess_012 = "Album name not found, please check field album: in your mail";
} 

if (!$CFG->freemail_mess_013) {
    $CFG->freemail_mess_013 = "Error: incorrect username, check username in subject line.";
}

function freemail_add_instance($freemail) {
    
    $freemail->timemodified = time();

    # May have to add extra stuff in here #
    
    return insert_record("freemail", $freemail);
}


/**
 * Given an object containing all the necessary data, 
 * (defined by the form in mod.html) this function 
 * will update an existing instance with new data.
 *
 * @param object $instance An object from the form in mod.html
 * @return boolean Success/Fail
 **/
function freemail_update_instance($freemail) {

    $freemail->timemodified = time();
    $freemail->id = $freemail->instance;

    # May have to add extra stuff in here #

    return update_record("freemail", $freemail);
}

/**
 * Given an ID of an instance of this module, 
 * this function will permanently delete the instance 
 * and any data that depends on it. 
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function freemail_delete_instance($id) {

    if (! $freemail = get_record("freemail", "id", "$id")) {
        return false;
    }

    $result = true;

    # Delete any dependent records here #

    if (! delete_records("freemail", "id", "$freemail->id")) {
        $result = false;
    }

    return $result;
}

/**
 * Return a small object with summary information about what a 
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @return null
 * @todo Finish documenting this function
 **/
function freemail_user_outline($course, $user, $mod, $freemail) {
    return $return;
}

/**
 * Print a detailed representation of what a user has done with 
 * a given particular instance of this module, for user activity reports.
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function freemail_user_complete($course, $user, $mod, $freemail) {
    return true;
}

/**
 * Given a course and a time, this module should find recent activity 
 * that has occurred in freemail activities and print it out. 
 * Return true if there was output, or false is there was none. 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function freemail_print_recent_activity($course, $isteacher, $timestart) {
    global $CFG;

    return false;  //  True if anything was printed, otherwise false 
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such 
 * as sending out mail, toggling flags etc ... 
 *
 * @uses $CFG
 * @return boolean
 * @todo Finish documenting this function
 **/
function freemail_cron () {
    global $CFG;
    
    require_once $CFG->dirroot."/lib/phpmailer/class.phpmailer.php";

    $url = $CFG->wwwroot."/mod/freemail/check_mail.php";

    $ch = curl_init();  
    curl_setopt($ch, CURLOPT_URL,$url); // set url to post to  
    curl_setopt($ch, CURLOPT_FAILONERROR, 1);  
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1); // return into a variable  
    curl_setopt($ch, CURLOPT_TIMEOUT, 28); // times out after 4s  
    $result = curl_exec($ch); // run the whole process  
    curl_close($ch);

    if (!strstr($result, "Parse mail is ended") || strstr($result, "Call failed")) {
        //Send error report
        $filename = $CFG->dataroot."/1/freemailerrorreport.txt";
        $zd = fopen ($filename, "r");
        $contents = fread ($zd, filesize($filename));
        fclose ($zd);
        
        if (!$contents || (($contents + 24*3600) < time())) {
            $ifp = fopen($filename, "w+"); 
            fwrite( $ifp, time()); 
            fclose( $ifp ); 
            
            if ( $admins = get_admins() ) {
                foreach ($admins as $admin) {
                  if ($admin->emailstop == 0) {
      $mail = new PHPMailer();
      
      $datasmtphosts = get_record('config', 'name', 'smtphosts');
      $datasmtpuser = get_record('config', 'name', 'smtpuser');
      $datasmtppass = get_record('config', 'name', 'smtppass');
      $datanoreply = get_record('config', 'name', 'noreplyaddress');
      $dataemailadress = get_record('freemail', 'name', 'mail_emailadress'); 
      
      
      if (!empty($datasmtphosts->value)) {
          $mail->IsSMTP();
          $mail->Host = $datasmtphosts->value;  // specify main and backup server
          $mail->SMTPAuth = true;     // turn on SMTP authentication
          $mail->Username = $datasmtpuser->value;  // SMTP username
          $mail->Password = $datasmtppass->value; // SMTP password
      }
      else
      {
          $mail->IsMail();
      }

      $mail->From     = $dataemailadress->value;
      $mail->FromName = "FreeMail Robot";
      $mail->AddAddress($admin->email, "");

      $mail->Subject = "FreeMail Problems";
      $mail->Body    = "FreeMail on {$CFG->wwwroot} is NOT WORKING. Please check the checkmail.php script.";

      if(!$mail->Send())
      {
         echo "\r\nMailer Error: " . $mail->ErrorInfo . "\r\n";
      }
      else
      {
         echo "\r\nsend problem report to site admin. \r\n";
      }
                  }
                }
            }
        }
    }

    return true;
}

/**
 * Must return an array of grades for a given instance of this module, 
 * indexed by user.  It also returns a maximum allowed grade.
 * 
 * Example:
 *    $return->grades = array of grades;
 *    $return->maxgrade = maximum allowed grade;
 *
 *    return $return;
 *
 * @param int $freemailid ID of an instance of this module
 * @return mixed Null or object with an array of grades and with the maximum grade
 **/
function freemail_grades($freemailid) {
   return NULL;
}

/**
 * Must return an array of user records (all data) who are participants
 * for a given instance of freemail. Must include every user involved
 * in the instance, independient of his role (student, teacher, admin...)
 * See other modules as example.
 *
 * @param int $freemailid ID of an instance of this module
 * @return mixed boolean/array of students
 **/
function freemail_get_participants($freemailid) {
    return false;
}

/**
 * This function returns if a scale is being used by one freemail
 * it it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $freemailid ID of an instance of this module
 * @return mixed
 * @todo Finish documenting this function
 **/
function freemail_scale_used ($freemailid,$scaleid) {
    $return = false;

    //$rec = get_record("freemail","id","$freemailid","scale","-$scaleid");
    //
    //if (!empty($rec)  && !empty($scaleid)) {
    //    $return = true;
    //}
   
    return $return;
}

//////////////////////////////////////////////////////////////////////////////////////
/// Any other freemail functions go here.  Each of them must have a name that 
/// starts with freemail_


?>
