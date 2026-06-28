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
 * Front-end class.
 *
 * @package availability_groupname
 * @copyright 2026 Portvgal
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace availability_groupname;

/**
 * Front-end class.
 *
 * @package availability_groupname
 * @copyright 2026 Portvgal
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class frontend extends \core_availability\frontend {
    /**
     * Gets strings used by the JavaScript form.
     *
     * @return string[]
     */
    protected function get_javascript_strings() {
        return [
            'conditiontitle',
            'error_setvalue',
            'label_operator',
            'label_value',
            'op_contains',
            'op_exact',
            'op_startswith',
        ];
    }
}
