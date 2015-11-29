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
 * @package   block_assignments
 * @copyright 2015, Senan Sharhan, Philip Raschke
 * @license   GNU General Public License <http://www.gnu.org/licenses/>
 */

/**
 * Class block_assignments
 * Main class of block plugin
 * Lists all graded and all open assignments of a user.
 */
class block_assignments extends block_base {

    public function init() {
        $this->title = get_string('assignments', 'block_assignments');
    }

    /**
     * Enables reading from configuration
     */
    public function has_config() {
        return true;
    }

    public function get_content() {
        global $CFG, $DB, $USER, $PAGE;
        $PAGE->requires->yui_module('moodle-block_assignments-assignments', 'M.block_assignments.assignments.init');
        $PAGE->requires->string_for_js('hide', 'block_assignments');
        $PAGE->requires->string_for_js('more', 'block_assignments');

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = new stdClass;
        if ($USER->id == null) {
            return $this->content;
        }
        $this->content->text = '';

        $courses = enrol_get_my_courses();
        if (count($courses) > 0) {
            $courseids = array();
            $assignments = array();
            $gradedassignments = array();
            $assignmentpaths = array();
            foreach ($courses as $course) {
                if (block_assignments_course_too_old($course)) {
                    continue;
                }
                $assignmentpaths = $assignmentpaths + block_assignments_get_course_modules($course);
                array_push($courseids, $course->id);
            }
            list($insql, $inparams) = $DB->get_in_or_equal($courseids);
            $sql = 'SELECT *
                    FROM {assign}
                    WHERE course '.$insql;
            $assignments = $DB->get_records_sql($sql, $inparams);

            $sql = 'SELECT *
                    FROM {assign_grades}
                    WHERE userid = ?';
            $assigngrades = $DB->get_records_sql($sql, array($USER->id));
        } else {
            $assigngrades = array();
            $assignments = array();
        }

        $linkinhead = '';
        $gradedassignmentsoutput = '';
        $overflow = false;
        $style = array('style' => 'text-align: right;');

        if (empty($assigngrades)) {
            $gradedassignmentsoutput .= block_assignments_no_data_row(2);
        } else {
            usort($assigngrades, 'block_assignments_graded_more_recent');
            $counter = 0;
            $rowcounter = 0;

            foreach ($assigngrades as $entry) {
                $counter++;

                if (!array_key_exists($entry->assignment, $assignments)) {
                    continue;
                }
                $assignment = $assignments[$entry->assignment];
                array_push($gradedassignments, $assignment->id);
                $course = $courses[$assignment->course];

                $rowcounter++;
                $displaynone = null;
                if ($rowcounter > 5) {
                    $displaynone = array('style' => 'display: none;');
                    $overflow = true;
                }

                $href = $assignmentpaths[$course->id][$assignment->id]->out();
                $linktext = "[".$course->fullname."] ".$assignment->name;
                $link = html_writer::tag('a', $linktext, array('href' => $href));
                $name = html_writer::tag('td', $link, null);
                $grade = html_writer::tag('td', number_format($entry->grade, 1)." / ".number_format($assignment->grade, 1), $style);
                $gradedassignmentsoutput .= html_writer::tag('tr', $name.$grade, $displaynone);
            }
            if ($overflow) {
                $linktext = '<b>'.get_string('more', 'block_assignments').'</b>';
                $href = new moodle_url('/blocks/assignments/view.php');
                $class = 'btn-show-all btn-show-all-right';
                $linkinhead = html_writer::tag('a', $linktext, array('class' => $class, 'href' => $href));
            }
        }

        $this->content->text .= html_writer::start_tag('div', array('class' => 'row-fluid'));
        $this->content->text .= html_writer::start_tag('div', array('class' => 'span6'));
        $class = 'table table-responsive table-striped table-hover table-bordered';
        $this->content->text .= html_writer::start_tag('table', array('id' => 'graded-assign', 'class' => $class));
        $thname = html_writer::tag('th', get_string('graded', 'block_assignments').$linkinhead, array('colspan' => '2'));
        $this->content->text .= html_writer::tag('thead', $thname);
        $this->content->text .= $gradedassignmentsoutput;
        $this->content->text .= html_writer::end_tag('table');

        $linkinhead = '';
        $openassignmentsoutput = '';
        $overflow = false;

        if (empty($assignments)) {
            $openassignmentsoutput .= block_assignments_no_data_row(2);
        } else {
            usort($assignments, 'block_assignments_due_more_recent');

            $counter = 0;
            $rowcounter = 0;
            foreach ($assignments as $assignment) {

                $counter++;
                $course = $courses[$assignment->course];

                if (!in_array($assignment->id, $gradedassignments)) {
                    $datedue = date_create();
                    date_timestamp_set($datedue, $assignment->duedate);
                    $datenow = date_create('NOW');
                    $duein = date_diff($datenow, $datedue);

                    $due = $duein->format('%R%a');
                    if ($due < + 0) {
                        if ($counter == count($assignments)) {
                            $openassignmentsoutput .= block_assignments_no_data_row(2);
                        }
                        continue;
                    } else if ($due == + 0) {
                        $due = $duein->format('%H:%I');
                        if ($due == + 1) {
                            $due = substr($due, 1)." ".get_string('hour', 'block_assignments');
                        } else {
                            $due = substr($due, 1)." ".get_string('hours', 'block_assignments');
                        }
                    } else if ($due == + 1) {
                        $due = substr($due, 1)." ".get_string('day', 'block_assignments');
                    } else {
                        $due = substr($due, 1)." ".get_string('days', 'block_assignments');
                    }
                    $due = get_string('until', 'block_assignments')." ".$due;

                    $rowcounter++;
                    $course = $courses[$assignment->course];

                    $displaynone = null;
                    if ($rowcounter > 5) {
                        $displaynone = array('style' => 'display: none;');
                        $overflow = true;
                    }
                    $href = $assignmentpaths[$course->id][$assignment->id]->out();
                    $linktext = "[".$course->fullname."] ".$assignment->name;
                    $link = html_writer::tag('a', $linktext, array('href' => $href));
                    $name = html_writer::tag('td', $link, null);
                    $due = html_writer::tag('td', $due, $style);
                    $openassignmentsoutput .= html_writer::tag('tr', $name.$due, $displaynone);
                } else {
                    if ($rowcounter == 0 AND $counter == count($assignments)) {
                        $openassignmentsoutput .= block_assignments_no_data_row(2);
                    }
                }
            }
            if ($overflow) {
                $linktext = '<b>'.get_string('more', 'block_assignments').'</b>';
                $class = 'btn-show-all btn-show-all-right';
                $href = new moodle_url('/blocks/assignments/view.php');
                $linkinhead = html_writer::tag('a', $linktext, array('class' => $class, 'href' => $href));
            }
        }

        $this->content->text .= html_writer::end_tag('div');
        $this->content->text .= html_writer::start_tag('div', array('class' => 'span6'));
        $class = 'table table-responsive table-striped table-hover table-bordered';
        $this->content->text .= html_writer::start_tag('table', array('id' => 'open-assign', 'class' => $class));
        $thname = html_writer::tag('th', get_string('open_assignments', 'block_assignments').$linkinhead, array('colspan' => '2'));
        $this->content->text .= html_writer::tag('thead', $thname);
        $this->content->text .= $openassignmentsoutput;
        $this->content->text .= html_writer::end_tag('table');
        $this->content->text .= html_writer::end_tag('div');
        $this->content->text .= html_writer::end_tag('div');

        if ($overflow) {
            $_SESSION['block_assignments_content'] = $this->content->text;
        }

        return $this->content;
    }

}

