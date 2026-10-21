<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// Run only after the foundation integration suite in a disposable database.
define('CLI_SCRIPT', true);
$root = getenv('SKYSCHOLAR_TEST_MOODLE');
if (!$root || !is_file($root . '/config.php')) { throw new RuntimeException('Set the disposable Moodle path.'); }
require($root . '/config.php');
if (!str_starts_with($CFG->wwwroot, 'http://127.0.0.1') || $CFG->dbname !== 'skyscholar_test') {
    throw new RuntimeException('Refusing a non-disposable database.');
}
require_once($CFG->dirroot . '/course/lib.php');
use local_skyscholarcontent\local\loader;
function content_verify(bool $ok, string $message): void {
    if (!$ok) { throw new RuntimeException('FAIL ' . $message); }
    echo 'PASS ' . $message . "\n";
}
\core\session\manager::set_user(get_admin());
$pack = loader::pack();
$attemptsnapshot = json_encode($DB->get_records('quiz_attempts', null, 'id'));
$gradesnapshot = json_encode($DB->get_records('grade_grades', null, 'id'));
// Simulate a course created by the previous CSV; it must be adopted, not copied.
$first = $pack['courses'][0];
$category = \core_course_category::get_default();
$existing = create_course((object)[
    'fullname' => '[DRAFT] ' . $first['name'], 'shortname' => 'SKS-DRAFT-' . substr($first['id'], 6),
    'idnumber' => 'skyscholar:' . $first['id'], 'category' => $category->id, 'format' => 'topics',
    'visible' => 0, 'summary' => 'Staff-edited description', 'summaryformat' => FORMAT_PLAIN,
]);
$before = $DB->count_records('course');
$totals = ['courses' => 0, 'pages' => 0, 'questions' => 0, 'assignments' => 0];
foreach ($pack['courses'] as $spec) {
    $result = loader::import_course($spec['id']);
    foreach ($totals as $field => $value) { $totals[$field] += $result[$field]; }
}
content_verify($totals === ['courses' => 14, 'pages' => 130, 'questions' => 35, 'assignments' => 15], 'all authored content imports through native APIs');
content_verify($DB->count_records('course') === $before + 14, 'earlier CSV course reused');
$adopted = loader::existing($first);
content_verify($adopted->id == $existing->id && $adopted->summary === 'Staff-edited description', 'existing course settings preserved');
$courseids = [];
foreach ($pack['courses'] as $spec) {
    $course = loader::existing($spec);
    $courseids[] = $course->id;
    content_verify($course->visible == 0, 'course stays hidden: ' . $spec['name']);
}
[$insql, $params] = $DB->get_in_or_equal($courseids);
$pages = $DB->get_records_select('page', "course $insql", $params);
content_verify(count($pages) === 130, '130 native Page instances exist');
foreach ($pages as $page) {
    content_verify(strlen($page->content) > 300 && (str_contains($page->content, 'DRAFT') || str_contains($page->content, 'Authored self-study')), 'Page body is populated, not just an editor wrapper');
    $cm = get_coursemodule_from_instance('page', $page->id, $page->course, false, MUST_EXIST);
    content_verify($cm->visible == 0, 'new Page activity stays hidden');
}
$sacaa = loader::existing($pack['courses'][9]);
$context = context_course::instance($sacaa->id);
$questions = $DB->get_records_sql("SELECT v.id, v.status FROM {question_versions} v
    JOIN {question_bank_entries} b ON b.id = v.questionbankentryid
    JOIN {question_categories} c ON c.id = b.questioncategoryid
    WHERE c.contextid = :context AND b.idnumber LIKE :prefix", ['context' => $context->id, 'prefix' => 'ssc-%']);
content_verify(count($questions) === 35 && count(array_filter($questions, fn($q) => $q->status === 'draft')) === 35,
    'all 35 question versions use native draft status');
content_verify($DB->count_records('assign') === 15, '15 native project Assignments exist');
require_once($CFG->dirroot . '/mod/assign/locallib.php');
$project = $DB->get_record('assign', ['course' => $existing->id], '*', MUST_EXIST);
$projectcm = get_coursemodule_from_instance('assign', $project->id, $existing->id, false, MUST_EXIST);
$projectapi = new assign(context_module::instance($projectcm->id), $projectcm, $existing);
content_verify($projectapi->get_submission_plugin_by_type('onlinetext')->is_enabled() && $projectcm->visible == 0,
    'project accepts native online-text submissions while staying hidden');
$DB->set_field('assign', 'intro', '<p>Staff-edited project.</p>', ['id' => $project->id]);
$assetcount = 0; $asseturl = null; $assetpageid = null;
foreach ($pages as $pagewithfiles) {
    $cmwithfiles = get_coursemodule_from_instance('page', $pagewithfiles->id, $pagewithfiles->course, false, MUST_EXIST);
    $files = get_file_storage()->get_area_files(context_module::instance($cmwithfiles->id)->id, 'mod_page', 'content', 0, 'id', false);
    foreach ($files as $file) {
        $assetcount++;
        content_verify(hash('sha256', $file->get_content()) === $pack['assets'][$file->get_filename()], 'native Page illustration content hash matches');
        if (!$asseturl) {
            $asseturl = moodle_url::make_pluginfile_url($file->get_contextid(), 'mod_page', 'content', 0, '/', $file->get_filename())->out(false);
            $assetpageid = $cmwithfiles->id;
        }
    }
}
content_verify($assetcount === 76, '76 illustrations stored in native Page file areas for backup/restore');
// Staff edits must survive a repeat import.
$page = reset($pages);
$DB->set_field('page', 'content', '<p>Staff-edited lesson: preserve me.</p>', ['id' => $page->id]);
$beforemodules = $DB->count_records('course_modules');
$beforequestions = $DB->count_records('question_versions');
foreach ($pack['courses'] as $spec) {
    $result = loader::import_course($spec['id']);
    content_verify($result['courses'] + $result['pages'] + $result['questions'] + $result['assignments'] === 0, 'repeat import adds no duplicates');
}
content_verify($DB->get_field('page', 'content', ['id' => $page->id]) === '<p>Staff-edited lesson: preserve me.</p>', 'staff lesson edit is preserved');
content_verify($DB->count_records('course_modules') == $beforemodules && $DB->count_records('question_versions') == $beforequestions, 'repeat import leaves native activities and question versions intact');
update_course((object)['id' => $existing->id, 'visible' => 1]);
$rejected = false;
try { loader::import_course($first['id']); } catch (RuntimeException $e) { $rejected = true; }
content_verify($rejected && $DB->get_field('course', 'visible', ['id' => $existing->id]) == 1, 'visible course refusal does not hide or rewrite it');
update_course((object)['id' => $existing->id, 'visible' => 0]);
$lock = \core\lock\lock_config::get_lock_factory('local_skyscholarcontent')->get_lock('content-import', 0);
// MariaDB named locks are reentrant on one connection: use a separate PHP process.
$process = proc_open([PHP_BINARY, __DIR__ . '/course_content_lock_probe.php'], [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
if (!is_resource($process)) { throw new RuntimeException('Could not start lock probe.'); }
$probeoutput = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$probeexit = proc_close($process);
$lock->release();
content_verify($probeexit === 0 && str_contains($probeoutput, 'LOCK_REFUSED'), 'concurrent importer refuses a second process');
content_verify(json_encode($DB->get_records('quiz_attempts', null, 'id')) === $attemptsnapshot, 'existing quiz attempts unchanged');
content_verify(json_encode($DB->get_records('grade_grades', null, 'id')) === $gradesnapshot, 'existing learner grades unchanged');
content_verify($DB->get_field('assign', 'intro', ['id' => $project->id]) === '<p>Staff-edited project.</p>', 'staff project edit survives repeat import');
$radio = loader::existing($pack['courses'][14]);
file_put_contents($root . '/content-test-fixture.json', json_encode(['radiocourse' => (int)$radio->id, 'othercourse' => (int)$existing->id,
    'asseturl' => $asseturl, 'assetpageid' => (int)$assetpageid], JSON_THROW_ON_ERROR));
$learner = $DB->get_record('user', ['username' => 'testlearner1'], '*', MUST_EXIST);
\core\session\manager::set_user($learner);
$rejected = false;
try { loader::import_course($first['id']); } catch (required_capability_exception $e) { $rejected = true; }
content_verify($rejected, 'learner cannot invoke the content loader');
content_verify(!has_capability('moodle/course:viewhiddencourses', context_course::instance($existing->id)), 'learner cannot access hidden draft courses');
\core\session\manager::set_user(get_admin());
echo "ALL COURSE CONTENT INTEGRATION CHECKS PASSED\n";
