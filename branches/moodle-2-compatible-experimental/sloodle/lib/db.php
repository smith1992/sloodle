<?php
/*
db.lib
Edmund Edgar, 2010-06-18
Sloodle database compatibility wrappers
For reasons best known to themselves, Moodle decided to suddenly rip out all their old db functions and put in a bunch of near-identical ones with slightly different syntax.
To avoid the need for a different release, we'll go through a layer of sloodle_ functions which wrap the appropriate Moodle database call.
*/

function sloodle_get_record($p1=null, $p2=null, $p3=null) {
   global $CFG;
   if ($CFG->version > 2010060800) {
      global $DB;
      return $DB->get_record($p1, array($p2=>$p3) );
   } else {
      return get_record($p1, $p2, $p3);
   }
}

function sloodle_get_records($p1=null, $p2=null, $p3=null, $p4=null, $p5=null) {
   global $CFG;
   if ($CFG->version > 2010060800) {
      global $DB;
      return $DB->get_records($p1, array($p2=>$p3, $p4=>$p5));
   } else {
      return get_records($p1, $p2, $p3, $p4, $p5);
   }
}

function sloodle_insert_record($p1=null, $p2=null) {
   global $CFG;
   if ($CFG->version > 2010060800) {
      global $DB;
      return $DB->insert_record($p1, $p2);
   } else {
      return insert_record($p1, $p2);
   }
}

function sloodle_update_record($p1=null, $p2=null) {
   global $CFG;
   if ($CFG->version > 2010060800) {
      global $DB;
      return $DB->update_record($p1, $p2);
   } else {
      return update_record($p1, $p2);
   }
}

function sloodle_count_records($p1=null, $p2=null, $p3=null) {
   global $CFG;
   if ($CFG->version > 2010060800) {
      global $DB;
      return $DB->count_records($p1, $p2, $p3);
   } else {
      return count_records($p1, $p2, $p3);
   }
}

function sloodle_delete_records($p1=null, $p2=null, $p3=null, $p4=null, $p5=null, $p6=null, $p7=null) {
   global $CFG;
   if ($CFG->version > 2010060800) {
      global $DB;
      return $DB->delete_records($p1, $p2, $p3, $p4, $p5, $p6, $p7);
   } else {
      return delete_records($p1, $p2, $p3, $p4, $p5, $p6, $p7);
   }
}

function sloodle_delete_record($p1=null, $p2=null, $p3=null, $p4=null, $p5=null, $p6=null, $p7=null) {
   global $CFG;
   if ($CFG->version > 2010060800) {
      global $DB;
      return $DB->delete_record($p1, $p2, $p3, $p4, $p5, $p6, $p7);
   } else {
      return delete_record($p1, $p2, $p3, $p4, $p5, $p6, $p7);
   }
}

function sloodle_get_field($p1=null, $p2=null, $p3=null, $p4=null, $p5=null, $p6=null, $p7=null, $p8=null) {
   global $CFG;
   if ($CFG->version > 2010060800) {
      global $DB;
      return $DB->get_field($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8);
   } else {
      return get_field($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8);
   }
}

function sloodle_set_field($p1=null, $p2=null, $p3=null, $p4=null, $p5=null, $p6=null, $p7=null, $p8=null) {
   global $CFG;
   if ($CFG->version > 2010060800) {
      global $DB;
      return $DB->set_field($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8);
   } else {
      return set_field($p1, $p2, $p3, $p4, $p5, $p6, $p7, $p8);
   }
}
?>
