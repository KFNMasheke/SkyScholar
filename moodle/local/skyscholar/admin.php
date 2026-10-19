<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');
admin_externalpage_setup('local_skyscholar');
require_capability('local/skyscholar:manage', context_system::instance());
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_sesskey();
    $id = required_param('resolve', PARAM_INT);
    $DB->set_field('local_skyscholar_report', 'status', 'resolved', ['id' => $id]);
    redirect(new moodle_url('/local/skyscholar/admin.php'), 'Report marked resolved.');
}
echo $OUTPUT->header();
echo $OUTPUT->heading('SkyScholar readiness');
$lastcron = (int)get_config('tool_task', 'lastcronstart');
echo $OUTPUT->notification($lastcron && time() - $lastcron < 300 ? 'Cron has run within five minutes.' :
    'Launch blocker: cron has not run within five minutes. Arrange an approved one-minute scheduler.',
    $lastcron && time() - $lastcron < 300 ? 'success' : 'warning');
foreach (\local_skyscholar\local\platform::tracks() as $track => $spec) {
    echo $OUTPUT->heading($spec['name'], 3);
    $table = new html_table();
    $table->head = ['Subject', 'Approved questions', 'Minimum to publish'];
    foreach (\local_skyscholar\local\platform::readiness($track) as $row) {
        $table->data[] = [s($row['name']), $row['approved'], $row['required']];
    }
    echo html_writer::table($table);
}
$page = optional_param('page', 0, PARAM_INT);
$page = max(0, $page);
$count = $DB->count_records('local_skyscholar_report', ['status' => 'open']);
echo $OUTPUT->heading('Open question reports', 3);
$table = new html_table();
$table->head = ['Report', 'Attempt / question', 'Description', 'Action'];
foreach ($DB->get_records('local_skyscholar_report', ['status' => 'open'], 'timecreated ASC, id ASC', '*', $page * 50, 50) as $report) {
    $button = new single_button(new moodle_url('/local/skyscholar/admin.php',
        ['resolve' => $report->id, 'sesskey' => sesskey()]), 'Resolve', 'post');
    $table->data[] = [$report->id, $report->attemptid . ' / ' . $report->slot,
        s($report->message), $OUTPUT->render($button)];
}
echo html_writer::table($table);
echo $OUTPUT->paging_bar($count, $page, 50, $PAGE->url);
echo $OUTPUT->footer();
