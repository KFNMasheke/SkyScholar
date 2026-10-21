<?php
// SPDX-License-Identifier: GPL-3.0-or-later
// Standalone use of Moodle's actual bundled SCSS and Mustache engines; no DB required.
define('MOODLE_INTERNAL', true);
define('DEBUG_DEVELOPER', 32767);
$core = getenv('SKYSCHOLAR_TEST_MOODLE');
if (!$core || !is_file($core . '/lib/classes/scss.php')) { throw new RuntimeException('Set SKYSCHOLAR_TEST_MOODLE to Moodle 4.5 source.'); }
$repo = dirname(__DIR__);
foreach (["hero.jpg", "uavlogo.png"] as $image) {
    if (!is_file($repo . "/moodle/theme/uavvisuals/pix/" . $image)) { throw new RuntimeException("Missing Moodle-compatible theme image: " . $image); }
}

$CFG = (object)['dirroot' => $core, 'wwwroot' => 'https://example.test/skyscholar', 'pathtosassc' => ''];
// Minimal path/context shims only; no site bootstrap or DB integration is claimed.
class moodle_url {
    private $url;
    public function __construct($path) { global $CFG; $this->url = $CFG->wwwroot . $path; }
    public function out($escape = true) { return $escape ? htmlspecialchars($this->url) : $this->url; }
}
class core_component { public static function get_plugin_types() { global $CFG; return ['theme' => $CFG->dirroot . '/theme']; } }
function debugging($message, $level = 0) { throw new RuntimeException($message); }
spl_autoload_register(function($class) use ($core) {
    $prefix = 'ScssPhp\\ScssPhp\\';
    if (str_starts_with($class, $prefix)) {
        $path = $core . '/lib/scssphp/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (is_file($path)) { require_once($path); }
    }
});
require_once($core . '/lib/classes/scss.php');
require_once($repo . '/moodle/theme/uavvisuals/lib.php');
$compiler = new core_scss();
$compiler->setImportPaths([$repo . '/moodle/theme/uavvisuals/scss', $core . '/theme/boost/scss']);
$theme = (object)['settings' => new stdClass()];
$css = $compiler->compile(theme_uavvisuals_get_pre_scss($theme) . "\n"
    . theme_uavvisuals_get_main_scss_content($theme) . "\n" . theme_uavvisuals_get_extra_scss($theme));
foreach (['.uav-learning-hero', '.uav-login-grid', '.path-mod-quiz', '/skyscholar/theme/uavvisuals/fonts/manrope.ttf', '.drawer'] as $needle) {
    if (!str_contains($css, $needle)) { throw new RuntimeException('Missing compiled CSS: ' . $needle); }
}
$csspath = getenv('SKYSCHOLAR_THEME_CSS_OUTPUT');
if ($csspath) { file_put_contents($csspath, $css); }
echo 'PASS Boost + child SCSS compiled (' . strlen($css) . " bytes), including subdirectory font URLs.\n";
require_once($core . '/lib/mustache/src/Mustache/Autoloader.php');
Mustache_Autoloader::register($core . '/lib/mustache/src');
$engine = new Mustache_Engine(['partials' => [
    'theme_boost/head' => '<!doctype html><html><head></head>',
    'theme_boost/footer' => '<footer>Native footer</footer>',
    'core/local/toast/wrapper' => '<div id="toast-wrapper"></div>',
]]);
$strings = [];
require($repo . '/moodle/theme/uavvisuals/lang/en/theme_uavvisuals.php');
$context = $string + ['brandname' => 'SkyScholar', 'herotitle' => '<script>bad()</script>',
    'herobody' => 'Test body', 'coursesurl' => 'https://example.test/skyscholar/course/index.php',
    'courseslabel' => 'Explore courses', 'imageurl' => 'https://example.test/photo.webp',
    'output' => ['main_content' => '<form id="login"><input name="logintoken" value="native-test-token"></form>'],
    'bodyattributes' => 'class="pagelayout-login"'];
