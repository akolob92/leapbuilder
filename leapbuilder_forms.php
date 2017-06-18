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
 * Contains class mod_leapbuilder_course_map_form
 *
 * @package   mod_leapbuilder
 * @copyright 2017 Aleksey Kolobov
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Form for editing concept in leapbuilder
 *
 * @package   mod_leapbuilder
 * @copyright 2017 Aleksey Kolobov
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_leapbuilder_concept_create_form extends moodleform
{
    /**
     * Definition of the form
     */
    public function definition()
    {

        global $DB;

        $mform = &$this->_form;

        $newconcept = !isset($this->_customdata['conceptid']);

        $mform->addElement('html', '<script type="text/javascript" src="js/cytoscape.min.js"></script>');
        $mform->addElement('html', '<script type="text/javascript" src="js/dagre.min.js"></script>');
        $mform->addElement('html', '<script type="text/javascript" src="js/cytoscape-dagre.js"></script>');

        if (!$newconcept) {
            $concepts = $DB->get_records_select('leapbuilder_concepts', 'leapbuilderid = ?', [$this->_customdata['leapbuilderid']]);
            $relations = $DB->get_records_select('leapbuilder_relations', 'leapbuilderid = ?', [$this->_customdata['leapbuilderid']]);
            $concepttypes = $DB->get_records_select('leapbuilder_concepttypes', 'leapbuilderid = ?', [$this->_customdata['leapbuilderid']]);

            $conceptsscript = '';
            $conceptsscript .= '<script type="text/javascript">';
            $conceptsscript .= 'window.concepts = ' . json_encode(array_values($concepts)) . '; ';
            $conceptsscript .= 'window.relations = ' . json_encode(array_values($relations)) . '; ';
            $conceptsscript .= 'window.conceptTypes = ' . json_encode(array_values($concepttypes)) . '; ';
            $conceptsscript .= 'window.conceptsForHighlight = ' . json_encode(array($this->_customdata['conceptid'])) . '; ';
//            $conceptsscript .= 'window.relationsForHighlight = '.json_encode(array($this->_customdata['relationid'])).'; ';
            $conceptsscript .= '</script>';

            $mform->addElement('html', $conceptsscript);

            $mform->addElement('html', '<div class="row">');
            $mform->addElement('html', '<div class="col-xs-6">');
        }


        $mform->addElement('hidden', 'leapbuilderid');
        $mform->setType('leapbuilderid', PARAM_INT);
        $this->set_data(['leapbuilderid' => $this->_customdata['leapbuilderid']]);

        $mform->addElement('hidden', 'conceptid');
        $mform->setType('conceptid', PARAM_INT);
        $this->set_data(['conceptid' => $this->_customdata['conceptid']]);


        $mform->addElement('text', 'name', 'Name');
        $mform->addRule('name', get_string('required'), 'required');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'importance', 'Importance');
        $mform->addRule('importance', get_string('required'), 'required');
        $mform->setType('importance', PARAM_FLOAT);
        $mform->setDefault('importance', 1);

        $mform->addElement('text', 'time', 'Time');
        $mform->addRule('time', get_string('required'), 'required');
        $mform->setType('time', PARAM_FLOAT);
        $mform->setDefault('time', 1);

        $instancetypes = get_fast_modinfo($this->_customdata['courseid'])->get_instances();
        $selectsectionsvalues = array();
        $selectresourcevalues = array();

        $sections = get_fast_modinfo($this->_customdata['courseid'])->get_section_info_all();

        foreach ($sections as $key => $val) {
            if (!$val->name) continue;

            $selectkey = $key . '@section';
            $selectvalue = $val->name;
            $selectsectionsvalues[$selectkey] = $selectvalue;
        }

        foreach ($instancetypes as $keytype => $valtype) {

            foreach ($valtype as $keyinstance => $valinstance) {

                if (!in_array(mb_strtolower($valinstance->modname), ['resource', 'quiz'])) continue;
                if ($valinstance->deletioninprogress) continue;
                if (!$valinstance->modname) continue;

                $selectkey = $valinstance->id . '@' . $valinstance->modname;
                $selectvalue = $valinstance->name;
                $selectresourcevalues[$selectkey] = $selectvalue;
            }
        }

        $concepttypes = $DB->get_records_select('leapbuilder_concepttypes', 'leapbuilderid = ?', [$this->_customdata['leapbuilderid']]);
        $concepttypeids = array(null => 'Choose type');
        foreach ($concepttypes as $val) {
            $concepttypeids[$val->id] = $val->name;
        }

        $mform->addElement('select', 'concepttypeid', 'Concept type', $concepttypeids);
        $mform->setType('concepttypeid', PARAM_INT);

        $mform->addElement('hidden', 'resourceinfo', null, ['id' => 'id_resourceinfo']);
        $mform->setType('resourceinfo', PARAM_TEXT);

        $advancedselect = '';
        $advancedselect .= '<select onchange="(document.querySelector(\'input#id_name\') || {}).value = this.value ? this.options[this.selectedIndex].innerHTML : \'\'; document.querySelector(\'input#id_resourceinfo\').value = this.value" class="custom-select">';
        $advancedselect .= '<option value="">No resource</option>';

        if (count($selectsectionsvalues)) {
            $advancedselect .= '<optgroup label="Sections">';
            foreach ($selectsectionsvalues as $key => $val) {
                $advancedselect .= '<option value="'.$key.'">'.$val.'</option>';
            }
            $advancedselect .= ' </optgroup>';
        }

        if (count($selectresourcevalues)) {
            $advancedselect .= '<optgroup label="Resources">';
            foreach ($selectresourcevalues as $key => $val) {
                $advancedselect .= '<option value="'.$key.'">'.$val.'</option>';
            }
            $advancedselect .= ' </optgroup>';
        }

        $advancedselect .= '</select>';

        $mform->addElement('static', 'static', 'Resource', $advancedselect);

        $mform->addElement('text', 'comment', 'Commentary');
        $mform->setType('comment', PARAM_TEXT);

        $mform->addElement('submit', 'save', 'Save Concept');


        if (!$newconcept) {

            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-xs-6">');
            $mform->addElement('html', '<div id="cy-container">');
            $mform->addElement('html', '<div id="cy">');
            $mform->addElement('html', '</div>');
            $mform->addElement('html', '</div>');
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<script src="js/graph.js"></script>');
        }
    }

    function validation($data, $files)
    {

        global $DB;

        $errors = parent::validation($data, $files);

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required!';
        }

        if (empty($data['importance'])) {
            $errors['importance'] = 'Importance if required!';
        }

        if (empty($data['time'])) {
            $errors['time'] = 'TDL: Время на изучение является обязательным полем!';
        }

        $concept = $DB->get_record_select(
            'leapbuilder_concepts',
            $DB->sql_compare_text('name') . '= ? AND leapbuilderid = ? AND id <> ?',
            array($data['name'], $data['leapbuilderid'], $data['conceptid']));

        if ($concept) {
            $errors['name'] = 'Name is already used!';
        }

        return $errors;
    }
}


