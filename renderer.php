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
 * mod/leapbuilder/renderer.php
 *
 * @package    mod
 * @subpackage leapbuilder
 * @copyright  2017 Aleksey Kolobov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 */

/** Prevent direct access to this script */
defined('MOODLE_INTERNAL') || die();

/**
 * mod_leapbuilder_renderer
 *
 * @copyright  2017 Aleksey Kolobov
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @since      Moodle 2.0
 * @package    mod
 * @subpackage leapbuilder
 */
class mod_leapbuilder_renderer extends plugin_renderer_base
{

    /**#@+
     * tab ids
     *
     * @var integer
     */
    const TAB_VIEW = 1;
    const TAB_CONCEPTS = 2;
    const TAB_RELATIONS = 3;
    const TAB_MODELS = 4;
    const TAB_CONCEPT_TYPES = 5;
    /**#@-*/

    /** object to represent associated leapbuilder activity */
    public $leapbuilder = null;

    /** array of allow modes for this page (mode is second row of tabs) */
    public $modes = array();

    /**
     * init
     *
     * @param xxx $leapbuilder
     */
    public function init($leapbuilder)
    {
        $this->leapbuilder = $leapbuilder;
    }

    ///////////////////////////////////////////
    // format tabs
    ///////////////////////////////////////////

    /**
     * tabs
     *
     * @return string HTML output to display navigation tabs
     */
    public function tabs($selected = null, $inactive = null, $activated = null)
    {

        $tab = $this->get_tab();
        $tabs = $this->get_tabs();

        if (class_exists('tabtree')) {
            // Moodle >= 2.6
            return $this->tabtree($tabs, $tab);
        } else {
            // Moodle <= 2.5
            $this->set_active_tabs($tabs, $tab);
            $html = convert_tree_to_html($tabs);
            return html_writer::tag('div', $html, array('class' => 'tabtree')) .
                html_writer::tag('div', '', array('class' => 'clearer'));
        }

    }

    /**
     * set_active_tabs
     *
     * @param array $tabs (passed by reference)
     * @param integer currently selected $tab id
     * @return boolean, TRUE if any tabs or child tabs were selected, FALSE otherwise
     */
    public function set_active_tabs(&$tabs, $tab)
    {
        $result = false;
        foreach (array_keys($tabs) as $t) {

            // selected
            if ($tabs[$t]->id == $tab) {
                $tabs[$t]->selected = true;
            } else {
                $tabs[$t]->selected = false;
            }

            // active
            if (isset($tabs[$t]->subtree) && $this->set_active_tabs($tabs[$t]->subtree, $tab)) {
                $tabs[$t]->active = true;
            } else {
                $tabs[$t]->active = false;
            }

            // inactive (make sure it is set)
            if (empty($tabs[$t]->inactive)) {
                $tabs[$t]->inactive = false;
            }

            // result
            $result = ($result || $tabs[$t]->selected || $tabs[$t]->active);
        }
        return $result;
    }

    /**
     * get_tab
     *
     * @return integer tab id
     */
    public function get_tab()
    {
        global $PAGE;

        $parts = explode('/', $PAGE->url->get_path());

        switch ($parts[count($parts) - 1]) {
            case 'models.php':
                $tab = self::TAB_MODELS;
                break;
            case 'concepts.php':
                $tab = self::TAB_CONCEPTS;
                break;
            case 'relations.php':
                $tab = self::TAB_RELATIONS;
                break;
            case 'concepttypes.php':
                $tab = self::TAB_CONCEPT_TYPES;
                break;
            case 'view.php':
                $tab = self::TAB_VIEW;
                break;
        }
        if (!$tab) {
            $tab = $this->get_default_tab();
        }
        return $tab;
    }

    /**
     * get_my_tab
     *
     * @return integer tab id
     */
    public function get_my_tab()
    {
        return self::TAB_VIEW;
    }

    /**
     * get_default_tab
     *
     * @return integer tab id
     */
    public function get_default_tab()
    {
        return self::TAB_VIEW;
    }

    /**
     * get_tabs
     *
     * @return string HTML output to display navigation tabs
     */
    public function get_tabs()
    {
        $tabs = array();

        if (isset($this->leapbuilder)) {
            if (isset($this->leapbuilder->cm)) {
                $cmid = $this->leapbuilder->cm->id;
            } else {
                $cmid = 0; // unusual !!
            }

//            if ($this->leapbuilder->can_viewconcepts()) {
            $tab = self::TAB_VIEW;
            $url = new moodle_url('/mod/leapbuilder/view.php', array('id' => $cmid));
            $tabs[$tab] = new tabobject($tab, $url, get_string('dashboard', 'mod_leapbuilder'));
//            }
//            if ($this->leapbuilder->can_viewconcepts()) {
            $tab = self::TAB_CONCEPTS;
            $url = new moodle_url('/mod/leapbuilder/concepts.php', array('id' => $cmid));
            $tabs[$tab] = new tabobject($tab, $url, get_string('concepts', 'mod_leapbuilder'));

            $tab = self::TAB_CONCEPT_TYPES;
            $url = new moodle_url('/mod/leapbuilder/concepttypes.php', array('id' => $cmid));
            $tabs[$tab] = new tabobject($tab, $url, get_string('concepttypes', 'mod_leapbuilder'));
//            }
//            if ($this->leapbuilder->can_viewrelations()) {
            $tab = self::TAB_RELATIONS;
            $url = new moodle_url('/mod/leapbuilder/relations.php', array('id' => $cmid));
            $tabs[$tab] = new tabobject($tab, $url, get_string('relations', 'mod_leapbuilder'));
//            }
//            if ($this->leapbuilder->can_viewmodels()) {
            $tab = self::TAB_MODELS;
            $url = new moodle_url('/mod/leapbuilder/models.php', array('id' => $cmid));
            $tabs[$tab] = new tabobject($tab, $url, get_string('models', 'mod_leapbuilder'));
//            }
        }
        return $tabs;
    }

/**
 * attach_tabs_subtree
 *
 * @return string HTML output to display navigation tabs
 */
public
function attach_tabs_subtree($tabs, $id, $subtree)
{
    foreach (array_keys($tabs) as $i) {
        if ($tabs[$i]->id == $id) {
            $tabs[$i]->subtree = $subtree;
        }
    }
    return $tabs;
}
}
