<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// Test-only visibility/enrolment fixture. Never included in the installer plugin.
define('CLI_SCRIPT', true);
$root = getenv('SKYSCHOLAR_TEST_MOODLE');
if (!$root || !is_file($root . '/config.php')) { exit(2); }
require($root . '/config.php');
if (!str_starts_with($CFG->wwwroot, 'http://127.0.0.1') || $CFG->dbname !== 'skyscholar_test') { exit(2); }
require_once($CFG->dirroot . '/course/lib.php');
\core\session\manager::set_user(get_admin());
$course = $DB->get_record('course', ['idnumber' => 'skyscholar:draft-radiotelephony-practice'], '*', MUST_EXIST);
$idnumber = 'ssc:' . substr(hash('sha256', 'draft-radiotelephony-practice:radio-lab-v2'), 0, 40);
$cm = $DB->get_record('course_modules', ['course' => $course->id, 'idnumber' => $idnumber], '*', MUST_EXIST);
$open = ($argv[1] ?? '') === '--open';
if ($open) {
    $category = \core_course_category::get($course->category);
    $ancestors = $category->get_parents();
    foreach ($ancestors as $parentid) { \core_course_category::get($parentid)->show(); }
    $category->show();
    $manual = enrol_get_plugin('manual');
    $instances = array_filter(enrol_get_instances($course->id, false), fn($x) => $x->enrol === 'manual');
    $instanceid = $instances ? reset($instances)->id : $manual->add_instance($course);
    $student = $DB->get_field('user', 'id', ['username' => 'testlearner1'], MUST_EXIST);
    $role = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
    $manual->enrol_user($instanceid, $student, $role);
}
update_course((object)['id' => $course->id, 'visible' => $open ? 1 : 0]);
set_coursemodule_visible($cm->id, $open ? 1 : 0);
rebuild_course_cache($course->id, true);
if (!$open) {
    \core_course_category::get($course->category)->hide();
    $parent = $DB->get_record('course_categories', ['idnumber' => 'skyscholar:draft-content']);
    if ($parent) { \core_course_category::get($parent->id)->hide(); }
}
