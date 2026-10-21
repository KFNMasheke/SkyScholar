<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_skyscholarcontent\local;
defined('MOODLE_INTERNAL') || die();

/** Additive provisioning. Native IDs provide ownership/idempotence; no parallel learner database. */
final class loader {
    public static function pack(): array {
        static $data;
        if ($data !== null) { return $data; }
        $path = __DIR__ . '/../../content/pack.json';
        $json = file_get_contents($path);
        $expected = trim(file_get_contents(__DIR__ . '/../../content/pack.sha256'));
        if (!$json || strlen($json) > 2 * 1024 * 1024 || !hash_equals($expected, hash('sha256', $json))) {
            throw new \RuntimeException('The bundled content file is missing or damaged. Re-upload this plugin package.');
        }
        $pack = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        self::validate($pack);
        return $data = $pack;
    }

    public static function validate(array $data): void {
        if (!in_array($data['schema'] ?? null, [1, 2], true) || count($data['courses'] ?? []) !== ($data['schema'] === 2 ? 15 : 14)
                || count($data['subjects'] ?? []) !== 7 || count($data['questions'] ?? []) !== 35) {
            throw new \InvalidArgumentException('Unexpected content package schema/counts.');
        }
        if ($data['schema'] === 2) {
            if (count($data['assets'] ?? []) !== 76 || count($data['radio']['scenarios'] ?? []) !== 27) {
                throw new \InvalidArgumentException('Incomplete illustrated curriculum or radio lab.');
            }
            foreach ($data['assets'] as $name => $hash) {
                if (!preg_match('/^[a-z0-9-]+\.png$/D', $name) || !is_string($hash)
                        || !preg_match('/^[a-f0-9]{64}$/D', $hash)) {
                    throw new \InvalidArgumentException('Invalid illustration identity.');
                }
                $file = __DIR__ . '/../../content/illustrations/' . $name;
                if (!is_file($file) || !hash_equals($hash, hash_file('sha256', $file))) {
                    throw new \RuntimeException('An illustration is missing or damaged. Re-upload the complete plugin.');
                }
            }
            $scenarioids = [];
            foreach ($data['radio']['scenarios'] as $scenario) {
                if (!preg_match('/^[a-z0-9-]+$/D', $scenario['id'] ?? '') || isset($scenarioids[$scenario['id']])) {
                    throw new \InvalidArgumentException('Duplicate or invalid radio scenario.');
                }
                $scenarioids[$scenario['id']] = true;
                foreach (['title', 'group', 'context', 'prompt', 'model', 'notes'] as $field) {
                    if (!is_string($scenario[$field] ?? null) || strlen($scenario[$field]) > 5000) {
                        throw new \InvalidArgumentException('Invalid radio scenario text.');
                    }
                }
                foreach ($scenario['checks'] ?? [] as $check) {
                    if (!is_string($check['label'] ?? null) || empty($check['any'])) {
                        throw new \InvalidArgumentException('Invalid radio checklist.');
                    }
                    foreach ($check['any'] as $term) {
                        if (!is_string($term) || strlen($term) > 200) { throw new \InvalidArgumentException('Invalid radio term.'); }
                    }
                }
            }
        }
        $ids = [];
        foreach ($data['courses'] as $course) {
            $id = $course['id'] ?? '';
            if (!is_string($id) || !preg_match('/^draft-[a-z0-9-]{1,70}$/D', $id) || isset($ids[$id])
                    || ($course['status'] ?? '') !== 'draft' || count($course['modules'] ?? []) !== 4) {
                throw new \InvalidArgumentException('Invalid course identifier or draft status.');
            }
            $ids[$id] = true;
            foreach (['name', 'category', 'summary', 'lesson', 'blueprint'] as $field) {
                if (!is_string($course[$field] ?? null) || $course[$field] === '' || strlen($course[$field]) > 100000) {
                    throw new \InvalidArgumentException('Missing course content: ' . $field);
                }
            }
            if ($data['schema'] === 2) {
                $pagekeys = [];
                foreach ($course['pages'] ?? [] as $page) {
                    if (!preg_match('/^[a-z0-9-]{1,60}$/D', $page['key'] ?? '') || isset($pagekeys[$page['key']])
                            || !is_int($page['section'] ?? null) || $page['section'] < 0 || $page['section'] > 4
                            || !is_string($page['name'] ?? null) || !is_string($page['html'] ?? null)
                            || strlen($page['html']) > 150000) {
                        throw new \InvalidArgumentException('Invalid curriculum Page.');
                    }
                    $pagekeys[$page['key']] = true;
                    foreach ($page['assets'] ?? [] as $name) {
                        if (!isset($data['assets'][$name])) { throw new \InvalidArgumentException('Unknown Page illustration.'); }
                    }
                }
                $expectedpages = $id === 'draft-radiotelephony-practice' ? 11 : 6;
                if (count($pagekeys) !== $expectedpages || !is_string($course['assignment']['html'] ?? null)) {
                    throw new \InvalidArgumentException('Incomplete lesson sequence or final project.');
                }
            }
            foreach ($course['modules'] as $title) {
                if (!is_string($title) || $title === '' || strlen($title) > 255) {
                    throw new \InvalidArgumentException('Invalid module heading.');
                }
            }
        }
        if (!isset($ids['draft-sacaa-drone-pilot-license-preparation'])) {
            throw new \InvalidArgumentException('The South African draft review course is required.');
        }
        $topics = [];
        foreach ($data['subjects'] as $subject) {
            if (!preg_match('/^[a-z-]{1,40}$/D', $subject['id'] ?? '') || isset($topics[$subject['id']])
                    || !is_string($subject['name'] ?? null) || !is_string($subject['html'] ?? null)
                    || strlen($subject['html']) > 100000) {
                throw new \InvalidArgumentException('Invalid subject note.');
            }
            $topics[$subject['id']] = true;
        }
        $qids = [];
        foreach ($data['questions'] as $question) {
            if (!preg_match('/^[a-z0-9][a-z0-9._-]{0,79}$/D', $question['id'] ?? '')
                    || isset($qids[$question['id']]) || !isset($topics[$question['topic'] ?? ''])
                    || ($question['status'] ?? '') !== 'draft' || ($question['reviewer'] ?? null) !== ''
                    || ($question['review_date'] ?? null) !== '') {
                throw new \InvalidArgumentException('Question must retain its unique ID and unreviewed draft status.');
            }
            $qids[$question['id']] = true;
            foreach (['stem', 'objective', 'source', 'explanation'] as $field) {
                if (!is_string($question[$field] ?? null) || trim($question[$field]) === ''
                        || strlen($question[$field]) > 10000) {
                    throw new \InvalidArgumentException('Missing question field: ' . $field);
                }
            }
            $options = $question['options'] ?? [];
            $feedback = $question['feedback'] ?? [];
            if (!is_array($options) || !is_array($feedback) || count($options) < 2 || count($options) > 6
                    || count($options) !== count($feedback) || !is_int($question['correct'] ?? null)
                    || $question['correct'] < 0 || $question['correct'] >= count($options)) {
                throw new \InvalidArgumentException('Invalid answer key or feedback.');
            }
            foreach (array_merge($options, $feedback) as $text) {
                if (!is_string($text) || trim($text) === '' || strlen($text) > 10000) {
                    throw new \InvalidArgumentException('Invalid answer or feedback text.');
                }
            }
            if (count(array_unique($options)) !== count($options)) {
                throw new \InvalidArgumentException('Duplicate answer options.');
            }
        }
    }