/**
 * Form for editing relation in leapbuilder
 *
 * @package   mod_leapbuilder
 * @copyright 2017 Aleksey Kolobov
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_leapbuilder_relation_create_form extends moodleform
{
    /**
     * Definition of the form
     */
    public function definition()
    {

        global $DB;

        $mform = &$this->_form;
        $newrelation = !isset($this->_customdata['relationid']);

        if (!$newrelation) {
            $mform->addElement('html', '<script type="text/javascript" src="js/cytoscape.min.js"></script>');
            $mform->addElement('html', '<script type="text/javascript" src="js/dagre.min.js"></script>');
            $mform->addElement('html', '<script type="text/javascript" src="js/cytoscape-dagre.js"></script>');

            $concepts = $DB->get_records_select('leapbuilder_concepts', 'leapbuilderid = ?', [$this->_customdata['leapbuilderid']]);
            $relations = $DB->get_records_select('leapbuilder_relations', 'leapbuilderid = ?', [$this->_customdata['leapbuilderid']]);
            $concepttypes = $DB->get_records_select('leapbuilder_concepttypes', 'leapbuilderid = ?', [$this->_customdata['leapbuilderid']]);

            $relationsscript = '';
            $relationsscript .= '<script type="text/javascript">';
            $relationsscript .= 'window.concepts = ' . json_encode(array_values($concepts)) . '; ';
            $relationsscript .= 'window.relations = ' . json_encode(array_values($relations)) . '; ';
            $relationsscript .= 'window.conceptTypes = ' . json_encode(array_values($concepttypes)) . '; ';
            $relationsscript .= 'window.relationsForHighlight = ' . json_encode(array($this->_customdata['relationid'])) . '; ';
            $relationsscript .= '</script>';

            $mform->addElement('html', $relationsscript);

            $mform->addElement('html', '<div class="row">');
            $mform->addElement('html', '<div class="col-xs-6">');
        }


        $mform->addElement('hidden', 'leapbuilderid');
        $mform->setType('leapbuilderid', PARAM_INT);
        $this->set_data(['leapbuilderid' => $this->_customdata['leapbuilderid']]);

        $mform->addElement('hidden', 'relationid');
        $mform->setType('relationid', PARAM_INT);
        $this->set_data(['relationid' => $this->_customdata['relationid']]);

        $concepts = $DB->get_records_select('leapbuilder_concepts', 'leapbuilderid = ? AND id is not null', [$this->_customdata['leapbuilderid']]);
        $conceptids = [null => 'Choose concept'];

        foreach ($concepts as $val) {
            $conceptids[$val->id] = $val->id . '.' . $val->name;
        }

        $mform->addElement('select', 'fromconceptid', 'Source Concept', $conceptids);
        $mform->setType('fromconceptid', PARAM_INT);
        $mform->addRule('fromconceptid', get_string('required'), 'required');

        $mform->addElement('select', 'toconceptid', 'Target Concept', $conceptids);
        $mform->setType('toconceptid', PARAM_INT);
        $mform->addRule('toconceptid', get_string('required'), 'required');

        $mform->addElement('text', 'influence', 'Influence');
        $mform->addRule('influence', get_string('required'), 'required');
        $mform->setType('influence', PARAM_FLOAT);
        $mform->setDefault('influence', 1);

        $mform->addElement('text', 'comment', 'Commentary');
        $mform->setType('comment', PARAM_TEXT);

        $mform->createElement('submit', 'save', 'Save relation');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'save', 'Save relation');
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);

        if (!$newrelation) {

            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<div class="col-xs-6">');
            $mform->addElement('html', '<div id="cy-container">');
            $mform->addElement('html', '<div id="cy">');
            $mform->addElement('html', '</div>');
            $mform->addElement('html', '</div>');
            $mform->addElement('html', '</div>');

            $mform->addElement('html', '<script src="js/graph.js"></script>');
        }
    }

    function validation($data, $files)
    {

        global $DB;

        $errors = parent::validation($data, $files);

        if (empty($data['influence'])) {
            $errors['influence'] = 'TDL: Влияние является обязательным полем!';
        }

        if (empty($data['toconceptid'])) {
            $errors['toconceptid'] = 'TDL: Источник является обязательным полем!';
        }

        if (empty($data['fromconceptid'])) {
            $errors['fromconceptid'] = 'TDL: Источник является обязательным полем!';
        }

        if (!empty($data['fromconceptid']) && !empty($data['toconceptid']) && $data['fromconceptid'] === $data['toconceptid']) {
            $errors['fromconceptid'] = $errors['toconceptid'] = 'TDL: нельзя создавать цикличные связи!';
        }

        return $errors;
    }
}


