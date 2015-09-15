<?php

class block_assignments extends block_base {

    public function init() {
        $this->title = get_string('assignments', 'block_assignments');
    }

    public function get_content() {
        // error_reporting(E_ALL);
		
		global $CFG, $DB, $USER, $PAGE;
		$CFG->cachejs = false;
		$PAGE->requires->yui_module('moodle-block_assignments-assignments', 'M.block_assignments.assignments.init');
		$PAGE->requires->string_for_js('hide', 'block_assignments');
		$PAGE->requires->string_for_js('more', 'block_assignments');
		
        if ($this->content !== null)
            return $this->content;
        
        $this->content = new stdClass;
		if($USER->id == null)
			return $this->content;
		$this->content->text = '';
	
		$this->content->text .= html_writer::start_tag('div', array('class' => 'row-fluid'));
		$this->content->text .= html_writer::start_tag('div', array('class' => 'span6'));
		
		$this->content->text .= html_writer::start_tag('table', array('id' => 'graded-assign', 'class' => 'table table-responsive table-striped table-hover table-bordered'));		
		$th_name = html_writer::tag('th', get_string('graded', 'block_assignments'), array('colspan' => '2'));
		$this->content->text .= html_writer::tag('thead', $th_name);
		
		$assign_grades = $DB->get_records('assign_grades', array('userid' => $USER->id));
		$graded_assignments = array();
		$assignment_paths = array();
		
		if(empty($assign_grades))
			$this->content->text .= no_data_row(2);
		else {
			usort($assign_grades, 'graded_more_recent');
			
			$rowCounter = 0;
			$overflow = false;
			
			foreach($assign_grades as $entry) {
				$rowCounter++;
				
				$assignment = $DB->get_record('assign', array('id' => $entry->assignment));
				array_push($graded_assignments, $assignment->id);
				
				$course = $DB->get_record('course', array('id' => $assignment->course));
				if(course_too_old($course))
					continue;
				
				if(!in_array($course->id, array_keys($assignment_paths)))
					$assignment_paths = $assignment_paths + get_course_modules($course);

				$displayNone = null;
				if($rowCounter > 5) {
					$displayNone = array('style' => 'display: none;');
					$overflow = true;
				}
				
				$link = html_writer::tag('a', "[".$course->fullname."] ".$assignment->name, array('href' => $assignment_paths[$course->id][$assignment->id]->out()));
				$name = html_writer::tag('td', $link, null);
				$style = array('style' => 'text-align: right;');
				$grade = html_writer::tag('td', number_format($entry->grade, 1)." / ".number_format($assignment->grade, 1), $style);
				$this->content->text .= html_writer::tag('tr', $name.$grade, $displayNone);
			}
		}
		if($overflow)
			$this->content->text .= append_show_all_button(2);
		
		$this->content->text .= html_writer::end_tag('table');
		
		$this->content->text .= html_writer::end_tag('div');
		$this->content->text .= html_writer::start_tag('div', array('class' => 'span6'));
		
		$this->content->text .= html_writer::start_tag('table', array('id' => 'open-assign', 'class' => 'table table-responsive table-striped table-hover table-bordered'));
		$th_name = html_writer::tag('th', get_string('open_assignments', 'block_assignments'), array('colspan' => '2'));
		$this->content->text .= html_writer::tag('thead', $th_name);

		$courses = enrol_get_my_courses();
		$assignments = array();
		foreach($courses as $course) {
			if(course_too_old($course))
				continue;
			$assignments = array_merge($assignments, $DB->get_records('assign', array('course' => $course->id)));
		}
		if(empty($assignments))
			$this->content->text .= no_data_row(2);
		else {		
			usort($assignments, 'due_more_recent');	
			
			$counter = 0;
			$rowCounter = 0;
			$overflow = false;
			
			foreach($assignments as $assignment) {
				$counter++;
				
				$course = $DB->get_record('course', array('id' => $assignment->course));				
				if(!in_array($course->id, array_keys($assignment_paths)))
					$assignment_paths = $assignment_paths + get_course_modules($course);
				
				if(!in_array($assignment->id, $graded_assignments)) {
					$date_due = date_create();
					date_timestamp_set($date_due, $assignment->duedate);
					$date_now = date_create('NOW');
					$due_in = date_diff($date_now, $date_due);
					
					$due = $due_in->format('%R%a');
					if($due < +0) {
						if($counter == count($assignments))
							$this->content->text .= no_data_row(2);
						continue;
					}
					else if($due == +0) {
						$due = $due_in->format('%H:%I');
						if($due == +1)
							$due = substr($due, 1)." ".get_string('hour', 'block_assignments');
						else
							$due = substr($due, 1)." ".get_string('hours', 'block_assignments');
					}
					else if($due == +1)
						$due = substr($due, 1)." ".get_string('day', 'block_assignments');
					else
						$due = substr($due, 1)." ".get_string('days', 'block_assignments');
					$due = get_string('until', 'block_assignments')." ".$due;
						
					
					$rowCounter++;
					$course = $DB->get_record('course', array('id' => $assignment->course));
					
					$displayNone = null;
					if($rowCounter > 5) {
						$displayNone = array('style' => 'display: none;');
						$overflow = true;
					}
				
					$link = html_writer::tag('a', "[".$course->fullname."] ".$assignment->name, array('href' => $assignment_paths[$course->id][$assignment->id]->out()));
					$name = html_writer::tag('td', $link, null);
					$due = html_writer::tag('td', $due, $style);
					$this->content->text .= html_writer::tag('tr', $name.$due, $displayNone);
				}
				else
					if($rowCounter == 0 AND $counter == count($assignments))
						$this->content->text .= no_data_row(2);
			}
		}
		if($overflow)
			$this->content->text .= append_show_all_button(2);
		
		$this->content->text .= html_writer::end_tag('table');
		
		$this->content->text .= html_writer::end_tag('div');
		$this->content->text .= html_writer::end_tag('div');
		
		if($overflow)
			$_SESSION['block_assignments_content'] = $this->content->text;
		
        return $this->content;
    }

}

