<?php
// SPDX-License-Identifier: GPL-3.0-or-later
define('CLI_SCRIPT', true);
require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');
[$options, $unknown] = cli_get_params(['help' => false, 'action' => '', 'file' => '', 'track' => ''], ['h' => 'help']);
if ($options['help']) {
    echo "SkyScholar CLI\n--action=configure (fresh installation only; disables registration)\n"
        . "--action=import --file=/absolute/questions.json\n--action=readiness --track=demo\n"
        . "--action=publish --track=demo\n";
    exit(0);
}
if ($unknown) {
    cli_error('Unknown options: ' . implode(', ', $unknown));
}
\core\session\manager::set_user(get_admin());
$lock = \core\lock\lock_config::get_lock_factory('local_skyscholar')->get_lock('content-mutation', 10);
if (!$lock) {
    cli_error('Another SkyScholar content operation is running.');
}
try {
    $platform = \local_skyscholar\local\platform::class;
    switch ($options['action']) {
        case 'configure':
            $platform::configure();
            echo "Courses provisioned. Public registration is disabled pending launch checks.\n";
            break;
        case 'import':
            if (!$options['file'] || !is_file($options['file']) || filesize($options['file']) > 5 * 1024 * 1024) {
                throw new InvalidArgumentException('Provide a readable JSON file smaller than 5 MiB.');
            }
            echo json_encode($platform::import(file_get_contents($options['file'])), JSON_PRETTY_PRINT) . "\n";
            break;
        case 'readiness':
            echo json_encode($platform::readiness($options['track']), JSON_PRETTY_PRINT) . "\n";
            break;
        case 'publish':
            $platform::publish($options['track']);
            echo "Published track: " . $options['track'] . "\n";
            break;
        default:
            throw new InvalidArgumentException('Select an action. See --help.');
    }
} catch (Throwable $error) {
    fwrite(STDERR, $error->getMessage() . "\n");
    exit(1);
} finally {
    $lock->release();
}
