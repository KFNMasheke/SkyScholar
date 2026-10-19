<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_login();
if (isguestuser()) {
    throw new moodle_exception('noguest');
}
$PAGE->set_context(context_system::instance());
$PAGE->set_url('/local/skyscholar/index.php');
$PAGE->set_title('My practice progress');
$PAGE->set_heading('SkyScholar');
echo $OUTPUT->header();
echo $OUTPUT->heading('My practice progress');
echo html_writer::tag('p', 'Free unlimited practice. Results show your study history, not a prediction of an official exam result.');
echo html_writer::link(new moodle_url('/course/index.php'), 'Browse free courses', ['class' => 'btn btn-primary mb-3']);
$courses = enrol_get_users_courses($USER->id, true);
$table = new html_table();
$table->head = ['Practice activity', 'Completed', 'Score', 'Review'];
$params = ['userid' => $USER->id, 'finished' => 'finished'];
$sql = "SELECT a.*, q.name AS quizname, q.sumgrades AS maximum, q.course AS courseid,
               cm.id AS cmid
          FROM {quiz_attempts} a JOIN {quiz} q ON q.id = a.quiz
          JOIN {modules} m ON m.name = 'quiz'
          JOIN {course_modules} cm ON cm.instance = q.id AND cm.module = m.id
         WHERE a.userid = :userid AND a.state = :finished AND a.preview = 0
      ORDER BY a.timefinish DESC, a.id DESC";
$attempts = $DB->get_records_sql($sql, $params, 0, 50);
$scores = [];
foreach ($attempts as $attempt) {
    if (!isset($courses[$attempt->courseid])) {
        continue;
    }
    $modinfo = get_fast_modinfo($attempt->courseid);
    if (empty($modinfo->cms[$attempt->cmid]) || !$modinfo->cms[$attempt->cmid]->uservisible) {
        continue;
    }
    $attemptobj = \mod_quiz\quiz_attempt::create($attempt->id);
    $options = $attemptobj->get_display_options(true);
    $score = '-';
    if ($options->marks && $attempt->maximum > 0 && $attempt->sumgrades !== null) {
        $value = round(100 * $attempt->sumgrades / $attempt->maximum, 1);
        $score = $value . '%';
        $scores[$attempt->quizname][] = $value;
    }
    $table->data[] = [format_string($attempt->quizname), userdate($attempt->timefinish), $score,
        $options->attempt ? html_writer::link(new moodle_url('/local/skyscholar/review.php', ['attempt' => $attempt->id]),
            'Review mistakes and report issues') : 'Review not available'];
}
if ($table->data) {
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification('Your completed attempts will appear here. Enrol in a free course to begin.', 'info');
}
if ($scores) {
    $averages = array_map(fn($values) => array_sum($values) / count($values), $scores);
    asort($averages);
    echo $OUTPUT->heading('Suggested revision', 3);
    echo html_writer::tag('p', 'Lowest activity averages from your latest 50 completed attempts. Revisit their practice quizzes.');
    foreach (array_slice($averages, 0, 3, true) as $name => $average) {
        echo html_writer::tag('p', s($name) . ': ' . round($average, 1) . '%');
    }
}
echo $OUTPUT->footer();
