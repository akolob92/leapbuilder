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
 * Prints a particular instance of leapbuilder
 *
 * You can have a rather longer description of the file as well,
 * if you like, and it can span multiple lines.
 *
 * @package    mod_leapbuilder
 * @copyright  2016 Your Name <your@email.address>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
require_once(dirname(dirname(dirname(__FILE__))) . '/config.php');
require_once(dirname(__FILE__) . '/lib.php');

require_once($CFG->dirroot . '/mod/leapbuilder/leapbuilder_forms.php');
require_once($CFG->dirroot . '/mod/leapbuilder/locallib.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$n = optional_param('n', 0, PARAM_INT);  // ... leapbuilder instance ID - it should be named as the first character of the module.

if ($id) {
    $cm = get_coursemodule_from_id('leapbuilder', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $leapbuilder = $DB->get_record('leapbuilder', array('id' => $cm->instance), '*', MUST_EXIST);
} else if ($n) {
    $leapbuilder = $DB->get_record('leapbuilder', array('id' => $n), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $leapbuilder->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('leapbuilder', $leapbuilder->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);


/*
 * Other things you may want to set - remove if not needed.
 * $PAGE->set_cacheable(false);
 * $PAGE->set_focuscontrol('some-html-id');
 * $PAGE->add_body_class('leapbuilder-'.$somevar);
 */

// create full object to represent this reader actvity
$leapbuilder = mod_leapbuilder::create($leapbuilder, $cm, $course);

// Print the page header.
$PAGE->set_url('/mod/leapbuilder/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($leapbuilder->fullname));
$PAGE->set_heading(format_string($course->fullname));

$output = $PAGE->get_renderer('mod_leapbuilder');
$output->init($leapbuilder);

echo $output->header();
echo $output->tabs();

$activeModel = $DB->get_record_select('leapbuilder_models', 'leapbuilderid = ? AND isactive = 1', [$leapbuilder->id]);

echo '<button class="btn btn-primary disabled">Export</button> ';
echo '<button class="btn btn-primary disabled">Import</button> ';


if (!$activeModel) {
    echo $output->box('You have no active course model!');
} else {
    echo '<script type="text/javascript" src="js/cytoscape.min.js"></script>';
    echo '<script type="text/javascript" src="js/dagre.min.js"></script>';
    echo '<script type="text/javascript" src="js/cytoscape-dagre.js"></script>';

    echo '<script type="text/javascript" src="js/cytoscape-dagre.js"></script>';

    $concepts = $DB->get_records_select('leapbuilder_concepts', 'leapbuilderid = ?', [$leapbuilder->id]);
    $relations = $DB->get_records_select('leapbuilder_relations', 'leapbuilderid = ?', [$leapbuilder->id]);
    $concepttypes = $DB->get_records_select('leapbuilder_concepttypes', 'leapbuilderid = ?', [$leapbuilder->id]);

    echo '<script type="text/javascript">';
    echo 'window.payload = ' . $activeModel->payload . '; ';
    echo 'window.concepts = ' . json_encode(array_values($concepts)) . '; ';
    echo 'window.relations = ' . json_encode(array_values($relations)) . '; ';
    echo 'window.conceptTypes = ' . json_encode(array_values($concepttypes)) . '; ';
    echo '</script>';

    echo '<div class="row"><div class="col-xs-12 m-a-1"><h2>Active model</h2></div></div>';

    echo '<div class="row">';
    echo '<div class="col-xs-6">';

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
                null, 'free', null, ['id' => 'concept-' . $value->id, 'disabled' => true, 'class' => 'concept-configuration'])
            ),

        );

        $table->data[] = new html_table_row($cells);
    }

    echo '<div style="height: 750px; overflow-y:scroll">';
    echo html_writer::table($table);
    echo '</div>';
    echo '</div>';


    echo '<div class="col-xs-6">';
    echo '<div id="cy-container">';
    echo '<div id="cy">';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    echo '<script src="js/graph.js"></script>';
    echo '<script src="js/dashboard.js"></script>';
}

echo $output->footer();