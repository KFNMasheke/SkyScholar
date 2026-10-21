<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require_once(__DIR__ . '/../../config.php');
require_login();
$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid,
    'idnumber' => 'skyscholar:draft-radiotelephony-practice'], '*', MUST_EXIST);
$idnumber = 'ssc:' . substr(hash('sha256', 'draft-radiotelephony-practice:radio-lab-v2'), 0, 40);
$cmrecord = $DB->get_record('course_modules', ['course' => $courseid, 'idnumber' => $idnumber], '*', MUST_EXIST);
$cm = get_coursemodule_from_id('page', $cmrecord->id, $courseid, false, MUST_EXIST);
require_login($course, true, $cm);
require_capability('mod/page:view', context_module::instance($cm->id));
$PAGE->set_url(new moodle_url('/local/skyscholarcontent/radio.php', ['courseid' => $courseid]));
$PAGE->set_context(context_module::instance($cm->id));
$PAGE->set_course($course);
$PAGE->set_pagelayout('incourse');
$PAGE->set_title('SkyScholar radiotelephony practice');
$PAGE->set_heading(format_string($course->fullname));
$pack = \local_skyscholarcontent\local\loader::pack();
$PAGE->requires->js_call_amd('local_skyscholarcontent/radio', 'init', [$pack['radio']['scenarios']]);
echo $OUTPUT->header();
echo $OUTPUT->heading('Radiotelephony practice lab');
echo $OUTPUT->notification('Fictional self-study rehearsal. No radio transmission, clearance or certificate is provided. Local procedure and pronunciation require competent instructor review.', 'info');
?>
<div id="ssc-radio-lab" class="row">
<div class="col-lg-8">
    <label for="ssc-radio-scenario">Choose a training scenario</label>
    <select id="ssc-radio-scenario" class="custom-select form-control mb-3"></select>
    <p id="ssc-radio-context" class="text-muted"></p>
    <h3>Received message or practice task</h3><p id="ssc-radio-prompt" class="p-3 border rounded"></p>
    <div class="d-flex flex-wrap mb-3" style="gap:.5rem">
        <button type="button" class="btn btn-secondary" id="ssc-radio-listen">Listen with browser voice</button>
        <button type="button" class="btn btn-secondary" id="ssc-radio-stopvoice">Stop voice</button>
    </div>
    <p class="small text-muted">The optional synthetic voice is a convenience, not an authoritative pronunciation model.</p>
    <label for="ssc-radio-response">Type your response or explanation</label>
    <textarea id="ssc-radio-response" rows="4" class="form-control mb-3"></textarea>
    <button type="button" class="btn btn-primary" id="ssc-radio-check">Review selected elements</button>
    <button type="button" class="btn btn-secondary" id="ssc-radio-next">Next scenario</button>
    <div id="ssc-radio-feedback" class="mt-3" aria-live="polite"></div>
    <details id="ssc-radio-reveal" class="border rounded p-3"><summary>Compare the model and limitations</summary><p id="ssc-radio-model" class="mt-3"></p><p id="ssc-radio-notes"></p></details>
</div>
<aside class="col-lg-4 mt-4 mt-lg-0">
    <div class="card mb-3"><div class="card-body"><h3>Record and review</h3>
        <p>Microphone access starts only when you choose Record. The recording stays as a temporary object in this browser session; this page does not upload or transcribe it.</p>
        <button type="button" class="btn btn-secondary mb-2" id="ssc-radio-record">Record practice locally</button>
        <button type="button" class="btn btn-secondary mb-2" id="ssc-radio-clear">Clear recording</button>
        <audio id="ssc-radio-audio" controls hidden class="w-100"></audio><p id="ssc-radio-status" class="small mt-2" role="status"></p>
    </div></div>
    <div class="card"><div class="card-body"><h3>Phonetic practice</h3>
        <label for="ssc-radio-token">Letters to spell</label><input id="ssc-radio-token" value="ZSABC" maxlength="20" class="form-control mb-2" autocomplete="off">
        <button type="button" class="btn btn-secondary mb-2" id="ssc-radio-spell">Show phonetics</button>
        <button type="button" class="btn btn-secondary mb-2" id="ssc-radio-random">Random group</button>
        <p id="ssc-radio-spelled" aria-live="polite"></p>
    </div></div>
</aside></div>
<noscript>JavaScript is required for interactive practice. The course lessons and model responses remain available in the native Page activities.</noscript>
<?php echo $OUTPUT->footer();
