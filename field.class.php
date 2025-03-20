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




// Important:
// Moodle stores the entries in the table `data_content`,
// which has the columns `id` (id of the entry),
// `fieldid` (id of the field in the `data_fields` table),
// `recordid` (id of the record in the `data_records` table),
// and the 5 free columns `content`, `content1`, ..., `content4`.
// We use:
// - `content` to store the query;
// - `content1` to store the answer.
// - `content2` to store the previous messages.



class data_field_harpiainteraction extends data_field_base
{

    var $type = 'harpiainteraction';

    // data records
    const colQuery = 'content';
    const colAnswer = 'content1';
    const colHistory = 'content2';

    // columns in the `data_fields` table:
    const colProvider = 'param1';
    const colExperimentType = 'param2';
    const colSystemPrompt = 'param3';


    var $query = null;
    var $answer = null;
    var $history = null;

    public function supports_preview(): bool
    {
        return true;
    }

    public function get_data_content_preview(int $recordid): stdClass
    {
        return (object) [
            'id' => 0,
            'fieldid' => $this->field->id,
            'recordid' => $recordid,
            self::colQuery => $this->query ?? '',
            self::colAnswer => $this->answer ?? '',
            self::colHistory => $this->history ?? [],
            'content3' => null,
            'content4' => null,
        ];
    }


    function display_add_field($recordid = 0, $formdata = null)
    {
        // This function generates the item in the form shown when the student (evaluator)
        // is adding or editing an entry

        global $DB, $OUTPUT, $PAGE;

        // Include the Javascript code that calls the server requesting the language model's answer
        $PAGE->requires->js('/mod/data/field/harpiainteraction/assets/harpiainteraction.js');

        if ($formdata) {
            $fieldname = 'field_' . $this->field->id . '_query';
            $query = $formdata->$fieldname;
            $fieldname = 'field_' . $this->field->id . '_answer';
            $answer = $formdata->$fieldname;
            $fieldname = 'field_' . $this->field->id . '_history';
            $history = json_decode($formdata->$fieldname ?? '[]');

            $content = [
                'history' => $history,
                'query' => $query,
                'answer' => $answer
            ];
        } else if ($recordid) {
            // Editing an existing record
            $where = array('fieldid' => $this->field->id, 'recordid' => $recordid);
            $content = [
                'history' => json_decode($DB->get_field('data_content', self::colHistory, $where) ?: '[]'),
                'query' => $DB->get_field('data_content', self::colQuery, $where),
                'answer' => $DB->get_field('data_content', self::colAnswer, $where),
            ];
        } else {
            // Creating a new record
            $history = [];
            $parent_rid = $_GET['parentrid'] ?? null;
            if ($parent_rid) {
                $where = array('fieldid' => $this->field->id, 'recordid' => $parent_rid);
                $history = array_merge(json_decode($DB->get_field('data_content', self::colHistory, $where) ?: '[]'), [
                    $DB->get_field('data_content', self::colQuery, $where),
                    $DB->get_field('data_content', self::colAnswer, $where),
                ]);
            }
            $content = [
                'history' => $history,
                'query' => '',
                'answer' => ''
            ];
        }

        $templatename = 'datafield_' . $this->type . '/' . $this->type . '_addfield';
        $data = [
            'field_id' => $this->field->id,
            'description' => $this->field->description ?? '',
            'query' => $content['query'] ?? '',
            'answer' => $content['answer'] ?? '',
            'history' => $content['history'] ?? [],
            'parent_rid' => $parent_rid ?? '',
        ];
        return $OUTPUT->render_from_template($templatename, $data);
    }



