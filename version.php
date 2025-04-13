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
 * HarpIA Interaction. Version definition.
 *
 * @package    datafield_harpiainteraction
 * @copyright  2025 C4AI-USP <c4ai@usp.br>
 * @author     Vinícius B. Matos
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->version = 2025041201;         // The current plugin version (Date: YYYYMMDDXX).
$plugin->requires = 2024100701;        // Requires this Moodle version.
$plugin->component = 'datafield_harpiainteraction';  // Full name of the plugin (used for diagnostics).
$plugin->maturity = MATURITY_ALPHA;

$plugin->dependencies = [
    'local_harpiaajax' => 2025041201,
];
