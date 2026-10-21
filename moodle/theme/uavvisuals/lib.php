<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
function theme_uavvisuals_get_main_scss_content($theme) {
    global $CFG;
    return file_get_contents($CFG->dirroot . '/theme/boost/scss/preset/default.scss');
}
function theme_uavvisuals_get_pre_scss($theme) {
    return '$primary: #243d31; $body-bg: #f7faf8; $body-color: #1d2823; '
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

/** Permission-aware catalogue, using Moodle's category API rather than raw SQL. */
function theme_uavvisuals_frontpage_context($page, $output): array {
    $data = theme_uavvisuals_brand_context($page, $output);
    $keys = ['announcement', 'searchlabel', 'searchplaceholder', 'searchbutton', 'freebadge',
        'heroaccent', 'heronote', 'catalogueeyebrow', 'cataloguetitle', 'cataloguebody',
        'opencourse', 'emptycourses', 'categoryeyebrow', 'categorytitle', 'aboutlabel',
        'abouttitle', 'aboutbody', 'aboutpointone', 'aboutpointtwo', 'aboutpointthree',
        'ctaheading', 'ctabody', 'ctalabel', 'footerbody', 'footerlearning', 'footerbrand',
        'loginlink', 'dashboardlink', 'approachlabel', 'approachtitle', 'featureslabel',
        'faqtitle', 'faqone', 'faqoneanswer', 'faqtwo', 'faqtwoanswer', 'faqthree', 'faqthreeanswer'];
    foreach ($keys as $key) {
        $data[$key] = get_string($key, 'theme_uavvisuals');
    }
    $data['searchurl'] = (new moodle_url('/course/search.php'))->out(false);
    $data['loginurl'] = (new moodle_url('/login/index.php'))->out(false);
    $data['dashboardurl'] = (new moodle_url('/my/'))->out(false);
    $data['features'] = [];
    foreach (['access', 'pace', 'practice', 'progress'] as $index => $name) {
        $data['features'][] = ['number' => '0' . ($index + 1),
            'title' => get_string('feature' . $name, 'theme_uavvisuals'),
            'body' => get_string('feature' . $name . 'body', 'theme_uavvisuals')];
    }
    $data['courses'] = [];
    foreach (core_course_category::top()->get_courses(['recursive' => true, 'limit' => 6]) as $course) {
        if (!$course->visible || $course->id == SITEID) {
            continue;
        }
        $context = context_course::instance($course->id);
        $category = core_course_category::get($course->category, IGNORE_MISSING);
        $data['courses'][] = [
            'name' => html_entity_decode(strip_tags(format_string($course->fullname, true, ['context' => $context])), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'category' => $category ? html_entity_decode(strip_tags(format_string($category->name, true,
                ['context' => $category->get_context()])), ENT_QUOTES | ENT_HTML5, 'UTF-8') : '',
            'url' => (new moodle_url('/course/view.php', ['id' => $course->id]))->out(false),
            'image' => \core_course\external\course_summary_exporter::get_course_image($course)
                ?: $data['imageurl'],
        ];
    }
    $data['hascourses'] = !empty($data['courses']);
    $data['categories'] = [];
    foreach (core_course_category::top()->get_children() as $category) {
        if (!$category->visible) { continue; }
        $data['categories'][] = [
            'name' => html_entity_decode(strip_tags(format_string($category->name, true,
                ['context' => $category->get_context()])), ENT_QUOTES | ENT_HTML5, 'UTF-8'),
            'url' => (new moodle_url('/course/index.php', ['categoryid' => $category->id]))->out(false),
        ];
        if (count($data['categories']) >= 8) { break; }
    }
    $data['hascategories'] = !empty($data['categories']);
    return $data;
}