    public function display_search_field($value = null)
    {
        // This function generates the search fields in the advanced search page
        $str = <<<ENDSTR
            <fieldset>
                <legend>%{field_name}</legend>
                <label for="f_%{field_id}_query">%{query_label}</label>
                <input type="text" class="form-control" size="16" id="f_%{field_id}" name="f_%{field_id}_query" value="%{query}" />
                <label for="f_%{field_id}_answer">%{answer_label}</label>
                <input type="text" class="form-control" size="16" id="f_%{field_id}" name="f_%{field_id}_answer" value="%{answer}" />
                <label for="f_%{field_id}_history">{{#str}}history, datafield_harpiainteraction{{/str}}</label>
                <input type="text" class="form-control" size="16" id="f_%{field_id}" name="f_%{field_id}_history" value="%{history}" />
            </fieldset>
        ENDSTR;
        return strtr($str, [
            '%{field_id}' => $this->field->id,
            '%{field_name}' => s($this->field->name),
            '%{query_label}' => s(get_string('query', 'datafield_harpiainteraction')),
            '%{query}' => s($value['query'] ?? ''),
            '%{answer_label}' => s(get_string('answer', 'datafield_harpiainteraction')),
            '%{answer}' => s($value['answer'] ?? ''),
            '%{history_label}' => s(get_string('history', 'datafield_harpiainteraction')),
            '%{history}' => s($value['history'] ?? ''),
        ]);
    }

    function generate_sql($tablealias, $value)
    {

        // This function generates the SQL conditions in the search.

        global $DB;

        $query = $value['query'];
        $answer = $value['answer'];
        $history = $value['history'];

        $colQuery = self::colQuery;
        $colAnswer = self::colAnswer;
        $colHistory = self::colHistory;
        $conditions = [
            "{$tablealias}.fieldid = {$this->field->id}",
            $DB->sql_like("{$tablealias}.{$colQuery}", ":c1", false),
            $DB->sql_like("{$tablealias}.{$colAnswer}", ":c2", false),
            $DB->sql_like("{$tablealias}.{$colHistory}", ":c3", false),
        ];
        return array(
            '(' . implode(" AND ", $conditions) . ')',
            array('c1' => "%$query%", 'c2' => "%$answer%", 'c3' => "%$history%")
        );
    }

    public function parse_search_field($defaults = null)
    {

        // This function parses the user input in the advanced search.

        $paramquery = 'f_' . $this->field->id . '_query';
        $paramanswer = 'f_' . $this->field->id . '_answer';
        $paramhistory = 'f_' . $this->field->id . '_history';
        $query = optional_param($paramquery, $defaults[$paramquery], PARAM_NOTAGS);
        $answer = optional_param($paramanswer, $defaults[$paramanswer], PARAM_NOTAGS);
        $history = optional_param($paramhistory, $defaults[$paramhistory], PARAM_NOTAGS);
        if ($query || $answer || $history) {
            return [
                'query' => $query,
                'answer' => $answer,
                'history' => $history,
            ];
        }
        return 0;
    }

    function update_content($recordid, $value, $name = '')
    {

        // This function is called once per FORM FIELD
        // (in our case, the form fields are:
        // - "interactionid".        

        // Extract name of the HTML field
        $name_parts = explode('_', $name);
        $key = $name_parts[array_key_last($name_parts)];


        if (!in_array($key, ['interactionid']))
            return;

        global $DB, $USER;


        switch ($key) {

            case "interactionid":

                if ($this->answer !== null)
                    return;


                $where = ['id' => $value, 'userid' => $USER->id, 'recordid' => null];
                $record = $DB->get_record('data_harpiainteraction', $where);
                $this->query = $record->query;
                $this->answer = $record->answer;
                $this->history = [];
                if ($record->parentrecordid) {
                    $where = ['fieldid' => $this->field->id, 'recordid' => $record->parentrecordid];
                    $prev_record = $DB->get_record('data_content', $where);
                    $this->history = json_decode($prev_record->{self::colHistory} ?: '[]') + [
                        $prev_record->{self::colQuery},
                        $prev_record->{self::colAnswer}
                    ];
                }

                break;

            default: // no other fields for now - data is obtained from the interaction table
                return;
        }

        if ($this->query !== null and $this->answer !== null and $this->history !== null) {
            // All values have been colected, so we store them in the DB

            $content = new stdClass();
            $content->fieldid = $this->field->id;
            $content->recordid = $recordid;
            $content->{self::colQuery} = $this->query;
            $content->{self::colAnswer} = $this->answer;
            $content->{self::colHistory} = json_encode($this->history ?: []);

            global $DB;
            if ($oldcontent = $DB->get_record('data_content', array('fieldid' => $this->field->id, 'recordid' => $recordid))) {
                // Updating an existing entry
                $content->id = $oldcontent->id;
                return $DB->update_record('data_content', $content);
            } else {
                // Creating a new entry
                return $DB->insert_record('data_content', $content);
            }
        }
    }

    function display_browse_field($recordid, $template)
    {
        // This function generates the summary of the data of this field,
        // displayed on the entry list

        global $OUTPUT;

        $content = $this->get_data_content($recordid);
        if (!$content || empty($content->{self::colQuery})) {
            return '';
        }

        $templatename = 'datafield_' . $this->type . '/' . $this->type . '_browse';
        $continue_url = '';
        if ($this->field->{self::colExperimentType} == 'chat')
            $continue_url = (new moodle_url('/mod/data/edit.php', [
                'd' => $this->data->id,
                'parentrid' => $recordid
            ]))->out();
        $data = [
            'field_id' => $this->field->id,
            'description' => $this->field->description ?? '',
            'query' => $content->{self::colQuery} ?? '',
            'answer' => $content->{self::colAnswer} ?? '',
            'history' => json_decode($content->{self::colHistory} ?? '[]') ?? [],
            'continue_url' => $continue_url,
        ];
        return $OUTPUT->render_from_template($templatename, $data);

    }

    function export_text_value($record)
    {
        // This function generates the string representation for the exported
        // spreadsheet.
        return json_encode(
            [
                "query" => $record->{self::colQuery},
                "output" => $record->{self::colAnswer},
                "history" => json_decode($record->{self::colHistory} ?? "[]"),
            ],
            JSON_UNESCAPED_UNICODE | JSON_HEX_QUOT
        );
    }


    public function get_config_for_external()
    {
        $configs = [];
        for ($i = 1; $i <= 10; $i++) {
            $configs["param$i"] = $this->field->{"param$i"};
        }
        return $configs;
    }


    public function get_field_params(): array
    {
        // This function defines the fields that will be available
        // in the Mustache template (shown when the field definition is created/edited
        // by a teacher).
        global $DB, $CFG;

        $data = parent::get_field_params();

        // get list of answer providers from HarpIA Ajax plugin
        require_once($CFG->dirroot . '/local/harpiaajax/send_message.php');
        $providers = send_message::fetch_providers()->providers;

        return [
            "name" => $data["name"],
            "description" => $data["description"],
            "answer_provider_col" => self::colProvider,
            "answer_provider" => $data[self::colProvider],
            "experiment_type_col" => self::colExperimentType,
            "experiment_type" => $data[self::colExperimentType],
            "system_prompt_col" => self::colSystemPrompt,
            "system_prompt" => $data[self::colSystemPrompt],
            "providers" => $providers, // list of all answer providers
        ];
    }
}
