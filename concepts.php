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
 * This script displays a particular page of a leapbuilder attempt that is in progress.
 *
 * @package   mod_leapbuilder
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/mod/leapbuilder/locallib.php');

require_once($CFG->dirroot . '/mod/leapbuilder/leapbuilder_forms.php');

$id = optional_param('id', 0, PARAM_INT); // Course_module ID, or
$scheme_id = optional_param('scheme_id', 0, PARAM_INT); // Course_module ID, or
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

$leapbuilder = mod_leapbuilder::create($leapbuilder, $cm, $course);

require_login($course, true, $cm);

// Print the page header.
$PAGE->set_url('/mod/leapbuilder/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($leapbuilder->fullname));
$PAGE->set_heading(format_string($course->fullname));

$PAGE->set_url('/mod/leapbuilder/concepts.php');

$output = $PAGE->get_renderer('mod_leapbuilder');
$output->init($leapbuilder);

echo $output->header();
echo $output->tabs();

echo html_writer::link(new moodle_url('/mod/leapbuilder/editconcept.php', ['id' => $id]), 'Create Concept', ['class' => 'btn btn-success m-b-1']);

if (!$concepts = $DB->get_records_select('leapbuilder_concepts', 'leapbuilderid = ?', array($leapbuilder->id))) {
    echo '<br />You have no created concepts!';
} else {
    $table = new html_table();
    $table->attributes['class'] = 'generaltable AttemptsTable';

    $table->head = array();
    $table->head[] = 'ID';
    $table->head[] = 'Name';
    $table->head[] = 'Concept Type';
    $table->head[] = 'Importance';
    $table->head[] = 'Time';
    $table->head[] = 'Resource';
    $table->head[] = 'Created At';
    $table->head[] = 'Action';
//    $table->align = array('left', 'left', 'left');

    foreach ($concepts as $value) {
        $editlink = html_writer::link(new moodle_url('/mod/leapbuilder/editconcept.php', ['id' => $id, 'conceptid' => $value->id]), 'Edit', ['class' => 'btn btn-warning m-r-1']);
        $dellink = html_writer::link(new moodle_url('/mod/leapbuilder/editconcept.php', ['id' => $id, 'conceptid' => $value->id, 'remove' => true]), 'Delete', ['class' => 'btn btn-danger']);
        $cells = array(
            new html_table_cell($value->id),
            new html_table_cell($value->name),
            $value->concepttypeid ? new html_table_cell(html_writer::link(new moodle_url('/mod/leapbuilder/editconcepttype.php', ['id' => $id, 'concepttypeid' => $value->concepttypeid]), $value->concepttypeid)) : '-',
            new html_table_cell($value->importance),
            new html_table_cell($value->time),
            new html_table_cell(generate_link($leapbuilder, $value)),
            new html_table_cell(date('Y-m-d H:i:s', $value->timecreated)),
            new html_table_cell($editlink . $dellink),
        );

        $table->data[] = new html_table_row($cells);
    }

    echo html_writer::table($table);
}


echo $output->footer();

function generate_link($leapbuilder, $value) {
    if ($value->resourceid && $value->resourcetype) {
        if ($value->resourcetype === 'section') {
            $sectionindex = null;
            $sections = get_fast_modinfo($leapbuilder->course->id)->get_section_info_all();
            foreach($sections as $key => $val) {
                if ($val->id == $value->resourceid) {
                    $sectionindex = $key;
                    break;
                }
            }
            return html_writer::link(new moodle_url('/course/view.php', ['id' => $leapbuilder->course->id], 'section-'.$sectionindex), $value->resourcetype);
        } else {
            return html_writer::link(new moodle_url('/mod/'.$value->resourcetype.'/view.php', ['id' => $value->resourceid]), $value->resourcetype);
        }
    } else {
        return '-';
    }
}
