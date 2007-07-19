<?php  // $Id: insert.php,v 1.2 2005/02/07 20:33:43 skodak Exp $
// Edited by Jeremy Kemp for the Sloodle project.
//MetaGlos - Searches Moodle glossary and returns text
//This file should sit in the www ROOT dir of you main Moodle course.

    require_once('../../../../config.php');
    require_once($CFG->dirroot .'/mod/chat/lib.php');

$glossaryid = optional_param('glossaryid','', PARAM_RAW);

$lastfive = 'SELECT mdl_glossary_entries.concept FROM mdl_glossary_entries WHERE glossaryid = "'.$glossaryid.'" ORDER BY timecreated DESC LIMIT 0, 5 ';

$lastfive_results = mysql_fetch_array(mysql_query($lastfive));

$glossaryname = 'SELECT mdl_glossary.name FROM mdl_glossary WHERE ((mdl_glossary.id)="'.$glossaryid.'")';
$glossaryname_results=mysql_fetch_array(mysql_query($glossaryname));

Print $glossaryname_results[name]."'s last five additions:/n";

foreach($lastfive_results as $concept) {
   Print $concept."/n";
}

//Print $lastfive_results[concept];

//Print $lastfive_results[1];

?>
