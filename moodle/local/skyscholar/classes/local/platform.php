<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_skyscholar\local;
defined('MOODLE_INTERNAL') || die();

/** Provision exclusively through Moodle APIs. The core owns users, questions and attempts. */
final class platform {
    public static function tracks(): array {
        return json_decode(file_get_contents(__DIR__ . '/../../content/tracks.json'), true, 32, JSON_THROW_ON_ERROR);
    }

    public static function course(string $track): \stdClass {
        global $DB;
        return $DB->get_record('course', ['idnumber' => 'skyscholar:' . $track], '*', MUST_EXIST);
    }

    public static function configure(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/course/modlib.php');
        set_config('auth', 'email');
        // Enable public sign-up only AFTER SMTP and anti-abuse checks in the launch runbook.
        set_config('registerauth', '');
        set_config('authloginviaemail', 1);
        set_config('guestloginbutton', 0);
        set_config('forcelogin', 1);
        set_config('enablecompletion', 1);
        set_config('enablemobilewebservice', 0);
        set_config('enrol_plugins_enabled', 'manual,self');
        set_config('timezone', 'Africa/Johannesburg');
        set_config('sessiontimeout', 7200);
        set_config('passwordpolicy', 1);
        set_config('minpasswordlength', 12);
        set_config('lockoutthreshold', 10);
        set_config('lockoutwindow', 900);
        set_config('lockoutduration', 900);
        set_config('cronclionly', 1);
        set_config('debug', 0);
        set_config('debugdisplay', 0);
        set_config('custommenuitems', 'My practice progress|/local/skyscholar/index.php');
        $category = $DB->get_record('course_categories', ['idnumber' => 'skyscholar']);
        if (!$category) {
            $category = \core_course_category::create((object)[
                'name' => 'SkyScholar free practice', 'idnumber' => 'skyscholar', 'parent' => 0,
            ]);
        }
        foreach (self::tracks() as $track => $spec) {
            $course = $DB->get_record('course', ['idnumber' => 'skyscholar:' . $track]);
            if (!$course) {
                $course = create_course((object)[
                    'fullname' => $spec['name'], 'shortname' => 'SS-' . strtoupper($track),
                    'idnumber' => 'skyscholar:' . $track, 'category' => $category->id,
                    'summary' => $spec['jurisdiction'] . ' Free unlimited practice. Independent study aid.',
                    'summaryformat' => FORMAT_PLAIN, 'format' => 'topics', 'visible' => 0,
                    'enablecompletion' => 1,
                ]);
            }
            $self = enrol_get_plugin('self');
            $instances = enrol_get_instances($course->id, false);
            $selfinstances = array_filter($instances, fn($instance) => $instance->enrol === 'self');
            $roleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
            $enrolsettings = ['status' => ENROL_INSTANCE_ENABLED, 'roleid' => $roleid,
                'customint6' => 1, 'customint1' => 0, 'customint2' => 0, 'customint3' => 0,
                'customint4' => 0, 'customint5' => 0, 'password' => '', 'cost' => 0,
                'enrolperiod' => 0, 'enrolstartdate' => 0, 'enrolenddate' => 0,
                'expirynotify' => 0, 'notifyall' => 0];
            if (!$selfinstances) {
                $self->add_instance($course, $enrolsettings);
            } else {
                // Moodle may create a disabled self-enrol instance when the course is created.
                $self->update_instance(reset($selfinstances), (object)$enrolsettings);
            }
            $section = 0;
            foreach ($spec['topics'] as $topic => $label) {
                course_create_sections_if_missing($course, ++$section);
                $sectionrecord = $DB->get_record('course_sections', ['course' => $course->id, 'section' => $section]);
                course_update_section($course, $sectionrecord, ['name' => $label]);
                self::category($course, $topic, $label);
                foreach (['practice', 'mock'] as $mode) {
                    self::quiz($course, $track, $topic, $label, $mode, $section, $spec);
                }
            }
        }
    }

    private static function category(\stdClass $course, string $topic, string $name): \stdClass {
        global $DB;
        $context = \context_course::instance($course->id);
        $idnumber = 'ss-' . $topic;
        $category = $DB->get_record('question_categories', ['contextid' => $context->id, 'idnumber' => $idnumber]);
        if (!$category) {
            $category = (object)['name' => $name, 'contextid' => $context->id, 'info' => '',
                'infoformat' => FORMAT_PLAIN, 'stamp' => make_unique_id_code(), 'parent' => 0,
                'sortorder' => 999, 'idnumber' => $idnumber];
            $category->id = $DB->insert_record('question_categories', $category);
        }
        return $category;
    }

