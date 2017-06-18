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
$model_id = optional_param('scheme_id', 0, PARAM_INT); // Course_module ID, or
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

$PAGE->requires->js('/mod/leapbuilder/js/jquery-3.2.1.min.js', true);

// Print the page header.
$PAGE->set_url('/mod/leapbuilder/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($leapbuilder->fullname));
$PAGE->set_heading(format_string($course->fullname));

$PAGE->set_url('/mod/leapbuilder/models.php');

$output = $PAGE->get_renderer('mod_leapbuilder');
$output->init($leapbuilder);

$remove = optional_param('remove', false, PARAM_BOOL);
$modelid = optional_param('modelid', null, PARAM_INT);

$model_create_form = new mod_leapbuilder_model_create_form('editmodel.php?id=' . $id . '&modelid=' . $modelid, [
    'leapbuilderid' => $leapbuilder->id,
    'courseid' => $course->id,
    'modelid' => $modelid,
]);

//Form processing and displaying is done here
if ($model_create_form->is_cancelled()) {
    //Handle form cancel operation, if cancel button is present on form
    redirect(new moodle_url('/mod/leapbuilder/models.php', ['id' => $id]));
} else if ($fromform = $model_create_form->get_data()) {
    if (!$modelid) {
        $model = new stdClass();
        $model->name = $fromform->name;
        $model->leapbuilderid = $fromform->leapbuilderid;
        $model->timerestriction = $fromform->timerestriction;
        $model->payload = $fromform->payload;
        $model->comment = $fromform->comment;
        $model->isactive = $fromform->isactive;
        $model->timecreated = time();
        $model->timemodified = time();

        $DB->insert_record('leapbuilder_models', $model);
    } else {
        $model = $DB->get_record('leapbuilder_models', ['id' => $modelid]);
        $model->name = $fromform->name;
        $model->timerestriction = $fromform->timerestriction;
        $model->payload = $fromform->payload;
        $model->comment = $fromform->comment;
        $model->isactive = $fromform->isactive;
        $model->timemodified = time();

        $DB->update_record('leapbuilder_models', $model);
    }

    redirect(new moodle_url('/mod/leapbuilder/models.php', ['id' => $id]));

    //In this case you process validated data. $mform->get_data() returns data posted in form.
} else {
    // this branch is executed if the form is submitted but the data doesn't validate and the form should be redisplayed
    // or on the first display of the form.

    if ($modelid) {
        if ($remove) {
            $DB->delete_records('leapbuilder_models', ['id' => $modelid]);
            redirect(new moodle_url('/mod/leapbuilder/models.php', ['id' => $id]));
        } else {
            //displays the form
            echo $output->header();
            echo $output->heading('Edit Model');
            $model = $DB->get_record('leapbuilder_models', ['id' => $modelid]);

            $model_create_form->set_data($model);
            $model_create_form->display();
        }
    } else {
        //displays the form
        echo $output->header();
        echo $output->heading('Create Model');
        $model_create_form->display();
    }
}


echo $output->footer();