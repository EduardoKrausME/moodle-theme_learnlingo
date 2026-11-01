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
 * Expose LearnLingo configuration as MCP tools.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo\mcp;

use local_mcp\diagnostics;
use local_mcp\extension\read_provider_interface;
use local_mcp\extension\write_provider_interface;
use local_mcp\write\tool_interface;
use Throwable;

/**
 * Automatically discovered by local_mcp while the theme is installed and enabled.
 *
 * No lib.php callback is needed; the READ and WRITE tools retain separate scopes.
 */
final class provider implements read_provider_interface, write_provider_interface {
    /**
     * Return available read tools.
     *
     * @return \local_mcp\read\tool_interface[]
     */
    public function get_read_tools(): array {
        return self::available_tools([
            'learnlingo_get_homepage' => get_homepage::class,
            'learnlingo_get_settings' => get_settings::class,
        ]);
    }

    /**
     * Return available write tools.
     *
     * @return tool_interface[]
     */
    public function get_write_tools(): array {
        return self::available_tools([
            'learnlingo_update_homepage' => update_homepage::class,
            'learnlingo_update_settings' => update_settings::class,
            'learnlingo_update_styles' => update_styles::class,
            'learnlingo_update_footer' => update_footer::class,
        ]);
    }

    /**
     * Keep available tools working when an installation has incomplete files or stale autoload caches.
     *
     * @param array $definitions Tool name to PHP class mapping.
     * @return array Instantiated MCP tools.
     */
    private static function available_tools(array $definitions): array {
        $tools = [];
        foreach ($definitions as $name => $classname) {
            $filename = __DIR__ . '/' . substr(strrchr($classname, '\\'), 1) . '.php';
            try {
                if (class_exists($classname)) {
                    $tools[] = new $classname();
                    continue;
                }
                self::log_missing_tool($name, $filename,
                    is_file($filename) ? 'autoload_failed' : 'file_missing');
            } catch (Throwable $e) {
                // A broken optional tool must not prevent other registered tools from running.
                self::log_missing_tool($name, $filename, 'load_error', get_class($e));
            }
        }
        return $tools;
    }

    /**
     * Log only metadata, never the tool arguments, returned data or credentials.
     *
     * @param string $name Tool name.
     * @param string $filename Expected PHP file path.
     * @param string $status Diagnostic status.
     * @param string $errorclass PHP error class, if one was thrown.
     * @return void
     */
    private static function log_missing_tool(string $name, string $filename,
                                             string $status, string $errorclass = ''): void {
        diagnostics::event('provider_tool_unavailable', [
            'provider' => self::class,
            'tool' => $name,
            'file' => basename($filename),
            'status' => $status,
            'error_class' => $errorclass,
            'message' => 'Update all LearnLingo theme files and purge Moodle caches.',
        ], 'ERROR');
    }
}