/**
 * Form for editing concept type in leapbuilder
 *
 * @package   mod_leapbuilder
 * @copyright 2017 Aleksey Kolobov
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_leapbuilder_concepttype_create_form extends moodleform
{
    /**
     * Definition of the form
     */
    public function definition()
    {

        global $DB;

        $mform = &$this->_form;

        $mform->addElement('hidden', 'leapbuilderid');
        $mform->setType('leapbuilderid', PARAM_INT);
        $this->set_data(['leapbuilderid' => $this->_customdata['leapbuilderid']]);

        $mform->addElement('hidden', 'concepttypeid');
        $mform->setType('concepttypeid', PARAM_INT);
        $this->set_data(['concepttypeid' => $this->_customdata['concepttypeid']]);

        $mform->addElement('text', 'name', 'Name');
        $mform->addRule('name', get_string('required'), 'required');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'importance', 'Importance');
        $mform->addRule('importance', get_string('required'), 'required');
        $mform->setType('importance', PARAM_FLOAT);
        $mform->setDefault('importance', 1);

        $mform->addElement('text', 'comment', 'Commentary');
        $mform->setType('comment', PARAM_TEXT);

        $mform->createElement('submit', 'save', 'Save Concept Type');

        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'save', 'Save Concept Type');
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    function validation($data, $files)
    {

        global $DB;

        $errors = parent::validation($data, $files);

        if (empty($data['importance'])) {
            $errors['importance'] = 'Importance is required!';
        }

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required!';
        }

        return $errors;
    }
}


