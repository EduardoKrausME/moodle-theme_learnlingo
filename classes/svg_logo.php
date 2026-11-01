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
 * Strict SVG validation for inline theme logos.
 *
 * Generated SVG is accepted only as simple vector artwork. No scripts, external
 * resources, HTML, embedded images, stylesheets or animation are permitted.
 *
 * @package theme_learnlingo
 * @copyright 2026 Eduardo Kraus
 * @license https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_learnlingo;

use DOMDocument;
use DOMNode;

/**
 * Validation and safe image URL creation for administrator-supplied SVG.
 */
final class svg_logo {
    /** Maximum SVG source size. */
    public const MAX_LENGTH = 24000;

    /**
     * Ensure an SVG contains only static vector artwork.
     *
     * @param string $source SVG document.
     * @return bool
     */
    public static function is_valid(string $source): bool {
        $source = trim($source);
        if ($source === '' || strlen($source) > self::MAX_LENGTH ||
            preg_match('/<!(?!\[CDATA\[)|<\?|\]\]>|\\x00/i', $source)) {
            return false;
        }
        if (!class_exists('\\DOMDocument')) {
            return false;
        }

        $previous = libxml_use_internal_errors(true);
        try {
            $document = new DOMDocument();
            if (!$document->loadXML($source, LIBXML_NONET) ||
                $document->doctype !== null || !$document->documentElement ||
                $document->documentElement->localName !== 'svg' ||
                $document->documentElement->namespaceURI !== 'http://www.w3.org/2000/svg') {
                return false;
            }
            // Reject markup or processing instructions outside the root element.
            foreach ($document->childNodes as $node) {
                if ($node->nodeType !== XML_ELEMENT_NODE) {
                    return false;
                }
            }
            return self::check_node($document->documentElement);
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }
    }

    /**
     * Recursively check the SVG element tree.
     *
     * @param DOMNode $node Node.
     * @return bool
     */
    private static function check_node(DOMNode $node): bool {
        $elements = ['svg', 'g', 'path', 'rect', 'circle', 'ellipse', 'line', 'polyline',
            'polygon', 'text', 'tspan', 'title', 'desc'];
        $attributes = ['xmlns', 'viewBox', 'width', 'height', 'x', 'y', 'x1', 'x2',
            'y1', 'y2', 'cx', 'cy', 'r', 'rx', 'ry', 'd', 'points', 'transform',
            'fill', 'stroke', 'stroke-width', 'stroke-linecap', 'stroke-linejoin',
            'stroke-miterlimit', 'fill-rule', 'clip-rule', 'opacity',
            'fill-opacity', 'stroke-opacity', 'font-family', 'font-size',
            'font-weight', 'text-anchor', 'letter-spacing', 'dominant-baseline',
            'preserveAspectRatio', 'role', 'aria-label', 'aria-hidden'];

        if (!in_array($node->localName, $elements, true) ||
            $node->namespaceURI !== 'http://www.w3.org/2000/svg') {
            return false;
        }
        foreach ($node->attributes as $attribute) {
            if (!in_array($attribute->nodeName, $attributes, true)) {
                return false;
            }
            if ($attribute->nodeName === 'xmlns') {
                if ($attribute->value !== 'http://www.w3.org/2000/svg') {
                    return false;
                }
                continue;
            }
            if ($attribute->namespaceURI !== null || strlen($attribute->value) > 6000 ||
                preg_match('/[<>\\x00-\\x1f]|url\\s*\\(|javascript:|data:|https?:|@import|\\\\/i',
                    $attribute->value)) {
                return false;
            }
        }
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                if (!self::check_node($child)) {
                    return false;
                }
            } else {
                if ($child->nodeType === XML_TEXT_NODE) {
                    if (trim($child->nodeValue) !== '' &&
                        !in_array($node->localName, ['text', 'tspan', 'title', 'desc'], true)) {
                        return false;
                    }
                } else {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Return a data URI for an approved SVG or null for an unsafe/empty SVG.
     *
     * @param string $svg SVG markup.
     * @return string|null
     */
    public static function data_url(string $svg): ?string {
        return self::is_valid($svg) ? 'data:image/svg+xml;base64,' . base64_encode(trim($svg)) : null;
    }
}
