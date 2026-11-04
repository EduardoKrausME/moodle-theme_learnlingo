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

/** Create/edit HTML and Mustache custom pages. */
class page_form extends \moodleform {
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
        $mform->addElement('text', 'slug', get_string('custompagesslug', 'theme_learnlingo'), ['size' => 60]);
        $mform->setType('slug', PARAM_ALPHANUMEXT);
        $mform->addRule('slug', null, 'required');
        $mform->addElement('select', 'categoryid', get_string('custompagescategory', 'theme_learnlingo'), $categories);
        $mform->setType('categoryid', PARAM_INT);
        $mform->addElement('textarea', 'content', get_string('custompageshtml', 'theme_learnlingo'),
            ['rows' => 22, 'cols' => 100, 'class' => 'form-control font-monospace']);
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', null, 'required');
        $mform->addElement('textarea', 'scss', get_string('custompagesscss', 'theme_learnlingo'),
            ['rows' => 12, 'cols' => 100, 'class' => 'form-control font-monospace']);
        $mform->setType('scss', PARAM_RAW);
        $mform->addElement('textarea', 'description', get_string('custompagesdescription', 'theme_learnlingo'),
            ['rows' => 3, 'cols' => 80]);
        $mform->setType('description', PARAM_TEXT);
        $mform->addElement('textarea', 'metadescription', get_string('custompagesmetadescription', 'theme_learnlingo'),
            ['rows' => 3, 'cols' => 80]);
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
        $mform->addElement('text', 'menutitle', get_string('custompagesmenutitle', 'theme_learnlingo'), ['size' => 60]);
        $mform->setType('menutitle', PARAM_TEXT);
        $mform->addElement('text', 'menuorder', get_string('custompagesmenuorder', 'theme_learnlingo'), ['size' => 10]);
        $mform->setType('menuorder', PARAM_INT);
        $mform->setDefault('menuorder', 0);
        $mform->addElement('text', 'sortorder', get_string('custompagesorder', 'theme_learnlingo'), ['size' => 10]);
        $mform->setType('sortorder', PARAM_INT);
        $mform->setDefault('sortorder', 0);
        $this->add_action_buttons();
    }

    public function validation($data, $files): array {
        $errors = parent::validation($data, $files);
        try {
            pages::validate((object)$data);
        } catch (\moodle_exception $e) {
            $errors['slug'] = $e->getMessage();
        }
        return $errors;
    }
}
