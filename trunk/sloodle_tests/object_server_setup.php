<?php
class ObjectServerSetup extends WebTestCase {

    function testServerConfirmation() {

        $this->assertTrue($this->get(SLOODLE_TEST_CONFIG_SLOODLE_URL.'/mod/sloodle/sl_classroom/sl_validate_object.php'));
        $this->assertText('OK|ok');

    }
}
?>
