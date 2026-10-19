<?php
// SPDX-License-Identifier: GPL-3.0-or-later
require(__DIR__ . '/../moodle/local/skyscholar/classes/local/validator.php');
use local_skyscholar\local\validator;
$path = __DIR__ . '/../moodle/local/skyscholar/content/';
$tracks = json_decode(file_get_contents($path . 'tracks.json'), true);
$fixture = json_decode(file_get_contents($path . 'demo.json'), true);
function expect(bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    echo 'PASS ' . $message . "\n";
}
$valid = validator::decode(json_encode($fixture), $tracks);
expect(count($valid['questions']) === 6, 'valid demonstration content');
$mutations = [
    'duplicate IDs' => function (&$data) { $data['questions'][] = $data['questions'][0]; },
    'unknown track' => function (&$data) { $data['track'] = 'unknown'; },
    'unknown topic' => function (&$data) { $data['questions'][0]['topic'] = 'unknown'; },
    'bad answer index' => function (&$data) { $data['questions'][0]['correct'] = 8; },
    'unreviewed approval' => function (&$data) { $data['questions'][0]['reviewer'] = ''; },
    'invalid date' => function (&$data) { $data['questions'][0]['review_date'] = '2026-02-31'; },
    'future review' => function (&$data) { $data['questions'][0]['review_date'] = '2099-01-01'; },
    'empty feedback' => function (&$data) { $data['questions'][0]['feedback'][0] = ''; },
    'oversized stem' => function (&$data) { $data['questions'][0]['stem'] = str_repeat('x', 10001); },
    'duplicate choices' => function (&$data) { $data['questions'][0]['options'][1] = $data['questions'][0]['options'][0]; },
];
foreach ($mutations as $name => $mutate) {
    $data = $fixture;
    $mutate($data);
    $rejected = false;
    try {
        validator::decode(json_encode($data), $tracks);
    } catch (InvalidArgumentException $error) {
        $rejected = true;
    }
    expect($rejected, 'reject ' . $name);
}
$fixture['questions'][0]['status'] = 'draft';
$fixture['questions'][0]['reviewer'] = '';
$fixture['questions'][0]['review_date'] = '';
expect(count(validator::decode(json_encode($fixture), $tracks)['questions']) === 6, 'allow explicitly unreviewed drafts');
