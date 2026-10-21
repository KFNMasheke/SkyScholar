<?php
// SPDX-License-Identifier: GPL-3.0-or-later
define('MOODLE_INTERNAL', true);
require_once(__DIR__ . '/../moodle/local/skyscholarcontent/classes/local/loader.php');
use local_skyscholarcontent\local\loader;
$pack = loader::pack();
foreach (['approved', 'reviewer', 'correct', 'duplicate', 'topic', 'assetpath', 'pagekey', 'radiocount'] as $case) {
    $data = $pack;
    if ($case === 'approved') { $data['questions'][0]['status'] = 'approved'; }
    if ($case === 'reviewer') { $data['questions'][0]['reviewer'] = 'invented'; }
    if ($case === 'correct') { $data['questions'][0]['correct'] = 9; }
    if ($case === 'duplicate') { $data['courses'][1]['id'] = $data['courses'][0]['id']; }
    if ($case === 'topic') { $data['questions'][0]['topic'] = 'unknown'; }
    if ($case === 'assetpath') { $data['assets']['../unsafe.png'] = array_shift($data['assets']); }
    if ($case === 'pagekey') { $data['courses'][0]['pages'][0]['key'] = '../unsafe'; }
    if ($case === 'radiocount') { array_pop($data['radio']['scenarios']); }
    $rejected = false;
    try { loader::validate($data); } catch (InvalidArgumentException $e) { $rejected = true; }
    if (!$rejected) { throw new RuntimeException('Invalid package accepted: ' . $case); }
}
echo "PASS bundled payload/hash and rejection of approval, invented review, bad keys, duplicate IDs and unknown subjects.\n";
