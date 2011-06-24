<?php

$firstname = $_GET["firstname"];
$lastname = $_GET["lastname"];
//$firstname = "Admin";
//$lastname = "User";

//echo $firstname." ".$lastname;
//echo '<p> OpenSim Login: '.$firstname.' '.$lastname.'</p>';
//echo '<p> Password: 111</p>';
  
$output = exec('main_opensim.bat '.$firstname.' '.$lastname);
//echo $output;
list($url,$args) = split(" ", $output,2);
pclose(popen("\"start_opensim.bat\" " .escapeshellarg($args), "r"));

//pclose(popen("start ".$command,"r"));
//echo $command;
header('Location: '.$url);

?>