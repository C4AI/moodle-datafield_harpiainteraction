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
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle. If not, see <https://www.gnu.org/licenses/>.


function xmldb_datafield_harpiainteraction_upgrade($oldversion)
{
    global $CFG;
    global $DB;



    $result = TRUE;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025031905) {

        // Define table data_harpiainteraction to be created.
        $table = new xmldb_table('data_harpiainteraction');

        // Adding fields to table data_harpiainteraction.
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('timestamp', XMLDB_TYPE_INTEGER, '19', null, XMLDB_NOTNULL, null, null);
        $table->add_field('userid', XMLDB_TYPE_INTEGER, '19', null, XMLDB_NOTNULL, null, null);
        $table->add_field('dataid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $table->add_field('parentdataid', XMLDB_TYPE_INTEGER, '10', null, null, null, null);
        $table->add_field('answer_provider', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null);
        $table->add_field('query', XMLDB_TYPE_TEXT, null, null, XMLDB_NOTNULL, null, null);
        $table->add_field('system_prompt', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('answer', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table data_harpiainteraction.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('dataid-', XMLDB_KEY_FOREIGN, ['dataid'], 'data', ['id']);
        $table->add_key('parentdataid-', XMLDB_KEY_FOREIGN, ['parentdataid'], 'data', ['id']);
        $table->add_key('userid-', XMLDB_KEY_FOREIGN, ['userid'], 'user', ['id']);

        // Conditionally launch create table for data_harpiainteraction.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        // harpiainteraction savepoint reached.
        upgrade_plugin_savepoint(true, 2025031905, 'datafield', 'harpiainteraction');

    }



    return $result;
}
?>