    private static function quiz(\stdClass $course, string $track, string $topic, string $label,
            string $mode, int $section, array $spec): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/modlib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $idnumber = "ss:$track:$topic:$mode";
        if ($DB->record_exists('course_modules', ['course' => $course->id, 'idnumber' => $idnumber])) {
            return;
        }
        [, , , , $data] = prepare_new_moduleinfo_data($course, 'quiz', $section);
        $data->name = $label . ($mode === 'practice' ? ' - practice' : ' - timed mock');
        $data->cmidnumber = $idnumber;
        $data->visible = 0;
        $data->introeditor = ['text' => 'Free unlimited attempts. This is a study exercise, not an official examination. '
            . 'The target score, question count and duration are SkyScholar study settings, not regulator specifications.',
            'format' => FORMAT_PLAIN, 'itemid' => 0];
        foreach (['timeopen', 'timeclose', 'attempts', 'attemptonlast', 'sumgrades',
                'delay1', 'delay2', 'showuserpicture', 'showblocks'] as $field) {
            $data->$field = 0;
        }
        $data->preferredbehaviour = $mode === 'practice' ? 'immediatefeedback' : 'deferredfeedback';
        $data->timelimit = $mode === 'mock' ? $spec['mock_minutes'] * 60 : 0;
        $data->overduehandling = 'autosubmit';
        $data->graceperiod = 0;
        $data->grademethod = QUIZ_GRADEHIGHEST;
        $data->grade = 100;
        $data->gradepass = $spec['practice_target'];
        $data->decimalpoints = 1;
        $data->questiondecimalpoints = -1;
        $data->questionsperpage = 1;
        $data->shuffleanswers = 1;
        $data->quizpassword = '';
        $data->subnet = '';
        $data->browsersecurity = '';
        $data->navmethod = QUIZ_NAVMETHOD_FREE;
        foreach (['attempt', 'correctness', 'maxmarks', 'marks', 'specificfeedback',
                'generalfeedback', 'rightanswer', 'overallfeedback'] as $option) {
            foreach (['during', 'immediately', 'open', 'closed'] as $when) {
                $field = $option . $when;
                $data->$field = $when !== 'during' || $mode === 'practice' || $option === 'attempt' ? 1 : 0;
            }
        }
        $data->overallfeedbackduring = 0;
        add_moduleinfo($data, $course);
    }

    public static function import(string $json): array {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/question/editlib.php');
        $data = validator::decode($json, self::tracks());
        $course = self::course($data['track']);
        $spec = self::tracks()[$data['track']];
        $transaction = $DB->start_delegated_transaction();
        $result = ['created' => 0, 'updated' => 0, 'unchanged' => 0];
        foreach ($data['questions'] as $q) {
            $externalid = $data['track'] . ':' . $q['id'];
            $hash = hash('sha256', json_encode($q, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            $mapping = $DB->get_record('local_skyscholar_content', ['externalid' => $externalid]);
            if ($mapping && $mapping->contenthash === $hash) {
                $result['unchanged']++;
                continue;
            }
            if ($mapping && $mapping->topic !== $q['topic']) {
                throw new \InvalidArgumentException('A version cannot change topic. Use a new question ID.');
            }
            $category = self::category($course, $q['topic'], $spec['topics'][$q['topic']]);
            $feedback = $q['explanation'] . "\n\nObjective: " . $q['objective'] . "\nReference: " . $q['source'];
            $form = (object)[
                'category' => (string)$category->id, 'name' => $q['id'], 'idnumber' => $q['id'],
                'questiontext' => self::editor($q['stem']), 'generalfeedback' => self::editor($feedback),
                'defaultmark' => 1, 'penalty' => 0, 'single' => 1, 'shuffleanswers' => 1,
                'answernumbering' => 'abc', 'showstandardinstruction' => 0,
                'correctfeedback' => self::editor('Correct.'), 'partiallycorrectfeedback' => self::editor(''),
                'incorrectfeedback' => self::editor('Review the explanation before trying again.'),
                'shownumcorrect' => 0, 'hint' => [],
                'status' => $q['status'] === 'approved' ? 'ready' : 'draft',
            ];
            foreach ($q['options'] as $index => $answer) {
                $form->answer[] = self::editor($answer);
                $form->fraction[] = $index === $q['correct'] ? 1 : 0;
                $form->feedback[] = self::editor($q['feedback'][$index]);
            }
            $question = (object)['qtype' => 'multichoice'];
            if ($mapping) {
                $question->id = $mapping->questionid;
            }
            $saved = \question_bank::get_qtype('multichoice')->save_question($question, $form);
            if (!empty($saved->errors)) {
                throw new \RuntimeException('Moodle rejected question: ' . $q['id']);
            }
            $row = (object)['externalid' => $externalid, 'questionid' => $saved->id,
                'courseid' => $course->id, 'topic' => $q['topic'], 'contenthash' => $hash,
                'reviewstate' => $q['status'], 'sourceurl' => $q['source'],
                'reviewer' => $q['reviewer'], 'reviewdate' => $q['review_date'], 'timemodified' => time()];
            if ($mapping) {
                $row->id = $mapping->id;
                $DB->update_record('local_skyscholar_content', $row);
                $result['updated']++;
            } else {
                $DB->insert_record('local_skyscholar_content', $row);
                $result['created']++;
            }
        }
        $transaction->allow_commit();
        // A draft replacement must not leave a published random quiz with too few ready questions.
        if ($course->visible) {
            foreach (self::readiness($data['track']) as $row) {
                if ($row['approved'] < $row['required']) {
                    require_once($CFG->dirroot . '/course/lib.php');
                    update_course((object)['id' => $course->id, 'visible' => 0]);
                    break;
                }
            }
        }
        return $result;
    }

    private static function editor(string $text): array {
        return ['text' => $text, 'format' => FORMAT_PLAIN, 'itemid' => 0];
    }

    public static function readiness(string $track): array {
        global $DB;
        $spec = self::tracks()[$track] ?? null;
        if (!$spec) {
            throw new \InvalidArgumentException('Unknown track.');
        }
        $course = self::course($track);
        $rows = [];
        foreach ($spec['topics'] as $topic => $label) {
            $category = self::category($course, $topic, $label);
            // Count the latest READY versions in Moodle, not the import metadata alone.
            $sql = "SELECT COUNT(1) FROM {question_bank_entries} b
                    JOIN {question_versions} v ON v.questionbankentryid = b.id
                    JOIN {local_skyscholar_content} c ON c.questionid = v.questionid
                    WHERE b.questioncategoryid = :category AND v.status = :ready
                    AND c.reviewstate = :approved AND v.version =
                    (SELECT MAX(v2.version) FROM {question_versions} v2 WHERE v2.questionbankentryid = b.id)";
            $count = $DB->count_records_sql($sql, ['category' => $category->id, 'ready' => 'ready', 'approved' => 'approved']);
            $rows[$topic] = ['name' => $label, 'approved' => $count,
                'required' => max($spec['practice_questions'], $spec['mock_questions']), 'categoryid' => $category->id];
        }
        return $rows;
    }

    public static function publish(string $track): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/course/lib.php');
        require_once($CFG->dirroot . '/mod/quiz/locallib.php');
        $course = self::course($track);
        $spec = self::tracks()[$track];
        $rows = self::readiness($track);
        foreach ($rows as $row) {
            if ($row['approved'] < $row['required']) {
                throw new \RuntimeException('Not published: insufficient approved questions for ' . $row['name']);
            }
        }
        foreach ($rows as $topic => $row) {
            foreach (['practice', 'mock'] as $mode) {
                $cm = $DB->get_record('course_modules', ['course' => $course->id,
                    'idnumber' => "ss:$track:$topic:$mode"], '*', MUST_EXIST);
                $settings = \mod_quiz\quiz_settings::create($cm->instance);
                $structure = \mod_quiz\structure::create_for_quiz($settings);
                if (!$DB->record_exists('quiz_slots', ['quizid' => $cm->instance])) {
                    $structure->add_random_questions(0, $spec[$mode . '_questions'], ['filter' => ['category' => [
                        'jointype' => 1, 'values' => [$row['categoryid']],
                        'filteroptions' => ['includesubcategories' => false],
                    ]]]);
                    \mod_quiz\grade_calculator::create($settings)->recompute_quiz_sumgrades();
                }
                set_coursemodule_visible($cm->id, 1);
            }
        }
        update_course((object)['id' => $course->id, 'visible' => 1]);
        rebuild_course_cache($course->id, true);
    }
}
