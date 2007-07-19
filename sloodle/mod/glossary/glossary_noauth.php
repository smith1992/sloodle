<?php  // $Id: insert.php,v 1.2 2005/02/07 20:33:43 skodak Exp $
// Edited by Jeremy Kemp for the Sloodle project.
//MetaGlos - Searches Moodle glossary and returns text
//This file should sit in the www ROOT dir of you main Moodle course.

    require_once('../../../../config.php');
    require_once($CFG->dirroot .'/mod/chat/lib.php');
$concept = optional_param('concept','', PARAM_RAW);
$glossaryid = optional_param('glossaryid','', PARAM_RAW);

$query = 'SELECT mdl_glossary_entries.concept, mdl_glossary_entries.definition FROM mdl_glossary_entries WHERE ((mdl_glossary_entries.glossaryid) = "'.$glossaryid.'") AND ((mdl_glossary_entries.concept) LIKE "'.$concept.'")';

$glossaryname = 'SELECT mdl_glossary.name FROM mdl_glossary WHERE ((mdl_glossary.id)="'.$glossaryid.'")';


$definition_results = mysql_fetch_array(mysql_query($query));
$glossaryname_results=mysql_fetch_array(mysql_query($glossaryname));

if ($definition_results['concept']!=''){
Print $definition_results['concept'].": ".$definition_results['definition'];
} else {
Print "'".$concept."' was not found in the '".$glossaryname_results['name']."' glossary.";
}
?>