    public static function existing(array $spec): ?\stdClass {
        global $DB;
        $shortname = 'SKS-DRAFT-' . substr($spec['id'], 6);
        $courses = $DB->get_records('course', ['idnumber' => 'skyscholar:' . $spec['id']]);
        if (count($courses) > 1) { throw new \RuntimeException('Duplicate course IDs: ' . $spec['name']); }
        $course = $courses ? reset($courses) : null;
        $named = $DB->get_record('course', ['shortname' => $shortname]);
        if (($course && $course->shortname !== $shortname) || ($named && (!$course || $named->id != $course->id))) {
            throw new \RuntimeException('Course identity conflict: ' . $spec['name'] . '. No existing course was changed.');
        }
        if ($course && ($course->id == SITEID || $course->visible || $course->format !== 'topics')) {
            throw new \RuntimeException('Existing course must be hidden and use the Topics format before import: ' . $spec['name']);
        }
        return $course ?: null;
    }

    /** Read-only checks. Installation/GET requests never provision content. */
    public static function preflight(): array {
        global $DB;
        require_capability('moodle/site:config', \context_system::instance());
        if (!$DB->record_exists('modules', ['name' => 'page', 'visible' => 1])) {
            throw new \RuntimeException('Enable the native Page activity before importing.');
        }
        if (self::pack()['schema'] === 2 && !$DB->record_exists('modules', ['name' => 'assign', 'visible' => 1])) {
            throw new \RuntimeException('Enable the native Assignment activity before importing.');
        }
        $result = [];
        foreach (self::pack()['courses'] as $spec) { $result[$spec['id']] = self::existing($spec); }
        return $result;
    }

