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
$context = ['brandname' => 'SkyScholar', 'herotitle' => '<script>bad()</script>',
    'herobody' => 'Test body', 'coursesurl' => 'https://example.test/skyscholar/course/index.php',
    'courseslabel' => 'Explore courses', 'imageurl' => 'https://example.test/photo.webp',
    'output' => ['main_content' => '<form id="login"><input name="logintoken" value="native-test-token"></form>'],
    'bodyattributes' => 'class="pagelayout-login"'];
foreach (['learning_hero', 'login'] as $name) {
    $template = file_get_contents($repo . '/moodle/theme/uavvisuals/templates/' . $name . '.mustache');
    $rendered = $engine->render($template, $context);
    if (str_contains($rendered, '<script>bad()')) { throw new RuntimeException('Unescaped setting in ' . $name); }
    if ($name === 'login' && !str_contains($rendered, '<input name="logintoken" value="native-test-token">')) {
        throw new RuntimeException('Native login form was not retained.');
    }
}
echo "PASS Mustache render, escaped welcome text and unchanged native login form output.\n";
