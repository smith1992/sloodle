<?php

$testsite = 'http://eddev_eddev.avatarclassroom.com';

$verbose = false;

$test_cases = array();


/*
You can create a test case manually like this:
*/

// Get the appropriate sub-class of SloodleLSLTestCase for the object name. 
// This will be done according to the mappings in SubClassForObjectName.
// The sub-class should know how to handle any special wrinkles in how the response should look compared to the request.
// If it can't find one, it will fall back on the parent class, which will do a fairly dump comparison.
/*
$tc = SloodleLSLTestCase::SubClassForObjectName( 'SLOODLE WebIntercom 1.0' );
$tc->setTestSite( $testsite );
$tc->setURL('/mod/sloodle/mod/chat-1.0/linker.php');
$tc->setPostVars( array(
	'sloodlecontrollerid' => 2,
	'sloodlepwd' => '484f2c3a-0c04-c1b8-af22-837737ec7ac5|553083664',
	'sloodlemoduleid' => 8,
	'sloodleuuid' => '484f2c3a-0c04-c1b8-af22-837737ec7ac5',
	'sloodleavname' => 'SLOODLE WebIntercom 1.0',
	'sloodleserveraccesslevel' => 0,
	'sloodleisobject' => 'true',
	)
);

$test_cases[] = $tc;
*/


/*
Alternatively, you can create the test cases automatically from a log.
*/
$test_cases = SloodleLSLTestCase::TestCasesFromLog( 'testlog.txt' );

foreach($test_cases as $tc) {
   $tc->setTestSite( $testsite );
   if (!$tc->fetch()) {
      print "Error fetching testcase";
   }
   $tc->runTests();   
//var_dump($tc);
   $tc->printResultLine();
}

class SloodleLSLTestCase {
	
   var $_site;
   var $_url;

   var $_postvars;

   var $_expectedBodyLines = array();
   var $_receivedBodyLines = array();

   var $_passes = array();
   var $_fails = array();

   function postVarsAsString() {
      if (!is_array($this->_postvars)) {
          return false;
      }
      $bits = array();
      foreach($this->_postvars as $n=>$v) {
         $bits[] = $n.'='. $v; 
      }
      return join('&', $bits);
   }

