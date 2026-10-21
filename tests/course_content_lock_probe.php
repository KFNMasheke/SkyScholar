<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// Child process for the guarded integration suite: independent DB lock connection.
define('CLI_SCRIPT', true);
$root = getenv('SKYSCHOLAR_TEST_MOODLE');
if (!$root || !is_file($root . '/config.php')) { exit(2); }
require($root . '/config.php');
if (!str_starts_with($CFG->wwwroot, 'http://127.0.0.1') || $CFG->dbname !== 'skyscholar_test') { exit(2); }
\core\session\manager::set_user(get_admin());
try {
    \local_skyscholarcontent\local\loader::import_course('draft-introduction-to-drone-technology');
} catch (RuntimeException $error) {
    if (str_contains($error->getMessage(), 'Another course import is running')) {
        echo "LOCK_REFUSED\n";
        exit(0);
    }
}
exit(1);
