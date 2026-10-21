<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace theme_uavvisuals\output;
defined('MOODLE_INTERNAL') || die();
class core_renderer extends \theme_boost\output\core_renderer {
    public function get_logo_url($maxwidth = null, $maxheight = 200) {
        return parent::get_logo_url($maxwidth, $maxheight) ?: $this->image_url('uavlogo', 'theme_uavvisuals');
    }
    public function get_compact_logo_url($maxwidth = 300, $maxheight = 300) {
        return parent::get_compact_logo_url($maxwidth, $maxheight) ?: $this->image_url('uavlogo', 'theme_uavvisuals');
    }
    public function full_header() {
        $header = parent::full_header();
        if (!in_array($this->page->pagelayout, ['frontpage', 'mydashboard', 'mycourses'], true)) {
            return $header;
        }
        // A missing value means the default on; an explicit 0 turns the hero off.
        if (isset($this->page->theme->settings->showhero) && !$this->page->theme->settings->showhero) {
            return $header;
        }
        if ($this->page->pagelayout === 'frontpage') {
            return $header . $this->render_from_template('theme_uavvisuals/frontpage',
                \theme_uavvisuals_frontpage_context($this->page, $this));
        }
        return $header . $this->render_from_template('theme_uavvisuals/learning_hero',
            \theme_uavvisuals_brand_context($this->page, $this));
    }
}
