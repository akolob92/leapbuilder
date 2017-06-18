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
 * Internal library of functions for leapbuilder module.
 *
 * All the leapbuilder specific functions, needed to implement the module
 * logic, should go here. Never include this file from your lib.php!
 *
 * @package   leapbuilder
 * @copyright 2017 Aleksey Kolobov
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/** Include required files */
require_once($CFG->dirroot.'/mod/leapbuilder/lib.php');

class mod_leapbuilder {

    /** @var stdclass course module record */
    public $cm;

    /** @var stdclass course record */
    public $course;

    /** @var stdclass context object */
    public $context;

    /** @var array of attempts */
    public $attempts;

    /**
     * Initializes the leapbuilder API instance using the data from DB
     *
     * Makes deep copy of all passed records properties. Replaces integer $course attribute
     * with a full database record (course should not be stored in instances table anyway).
     *
     * The method is "private" to prevent it being called directly. To create a new
     * instance of this class please use the self::create() method (see below).
     *
     * @param stdclass $dbrecord leapbuilder instance data from the {leapbuilder} table
     * @param stdclass $cm       Course module record as returned by {@link get_coursemodule_from_id()}
     * @param stdclass $course   Course record from {course} table
     * @param stdclass $context  The context of the leapbuilder instance
     */
    private function __construct($dbrecord=null, $cm=null, $course=null, $context=null) {
        global $COURSE;

        $this->fullname = 'Leap Builder';

        if ($dbrecord) {
            foreach ($dbrecord as $field => $value) {
                $this->$field = $value;
            }
        }

        if ($cm) {
            $this->cm = $cm;
        }

        if ($course) {
            $this->course = $course;
        } else {
            $this->course = $COURSE;
        }

        if ($context) {
            $this->context = $context;
        } else if ($cm) {
            $this->context = self::context(CONTEXT_MODULE, $cm->id);
        } else {
            $this->context = self::context(CONTEXT_COURSE, $this->course->id);
        }

        $this->time = time();
    }

    ////////////////////////////////////////////////////////////////////////////////
    // Static methods                                                             //
    ////////////////////////////////////////////////////////////////////////////////

    /**
     * Creates a new leapbuilder object
     *
     * @param stdclass $dbrecord a row from the leapbuilder table
     * @param stdclass $cm a row from the course_modules table
     * @param stdclass $course a row from the course table
     * @return leapbuilder the new leapbuilder object
     */
    static public function create($dbrecord, $cm, $course, $context=null, $attempt=null) {
        return new mod_leapbuilder($dbrecord, $cm, $course, $context, $attempt);
    }


    /**
     * context
     *
     * a wrapper method to offer consistent API to get contexts
     * in Moodle 2.0 and 2.1, we use context() function
     * in Moodle >= 2.2, we use static context_xxx::instance() method
     *
     * @param integer $contextlevel
     * @param integer $instanceid (optional, default=0)
     * @param int $strictness (optional, default=0 i.e. IGNORE_MISSING)
     * @return required context
     * @todo Finish documenting this function
     */
    public static function context($contextlevel, $instanceid=0, $strictness=0) {
        if (class_exists('context_helper')) {
            // use call_user_func() to prevent syntax error in PHP 5.2.x
            // return $classname::instance($instanceid, $strictness);
            $class = context_helper::get_class_for_level($contextlevel);
            return call_user_func(array($class, 'instance'), $instanceid, $strictness);
        } else {
            return get_context_instance($contextlevel, $instanceid);
        }
    }
}