function append_show_all_button($colspan) {
	$link = html_writer::tag('a', '<b>'.get_string('more', 'block_assignments').'</b>', array('class' => 'btn-show-all', 'href' => new moodle_url('/blocks/assignments/view.php')));
	$showBtn = html_writer::tag('td', $link, array('colspan' => $colspan, 'style' => 'background-color: white;'));
	return html_writer::tag('tr', $showBtn, array('class' => 'more'));	
}

function get_course_modules($course) {
	$assignment_paths = array();
	$cms = get_fast_modinfo($course)->get_cms();
	foreach($cms as $cm) {
		if ($cm->is_user_access_restricted_by_capability())
			continue;
		
		if($cm->modname == 'assign') {
			if(array_key_exists($course->id, $assignment_paths)) {
				$assignment_paths[$course->id] = $assignment_paths[$course->id] + array($cm->instance => $cm->url);
			}
			else {
				$assignment_paths[$course->id] = array($cm->instance => $cm->url);
			}
		}
	}
	return $assignment_paths;
}

function graded_more_recent($a, $b) {
	if($a->timemodified == $b->timemodified)
		return 0;
	return ($a->timemodified > $b->timemodified) ? -1 : 1;
}

function due_more_recent($a, $b) {
	if($a->duedate == $b->duedate)
		return 0;
	return ($a->duedate < $b->duedate) ? -1 : 1;
}

function no_data_row($colspan) {
	$td = html_writer::tag('td', get_string('no_assignments', 'block_assignments'), array('colspan' => $colspan));
	return html_writer::tag('tr', $td, null);
}

function course_too_old($course) {
	$course_start = date_create();
	date_timestamp_set($course_start, $course->startdate);
	$date_now = date_create('NOW');
	$diff = date_diff($date_now, $course_start);
	$diff = $diff->format('%m');
	return $diff > +7;
}