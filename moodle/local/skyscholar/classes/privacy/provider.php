<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_skyscholar\privacy;
defined('MOODLE_INTERNAL') || die();
use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider, \core_privacy\local\request\core_userlist_provider {
    public static function get_metadata(collection $collection): collection {
        return $collection->add_database_table('local_skyscholar_report', [
            'userid' => 'privacy:metadata:userid', 'attemptid' => 'privacy:metadata:attemptid',
            'slot' => 'privacy:metadata:slot', 'message' => 'privacy:metadata:message',
            'status' => 'privacy:metadata:status', 'timecreated' => 'privacy:metadata:timecreated',
        ], 'privacy:metadata:reports');
    }
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;
        $list = new contextlist();
        if ($DB->record_exists('local_skyscholar_report', ['userid' => $userid])) {
            $list->add_system_context();
        }
        return $list;
    }
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;
        $context = \context_system::instance();
        if (!in_array($context->id, $contextlist->get_contextids())) {
            return;
        }
        $reports = $DB->get_records('local_skyscholar_report', ['userid' => $contextlist->get_user()->id]);
        writer::with_context($context)->export_data(['SkyScholar'], (object)['reports' => array_values($reports)]);
    }
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel === CONTEXT_SYSTEM) {
            $DB->delete_records('local_skyscholar_report');
        }
    }
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;
        if (in_array(\context_system::instance()->id, $contextlist->get_contextids())) {
            $DB->delete_records('local_skyscholar_report', ['userid' => $contextlist->get_user()->id]);
        }
    }
    public static function get_users_in_context(userlist $userlist) {
        if ($userlist->get_context()->contextlevel === CONTEXT_SYSTEM) {
            $userlist->add_from_sql('userid', 'SELECT userid FROM {local_skyscholar_report}', []);
        }
    }
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;
        if ($userlist->get_context()->contextlevel === CONTEXT_SYSTEM && $userlist->get_userids()) {
            $DB->delete_records_list('local_skyscholar_report', 'userid', $userlist->get_userids());
        }
    }
}