/**
 * Used to retrieve the paths to the assignments
 * @param $course a course
 * @return two dimensional array - array[courseid][assignmentid]
 */
function block_assignments_get_course_modules($course) {
    $assignmentpaths = array();
    $cms = get_fast_modinfo($course)->get_cms();
    foreach ($cms as $cm) {
        if ($cm->is_user_access_restricted_by_capability()) {
            continue;
        }

        if ($cm->modname == 'assign') {
            if (array_key_exists($course->id, $assignmentpaths)) {
                $assignmentpaths[$course->id] = $assignmentpaths[$course->id] + array($cm->instance => $cm->url);
            } else {
                $assignmentpaths[$course->id] = array($cm->instance => $cm->url);
            }
        }
    }
    return $assignmentpaths;
}

/**
 * This function decides which of two assignments was graded more recent
 * @param $a, $b - two assignments
 * @return int -1 or 1, respectively
 */
function block_assignments_graded_more_recent($a, $b) {
    if ($a->timemodified == $b->timemodified) {
        return 0;
    }
    return ($a->timemodified > $b->timemodified) ? -1 : 1;
}

/**
 * This function decides which of two assignments is due more recent
 * @param $a, $b - two assignments
 * @return int -1 or 1, respectively
 */
function block_assignments_due_more_recent($a, $b) {
    if ($a->duedate == $b->duedate) {
        return 0;
    }
    return ($a->duedate < $b->duedate) ? -1 : 1;
}

/**
 * This function displays a row hinting that no data is available
 * @param $colspan - number of cols the row has to span
 * @return html - a row
 */
function block_assignments_no_data_row($colspan) {
    $td = html_writer::tag('td', get_string('no_assignments', 'block_assignments'), array('colspan' => $colspan));
    return html_writer::tag('tr', $td, null);
}

/**
 * This function decides whether a course is too old
 * Therefore a configuration value is used to decide the maximum age of a course
 * @param $course - the respective course
 * @return bool true or false
 */
function block_assignments_course_too_old($course) {
    $max = get_config('block_assignments', 'current_months') + 1;
    $coursestart = date_create();
    date_timestamp_set($coursestart, $course->startdate);
    $datenow = date_create('NOW');
    $diff = date_diff($datenow, $coursestart);
    $diff = $diff->format('%m');
    return $diff > + $max;
}