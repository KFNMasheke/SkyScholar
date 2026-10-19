<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// Destructive integration fixture: ONLY a disposable database on 127.0.0.1.
define('CLI_SCRIPT', true);
$root = getenv('SKYSCHOLAR_TEST_MOODLE');
if (!$root || !is_file($root . '/config.php')) {
    fwrite(STDERR, "Set SKYSCHOLAR_TEST_MOODLE to an isolated installed Moodle path.\n");
    exit(1);
}
require($root . '/config.php');
if (!str_starts_with($CFG->wwwroot, 'http://127.0.0.1') || $CFG->dbname !== 'skyscholar_test') {
    throw new RuntimeException('Refusing to run against a non-test site/database.');
}
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->dirroot . '/course/lib.php');
function verify(bool $pass, string $message): void {
    if (!$pass) {
        throw new RuntimeException('FAIL ' . $message);
    }
    echo 'PASS ' . $message . "\n";
}
use local_skyscholar\local\platform;
\core\session\manager::set_user(get_admin());
// Reset this suite's courses only; the guard above refuses any non-test database.
$DB->delete_records('local_skyscholar_content');
$DB->delete_records('local_skyscholar_report');
foreach ($DB->get_records_select('course', 'idnumber LIKE ?', ['skyscholar:%']) as $oldcourse) {
    delete_course($oldcourse, false);
}
platform::configure();
$before = $DB->count_records('course');
platform::configure();
verify($before === $DB->count_records('course'), 'course bootstrap is idempotent');
verify($DB->count_records('quiz') === 16, 'two demo and fourteen hidden aviation quiz shells');
$demo = platform::course('demo');
$aviation = platform::course('za-remote');
verify(!$aviation->visible, 'unreviewed aviation course stays hidden');
verify($CFG->registerauth === '', 'public registration remains disabled pending launch checks');
$json = file_get_contents($CFG->dirroot . '/local/skyscholar/content/demo.json');
$result = platform::import($json);
verify($result['created'] === 6, 'six demo questions imported');
verify(platform::import($json)['unchanged'] === 6, 'duplicate import is a no-op');
$rejected = false;
try {
    platform::publish('za-remote');
} catch (RuntimeException $error) {
    $rejected = true;
}
verify($rejected, 'undersized aviation bank cannot publish');
platform::publish('demo');
platform::publish('demo');
verify($DB->count_records('quiz_slots') === 10, 'repeat publish does not duplicate random slots');
verify(platform::course('demo')->visible == 1, 'reviewed demo track published');
$draft = json_decode($json, true);
$draft['questions'][0]['status'] = 'draft';
$draft['questions'][0]['reviewer'] = '';
$draft['questions'][0]['review_date'] = '';
verify(platform::import(json_encode($draft))['updated'] === 1, 'changed question creates a new version');
verify(platform::readiness('demo')['platform']['approved'] === 5, 'latest draft removes question from approved pool');
verify(!platform::course('demo')->visible, 'undersized published bank is hidden after draft replacement');
platform::import($json);
verify(platform::readiness('demo')['platform']['approved'] === 6, 'reviewed version restores approved pool');
platform::publish('demo');

$students = [];
for ($i = 1; $i <= 2; $i++) {
    $user = (object)['username' => 'testlearner' . $i, 'auth' => 'manual', 'confirmed' => 1,
        'mnethostid' => $CFG->mnet_localhost_id,
        'firstname' => 'Test', 'lastname' => 'Learner ' . $i, 'email' => 'learner' . $i . '@example.test',
        'password' => 'DisposableTest!2026'];
    $existing = $DB->get_record('user', ['username' => $user->username, 'deleted' => 0]);
    $user->id = $existing ? $existing->id : user_create_user($user);
    if ($existing) {
        $DB->set_field('user', 'mnethostid', $CFG->mnet_localhost_id, ['id' => $user->id]);
    }
    $students[] = $DB->get_record('user', ['id' => $user->id], '*', MUST_EXIST);
    foreach (enrol_get_instances($demo->id, true) as $instance) {
        if ($instance->enrol === 'self') {
            enrol_get_plugin('self')->enrol_user($instance, $user->id, $instance->roleid);
        }
    }
    verify(is_enrolled(context_course::instance($demo->id), $user->id, '', true), 'learner ' . $i . ' has active free enrolment');
}
\core\session\manager::set_user($students[0]);
verify(!has_capability('local/skyscholar:manage', context_system::instance()), 'learner cannot manage content/reports');
$cm = $DB->get_record('course_modules', ['idnumber' => 'ss:demo:platform:mock'], '*', MUST_EXIST);
$quiz = \mod_quiz\quiz_settings::create($cm->instance);
verify($quiz->get_quiz()->attempts == 0 && $quiz->get_quiz()->timelimit == 600, 'unlimited timed mock configuration');
$attempt = quiz_prepare_and_start_new_attempt($quiz, 1, null, false);
$attemptobj = \mod_quiz\quiz_attempt::create($attempt->id);
verify(count($attemptobj->get_slots()) === 6, 'native attempt receives six random questions');
$responses = [];
foreach ($attemptobj->get_slots() as $slot) {
    $qa = $attemptobj->get_question_attempt($slot);
    $correct = $qa->get_question()->get_correct_response();
    $responses[$qa->get_qt_field_name('answer')] = $correct['answer'];
    $responses[$qa->get_control_field_name('sequencecheck')] = $qa->get_sequence_check_count();
}
$attemptobj->process_submitted_actions(time(), false, $responses);
$attemptobj->process_finish(time(), false);
$graded = $DB->get_record('quiz_attempts', ['id' => $attempt->id], '*', MUST_EXIST);
verify($graded->state === 'finished' && (float)$graded->sumgrades === 6.0, 'native grading persists full marks');
verify($DB->get_field('quiz_grades', 'grade', ['quiz' => $cm->instance, 'userid' => $students[0]->id]) == 100,
    'gradebook persists the final percentage');
verify(\mod_quiz\quiz_attempt::create($attempt->id)->is_own_attempt(), 'owner can identify own attempt');
$expired = quiz_prepare_and_start_new_attempt($quiz, 2, $graded, false);
$expiredobj = \mod_quiz\quiz_attempt::create($expired->id);
$expiredobj->handle_if_time_expired(time() + 601, false);
verify($DB->get_field('quiz_attempts', 'state', ['id' => $expired->id]) === 'finished',
    'abandoned timed attempt is finalised by native expiry processing');
\core\session\manager::set_user($students[1]);
verify(!\mod_quiz\quiz_attempt::create($attempt->id)->is_own_attempt(), 'another learner fails attempt ownership check');
\core\session\manager::set_user(get_admin());
$reportid = $DB->insert_record('local_skyscholar_report', (object)[
    'userid' => $students[0]->id, 'attemptid' => $attempt->id, 'slot' => 1,
    'message' => 'Functional test report', 'status' => 'open', 'timecreated' => time(),
]);
verify($reportid > 0, 'question report persistence');
$contexts = \local_skyscholar\privacy\provider::get_contexts_for_userid($students[0]->id);
verify(in_array(context_system::instance()->id, $contexts->get_contextids()), 'privacy discovery includes question reports');
$approved = new \core_privacy\local\request\approved_contextlist($students[0], 'local_skyscholar', $contexts->get_contextids());
\local_skyscholar\privacy\provider::delete_data_for_user($approved);
verify(!$DB->record_exists('local_skyscholar_report', ['userid' => $students[0]->id]), 'privacy erasure removes reports');
echo "ALL INTEGRATION CHECKS PASSED\n";
