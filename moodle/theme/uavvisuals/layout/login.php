<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
$context = theme_uavvisuals_brand_context($PAGE, $OUTPUT);
$context += [
    'output' => $OUTPUT,
    'bodyattributes' => $OUTPUT->body_attributes(),
    'sitename' => format_string($SITE->shortname),
    'loginheading' => get_string('loginheading', 'theme_uavvisuals'),
    'logindescription' => get_string('logindescription', 'theme_uavvisuals'),
    'loginlabel' => get_string('loginlabel', 'theme_uavvisuals'),
];
echo $OUTPUT->render_from_template('theme_uavvisuals/login', $context);
