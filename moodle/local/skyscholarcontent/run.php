<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// No content mutation on GET, plugin install or upgrade.
define('AJAX_SCRIPT', true);
require_once(__DIR__ . '/../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());
require_sesskey();
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    die();
}
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
try {
    $key = required_param('course', PARAM_ALPHANUMEXT);
    $result = \local_skyscholarcontent\local\loader::import_course($key);
    echo json_encode(['success' => true, 'result' => $result], JSON_THROW_ON_ERROR);
} catch (Throwable $error) {
    http_response_code(400);
    // Do not expose traces, SQL, credentials or raw internal exception messages.
    $safe = $error instanceof InvalidArgumentException || get_class($error) === RuntimeException;
    $message = $safe ? $error->getMessage() : 'Moodle could not complete this course. Its transaction was rolled back. Ask the administrator to check server logs before retrying.';
    echo json_encode(['success' => false, 'message' => $message], JSON_THROW_ON_ERROR);
}