   function fetch() {
      $curl_handle=curl_init();
      curl_setopt($curl_handle, CURLOPT_URL, $this->_site.$this->_url);
      curl_setopt($curl_handle, CURLOPT_POST,1);
      curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $this->postVarsAsString() );
      curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT,2);
      curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER,1);
      $body = curl_exec($curl_handle);
      curl_close($curl_handle);

      $this->setReceivedBodyLines(explode("\n", $body));
      print $body."\n";

      return true;

   }

   function setTestSite($site) {
      $this->_site = $site;
   }

   function setURL($url) {
      $this->_url = $url;
   }

   function setPostVars($arr) {
      $this->_postvars = $arr;
   }

   function setReceivedBodyLines($arr) {
      $this->_receivedBodyLines = $arr;
   }

   function setExpectedBodyLines( $lines ) {
      $this->_expectedBodyLines = $lines;
   }

   function comparableLine( $line, $number, $isReceivedNotExpected ) {
      // The first item is usually an ID, which varies legitimately
      if ($line > 0) {
          if (preg_match('/^\d+(\|.*)$/', $line, $matches)) {
             $line = '[[REPLACED-ID]]'.$matches[1];
          }
      }
      return $line; 
   }

   function runTests() {
      for ($i=0; $i<count($this->_expectedBodyLines); $i++) {
         $title = "Line comparison $i"; 
         $expectedLine = $this->comparableLine( $this->_expectedBodyLines[$i], $i, true );
         $receivedLine = $this->comparableLine( $this->_receivedBodyLines[$i], $i, false);
         $isMatch = ( $expectedLine  ==  $receivedLine);
         if ($isMatch) {
            array_push($this->_passes, array('title'=>$title, 'expected' => $expectedLine, 'received' => $receivedLine));
         }  else {
            array_push($this->_fails,  array('title'=>$title, 'expected' => $expectedLine, 'received' => $receivedLine));
         }
      }
   }

   function passes() {
      return $this->_passes;
   }

   function fails() {
      return $this->_fails;
   }

   function printResultLine() {
      print (count($this->_fails) > 0) ? '+++FAIL+++ ' : '+++PASS+++ ';
      $faillines = array();
      print count($this->_passes)." passes, ".count($this->_fails)." fails "."\n";
      foreach($this->fails() as $fl) {
          print "---------".$fl['title']."\n";
          print "--------- Expected:".$fl['expected']."\n";
          print "--------- Received:".$fl['received']."\n";
      }
   }

   function SubClassForObjectName( $objectName ) {
      $objectRegexToClassMappings = array(
	 '/SLOODLE WebIntercom 1.0/' => 'SloodleWebIntercomTestCase',
      );
      foreach( $objectRegexToClassMappings as $regex => $subclass ) {
         if ( preg_match($regex, $objectName) ) {
            return new $subclass; 
         }
      }
      return new SloodleLSLTestCase(); // default to the parent class
   }

   function TestCasesFromLog( $log ) {
      $tcarrs = array();
      $tcs = array();

      if ( !$handle = fopen($log, 'r') ) {
         print "Error: could not open log file $log to create test cases";
         return null;
      }
      $request_id = null;
      $response_id = null;
      while (!feof($handle)) {
         $line = fgets($handle);
         $line = preg_replace("/\n/", "", $line);
         
         if (preg_match('/^------START-REQUEST-(.*?)---(.*?)------$/', $line, $matches ) ) {
            $request_id = $matches[1].'---'.$matches[2];
            $request_url = $matches[1];
            $tcarrs[$request_id] = array('REQUEST_URL' => $request_url, 'REQUEST_POST' => array(), 'REQUEST_REQUEST'=> array(), 'RESPONSE_LINES' => array() );
         } else if (preg_match('/^------END-REQUEST-(.*?)------$/', $line, $matches ) ) {
            $request_id = null; 
            $response_id = null;
         } else if (preg_match('/^------START-RESPONSE-(.*?)------$/', $line, $matches ) ) {
            $request_id = null; 
	    $response_id = $matches[1];
	 } else if (preg_match('/^------END-RESPONSE-(.*?)------$/', $line, $matches ) ) {
            $request_id = null; 
	    $response_id = null;
         } else {
		 if ($request_id != null) {
                     if (preg_match('/^POST\:\s(.*?)\s=\>\s(.*?)$/', $line, $matches)) {
                        $n = $matches[1];
                        $v = $matches[2];
                        $tcarrs[ $request_id ]['REQUEST_POST'][$n] = $v;
                     } else if (preg_match('/^REQUEST\:\s(.*?)\s=>\s(.*?)$/', $line, $matches)) {
                        $n = $matches[1];
                        $v = $matches[2];
                        $tcarrs[ $request_id ]['REQUEST_REQUEST'][$n] = $v;
	             } else {
                        print "Warning: no match for line $line";
                     }
                 } else if ($response_id != null ) {
                     $tcarrs[ $response_id ]['RESPONSE_LINES'][] = $line;
                 }
         }
         
      }
      fclose($handle);

      foreach($tcarrs as $rqid=>$arr) {
         if ( !isset($arr['REQUEST_URL']) || $arr['REQUEST_URL'] == '' ) {
            continue;
         }
         $object_name = $arr['REQUEST_REQUEST']['HTTP_X_SECONDLIFE_OBJECT_NAME'];
         $tc = SloodleLSLTestCase::SubClassForObjectName( $object_name );
         $tc->setURL( $arr['REQUEST_URL'] );
         $tc->setPostVars( $arr['REQUEST_POST'] );
         $tc->setExpectedBodyLines( $arr['RESPONSE_LINES'] );
         $tcs[] = $tc;
      }
     
//var_dump($tcs);
      return $tcs;

   }

}

class SloodleWebIntercomTestCase extends SloodleLSLTestCase{

   function SloodleWebIntercomTestCase() {
   }

}


/*
class SloodleQuizTestCase extends SloodleLSLTestCase {

   var $_received = array(
   );

   var $_expected = array(
   );

   function errors() {
      
   }   

}


------START-REQUEST-/mod/sloodle/mod/chat-1.0/linker.php---216.82.35.41---59735---1276833321------
POST: sloodlecontrollerid => 2
POST: sloodlepwd => 484f2c3a-0c04-c1b8-af22-837737ec7ac5|553083664
POST: sloodlemoduleid => 8
POST: sloodleuuid => 484f2c3a-0c04-c1b8-af22-837737ec7ac5
POST: sloodleavname => SLOODLE WebIntercom 1.0
POST: sloodleserveraccesslevel => 0
POST: sloodleisobject => true
POST: message => (SL-object) Edmund Earp has entered this chat
REQUEST: HTTP_X_SECONDLIFE_OBJECT_NAME => SLOODLE WebIntercom 1.0
REQUEST: REQUEST_URI => /mod/sloodle/mod/chat-1.0/linker.php
------END-REQUEST-/mod/sloodle/mod/chat-1.0/linker.php---216.82.35.41---59735---1276833321------
------START-RESPONSE-/mod/sloodle/mod/chat-1.0/linker.php---216.82.35.41---59735---1276833321------
1|OK|10101||||484f2c3a-0c04-c1b8-af22-837737ec7ac5
14|Guest User  |(SL-object) Edmund Earp has left this chat
15|Guest User  |(SL-object) Edmund Earp has entered this chat
------END-RESPONSE-/mod/sloodle/mod/chat-1.0/linker.php---216.82.35.41---59735---1276833321------

*/
?>
