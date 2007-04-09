<?php
class ClassSetupTest extends WebTestCase {
    
	function testTeacherCourseList() {
		// http://moodle.edochan.com/mod/sloodle/login/sl_teacher_courses.php?pwd=315d214c-880b-a03b-7f1f-2afc083b2257|30766321&avname=Edmund%20Earp&uuid=746ad236-d28d-4aab-93de-1e09a076c5f3
		$url = SLOODLE_TEST_CONFIG_SLOODLE_URL.'/mod/sloodle/login/sl_teacher_courses.php?pwd='.urlencode(SLOODLE_TEST_CONFIG_PRIM_PASSWORD).'&avname='.urlencode(SLOODLE_TEST_CONFIG_MOODLE_REGISTERED_TEACHER_AVATAR_NAME).'&uuid='.urlencode(SLOODLE_TEST_CONFIG_MOODLE_REGISTERED_TEACHER_AVATAR_UUID);
        $this->assertTrue($this->get($url));
        $this->assertText('OK|'.SLOODLE_TEST_CONFIG_MOODLE_REGISTERED_TEACHER_AVATAR_UUID.'|5|JP101|Introductory Japanese');
	}
}
?>
