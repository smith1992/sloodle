<?php  // $Id: insert.php,v 1.2 2005/02/07 20:33:43 skodak Exp $
// Edited by Jeremy Kemp for the Sloodle project.
//AviLister - returns real name when queried with SL Avatar name (MSN ID)
//This file should sit in the www ROOT dir of you main Moodle course.

    require_once('../../../../config.php');
    require_once($CFG->dirroot .'/mod/chat/lib.php');

$avi_name = optional_param('avi_name','', PARAM_RAW);

$query = 'SELECT mdl_user.firstname, mdl_user.lastname FROM mdl_user WHERE ((mdl_user.msn)="'.$avi_name.'")';

$results = mysql_fetch_array(mysql_query($query));
if ($results['firstname']!=''){
Print $avi_name."= ".$results['firstname']." ".$results['lastname'];
} else {
Print $avi_name."= ?";	
}
?>