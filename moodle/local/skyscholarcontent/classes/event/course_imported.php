<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_skyscholarcontent\event;
defined('MOODLE_INTERNAL') || die();
class course_imported extends \core\event\base {
    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'course';
    }
    public static function get_name() { return get_string('eventcourseimported', 'local_skyscholarcontent'); }
    public function get_description() {
        return "User {$this->userid} imported bundled SkyScholar draft content into course {$this->objectid}.";
    }
    public function get_url() { return new \moodle_url('/course/view.php', ['id' => $this->objectid]); }
}