    public static function import_course(string $key): array {
        global $CFG, $DB;
        self::preflight();
        $pack = self::pack();
        $specs = array_column($pack['courses'], null, 'id');
        if (!isset($specs[$key])) { throw new \InvalidArgumentException('Unknown bundled course.'); }
        $spec = $specs[$key];
        $lock = \core\lock\lock_config::get_lock_factory('local_skyscholarcontent')->get_lock('content-import', 0);
        if (!$lock) { throw new \RuntimeException('Another course import is running. Please retry.'); }
        try {
            require_once($CFG->dirroot . '/course/lib.php');
            require_once($CFG->dirroot . '/course/modlib.php');
            require_once($CFG->dirroot . '/question/editlib.php');
            $transaction = $DB->start_delegated_transaction();
            try {
                $course = self::existing($spec);
                $result = ['name' => $spec['name'], 'courses' => 0, 'pages' => 0, 'questions' => 0, 'assignments' => 0];
                if (!$course) {
                    $parent = self::category('skyscholar:draft-content', 'SkyScholar — Draft content', 0);
                    $category = self::category('ssc:' . substr(hash('sha256', $spec['category']), 0, 30),
                        $spec['category'], $parent->id);
                    $course = create_course((object)[
                        'fullname' => '[DRAFT] ' . $spec['name'], 'shortname' => 'SKS-DRAFT-' . substr($key, 6),
                        'idnumber' => 'skyscholar:' . $key, 'category' => $category->id, 'visible' => 0,
                        'summary' => $spec['summary'], 'summaryformat' => FORMAT_HTML,
                        'format' => 'topics', 'numsections' => 4, 'newsitems' => 0, 'enablecompletion' => 0,
                    ]);
                    $result['courses']++;
                }
                if (!empty($spec['previoussummary']) && $course->summary === $spec['previoussummary']) {
                    update_course((object)['id' => $course->id, 'summary' => $spec['summary'], 'summaryformat' => FORMAT_HTML]);
                }
                course_create_sections_if_missing($course, range(0, 4));
                foreach ($spec['modules'] as $index => $title) {
                    $section = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $index + 1], '*', MUST_EXIST);
                    if ($section->name === null || $section->name === '') {
                        course_update_section($course, $section, ['name' => $title]);
                    }
                }
                if ($key !== 'draft-radiotelephony-practice') {
                    $result['pages'] += self::page($course, $key, 'plan', 'DRAFT — Course plan and learning outcomes', $spec['blueprint'], 0);
                    $result['pages'] += self::page($course, $key, 'lesson-01', 'DRAFT — Opening lesson and desk exercise', $spec['lesson'], 1);
                }
                foreach ($spec['pages'] ?? [] as $page) {
                    $html = str_replace('[[RADIOLAB]]', (new \moodle_url('/local/skyscholarcontent/radio.php', ['courseid' => $course->id]))->out(false), $page['html']);
                    $result['pages'] += self::page($course, $key, $page['key'], $page['name'], $html, $page['section'], $page['assets']);
                }
                if (!empty($spec['assignment'])) {
                    $result['assignments'] += self::assignment($course, $key, $spec['assignment']);
                }
                if ($key === 'draft-sacaa-drone-pilot-license-preparation') {
                    foreach ($pack['subjects'] as $subject) {
                        $section = in_array($subject['id'], ['meteorology', 'navigation', 'communications'], true) ? 3 : 2;
                        $result['pages'] += self::page($course, $key, 'subject-' . $subject['id'],
                            'DRAFT — ' . $subject['name'], $subject['html'], $section);
                        foreach ($pack['questions'] as $question) {
                            if ($question['topic'] === $subject['id']) {
                                $result['questions'] += self::question($course, $subject, $question);
                            }
                        }
                    }
                }
                $transaction->allow_commit();
            } catch (\Throwable $error) { $transaction->rollback($error); }
            rebuild_course_cache($course->id, true);
            $result['courseid'] = (int)$course->id;
            if ($result['courses'] + $result['pages'] + $result['questions'] + $result['assignments'] > 0) {
                \local_skyscholarcontent\event\course_imported::create([
                    'context' => \context_course::instance($course->id), 'objectid' => $course->id,
                    'other' => ['pages' => $result['pages'], 'questions' => $result['questions'], 'assignments' => $result['assignments'], 'pack' => $pack['schema']],
                ])->trigger();
            }
            return $result;
        } finally { $lock->release(); }
    }

    private static function category(string $idnumber, string $name, int $parent): object {
        global $DB;
        $matches = $DB->get_records('course_categories', ['idnumber' => $idnumber]);
        if (count($matches) > 1) { throw new \RuntimeException('Duplicate content category IDs.'); }
        if ($matches) {
            $category = reset($matches);
            if ($category->parent != $parent || $category->visible) {
                throw new \RuntimeException('The content import category must remain hidden in its original location.');
            }
            return $category;
        }
        return \core_course_category::create((object)[
            'name' => $name, 'idnumber' => $idnumber, 'parent' => $parent, 'visible' => 0,
        ]);
    }

    private static function page(\stdClass $course, string $key, string $item, string $name, string $html, int $section, array $assets = []): int {
        global $DB;
        $idnumber = 'ssc:' . substr(hash('sha256', $key . ':' . $item), 0, 40);
        $cms = $DB->get_records('course_modules', ['course' => $course->id, 'idnumber' => $idnumber]);
        if (count($cms) > 1) { throw new \RuntimeException('Duplicate page identifiers.'); }
        if ($cms) {
            $cm = reset($cms);
            $pageid = $DB->get_field('modules', 'id', ['name' => 'page'], MUST_EXIST);
            if ($cm->module != $pageid || $cm->deletioninprogress || !$DB->record_exists('page', ['id' => $cm->instance, 'course' => $course->id])) {
                throw new \RuntimeException('An imported page was removed or its identity changed. Resolve it before retrying.');
            }
            return 0; // Preserve staff edits and existing activity records.
        }
        [, , , , $data] = prepare_new_moduleinfo_data($course, 'page', $section);
        $data->name = $name;
        $data->cmidnumber = $idnumber;
        $data->visible = 0;
        $data->completion = COMPLETION_TRACKING_NONE;
        $data->introeditor = ['text' => 'Unreviewed draft. Opening content only; keep hidden for staff review.', 'format' => FORMAT_PLAIN, 'itemid' => 0];
        $data->page = ['text' => $html, 'format' => FORMAT_HTML, 'itemid' => 0];
        // page_add_instance only copies the editor array when a form object is supplied.
        $data->content = $html;
        $data->contentformat = FORMAT_HTML;
        $data->display = 5; // RESOURCELIB_DISPLAY_OPEN: native Page view.
        $data->printintro = 0;
        $data->printlastmodified = 0;
        $created = add_moduleinfo($data, $course);
        $context = \context_module::instance($created->coursemodule);
        foreach ($assets as $filename) {
            get_file_storage()->create_file_from_pathname([
                'contextid' => $context->id, 'component' => 'mod_page', 'filearea' => 'content',
                'itemid' => 0, 'filepath' => '/', 'filename' => $filename, 'mimetype' => 'image/png',
            ], __DIR__ . '/../../content/illustrations/' . $filename);
        }
        return 1;
    }

    private static function assignment(\stdClass $course, string $key, array $spec): int {
        global $DB;
        $idnumber = 'ssc:' . substr(hash('sha256', $key . ':v2-project-assignment'), 0, 40);
        $cms = $DB->get_records('course_modules', ['course' => $course->id, 'idnumber' => $idnumber]);
        if (count($cms) > 1) { throw new \RuntimeException('Duplicate project identifiers.'); }
        if ($cms) {
            $cm = reset($cms);
            $module = $DB->get_field('modules', 'id', ['name' => 'assign'], MUST_EXIST);
            if ($cm->module != $module || $cm->deletioninprogress || !$DB->record_exists('assign', ['id' => $cm->instance, 'course' => $course->id])) {
                throw new \RuntimeException('An imported project was removed or its identity changed.');
            }
            return 0;
        }
        [, , , , $data] = prepare_new_moduleinfo_data($course, 'assign', 4);
        $data->name = $spec['name']; $data->cmidnumber = $idnumber; $data->visible = 0;
        $data->completion = COMPLETION_TRACKING_NONE;
        $data->introeditor = ['text' => $spec['html'], 'format' => FORMAT_HTML, 'itemid' => 0];
        foreach (['requiresubmissionstatement', 'sendnotifications', 'sendlatenotifications', 'sendstudentnotifications',
            'duedate', 'cutoffdate', 'gradingduedate', 'allowsubmissionsfromdate', 'teamsubmission',
            'requireallteammemberssubmit', 'blindmarking', 'markingworkflow', 'markingallocation'] as $field) { $data->$field = 0; }
        $data->submissiondrafts = 1; $data->grade = 100; $data->gradepass = 0;
        $data->maxattempts = -1; $data->attemptreopenmethod = 'manual';
        $data->assignsubmission_onlinetext_enabled = 1;
        $data->assignsubmission_onlinetext_wordlimit_enabled = 0;
        $data->assignsubmission_file_enabled = 0;
        $data->assignfeedback_comments_enabled = 1;
        $data->assignfeedback_file_enabled = 0;
        $data->assignfeedback_editpdf_enabled = 0;
        add_moduleinfo($data, $course);
        return 1;
    }

    private static function editor(string $text): array {
        return ['text' => $text, 'format' => FORMAT_PLAIN, 'itemid' => 0];
    }

    private static function question(\stdClass $course, array $subject, array $item): int {
        global $DB;
        $context = \context_course::instance($course->id);
        $idnumber = 'ssc-draft-' . $subject['id'];
        $category = $DB->get_record('question_categories', ['contextid' => $context->id, 'idnumber' => $idnumber]);
        if (!$category) {
            $category = (object)['name' => 'STAFF REVIEW — ' . $subject['name'], 'contextid' => $context->id,
                'idnumber' => $idnumber, 'info' => 'Unreviewed original drafts; no learner quiz is populated.',
                'infoformat' => FORMAT_PLAIN, 'stamp' => make_unique_id_code(), 'parent' => 0, 'sortorder' => 999];
            $category->id = $DB->insert_record('question_categories', $category);
        }
        $qid = 'ssc-' . $item['id'];
        if ($DB->record_exists('question_bank_entries', ['questioncategoryid' => $category->id, 'idnumber' => $qid])) {
            return 0; // Do not overwrite edits, reviews or question versions.
        }
        $form = (object)[
            'category' => (string)$category->id, 'name' => 'DRAFT / DO NOT PUBLISH / ' . $item['id'], 'idnumber' => $qid,
            'questiontext' => self::editor('STAFF REVIEW ONLY. Not approved aviation training.' . "\n\n" . $item['stem']),
            'generalfeedback' => self::editor($item['explanation'] . "\n\nObjective: " . $item['objective'] . "\nReference: " . $item['source']),
            'defaultmark' => 1, 'penalty' => 0, 'single' => 1, 'shuffleanswers' => 1,
            'answernumbering' => 'abc', 'showstandardinstruction' => 0,
            'correctfeedback' => self::editor('Correct.'), 'partiallycorrectfeedback' => self::editor(''),
            'incorrectfeedback' => self::editor('Review the explanation.'), 'shownumcorrect' => 0,
            'hint' => [], 'status' => 'draft',
        ];
        foreach ($item['options'] as $index => $answer) {
            $form->answer[] = self::editor($answer);
            $form->fraction[] = $index === $item['correct'] ? 1 : 0;
            $form->feedback[] = self::editor($item['feedback'][$index]);
        }
        $saved = \question_bank::get_qtype('multichoice')->save_question((object)['qtype' => 'multichoice'], $form);
        if (!empty($saved->errors)) { throw new \RuntimeException('Moodle rejected draft question ' . $item['id']); }
        return 1;
    }
}