/**
 * Form for editing model in leapbuilder
 *
 * @package   mod_leapbuilder
 * @copyright 2017 Aleksey Kolobov
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_leapbuilder_model_create_form extends moodleform
{
    /**
     * Definition of the form
     */
    public function definition()
    {

//        js_reset_all_caches();
//        theme_reset_all_caches();

        global $DB;

        $mform = &$this->_form;

        $mform->addElement('html', '<script type="text/javascript" src="js/cytoscape.min.js"></script>');
        $mform->addElement('html', '<script type="text/javascript" src="js/dagre.min.js"></script>');
        $mform->addElement('html', '<script type="text/javascript" src="js/cytoscape-dagre.js"></script>');

        $concepts = $DB->get_records_select('leapbuilder_concepts', 'leapbuilderid = ?', [$this->_customdata['leapbuilderid']]);
        $relations = $DB->get_records_select('leapbuilder_relations', 'leapbuilderid = ?', [$this->_customdata['leapbuilderid']]);
        $concepttypes = $DB->get_records_select('leapbuilder_concepttypes', 'leapbuilderid = ?', [$this->_customdata['leapbuilderid']]);

        $conceptsscript = '';
        $conceptsscript .= '<script type="text/javascript">';
        $conceptsscript .= 'window.concepts = ' . json_encode(array_values($concepts)) . '; ';
        $conceptsscript .= 'window.relations = ' . json_encode(array_values($relations)) . '; ';
        $conceptsscript .= 'window.conceptTypes = ' . json_encode(array_values($concepttypes)) . '; ';
        $conceptsscript .= '</script>';
        $mform->addElement('html', $conceptsscript);
        $mform->addElement('html', '<div id="model-alert-success" class="hidden alert alert-success"><strong>Model is optimized. </strong><span>Now you can save your model.</span></div>');
        $mform->addElement('html', '<div id="model-alert-error" class="hidden alert alert-danger"><strong>Error. </strong><span>Something is wrong.</span></div>');


        $mform->addElement('html', '<div class="row">');
        $mform->addElement('html', '<div id="model-overlay" class="hidden"><div class="card" id="model-overlay-infobox">Model is calculating...</div></div>');

        $mform->addElement('html', '<div class="col-xs-6">');

        $mform->addElement('hidden', 'leapbuilderid');
        $mform->setType('leapbuilderid', PARAM_INT);
        $this->set_data(['leapbuilderid' => $this->_customdata['leapbuilderid']]);


        $mform->addElement('hidden', 'modelid');
        $mform->setType('modelid', PARAM_INT);
        $this->set_data(['modelid' => $this->_customdata['modelid']]);

        $mform->addElement('hidden', 'payload');
        $mform->setType('payload', PARAM_TEXT);

        $mform->addElement('text', 'name', 'Name');
        $mform->setType('name', PARAM_TEXT);

        $mform->addElement('text', 'comment', 'Commentary');
        $mform->setType('comment', PARAM_TEXT);

        $mform->addElement('text', 'timerestriction', 'Time restriction');
        $mform->setType('timerestriction', PARAM_FLOAT);

        $mform->addElement('text', 'alpha', 'Alpha', 'model-dependency');
        $mform->setType('alpha', PARAM_FLOAT);
        $mform->setDefault('alpha', 1);

        $mform->addElement('text', 'beta', 'Beta', 'model-dependency');
        $mform->setType('beta', PARAM_FLOAT);
        $mform->setDefault('beta', 1);

        $mform->addElement('text', 'calc', 'Calculated Value', 'disabled');
        $mform->setType('calc', PARAM_FLOAT);


        $mform->addElement('checkbox', 'isactive', 'Active');
        $mform->setType('isactive', PARAM_BOOL);
        $mform->setDefault('isactive', true);


        // -----------------------------
        $table = new html_table();
        $table->attributes['class'] = 'generaltable AttemptsTable';

        $table->head = array();
        $table->head[] = 'ID';
        $table->head[] = 'Name';
        $table->head[] = 'Concept Type';
        $table->head[] = 'Importance';
        $table->head[] = 'Time';
        $table->head[] = 'Status';

        foreach ($concepts as $value) {
            $cells = array(
                new html_table_cell($value->id),
                new html_table_cell($value->name),
                new html_table_cell($value->concepttypeid ?? '-'),
                new html_table_cell($value->importance),
                new html_table_cell($value->time),
                new html_table_cell(html_writer::select(
                    array('free' => 'Free', 'include' => 'Include', 'exclude' => 'Exclude'),
                    null, 'free', null, ['id' => 'concept-' . $value->id, 'class' => 'concept-configuration'])
                ),

            );

            $table->data[] = new html_table_row($cells);
        }

        $mform->addElement('html', '<div style="height: 400px; overflow-y:scroll">');
        $mform->addElement('html', html_writer::table($table));
        $mform->addElement('html', '</div>');

        // -----------------------------
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('button', 'run_optimize', 'Recalculate Optimal');
        $buttonarray[] = $mform->createElement('submit', 'save', 'Save Model');
        $buttonarray[] = $mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->addElement('html', '</div>');


        $mform->addElement('html', '<div class="col-xs-6">');
        $mform->addElement('html', '<div id="cy-container">');
        $mform->addElement('html', '<div id="cy">');
        $mform->addElement('html', '</div>');
        $mform->addElement('html', '</div>');
        $mform->addElement('html', '</div>');
        $mform->addElement('html', '</div>');

        $mform->addElement('html', '<script src="js/graph.js"></script>');
        $mform->addElement('html', '<script src="js/lib.js"></script>');

    }

    function validation($data, $files)
    {

        global $DB;

        $errors = parent::validation($data, $files);

        if (empty($data['name'])) {
            $errors['name'] = 'Name is required!';
        }

        return $errors;
    }
}
