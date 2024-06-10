<?php

// Moodle stores the entries in the table `data_content`,
// which has the columns `id` (id of the entry),
// `fieldid` (id of the field in the `data_fields` table),
// `recordid` (id of the record in the `data_records` table),
// and the 5 free columns `content`, `content1`, ..., `content4`.
// We use:
// - `content` to store the query;
// - `content1` to store the answer.





class data_field_harpiainteraction extends data_field_base {

    var $type = 'harpiainteraction';

    var $query = null;
    var $answer = null;

    public function supports_preview(): bool {
        return true;
    }

    public function get_data_content_preview(int $recordid): stdClass {
        return (object)[
            'id' => 0,
            'fieldid' => $this->field->id,
            'recordid' => $recordid,
            'content' => $this->query ?? '',
            'content1' => $this->answer ?? '',
            'content2' => null,
            'content3' => null,
            'content4' => null,
        ];
    }

    
    function display_add_field($recordid = 0, $formdata = null) {
        // This function generates the item in the form shown when the student (evaluator)
        // is adding or editing an entry

        global $DB, $OUTPUT, $PAGE;
        
        // Include the Javascript code that calls the server requesting the language model's answer
        $PAGE->requires->js('/mod/data/field/harpiainteraction/assets/harpiainteraction.js');

        if ($formdata) {
            $fieldname = 'field_' . $this->field->id . '_query';
            $query   = $formdata->$fieldname;
            $fieldname = 'field_' . $this->field->id . '_answer';
            $answer   = $formdata->$fieldname;

            $content = [
                'query' => $query,
                'answer' => $answer
            ];
        } else if ($recordid) {
            // Editing an existing record
            $where = array('fieldid'=>$this->field->id, 'recordid'=>$recordid);
            $content = [
                'query' => $DB->get_field('data_content', 'content', $where),
                'answer' => $DB->get_field('data_content', 'content1', $where),
            ];
        } else {
            // Creating a new record
            $content = [
                'query' => '',
                'answer' => ''
            ];
        }

        $provider_hash = password_hash($this->field->param1, PASSWORD_DEFAULT);

        $str = <<<ENDSTR
            <div title="%{description_label}" class="mod-data-input form-inline" data-field-id="%{field_id}">
                <div style="width:100%;">
                    <table data-field-id="%{field_id}" style="width:100%;">
                        <tbody>
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

        return  strtr($str, [
            '%{description_label}' => s($this->field->description),
            '%{query_label}' => s(get_string('query', 'datafield_harpiainteraction')),
            '%{send_btn}' => s(get_string('send', 'datafield_harpiainteraction')),
            '%{answer_label}' => s(get_string('answer', 'datafield_harpiainteraction')),
            '%{contexts_label}' => s(get_string('contexts', 'datafield_harpiainteraction')),
            '%{field_id}' => $this->field->id,
            '%{query_value}' => s($content['query'] ?? 'NULL'),
            '%{answer_value}' => s($content['answer'] ?? ''),
            '%{send_display}' => $content['answer'] ? 'none' : 'initial',
            '%{query_attrs}' => $content['answer'] ? 'readonly="readonly"' : '',
            '%{provider_hash}' => s($provider_hash),  # prevent users from guessing other providers
        ]);
    }



    public function display_search_field($value = null) {
        // This function generates the search fields in the advanced search page
        $str = <<<ENDSTR
            <fieldset>
                <legend>%{field_name}</legend>
                <label for="f_%{field_id}_query">%{query_label}</label>
                <input type="text" class="form-control" size="16" id="f_%{field_id}" name="f_%{field_id}_query" value="%{query}" />
                <label for="f_%{field_id}_answer">%{answer_label}</label>
                <input type="text" class="form-control" size="16" id="f_%{field_id}" name="f_%{field_id}_answer" value="%{answer}" />
            </fieldset>
        ENDSTR;
        return strtr($str, [
            '%{field_id}' => $this->field->id,
            '%{field_name}' =>  s($this->field->name),
            '%{query_label}' => s(get_string('query', 'datafield_harpiainteraction')),
            '%{query}' => s($value['query'] ?? ''), 
            '%{answer_label}' => s(get_string('answer', 'datafield_harpiainteraction')),
            '%{answer}' => s($value['answer'] ?? ''), 
        ]);
    }

    function generate_sql($tablealias, $value) {
        
        // This function generates the SQL conditions in the search.

        global $DB;

        $query = $value['query'];
        $answer = $value['answer'];

        $conditions = [
            "{$tablealias}.fieldid = {$this->field->id}",
            $DB->sql_like("{$tablealias}.content", ":c1", false),
            $DB->sql_like("{$tablealias}.content1", ":c2", false),
        ];
        return array(
            '(' . implode(" AND ", $conditions) . ')',
            array('c1' => "%$query%", 'c2' => "%$answer%")
        );  
    }

    public function parse_search_field($defaults = null) {
        
        // This function parses the user input in the advanced search.

        $paramquery = 'f_' . $this->field->id . '_query';
        $paramanswer = 'f_' . $this->field->id . '_answer';
        $query = optional_param($paramquery, $defaults[$paramquery], PARAM_NOTAGS);
        $answer = optional_param($paramanswer, $defaults[$paramanswer], PARAM_NOTAGS);
        if ($query || $answer) {
            return [
                'query' => $query,
                'answer' => $answer,
            ];
        }
        return 0;
    }

    function update_content($recordid, $value, $name='') {
        
        // This function is called
        // once per form field (in our case, the form fields are query and answer).        

        // Extract name of the HTML field
        $name_parts = explode('_', $name);  
        $key = $name_parts[array_key_last($name_parts)];
        if (!in_array($key, ['query', 'answer']))
            return;
        $this->$key = $value ?? '';

        if ($this->query !== null and $this->answer !== null) {
            // All values have been colected, so we store them in the DB

            $content = new stdClass();
            $content->fieldid = $this->field->id;
            $content->recordid = $recordid;
            $content->content = $this->query;
            $content->content1 = $this->answer;

            global $DB;
            if ($oldcontent = $DB->get_record('data_content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
                // Updating an existing entry
                $content->id = $oldcontent->id;
                return $DB->update_record('data_content', $content);
            } else {
                // Creating a new entry
                return $DB->insert_record('data_content', $content);
            }
        }
    }

    function display_browse_field($recordid, $template) {

        // This function generates the summary of the data of this field,
        // displayed on the entry list

        $content = $this->get_data_content($recordid);
        if (!$content || empty($content->content)) {
            return '';
        }
        $str = <<<ENDSTR
            <u>%{query_label}</u> <i>%{query}</i>
            <br>
            <u>%{answer_label}</u> <i>%{answer}</i>
        ENDSTR;
        
        return strtr($str, [
            '%{query}' => s($content->content),
            '%{answer}' => s($content->content1),
            '%{query_label}' => s(get_string('query', 'datafield_harpiainteraction')),
            '%{answer_label}' => s(get_string('answer', 'datafield_harpiainteraction')),
        ]);
    }

    public function get_config_for_external() {
        $configs = [];
        for ($i = 1; $i <= 10; $i++) {
            $configs["param$i"] = $this->field->{"param$i"};
        }
        return $configs;
    }
}
