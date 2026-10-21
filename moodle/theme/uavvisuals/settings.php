<?php
// SPDX-License-Identifier: GPL-3.0-or-later
defined('MOODLE_INTERNAL') || die();
if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('theme_uavvisuals/identity',
        get_string('pluginname', 'theme_uavvisuals'), get_string('settingsintro', 'theme_uavvisuals')));
    $settings->add(new admin_setting_configcheckbox('theme_uavvisuals/showhero',
        get_string('showhero', 'theme_uavvisuals'), get_string('showherodesc', 'theme_uavvisuals'), 1));
    $settings->add(new admin_setting_configtext('theme_uavvisuals/herotitle',
        get_string('herotitle', 'theme_uavvisuals'), '', get_string('herotitledefault', 'theme_uavvisuals'), PARAM_TEXT));
    $settings->add(new admin_setting_configtextarea('theme_uavvisuals/herobody',
        get_string('herobody', 'theme_uavvisuals'), '', get_string('herobodydefault', 'theme_uavvisuals'), PARAM_TEXT));
}
