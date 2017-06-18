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
 * @package   mod_leapbuilder
 * @copyright 2017 Aleksey Kolobov
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Saves a new instance of the leapbuilder into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $leapbuilder Submitted data from the form in mod_form.php
 * @param mod_leapbuilder_mod_form $mform The form instance itself (if needed)
 * @return int The id of the newly inserted leapbuilder record
 */
function leapbuilder_add_instance(stdClass $leapbuilder, mod_leapbuilder_mod_form $mform = null) {
    global $DB;
    $leapbuilder->timemodified = time();
    $leapbuilder->timecreated = time();
    // You may have to add extra stuff in here.
    $leapbuilder->id = $DB->insert_record('leapbuilder', $leapbuilder);
    return $leapbuilder->id;
}

function leapbuilder_update_instance($quiz, $mform) {
}



/**
 * Exception for reporting error in Reader module
 */
class reader_exception extends moodle_exception {
    /**
     * Constructor
     * @param string $debuginfo some detailed information
     */
    function __construct($debuginfo=null) {
        parent::__construct('error', 'leapbuilder', '', null, $debuginfo);
    }
}
