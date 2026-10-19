<?php
// SPDX-License-Identifier: GPL-3.0-or-later
namespace local_skyscholar;
defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->libdir . '/formslib.php');
class report_form extends \moodleform {
    public function definition() {
        $form = $this->_form;
        foreach (['attempt', 'slot'] as $field) {
            $form->addElement('hidden', $field, $this->_customdata[$field]);
            $form->setType($field, PARAM_INT);
        }
        $form->addElement('textarea', 'message', 'What needs correcting?', ['rows' => 6, 'maxlength' => 2000]);
        $form->setType('message', PARAM_TEXT);
        $form->addRule('message', 'Describe the issue.', 'required');
        $form->addRule('message', 'Maximum 2000 characters.', 'maxlength', 2000);
        $this->add_action_buttons(true, 'Submit report');
    }
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (trim($data['message']) === '' || \core_text::strlen($data['message']) > 2000) {
            $errors['message'] = 'Provide a description up to 2000 characters.';
        }
        return $errors;
    }
}
