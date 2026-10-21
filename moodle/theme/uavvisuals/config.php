<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
require_once(__DIR__ . '/lib.php');
$THEME->name = 'uavvisuals';
$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_sheets = [];
// Keep every native Boost layout, including quiz secure and maintenance layouts.
$THEME->layouts = [
    'login' => ['file' => 'login.php', 'regions' => [], 'options' => ['langmenu' => true]],
];
$THEME->scss = function($theme) { return theme_uavvisuals_get_main_scss_content($theme); };
$THEME->prescsscallback = 'theme_uavvisuals_get_pre_scss';
$THEME->extrascsscallback = 'theme_uavvisuals_get_extra_scss';
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->enable_dock = false;
$THEME->haseditswitch = true;
$THEME->usescourseindex = true;
$THEME->iconsystem = \core\output\icon_system::FONTAWESOME;
$THEME->activityheaderconfig = ['notitle' => true];
$THEME->yuicssmodules = [];
