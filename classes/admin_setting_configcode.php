<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Code-oriented textarea for HTML and SCSS theme settings.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use admin_setting_configtextarea;

defined('MOODLE_INTERNAL') || die;

// Admin setting base classes are defined in Moodle's admin library.
global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * Keep the same config persistence and validation as the standard admin textarea.
 */
class admin_setting_configcode extends admin_setting_configtextarea {
    /** @var string Syntax language. */
    private $language;

    /** @var string Textarea column count. */
    private $codecols;

    /** @var string Textarea row count. */
    private $coderows;

    /**
     * Create an HTML or SCSS textarea that preserves Moodle's raw configuration storage.
     *
     * @param string $name Setting name.
     * @param string $visiblename Setting label.
     * @param string $description Setting description.
     * @param string $defaultsetting Default value.
     * @param string $language html or scss.
     * @param string $cols Number of columns.
     * @param string $rows Number of rows.
     */
    public function __construct($name, $visiblename, $description, $defaultsetting,
            $language = 'html', $cols = '90', $rows = '16') {
        $this->language = $language === 'scss' ? 'scss' : 'html';
        $this->codecols = $cols;
        $this->coderows = $rows;
        parent::__construct($name, $visiblename, $description, $defaultsetting, PARAM_RAW, $cols, $rows);
    }

    /**
     * Block Sass directives that could escape the home/footer component wrapper.
     *
     * @param mixed $data Submitted value.
     * @return bool|string True or a validation error.
     */
    public function validate($data) {
        $result = parent::validate($data);
        if ($result !== true) {
            return $result;
        }
        if ($this->language === 'scss' &&
            preg_match('~/(homepagescss|footerscss)$~', $this->name) &&
            preg_match('~@(at-root|import|use|forward|extend|keyframes|font-face|charset|namespace|property)\\b~i',
                (string)$data)) {
            return get_string('jsonvalidatescssdirective', 'theme_learnlingo');
        }
        return true;
    }

    /**
     * Render an ordinary form textarea with optional progressive code highlighting.
     *
     * @param mixed $data Current value.
     * @param string $query Admin settings search query.
     * @return string
     */
    public function output_html($data, $query = '') {
        global $OUTPUT;

        $default = $this->get_defaultsetting();
        $defaultinfo = $default;
        if ($default !== null && $default !== '') {
            $defaultinfo = "\n" . $default;
        }

        $context = [
            'id' => $this->get_id(),
            'name' => $this->get_full_name(),
            'value' => (string)($data ?? ''),
            'cols' => $this->codecols,
            'rows' => $this->coderows,
            'language' => $this->language,
            'languageupper' => strtoupper($this->language),
            'readonly' => $this->is_readonly(),
        ];
        $element = $OUTPUT->render_from_template('theme_learnlingo/setting_configcode', $context);
        // Keep the existing code textarea and Moodle form persistence. The
        // visual editor is an optional, separate route for each HTML/SCSS pair.
        $targets = [
            'theme_learnlingo/homepagehtml' => 'homepage',
            'theme_learnlingo/footerhtml' => 'footer',
        ];
        if (isset($targets[$this->name]) && !$this->is_readonly()) {
            $url = new \moodle_url('/theme/learnlingo/_editor/editor.php',
                ['target' => $targets[$this->name]]);
            $element .= \html_writer::div(\html_writer::link($url,
                get_string('visualeditoropen', 'theme_learnlingo'),
                ['class' => 'btn btn-outline-primary mt-2']), 'mt-2');
        }
        return format_admin_setting(
            $this, $this->visiblename, $element, $this->description, true, '', $defaultinfo, $query
        );
    }
}
