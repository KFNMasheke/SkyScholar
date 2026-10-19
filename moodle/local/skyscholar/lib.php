<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
function local_skyscholar_extend_navigation(global_navigation $navigation): void {
    if (isloggedin() && !isguestuser()) {
        $navigation->add(get_string('dashboard', 'local_skyscholar'),
            new moodle_url('/local/skyscholar/index.php'), navigation_node::TYPE_CUSTOM);
    }
}