$context['herotitle'] = '<script>bad()</script>';
foreach (['learning_hero', 'login', 'frontpage'] as $name) {
    $template = file_get_contents($repo . '/moodle/theme/uavvisuals/templates/' . $name . '.mustache');
    $rendered = $engine->render($template, $context);
    if (str_contains($rendered, '<script>bad()')) { throw new RuntimeException('Unescaped setting in ' . $name); }
    if ($name === 'login' && !str_contains($rendered, '<input name="logintoken" value="native-test-token">')) {
        throw new RuntimeException('Native login form was not retained.');
    }
}
echo "PASS Mustache render, escaped welcome text and unchanged native login form output.\n";

$context['hascourses'] = true;
$context['hascategories'] = true;
$context['searchurl'] = 'https://example.test/skyscholar/course/search.php';
$context['features'] = [['number' => '01', 'title' => 'Open access', 'body' => 'Free practice']];
$context['courses'] = [['name' => '<img src=x onerror=bad()>', 'category' => 'Air law', 'url' => '/course/view.php?id=2', 'image' => '/photo.jpg']];
$context['categories'] = [['name' => 'Air law & rules', 'url' => '/course/index.php?categoryid=2']];
$frontpage = $engine->render(file_get_contents($repo . '/moodle/theme/uavvisuals/templates/frontpage.mustache'), $context);
foreach (['name="search"', 'method="get"', 'course/search.php', '<details>', '&lt;img', 'Air law &amp; rules'] as $needle) {
    if (!str_contains($frontpage, $needle)) { throw new RuntimeException('Missing catalogue behaviour: ' . $needle); }
}
if (str_contains($frontpage, '<img src=x onerror=bad()>')) { throw new RuntimeException('Unescaped course name.'); }
$previewpath = getenv('SKYSCHOLAR_THEME_PREVIEW_OUTPUT');
if ($previewpath) {
    $preview = $context;
    $preview['herotitle'] = $string['herotitledefault'];
    $preview['herobody'] = $string['herobodydefault'];
    $preview['brandname'] = $string['brandname'];
    $preview['hascourses'] = false;
    $preview['hascategories'] = false;
    $preview['courses'] = [];
    $preview['categories'] = [];
    $preview['features'] = [];
    foreach (['access', 'pace', 'practice', 'progress'] as $i => $name) {
        $preview['features'][] = ['number' => '0' . ($i + 1), 'title' => $string['feature' . $name], 'body' => $string['feature' . $name . 'body']];
    }
    $preview['imageurl'] = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($repo . '/moodle/theme/uavvisuals/pix/hero.jpg'));
    foreach (['coursesurl' => '/course/index.php', 'searchurl' => '/course/search.php', 'loginurl' => '/login/index.php', 'dashboardurl' => '/my/'] as $key => $path) {
        $preview[$key] = 'https://uavvisuals.co.za/skyscholar' . $path;
    }
    $preview['uavurl'] = 'https://uavvisuals.co.za/';
    $preview['uavlabel'] = $string['backtouav'];
    $previewcss = $css;
    foreach (['manrope.ttf', 'manrope-bold.ttf', 'serif.ttf'] as $font) {
        $previewcss = str_replace('https://example.test/skyscholar/theme/uavvisuals/fonts/' . $font,
            'data:font/ttf;base64,' . base64_encode(file_get_contents($repo . '/moodle/theme/uavvisuals/fonts/' . $font)), $previewcss);
    }
    $markup = $engine->render(file_get_contents($repo . '/moodle/theme/uavvisuals/templates/frontpage.mustache'), $preview);
    file_put_contents($previewpath, '<!doctype html><html lang="en"><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>SkyScholar theme design preview</title><style>' . $previewcss . '</style><body><header style="padding:18px 5%;background:white;display:flex;align-items:center;justify-content:space-between"><strong>SkyScholar / UAV Visuals</strong><a href="https://uavvisuals.co.za/skyscholar/login/index.php" class="btn btn-primary">Sign in</a></header><main>' . $markup . '</main></body></html>');
}
echo "PASS Catalogue populated/empty rendering, escaped course/category names, native course search and FAQ controls.\n";
