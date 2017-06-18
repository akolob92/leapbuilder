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

require_login($course, true, $cm);

// Get submitted parameters.
//$schemeid = required_param('schemeid', PARAM_INT);

$PAGE->set_url('/mod/leapbuilder/build.php', array('id' => $cm->id, 'scheme_id' => $scheme_id));

$output = $PAGE->get_renderer('mod_leapbuilder');

echo $output->header();
echo $output->heading('Successful choice of scheme');

$scheme_create_form = new mod_leapbuilder_scheme_create_form('', array('id' => $id));

//Form processing and displaying is done here
if ($scheme_create_form->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
} else if ($fromform = $scheme_create_form->get_data()) {
    if ($scheme = $DB->get_record_select('leapbuilder_schemes', $DB->sql_compare_text('name') . '= ? AND courseid = ?', array($fromform->name, $id))) {
        echo $output->box(get_string('error:scheme_name_unique', 'mod_leapbuilder'), 'generalbox', 'error');
    } else {
        $scheme = new stdClass();
        $scheme->name = $fromform->name;
        $scheme->courseid = $id;
        $scheme->timecreated = time();
        $scheme->timemodified = time();

        var_dump($id);
        var_dump($scheme);
        $DB->insert_record('leapbuilder_schemes', $scheme);
    }
    //In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
    // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.

    //displays the form
    $scheme_create_form->display();
}


echo $output->footer();