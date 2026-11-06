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
 * Modern single-image setting using the Moodle File API without its filemanager UI.
 *
 * @package    theme_learnlingo
 * @copyright  2026 Eduardo Kraus
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

defined('MOODLE_INTERNAL') || die;

global $CFG;
require_once($CFG->libdir . '/adminlib.php');

/**
 * A single image picker with direct AJAX upload and removal.
 *
 * Extending core's stored-file setting preserves configuration ownership,
 * the File API file area and its normal settings form submission behaviour.
 */
class admin_setting_configstoredfilemodern extends \admin_setting_configstoredfile {
    /**
     * Resolve an AJAX setting to a whitelisted canonical config key.
     *
     * Previously rendered forms submit the HTML field name (s_component_setting)
     * instead of the config key (component/setting). Keep them working until
     * the page is refreshed, but never accept an unknown setting.
     *
     * @param string $submittedname Submitted setting identifier.
     * @param string[] $allowednames Whitelisted config setting names.
     * @return string|null Canonical name, or null if not allowed.
     */
    public static function normalise_ajax_setting_name(string $submittedname, array $allowednames): ?string {
        foreach ($allowednames as $name) {
            if ($submittedname === $name || $submittedname === 's_' . str_replace('/', '_', $name)) {
                return $name;
            }
        }
        return null;
    }

    /**
     * Render one upload surface, with no second native filemanager control.
     *
     * The hidden draft ID remains so saving other settings in the form
     * cannot restore an older image after an AJAX upload or deletion.
     *
     * @param mixed $data Current setting.
     * @param string $query Search query.
     * @return string
     */
    public function output_html($data, $query = '') {
        global $OUTPUT, $USER;

        $options = $this->get_options();
        $name = $this->get_full_name();
        // Keep Moodle's HTML field name separate from the canonical AJAX config key.
        // Core splits settings such as core_admin/logo into $plugin and $name.
        $settingname = $this->plugin === null ? $this->name : $this->plugin . '/' . $this->name;
        $draftid = file_get_submitted_draft_itemid($name);
        $component = is_null($this->plugin) ? 'core' : $this->plugin;

        file_prepare_draft_area(
            $draftid,
            $options['context']->id,
            $component,
            $this->filearea,
            $this->itemid,
            $options
        );

        $usercontext = \context_user::instance($USER->id);
        $files = get_file_storage()->get_area_files(
            $usercontext->id,
            'user',
            'draft',
            $draftid,
            'sortorder,filepath,filename',
            false
        );

        $filename = '';
        $previewurl = '';
        foreach ($files as $file) {
            if (strpos($file->get_mimetype(), 'image/') !== 0) {
                continue;
            }
            $filename = $file->get_filename();
            $previewurl = \moodle_url::make_draftfile_url(
                $draftid,
                $file->get_filepath(),
                $filename
            )->out(false);
            break;
        }

        $types = $options['accepted_types'];
        $accept = '';
        if (is_array($types)) {
            $accept = implode(',', $types);
        } else if ($types === 'image') {
            $accept = 'image/*';
        }
        // This control is intentionally image-only, including unrestricted
        // core settings that historically accepted any file type.
        if ($accept === '' || $accept === '*') {
            $accept = 'image/*';
        }

        $element = $OUTPUT->render_from_template('theme_learnlingo/setting_storedfilemodern', [
            'id' => $this->get_id(),
            'name' => $name,
            'settingname' => $settingname,
            'draftid' => $draftid,
            'readonly' => $this->is_readonly(),
            'filename' => $filename,
            'previewurl' => $previewurl,
            'haspreview' => $previewurl !== '',
            'accept' => $accept,
            'uploadurl' => (new \moodle_url('/theme/learnlingo/admin/storedfile_upload.php'))->out(false),
            'sesskey' => sesskey(),
        ]);

        return format_admin_setting(
            $this,
            $this->visiblename,
            $element,
            $this->description,
            true,
            '',
            '',
            $query
        );
    }
}
