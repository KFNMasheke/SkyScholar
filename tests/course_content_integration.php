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
// Simulate a course created by the previous CSV; it must be adopted, not copied.
$first = $pack['courses'][0];
$category = \core_course_category::get_default();
$existing = create_course((object)[
    'fullname' => '[DRAFT] ' . $first['name'], 'shortname' => 'SKS-DRAFT-' . substr($first['id'], 6),
    'idnumber' => 'skyscholar:' . $first['id'], 'category' => $category->id, 'format' => 'topics',
    'visible' => 0, 'summary' => 'Staff-edited description', 'summaryformat' => FORMAT_PLAIN,
]);
$before = $DB->count_records('course');
$totals = ['courses' => 0, 'pages' => 0, 'questions' => 0];
foreach ($pack['courses'] as $spec) {
    $result = loader::import_course($spec['id']);
    foreach ($totals as $field => $value) { $totals[$field] += $result[$field]; }
}
content_verify($totals === ['courses' => 13, 'pages' => 35, 'questions' => 35], 'all authored content imports through native APIs');
content_verify($DB->count_records('course') === $before + 13, 'earlier CSV course reused');
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
content_verify(count($pages) === 35, '35 native Page instances exist');
foreach ($pages as $page) {
    content_verify(strlen($page->content) > 300 && str_contains($page->content, 'DRAFT'), 'Page body is populated, not just an editor wrapper');
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
// Staff edits must survive a repeat import.
$page = reset($pages);
$DB->set_field('page', 'content', '<p>Staff-edited lesson: preserve me.</p>', ['id' => $page->id]);
$beforemodules = $DB->count_records('course_modules');
$beforequestions = $DB->count_records('question_versions');
foreach ($pack['courses'] as $spec) {
    $result = loader::import_course($spec['id']);
    content_verify($result['courses'] + $result['pages'] + $result['questions'] === 0, 'repeat import adds no duplicates');
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
$learner = $DB->get_record('user', ['username' => 'testlearner1'], '*', MUST_EXIST);
\core\session\manager::set_user($learner);
$rejected = false;
try { loader::import_course($first['id']); } catch (required_capability_exception $e) { $rejected = true; }
content_verify($rejected, 'learner cannot invoke the content loader');
content_verify(!has_capability('moodle/course:viewhiddencourses', context_course::instance($existing->id)), 'learner cannot access hidden draft courses');
\core\session\manager::set_user(get_admin());
echo "ALL COURSE CONTENT INTEGRATION CHECKS PASSED\n";
