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
 * Modern, accessible file setting built on Moodle's existing filemanager.
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
 * A polished upload surface retaining core's File API, draft validation and persistence.
 */
class admin_setting_configstoredfilemodern extends \admin_setting_configstoredfile {
    /**
     * Render the standard Moodle filemanager with a visual upload and preview surface.
     *
     * Files are still prepared and saved exclusively by Moodle's built-in
     * admin_setting_configstoredfile methods. No second upload endpoint is used.
     *
     * @param mixed $data Current setting.
     * @param string $query Admin settings search query.
     * @return string
     */
    public function output_html($data, $query = '') {
        global $CFG, $OUTPUT, $USER;

        $options = $this->get_options();
        $id = $this->get_id();
        $name = $this->get_full_name();
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

        require_once($CFG->dirroot . '/lib/form/filemanager.php');

        $fmoptions = new \stdClass();
        foreach ([
            'mainfile', 'maxbytes', 'maxfiles', 'subdirs',
            'accepted_types', 'return_types', 'context', 'areamaxbytes',
        ] as $option) {
            $fmoptions->$option = $options[$option];
        }

        $fm = new \MoodleQuickForm_filemanager($name, $this->visiblename, ['id' => $id], $fmoptions);
        $fm->setValue($draftid);

        // Preview the prepared draft, not an unprotected direct file URL.
        // This works equally for core_admin and theme_boost file areas.
        $usercontext = \context_user::instance($USER->id);
        $draftfiles = get_file_storage()->get_area_files(
            $usercontext->id,
            'user',
            'draft',
            $draftid,
            'sortorder,filepath,filename',
            false
        );

        $preview = '';
        $filename = '';
        foreach ($draftfiles as $file) {
            if (strpos($file->get_mimetype(), 'image/') !== 0) {
                continue;
            }
            $filename = $file->get_filename();
            $preview = \moodle_url::make_draftfile_url(
                $draftid,
                $file->get_filepath(),
                $filename
            )->out(false);
            break;
        }

        // The JS preview pattern uses the same URL creator that Moodle uses.
        // Files configured here are single files in the root of a draft area.
        $draftpattern = \moodle_url::make_draftfile_url($draftid, '/', '__FILENAME__')->out(false);
        $element = $OUTPUT->render_from_template('theme_learnlingo/setting_storedfilemodern', [
            'id' => $id,
            'readonly' => $this->is_readonly(),
            'previewurl' => $preview,
            'filename' => $filename,
            'haspreview' => $preview !== '',
            'draftpattern' => $draftpattern,
            'filemanagerhtml' => $fm->toHtml(),
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
