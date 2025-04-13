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

namespace datafield_harpiainteraction\privacy;

use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use mod_data\privacy\datafield_provider;


/**
 * HarpIA Interaction. Privacy provider.
 * @package    datafield_harpiainteraction
 * @copyright  2025 C4AI-USP <c4ai@usp.br>
 * @author     Vinícius B. Matos
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements datafield_provider, \core_privacy\local\metadata\null_provider {
    #[\Override]
    public static function get_reason(): string {
        return 'privacy:metadata';
    }

    #[\Override]
    public static function export_data_content($context, $recordobj, $fieldobj, $contentobj, $defaultvalue) {

        /* This function is called when the user data is being exported. */

        $dir = __DIR__;
        require_once("{$dir}/../../field.class.php");

        $colhistory = \data_field_harpiainteraction::COL_HISTORY;
        $colquery = \data_field_harpiainteraction::COL_QUERY;
        $colanswer = \data_field_harpiainteraction::COL_ANSWER;

        $defaultvalue->history = json_decode($defaultvalue->$colhistory ?? '[]');
        $defaultvalue->query = $defaultvalue->$colquery;
        $defaultvalue->answer = $defaultvalue->$colanswer;

        unset($defaultvalue->$colquery);
        unset($defaultvalue->$colanswer);
        unset($defaultvalue->$colhistory);

        writer::with_context($context)->export_data([$recordobj->id, $contentobj->id], $defaultvalue);
    }

    #[\Override]
    public static function delete_data_content($context, $recordobj, $fieldobj, $contentobj) {
        /* Nothing to do here. Data will be deleted when the field tables are deleted. */
    }
}
