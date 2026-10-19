<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
$attemptid = required_param('attempt', PARAM_INT);
$slot = required_param('slot', PARAM_INT);
$attemptobj = \mod_quiz\quiz_attempt::create($attemptid);
require_login($attemptobj->get_course(), false, $attemptobj->get_cm());
if (!$attemptobj->is_own_attempt() || !$attemptobj->is_finished() ||
        !in_array($slot, array_map('intval', $attemptobj->get_slots()), true)) {
    throw new moodle_exception('nopermissions', 'error', '', 'Report this question');
}
$attemptobj->check_review_capability();
if (!$attemptobj->get_display_options(true)->attempt) {
    throw new moodle_exception('nopermissions', 'error', '', 'Report this question');
}
$back = new moodle_url('/local/skyscholar/review.php', ['attempt' => $attemptid]);
$PAGE->set_url('/local/skyscholar/report.php', ['attempt' => $attemptid, 'slot' => $slot]);
$PAGE->set_context($attemptobj->get_context());
$PAGE->set_title('Report question issue');
$PAGE->set_heading('SkyScholar');
$form = new \local_skyscholar\report_form(null, ['attempt' => $attemptid, 'slot' => $slot]);
if ($form->is_cancelled()) {
    redirect($back);
}
if ($data = $form->get_data()) {
    $lock = \core\lock\lock_config::get_lock_factory('local_skyscholar')->get_lock('report-user-' . $USER->id, 5);
    if (!$lock) {
        throw new moodle_exception('error', 'error');
    }
    try {
        $params = ['userid' => $USER->id, 'attemptid' => $attemptid, 'slot' => $slot];
        if (!$DB->record_exists('local_skyscholar_report', $params)) {
            if ($DB->count_records_select('local_skyscholar_report', 'userid = ? AND timecreated > ?',
                    [$USER->id, time() - HOURSECS]) >= 20) {
                throw new moodle_exception('error', 'error', '', 'Report limit reached. Try later.');
            }
            $DB->insert_record('local_skyscholar_report', (object)($params + [
                'message' => $data->message, 'status' => 'open', 'timecreated' => time(),
            ]));
        }
    } finally {
        $lock->release();
    }
    redirect($back, 'Your question report has been recorded.', null, \core\output\notification::NOTIFY_SUCCESS);
}
echo $OUTPUT->header();
echo $OUTPUT->heading('Report an issue with question ' . $slot);
echo html_writer::tag('p', 'Describe the issue and any useful source reference. Avoid including personal information.');
$form->display();
echo $OUTPUT->footer();
