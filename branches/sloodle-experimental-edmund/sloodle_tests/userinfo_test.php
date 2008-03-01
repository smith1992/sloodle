<?php
class UserInfoTest extends WebTestCase {
    
    function testHomepage() {

		//global $SLOODLE_TEST_CONFIG;
        $this->assertTrue($this->get(SLOODLE_TEST_CONFIG_SLOODLE_URL));

    }

	function testAlreadyRegisteredUserRegistrationAttempt() {
		//http://moodle.edochan.com/mod/sloodle/login/sl_sloodle_reg.php?pwd=315d214c-880b-a03b-7f1f-2afc083b2257|30766321&avname=Edmund%20Earp&uuid=746ad236-d28d-4aab-93de-1e09a076c5f3
		$url = SLOODLE_TEST_CONFIG_SLOODLE_URL.'/mod/sloodle/login/sl_sloodle_reg.php?pwd='.urlencode(SLOODLE_TEST_CONFIG_PRIM_PASSWORD).'&avname='.urlencode(SLOODLE_TEST_CONFIG_MOODLE_REGISTERED_AVATAR_NAME).'&uuid='.urlencode(SLOODLE_TEST_CONFIG_MOODLE_REGISTERED_AVATAR_UUID);
        $this->assertTrue($this->get($url));
        $this->assertText('ERROR|MISC|user already registered with all the info sloodle needs');
	}

	function testWrongPrimPasswordFailure() {

		$url = SLOODLE_TEST_CONFIG_SLOODLE_URL.'/mod/sloodle/login/sl_sloodle_reg.php?pwd='.urlencode('THISISWRONG').'&avname='.urlencode(SLOODLE_TEST_CONFIG_MOODLE_REGISTERED_AVATAR_NAME).'&uuid='.urlencode(SLOODLE_TEST_CONFIG_MOODLE_REGISTERED_AVATAR_UUID);
        $this->assertTrue($this->get($url));
        $this->assertText('ERROR|MISC|Sloodle Prim Password did not match the one set in the sloodle module');

	}

	function testNotAlreadyRegisteredUserRegistrationAttempt() {

		$url = SLOODLE_TEST_CONFIG_SLOODLE_URL.'/mod/sloodle/login/sl_sloodle_reg.php?pwd='.urlencode(SLOODLE_TEST_CONFIG_PRIM_PASSWORD).'&avname='.urlencode(SLOODLE_TEST_CONFIG_MOODLE_UNREGISTERED_AVATAR_NAME).'&uuid='.urlencode(SLOODLE_TEST_CONFIG_MOODLE_UNREGISTERED_AVATAR_UUID);
        $this->assertTrue($this->get($url));
        $this->assertText('OK|'.SLOODLE_TEST_CONFIG_MOODLE_UNREGISTERED_AVATAR_UUID.'|');

	}

}
?>
