<?php
// This file is part of Moodle - http://moodle.org/
// @package theme_learnlingo
// @copyright 2026 Eduardo Kraus
// @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later

namespace theme_learnlingo\form;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->libdir . '/formslib.php');

use theme_learnlingo\pages;

/** Flat category form. Categories cannot contain other categories. */
class category_form extends \moodleform {
    protected function definition(): void {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);
        $mform->addElement('text', 'name', get_string('custompagescategoryname', 'theme_learnlingo'), ['size' => 60]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required');
        $mform->addElement('text', 'slug', get_string('custompagesslug', 'theme_learnlingo'), ['size' => 60]);
        $mform->setType('slug', PARAM_ALPHANUMEXT);
        $mform->addRule('slug', null, 'required');
        $mform->addElement('textarea', 'description',
            get_string('custompagesdescription', 'theme_learnlingo'), ['rows' => 3, 'cols' => 80]);
        $mform->setType('description', PARAM_TEXT);
        $mform->addElement('text', 'sortorder', get_string('custompagesorder', 'theme_learnlingo'), ['size' => 10]);
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);
        $mform->addElement('advcheckbox', 'visible', get_string('custompagescatvisible', 'theme_learnlingo'));
        $mform->setDefault('visible', 1);
        $this->add_action_buttons();
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        try {
            pages::validate((object)$data, true);
        } catch (\moodle_exception $e) {
            $errors['slug'] = $e->getMessage();
        }
        return $errors;
    }
}
