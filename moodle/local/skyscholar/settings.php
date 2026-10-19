<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
if ($hassiteconfig) {
    $ADMIN->add('localplugins', new admin_externalpage('local_skyscholar', 'SkyScholar readiness',
        new moodle_url('/local/skyscholar/admin.php'), 'local/skyscholar:manage'));
}
