<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// Run before install: php scripts/preflight.php --moodle=/path/to/webroot --data=/private/moodledata
// Run after install with the same arguments to include database and cron checks.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
$options = getopt('', ['moodle:', 'data:']);
$root = realpath($options['moodle'] ?? '');
$data = realpath($options['data'] ?? '');
$failures = [];
function check_requirement(string $name, bool $pass): void {
    global $failures;
    echo ($pass ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$pass) {
        $failures[] = $name;
    }
}
check_requirement('PHP 8.2 or 8.3, 64-bit (recommended deployment baseline)',
    PHP_VERSION_ID >= 80200 && PHP_VERSION_ID < 80400 && PHP_INT_SIZE === 8);
foreach (['ctype', 'curl', 'dom', 'fileinfo', 'gd', 'iconv', 'intl', 'json', 'mbstring',
        'mysqli', 'openssl', 'simplexml', 'sodium', 'xml', 'xmlreader', 'zip', 'zlib'] as $extension) {
    check_requirement('PHP extension ' . $extension, extension_loaded($extension));
}
check_requirement('max_input_vars >= 5000', (int)ini_get('max_input_vars') >= 5000);
$memory = ini_get('memory_limit');
$bytes = (int)$memory;
$unit = strtolower(substr($memory, -1));
$bytes *= match ($unit) { 'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1 };
check_requirement('memory_limit >= 256M or unlimited', $memory === '-1' || $bytes >= 256 * 1024 * 1024);
check_requirement('Moodle files present', $root && is_file($root . '/version.php') && is_file($root . '/lib/setup.php'));
check_requirement('Writable private dataroot outside webroot', $root && $data && is_writable($data) &&
    $data !== $root && !str_starts_with($data . '/', $root . '/'));
if ($root && is_file($root . '/config.php')) {
    define('CLI_SCRIPT', true);
    require($root . '/config.php');
    $info = $DB->get_server_info();
    check_requirement('MariaDB >= 10.6.7', $CFG->dbtype === 'mariadb' && version_compare($info['version'], '10.6.7', '>='));
    check_requirement('HTTPS wwwroot', str_starts_with($CFG->wwwroot, 'https://'));
    check_requirement('Secure cookies', !empty($CFG->cookiesecure));
    check_requirement('Debug output disabled', empty($CFG->debugdisplay));
    check_requirement('Cron within five minutes', (int)get_config('tool_task', 'lastcronstart') > time() - 300);
} else {
    echo "PENDING database, HTTPS, secure cookies and cron: rerun after installation.\n";
}
exit($failures ? 1 : 0);
