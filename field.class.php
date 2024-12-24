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

    // data fields
    const colProviderHash = 'param1';
    const colExperimentType = 'param2';
    const colSystemPrompt = 'param3';


    var $query = null;
    var $answer = null;

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
            $parent_rid = $_GET['parentrid'];
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

        $provider_hash = password_hash($this->field->{self::colProviderHash}, PASSWORD_DEFAULT);

        $history_html = '<input type="hidden" name="field_%{field_id}_history" value="' . s(json_encode($content['history'])) . '" />';
        if ($content['history']) {
            $fromUser = true;
            $parts = ['<tr><td style="vertical-align: top;">%{history_label}</td><td><table border="1">'];
            foreach ($content['history'] as $msg) {
                $parts[] = '<tr><th>' .
                    ($fromUser ? '%{user_label}' : '%{bot_label}') .
                    '</th><td>' . s($msg) . '</td></tr>';
                $fromUser = !$fromUser;
            }
            $parts[] = '</table></td></tr>';
            $history_html .= '<tr><td colspan="2">' . implode('', $parts) . '</td></tr>';
        }




        $str = <<<ENDSTR
            <div title="%{description_label}" class="mod-data-input form-inline" data-field-id="%{field_id}">
                <div style="width:100%;">
                    <table data-field-id="%{field_id}" style="width:100%;">
                        <tbody>
                            $history_html
                            <tr>
                                <td>
                                    <label for="field_%{field_id}_query">%{query_label}&nbsp;&nbsp;</label>
                                </td>
                                <td style="margin: 1em; width:99%;">
                                    <div style="display:flex;">
                                        <input class="harpiainteraction-field" type="text" class="form-control" id="field_%{field_id}_query"
                                            name="field_%{field_id}_query" value="%{query_value}" %{query_attrs} style="flex:2" />
                                        &nbsp;&nbsp;
                                        <button id="field_%{field_id}_send" class="btn btn-primary harpiainteraction-btn-send" type="button" style="display:%{send_display}" >%{send_btn}</button>
                                    </div>
                                </td>
                            </tr>
                            <tr data-field-id="%{field_id}" style="width:100%;margin-top:1em;">
                                <td style="vertical-align:top;">
                                    <label>%{answer_label}</label>
                                </td>
                                <td>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2">
                                    <p class="lm-answer"
                                        style="font-style:italic; border:none; resize: none; width:100%; vertical-align:top; margin-left:1em;"
                                    >%{answer_value}</p>
                                    <input type="hidden" name="field_%{field_id}_answer" class="lm-answer-hidden" value="%{answer_value}" />
                                </td>
                            </tr>
                            <tr class="lm-contexts" style="display:none">
                                <td>%{contexts_label}</td>
                            </tr>
                            
                            <template class="lm-context-template">
                                <tr>
                                    <td colspan="2">
                                        <details class="lm-context" style="width:100%;">
                                            <summary class="lm-context-header"></summary>
                                            <p class="lm-context-text" style="font-style:italic"></p>
                                        </details>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <br/>
                <input type="hidden" name="field_%{field_id}_providerhash" value="%{provider_hash}" />
            </div>
            <style>.harpiainteraction-field:read-only { border: none; cursor: not-allowed; font-style: italic; }</style>
            <script>
                document.getElementById('field_%{field_id}_query').addEventListener('keypress', function(event) {
                    if (event.keyCode === 13) {
                        event.preventDefault();
                        document.getElementById('field_%{field_id}_send').click();
                    }
                });
            </script>
        ENDSTR;

        return strtr($str, [
            '%{description_label}' => s($this->field->description),
            '%{query_label}' => s(get_string('query', 'datafield_harpiainteraction')),
            '%{send_btn}' => s(get_string('send', 'datafield_harpiainteraction')),
            '%{answer_label}' => s(get_string('answer', 'datafield_harpiainteraction')),
            '%{contexts_label}' => s(get_string('contexts', 'datafield_harpiainteraction')),
            '%{history_label}' => s(get_string('history', 'datafield_harpiainteraction')),
            '%{field_id}' => $this->field->id,
            '%{query_value}' => s($content['query'] ?? 'NULL'),
            '%{answer_value}' => s($content['answer'] ?? ''),
            '%{send_display}' => $content['answer'] ? 'none' : 'initial',
            '%{query_attrs}' => $content['answer'] ? 'readonly="readonly"' : '',
            '%{provider_hash}' => s($provider_hash),  # prevent users from guessing other providers
            '%{user_label}' => s(get_string('usersender', 'datafield_harpiainteraction')),
            '%{bot_label}' => s(get_string('botsender', 'datafield_harpiainteraction')),
        ]);
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
                <label for="f_%{field_id}_history">%{history_label}</label>
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

        // This function is called
        // once per FORM FIELD (in our case, the form fields are query, answer and history).        

        // Extract name of the HTML field
        $name_parts = explode('_', $name);
        $key = $name_parts[array_key_last($name_parts)];
        if (!in_array($key, ['query', 'answer', 'history']))
            return;
        if ($key === 'history')
            $this->$key = json_decode($value ?: '[]') ?? [];
        else
            $this->$key = $value ?? '';


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

        $content = $this->get_data_content($recordid);
        if (!$content || empty($content->{self::colQuery})) {
            return '';
        }
        $history_html = '';
        $history = json_decode($content->{self::colHistory} ?: '[]');
        if ($history) {
            $fromUser = true;
            $parts = ['<details><summary>%{history_label}</summary><table border="1">'];
            foreach ($history as $msg) {
                $parts[] = '<tr><th>' .
                    ($fromUser ? '%{user_label}' : '%{bot_label}') .
                    '</th><td>' . s($msg) . '</td></tr>';
                $fromUser = !$fromUser;
            }
            $parts[] = '</table></details><br>';
            $history_html .= implode('', $parts);
        }


        $str = $history_html . <<<ENDSTR
            <u>%{query_label}</u> <i>%{query}</i>
            <br>
            <u>%{answer_label}</u> <i>%{answer}</i>
            <br>
        ENDSTR;

        if ($this->field->{self::colExperimentType} == 'chat')
            $str .= '<br><a href="' . (new moodle_url('/mod/data/edit.php', [
                'd' => $this->data->id,
                'parentrid' => $recordid
            ]))->out() . '">%{continue_label}</a>';

        return strtr($str, [
            '%{query}' => s($content->{self::colQuery}),
            '%{answer}' => s($content->{self::colAnswer}),
            '%{query_label}' => s(get_string('query', 'datafield_harpiainteraction')),
            '%{answer_label}' => s(get_string('answer', 'datafield_harpiainteraction')),
            '%{history_label}' => s(get_string('history', 'datafield_harpiainteraction')),
            '%{continue_label}' => s(get_string('continue', 'datafield_harpiainteraction')),
            '%{user_label}' => s(get_string('usersender', 'datafield_harpiainteraction')),
            '%{bot_label}' => s(get_string('botsender', 'datafield_harpiainteraction')),
        ]);
    }


    public function get_config_for_external()
    {
        $configs = [];
        for ($i = 1; $i <= 10; $i++) {
            $configs["param$i"] = $this->field->{"param$i"};
        }
        return $configs;
    }
}