<?php

class data_field_harpiainteraction extends data_field_base {

    var $type = 'harpiainteraction';

    var $query = "";
    var $output = "";

    public function supports_preview(): bool {
        return true;
    }

    public function get_data_content_preview(int $recordid): stdClass {
        return (object)[
            'id' => 0,
            'fieldid' => $this->field->id,
            'recordid' => $recordid,
            'content' => $this->query,
            'content1' => $this->output,
            'content2' => null,
            'content3' => null,
            'content4' => null,
        ];
    }

    /* Function that generates the HTML code shown when the student (evaluator)
       is adding or editing an entry */
    function display_add_field($recordid = 0, $formdata = null) {


        global $DB, $OUTPUT, $PAGE;
        $PAGE->requires->js('/mod/data/field/harpiainteraction/assets/harpiainteraction.js');

        if ($formdata) {
            $fieldname = 'field_' . $this->field->id . '_query';
            $query   = $formdata->$fieldname;
            $fieldname = 'field_' . $this->field->id . '_output';
            $output   = $formdata->$fieldname;

            $content = [
                'query' => $query,
                'output' => $output
            ];
        } else if ($recordid) {
            $content = json_decode($DB->get_field('data_content', 'content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid)));
        } else {
            $content = [
                'query' => '',
                'output' => ''
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
                                    >%{output_value}</p>
                                    <input type="hidden" name="field_%{field_id}_output" class="lm-answer-hidden" value="%{output_value}" />
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
            '%{query_value}' => s($content->query),
            '%{output_value}' => s($content->output),
            '%{send_display}' => $content->output ? 'none' : 'initial',
            '%{query_attrs}' => $content->output ? 'readonly="readonly"' : '',
            '%{provider_hash}' => s($provider_hash),  # prevent users from guessing other providers
        ]);
    }


    /**
     * Display the search field in advanced search page
     * @param mixed $value
     * @return string
     * @throws coding_exception
     */
    public function display_search_field($value = null) {
        $str = '';
        // TODO: implement search

        return $str;
    }

    function generate_sql($tablealias, $value) {
        // TODO: implement search
        return array();
    }

    public function parse_search_field($defaults = null) {
        // TODO: implement search
        return 0;
    }

    function update_content($recordid, $value, $name='') {
        global $DB;

        $name_parts = explode('_', $name);
        $key = $name_parts[array_key_last($name_parts)];
        if (! in_array($key, ['query', 'output']))
            return;
        $this->$key = $value;

        if ($this->query and $this->output) {
            $content = new stdClass();
            $content->fieldid = $this->field->id;
            $content->recordid = $recordid;
            $content->content = json_encode([
                'query' => $this->query,
                'output' => $this->output
            ]);
            if ($oldcontent = $DB->get_record('data_content', array('fieldid'=>$this->field->id, 'recordid'=>$recordid))) {
                $content->id = $oldcontent->id;
                return $DB->update_record('data_content', $content);
            } else {
                return $DB->insert_record('data_content', $content);
            }
        }
    }

    function display_browse_field($recordid, $template) {
        $content = $this->get_data_content($recordid);
        if (!$content || empty($content->content)) {
            return '';
        }
        $data = json_decode($content->content);
        $str = <<<ENDSTR
            <u>Query:</u> <i>%{query}</i>
            <br>
            <u>Output:</u> <i>%{output}</i>
        ENDSTR;
        
        return strtr($str, [
            '%{query}' => s($data->query),
            '%{output}' => s($data->output),
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
