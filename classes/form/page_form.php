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
use theme_learnlingo\content_editor_banner;
use theme_learnlingo\pages;

/**
 * Create/edit HTML and Mustache custom pages.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_form extends moodleform {
    /**
     * Define the Moodle form fields.
     *
     * @return void
     */
    protected function definition(): void {
        $mform = $this->_form;
        $categories = [0 => get_string('custompagesroot', 'theme_learnlingo')];
        foreach ($this->_customdata['categories'] as $category) {
            $categories[(int)$category->id] = $category->name;
        }

        $mform->addElement('hidden', 'id', 0);
        $mform->setType('id', PARAM_INT);

        $mform->addElement('text', 'title', get_string('custompagestitle', 'theme_learnlingo'), ['size' => 60]);
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required');

        $mform->addElement('text', 'menutitle', get_string('custompagesmenutitle', 'theme_learnlingo'), ['size' => 60]);
        $mform->setType('menutitle', PARAM_TEXT);

        $mform->addElement('text', 'slug', get_string('custompagesslug', 'theme_learnlingo'), ['size' => 60]);
        $mform->setType('slug', PARAM_ALPHANUMEXT);
        $mform->addRule('slug', null, 'required');

        $mform->addElement('select', 'categoryid', get_string('custompagescategory', 'theme_learnlingo'), $categories);
        $mform->setType('categoryid', PARAM_INT);

        // Place the editing guidance immediately above the HTML content field.
        $mform->addElement('html', content_editor_banner::render(
            'page', (int)($this->_customdata['pageid'] ?? 0)
        ));

        $mform->addElement('textarea', 'content', get_string('custompageshtml', 'theme_learnlingo'),
            ['rows' => 22, 'class' => 'form-control w-100 font-monospace']);
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', null, 'required');

        $mform->addElement('textarea', 'scss', get_string('custompagesscss', 'theme_learnlingo'),
            ['rows' => 12, 'class' => 'form-control w-100 font-monospace']);
        $mform->setType('scss', PARAM_RAW);

        $mform->addElement('textarea', 'metadescription', get_string('custompagesmetadescription', 'theme_learnlingo'),
            ['rows' => 3, 'class' => 'form-control w-100']);
        $mform->setType('metadescription', PARAM_TEXT);

        $mform->addElement('select', 'status', get_string('custompagesstatus', 'theme_learnlingo'), [
            0 => get_string('custompagesdraft', 'theme_learnlingo'),
            1 => get_string('custompagespublished', 'theme_learnlingo'),
        ]);
        $mform->setType('status', PARAM_INT);

        $mform->addElement('select', 'accesslevel', get_string('custompagesaccess', 'theme_learnlingo'), [
            0 => get_string('custompagespublic', 'theme_learnlingo'),
            1 => get_string('custompageslogin', 'theme_learnlingo'),
        ]);
        $mform->setType('accesslevel', PARAM_INT);

        $mform->addElement('advcheckbox', 'fullwidth', get_string('custompagesfullwidth', 'theme_learnlingo'));
        $mform->setDefault('fullwidth', 1);

        $mform->addElement('advcheckbox', 'showinmenu', get_string('custompagesshowinmenu', 'theme_learnlingo'));

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
            pages::validate((object)$data);
        } catch (moodle_exception $e) {
            $errors['slug'] = $e->getMessage();
        }
        return $errors;
    }
}
