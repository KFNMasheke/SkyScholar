<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
if ($hassiteconfig) {
    $ADMIN->add('courses', new admin_externalpage('local_skyscholarcontent',
        get_string('pluginname', 'local_skyscholarcontent'),
        new moodle_url('/local/skyscholarcontent/admin.php'), 'moodle/site:config'));
}
