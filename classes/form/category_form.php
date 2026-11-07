<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Administrative form for LearnLingo custom pages.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\form;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->libdir . '/formslib.php');

use moodle_exception;
use moodleform;
use theme_learnlingo\pages;

/**
 * Flat category form. Categories cannot contain other categories.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class category_form extends moodleform {
    /**
     * Define the Moodle form fields.
     *
     * @return void
     */
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



        $mform->addElement('advcheckbox', 'visible', get_string('custompagescatvisible', 'theme_learnlingo'));
        $mform->setDefault('visible', 1);
        $this->add_action_buttons();
    }

    /**
     * Validate slug uniqueness and category consistency.
     *
     * @param array $data Submitted form values.
     * @param array $files Uploaded file metadata.
     * @return array Validation errors.
     */
    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        try {
            pages::validate((object)$data, true);
        } catch (moodle_exception $e) {
            $errors['slug'] = $e->getMessage();
        }
        return $errors;
    }
}
