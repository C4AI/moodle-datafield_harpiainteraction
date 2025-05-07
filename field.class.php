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

use mod_data\manager;

/**
 * HarpIA Interaction. Class that defines the field's behaviour.
 *
 * @package    datafield_harpiainteraction
 * @copyright  2025 C4AI-USP <c4ai@usp.br>
 * @author     Vinícius B. Matos
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_field_harpiainteraction extends data_field_base {
    // Important:
    // Moodle stores the entries in the table {data_content},
    // which has the columns `id` (id of the entry),
    // `fieldid` (id of the field in the {data_fields} table),
    // `recordid` (id of the record in the `data_records` table),
    // and the 5 free columns `content`, `content1`, ..., `content4`.
    // We use:
    // - `content` to store the query;
    // - `content1` to store the answer.
    // - `content2` to store the previous messages.


    /**
     * The type of the data field.
     *
     * @var string
     */
    public $type = 'harpiainteraction';

    /**
     * Name of the column that stores the query in the {data_content} table.
     */
    const COL_QUERY = 'content';

    /**
     * Name of the column that stores the answer in the {data_content} table.
     */
    const COL_ANSWER = 'content1';

    /**
     * Name of the column that stores the history in the {data_content} table.
     */
    const COL_HISTORY = 'content2';

    /**
     * Name of the column that stores the answer provider name in the {data_fields} table.
     */
    const COL_PROVIDER = 'param1';

    /**
     * Name of the column that stores the experiment type in the {data_fields} table.
     */
    const COL_EXP_TYPE = 'param2';

    /**
     * Name of the column that stores the system prompt in the {data_fields} table.
     */
    const COL_SYSTEM_PROMPT = 'param3';

    /**
     * The user's query.
     * @var string
     */
    private $query = null;

    /**
     * The answer provider's output.
     * @var string
     */
    private $answer = null;

    /**
     * The message history.
     * @var array
     */
    private $history = null;

    /**
     * The id of the interaction with the answer provider.
     * @var int
     */
    private $interactionid = 0;

    #[\Override]
    public function supports_preview(): bool {
        return true;
    }

    #[\Override]
    public function get_data_content_preview(int $recordid): stdClass {
        return (object) [
            'id' => 0,
            'fieldid' => $this->field->id,
            'recordid' => $recordid,
            self::COL_QUERY => $this->query ?? '',
            self::COL_ANSWER => $this->answer ?? '',
            self::COL_HISTORY => $this->history ?? [],
            'content3' => null,
            'content4' => null,
        ];
    }

    #[\Override]
    public function display_add_field($recordid = 0, $formdata = null) {
        /* This function generates the item in the form shown when the student (evaluator)
         is adding or editing an entry. */

        global $DB, $OUTPUT, $PAGE, $USER;

        // Include the Javascript code that calls the server requesting the language model's answer.
        $PAGE->requires->js('/mod/data/field/harpiainteraction/assets/harpiainteraction.js');

        // Start with empty values.
        $interactionid = 0;
        $history = [];
        $query = '';
        $answer = '';
        $parentrid = $_GET['parentrid'] ?? null;

        if ($recordid) { // Editing an existing record.
            // Retrieve data from the record.
            $where = ['fieldid' => $this->field->id, 'recordid' => $recordid];
            $record = $DB->get_record('data_content', $where);
            $history = json_decode($record->{self::COL_HISTORY} ?: '[]');
            $query = $record->{self::COL_QUERY};
            $answer = $record->{self::COL_ANSWER};
            // Find its interaction id.
            $where = ['dataid' => $this->data->id, 'recordid' => $recordid];
            $interactionid = $DB->get_field('data_harpiainteraction', 'id', $where) ?? 0;
        } else { // Creating a new record.
            // If it has a parent record, fetch its history.
            if ($parentrid) {
                $where = ['fieldid' => $this->field->id, 'recordid' => $parentrid];
                $parentcontent = $DB->get_record('data_content', $where);
                if (!$parentcontent) {
                    throw new invalid_parameter_exception('Invalid parent rid');
                }

                // Make sure that the user can see the parent record.
                $manager = manager::create_from_instance($this->data);
                $context = $manager->get_context();
                $cm = $manager->get_coursemodule();
                $currentgroup = groups_get_activity_group($cm);
                $prevrecord = $DB->get_record('data_records', ['id' => $parentrid]);
                $canmanageentries = has_capability('mod/data:manageentries', $context);
                if (!data_can_view_record($this->data, $prevrecord, $currentgroup, $canmanageentries)) {
                    // If the user cannot see a previous interaction, they cannot continue the conversation.
                    throw new \moodle_exception('noaccess', 'datafield_harpiainteraction');
                }
                if ($this->field->{self::COL_EXP_TYPE} !== 'chat') {
                    // If an experiment type only allows single interactions, do not allow continuation.
                    throw new \moodle_exception('nochat', 'datafield_harpiainteraction');
                }

                // Include the past history and the last interaction.
                $history = array_merge(
                    json_decode($parentcontent->{self::COL_HISTORY} ?: '[]'),
                    [
                        $parentcontent->{self::COL_QUERY},
                        $parentcontent->{self::COL_ANSWER},
                    ]
                );
            }
        }

        if ($formdata) { // Re-showing the form after submitting and failing (e.g. due to missing value).
            // If an interaction has already been completed, retrieve its result to overwrite it.
            $interactionid = $formdata->{'field_' . $this->field->id . '_interactionid'};
            $where = ['id' => $interactionid, 'userid' => $USER->id, 'recordid' => null];
            $interaction = $DB->get_record('data_harpiainteraction', $where);
            if ($interaction) {
                $query = $interaction->query;
                $answer = $interaction->answer;
            }
        }

        $templatename = "datafield_{$this->type}/{$this->type}_addfield";
        $data = [
            'field_id' => $this->field->id,
            'description' => $this->field->description ?? '',
            'query' => $query,
            'answer' => $answer,
            'history' => $history,
            'parent_rid' => $parentrid ?? '',
            'interaction_id' => $interactionid,
        ];
        return $OUTPUT->render_from_template($templatename, $data);
    }


    #[\Override]
    public function display_search_field($value = null) {
        /* This function generates the search fields in the advanced search page. */
        global $OUTPUT;
        $templatename = "datafield_{$this->type}/{$this->type}_search";
        $data = [
            'field_id' => $this->field->id,
            'field_name' => $this->field->name,
            'query' => $value['query'] ?? '',
            'answer' => $value['answer'] ?? '',
            'history' => $value['history'] ?? '',
        ];
        return $OUTPUT->render_from_template($templatename, $data);
    }

    #[\Override]
    public function generate_sql($tablealias, $value) {

        /* This function generates the SQL conditions in the search. */

        global $DB;

        $query = $value['query'];
        $answer = $value['answer'];
        $history = $value['history'];

        $colquery = self::COL_QUERY;
        $colanswer = self::COL_ANSWER;
        $colhistory = self::COL_HISTORY;
        $conditions = [
            "{$tablealias}.fieldid = {$this->field->id}",
            $DB->sql_like("{$tablealias}.{$colquery}", ":c1", false),
            $DB->sql_like("{$tablealias}.{$colanswer}", ":c2", false),
            $DB->sql_like("{$tablealias}.{$colhistory}", ":c3", false),
        ];
        return [
            '(' . implode(" AND ", $conditions) . ')',
            ['c1' => "%$query%", 'c2' => "%$answer%", 'c3' => "%$history%"],
        ];
    }

    #[\Override]
    public function parse_search_field($defaults = null) {

        // This function parses the user input in the advanced search.

        $paramquery = 'f_' . $this->field->id . '_query';
        $paramanswer = 'f_' . $this->field->id . '_answer';
        $paramhistory = 'f_' . $this->field->id . '_history';
        $query = optional_param($paramquery, $defaults[$paramquery] ?? '', PARAM_NOTAGS);
        $answer = optional_param($paramanswer, $defaults[$paramanswer] ?? '', PARAM_NOTAGS);
        $history = optional_param($paramhistory, $defaults[$paramhistory] ?? '', PARAM_NOTAGS);
        if ($query || $answer || $history) {
            return [
                'query' => $query,
                'answer' => $answer,
                'history' => $history,
            ];
        }
        return 0;
    }

    #[\Override]
    public function update_content($recordid, $value, $name = '') {

        /* This function is called once per FORM FIELD.
           In our case, the only form field is "interactionid",
           and we fetch all the data from the interaction table. */

        // Extract name of the HTML field.
        $nameparts = explode('_', $name);
        $key = $nameparts[array_key_last($nameparts)];

        if (!in_array($key, ['interactionid'])) {
            return;
        }

        global $DB, $USER;

        $interactionid = 0;
        switch ($key) {
            case "interactionid":
                $interactionid = $value;
                break;
            default: // No other fields for now - data is obtained from the interaction table.
                return;
        }
        $interaction = null;


        if ($interactionid) {
            // All form fields have been collected.
            global $DB;
            // Find the interaction.
            $where = ['id' => $interactionid, 'userid' => $USER->id, 'recordid' => null];
            $interaction = $DB->get_record('data_harpiainteraction', $where);
            if (!$interaction) {
                return false;
            }
            $this->query = $interaction->query;
            $this->answer = $interaction->answer;
            $this->history = [];
            if ($interaction->parentrecordid) {
                $where = ['fieldid' => $this->field->id, 'recordid' => $interaction->parentrecordid];
                $prevrecord = $DB->get_record('data_content', $where);
                $this->history = array_merge(json_decode($prevrecord->{self::COL_HISTORY} ?: '[]'), [
                    $prevrecord->{self::COL_QUERY},
                    $prevrecord->{self::COL_ANSWER},
                ]);
            }

            $content = new stdClass();
            $content->fieldid = $this->field->id;
            $content->recordid = $recordid;
            $content->{self::COL_QUERY} = $this->query;
            $content->{self::COL_ANSWER} = $this->answer;
            $content->{self::COL_HISTORY} = json_encode($this->history ?: []);
            if ($oldcontent = $DB->get_record('data_content', ['fieldid' => $this->field->id, 'recordid' => $recordid])) {
                // Updating an existing row.
                // This is called even for new entries, because Moodle creates the row with NULL values.
                $content->id = $oldcontent->id;
                if (!$DB->update_record('data_content', $content)) {
                    return false;
                }
            } else {
                // Creating a new row.
                if (!$DB->insert_record('data_content', $content)) {
                    return false;
                }
            }

            // Link with the interaction.
            $interactiondata = new stdClass();
            $interactiondata->id = $interactionid;
            $interactiondata->dataid = $this->data->id;
            $interactiondata->recordid = $recordid;
            return $DB->update_record('data_harpiainteraction', $interactiondata);
        }
    }

    #[\Override]
    public function display_browse_field($recordid, $template) {
        /* This function generates the summary of the data of this field, displayed on the entry list. */

        global $OUTPUT;

        $content = $this->get_data_content($recordid);
        if (!$content || empty($content->{self::COL_QUERY})) {
            return '';
        }

        $templatename = 'datafield_' . $this->type . '/' . $this->type . '_browse';
        $continueurl = '';
        if ($this->field->{self::COL_EXP_TYPE} == 'chat') {
            $continueurl = (new moodle_url('/mod/data/edit.php', [
                'd' => $this->data->id,
                'parentrid' => $recordid,
            ]))->out();
        }
        $data = [
            'query' => $content->{self::COL_QUERY} ?? '',
            'answer' => $content->{self::COL_ANSWER} ?? '',
            'history' => json_decode($content->{self::COL_HISTORY} ?? '[]') ?? [],
            'continue_url' => $continueurl,
        ];
        return $OUTPUT->render_from_template($templatename, $data);
    }

    #[\Override]
    public function export_text_value($record) {
        // This function generates the string representation for the exported
        // spreadsheet.
        return json_encode(
            [
                "query" => $record->{self::COL_QUERY},
                "output" => $record->{self::COL_ANSWER},
                "history" => json_decode($record->{self::COL_HISTORY} ?? "[]"),
            ],
            JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT
        );
    }

    #[\Override]
    public function get_config_for_external() {
        $configs = [];
        for ($i = 1; $i <= 10; $i++) {
            $configs["param$i"] = $this->field->{"param$i"};
        }
        return $configs;
    }


    #[\Override]
    public function get_field_params(): array {
        // This function defines the fields that will be available
        // in the Mustache template (shown when the field definition is created/edited
        // by a teacher).
        global $DB, $CFG;

        $data = parent::get_field_params();

        // Get list of answer providers from HarpIA Ajax plugin.
        require_once($CFG->dirroot . '/local/harpiaajax/send_message.php');
        $providers = send_message::fetch_providers()->providers;

        return [
            "name" => $data["name"],
            "description" => $data["description"],
            "answer_provider_col" => self::COL_PROVIDER,
            "answer_provider" => $data[self::COL_PROVIDER],
            "experiment_type_col" => self::COL_EXP_TYPE,
            "experiment_type" => $data[self::COL_EXP_TYPE],
            "system_prompt_col" => self::COL_SYSTEM_PROMPT,
            "system_prompt" => $data[self::COL_SYSTEM_PROMPT],
            "providers" => $providers,
        ];
    }
}
