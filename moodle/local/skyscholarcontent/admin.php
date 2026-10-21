<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require_once(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
admin_externalpage_setup('local_skyscholarcontent');
require_capability('moodle/site:config', context_system::instance());
$PAGE->set_title(get_string('heading', 'local_skyscholarcontent'));
$PAGE->set_heading(get_string('heading', 'local_skyscholarcontent'));
$pack = \local_skyscholarcontent\local\loader::pack();
$targets = [];
$error = '';
try { $targets = \local_skyscholarcontent\local\loader::preflight(); }
catch (Throwable $e) { $error = $e->getMessage(); }
$PAGE->requires->js_call_amd('local_skyscholarcontent/importer', 'init', [
    (new moodle_url('/local/skyscholarcontent/run.php'))->out(false), sesskey(),
    array_column($pack['courses'], 'id'),
    ['running' => get_string('running', 'local_skyscholarcontent'),
        'complete' => get_string('complete', 'local_skyscholarcontent'),
        'failed' => get_string('failed', 'local_skyscholarcontent'),
        'retry' => get_string('retry', 'local_skyscholarcontent')],
]);
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('heading', 'local_skyscholarcontent'));
echo html_writer::tag('p', get_string('intro', 'local_skyscholarcontent'));
echo $OUTPUT->notification(get_string('safety', 'local_skyscholarcontent'), 'info');
if ($error) {
    echo $OUTPUT->notification(s($error), 'error');
} else {
    $table = new html_table();
    $table->head = [get_string('course', 'local_skyscholarcontent'), get_string('state', 'local_skyscholarcontent')];
    foreach ($pack['courses'] as $course) {
        $existing = $targets[$course['id']];
        $name = s($course['name']);
        if ($existing) { $name = html_writer::link(new moodle_url('/course/view.php', ['id' => $existing->id]), $name); }
        $table->data[] = [$name, get_string($existing ? 'existingcourse' : 'newcourse', 'local_skyscholarcontent')];
    }
    echo html_writer::table($table);
    echo html_writer::tag('button', get_string('start', 'local_skyscholarcontent'),
        ['type' => 'button', 'id' => 'ssc-start', 'class' => 'btn btn-primary', 'disabled' => 'disabled']);
    echo html_writer::tag('noscript', 'JavaScript is required for this sequential importer. No content has been changed.');
}
echo html_writer::tag('p', '', ['id' => 'ssc-status', 'role' => 'status', 'aria-live' => 'polite', 'class' => 'mt-3']);
echo html_writer::tag('ol', '', ['id' => 'ssc-results']);
echo html_writer::tag('p', html_writer::link(new moodle_url('/course/management.php'),
    get_string('viewcourses', 'local_skyscholarcontent'), ['class' => 'btn btn-secondary']));
echo $OUTPUT->footer();
