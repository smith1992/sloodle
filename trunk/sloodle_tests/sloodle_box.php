<?php
require_once('config.php');
require_once('lib/simpletest/web_tester.php');
require_once('lib/simpletest/reporter.php');

$test = &new GroupTest('Sloodlebox Tests');
$test->addTestFile('userinfo_test.php');
$test->addTestFile('object_server_setup.php');
exit ($test->run(new HtmlReporter()) ? 0 : 1);
?>
