<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
function theme_uavvisuals_get_main_scss_content($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
}
function theme_uavvisuals_get_pre_scss($theme) {
    return '$primary: #243d31; $body-bg: #efeee7; $body-color: #1d2823; '
        . '$link-color: #315640; $font-family-sans-serif: "UAV Manrope", Arial, sans-serif; '
        . '$border-radius: .35rem; $border-radius-lg: .55rem; $input-btn-focus-width: .2rem; '
        . '$input-btn-focus-color: rgba(126, 92, 26, .65); '
        . '$navbar-height: 72px;';
}
function theme_uavvisuals_get_extra_scss($theme) {
    global $CFG;
    $root = (new moodle_url('/theme/uavvisuals'))->out(false);
    $content = '@font-face{font-family:"UAV Manrope";src:url("' . $root . '/fonts/manrope.ttf") format("truetype");'
        . 'font-weight:400 500;font-display:swap;} '
        . '@font-face{font-family:"UAV Manrope";src:url("' . $root . '/fonts/manrope-bold.ttf") format("truetype");'
        . 'font-weight:600 800;font-display:swap;} '
        . '@font-face{font-family:"UAV Serif";src:url("' . $root . '/fonts/serif.ttf") format("truetype");'
        . 'font-style:italic;font-weight:400;font-display:swap;} ';
    return $content . file_get_contents(__DIR__ . '/scss/uavvisuals.scss');
}
/** Plain-text admin settings only; templates escape the values. */
function theme_uavvisuals_brand_context($page, $output): array {
    $theme = $page->theme;
    $setting = function($name, $fallback) use ($theme) {
        return !empty($theme->settings->$name) ? $theme->settings->$name : get_string($fallback, 'theme_uavvisuals');
    };
    return [
        'brandname' => get_string('brandname', 'theme_uavvisuals'),
        'eyebrow' => get_string('eyebrow', 'theme_uavvisuals'),
        'herotitle' => $setting('herotitle', 'herotitledefault'),
        'herobody' => $setting('herobody', 'herobodydefault'),
        'herodetail' => get_string('herodetail', 'theme_uavvisuals'),
        'coursesurl' => (new moodle_url('/course/index.php'))->out(false),
        'courseslabel' => get_string('explorecourses', 'theme_uavvisuals'),
        'imageurl' => $output->image_url('hero', 'theme_uavvisuals')->out(false),
        'logourl' => $output->image_url('uavlogo', 'theme_uavvisuals')->out(false),
        'uavurl' => 'https://uavvisuals.co.za/',
        'uavlabel' => get_string('backtouav', 'theme_uavvisuals'),
    ];
}
