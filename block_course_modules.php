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
 * Privacy Subsystem implementation for block_course_modules.
 *
 * @package    block_course_modules
 * @copyright  2022 Swamy <swamy.gurugari@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();
require_once($CFG->libdir . '/filelib.php');

class block_course_modules extends block_list {
    public function init() {
        $this->title = get_string('pluginname', 'block_course_modules');
    }

    public function get_content() {
        global $CFG, $DB, $OUTPUT, $USER;
        $userid = $USER->id;
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        $this->content->items = array();
        $this->content->icons = array();
        $this->content->footer = '';

        $course = $this->page->course;
        require_once($CFG->dirroot.'/course/lib.php');

        $modinfo = get_fast_modinfo($course);
        $modfullnames = array();
        $archetypes = array();
        foreach ($modinfo->cms as $cm) {
            // Exclude activities that aren't visible or have no view link (e.g. label). Account for folder being displayed inline.
            if (!$cm->uservisible || (!$cm->has_view() && strcmp($cm->modname, 'folder') !== 0)) {
                continue;
            }
            if (array_key_exists($cm->modname, $modfullnames)) {
                continue;
            }
            if (!array_key_exists($cm->modname, $archetypes)) {
                $archetypes[$cm->modname] = plugin_supports('mod', $cm->modname, FEATURE_MOD_ARCHETYPE, MOD_ARCHETYPE_OTHER);
            }
            if ($archetypes[$cm->modname] == MOD_ARCHETYPE_RESOURCE) {
                if (!array_key_exists('resources', $modfullnames)) {
                    $modfullnames['resources'] = get_string('resources');
                }
            } else {
                $modfullnames[$cm->modname] = $cm->modplural;
            }
            $cmd = get_fast_modinfo($course, $userid)->instances[$cm->modname][$cm->instance];
            $cmid = $cmd->id;
            $createddate = date('d-M-y', $cm->added);
            $compstatus = $cm->completion;
            $cmname = $cm->name;
            $cmmodname = $cm->modname;
            if ($compstatus == 1) {
                $completionstatus = '<b style="color:green;">Completed</b>';
            } else {
                $completionstatus = '<b style="color:red;">Not Completed</b>';
            }
            $activityname = $cmid.'-'.$cmname.'-'.$createddate.'-'.$completionstatus;
            $this->content->items[] = '<a href="'.$CFG->wwwroot.'/mod/'.$cmmodname.'/view.php?id='.$cmid.'">'.$activityname.'</a>';
        }

        core_collator::asort($modfullnames);
        return $this->content;
    }

    /**
     * Returns the role that best describes this blocks contents.
     *
     * This returns 'navigation' as the blocks contents is a list of links to activities and resources.
     *
     * @return string 'navigation'
     */
    public function get_aria_role() {
        return 'navigation';
    }

    public function applicable_formats() {
        return array('all' => true, 'mod' => false, 'my' => false, 'admin' => false,
                     'tag' => false);
    }
}


