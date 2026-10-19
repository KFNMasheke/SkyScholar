<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
$attemptid = required_param('attempt', PARAM_INT);
$attemptobj = \mod_quiz\quiz_attempt::create($attemptid);
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
if (!$attemptobj->is_own_attempt() || !$attemptobj->is_finished()) {
    throw new moodle_exception('nopermissions', 'error', '', 'Review this attempt');
}
$attemptobj->check_review_capability();
$mistakes = optional_param('mistakes', 0, PARAM_BOOL);
$options = $attemptobj->get_display_options(true);
if (!$options->attempt) {
    throw new moodle_exception('nopermissions', 'error', '', 'Review this attempt');
}
$PAGE->set_url('/local/skyscholar/review.php', ['attempt' => $attemptid]);
$PAGE->set_context($attemptobj->get_context());
$PAGE->set_title('Attempt revision');
$PAGE->set_heading('SkyScholar');
echo $OUTPUT->header();
echo $OUTPUT->heading('Attempt revision');
echo html_writer::link(new moodle_url('/mod/quiz/review.php', ['attempt' => $attemptid]),
    'Open full answers and explanations', ['class' => 'btn btn-primary mb-3']);
if ($options->correctness) {
    echo html_writer::tag('p', html_writer::link(new moodle_url('/local/skyscholar/review.php',
        ['attempt' => $attemptid, 'mistakes' => !$mistakes]), $mistakes ? 'Show all questions' : 'Show mistakes only'));
}
echo html_writer::tag('p', 'Question flags in Moodle bookmark items within an attempt. Use the links below to send a quality report to the content team.');
$table = new html_table();
$table->head = ['Question', 'Result', 'Content issue'];
foreach ($attemptobj->get_slots() as $slot) {
    $qa = $attemptobj->get_question_attempt($slot);
    if ($mistakes && $options->correctness && $qa->get_state()->is_correct()) {
        continue;
    }
    $state = $options->correctness ? $qa->get_state_string(true) : 'Result not available';
    $table->data[] = ['Question ' . (int)$slot, s($state),
        html_writer::link(new moodle_url('/local/skyscholar/report.php', ['attempt' => $attemptid, 'slot' => $slot]),
            'Report a question issue')];
}
echo html_writer::table($table);
echo $OUTPUT->footer();
