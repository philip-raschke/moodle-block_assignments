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
require_once('../../config.php');

require_login();
if (isguestuser()) {
    print_error('guestsarenotallowed');
}

global $PAGE, $OUTPUT;

$blocktitle = get_string('assignments', 'block_assignments');
$pagetitle = get_string('assignments', 'block_assignments');

$content = $_SESSION['block_assignments_content'];
$content = str_replace('display: none', '', $content);
$content = preg_replace("/<tr class=\"more\">(.*?)<\/tr>/", '', $content);

// Set up page appearance.
$PAGE->set_url($url);
$PAGE->set_pagelayout('standard');
$PAGE->set_heading($blocktitle);

// Add navigational breadcrumbs to page.
$settingsnode = $PAGE->settingsnav->add($blocktitle);
$editnode = $settingsnode->add($pagetitle, $url);
$editnode->make_active();

// Print the page.
echo $OUTPUT->header();
echo $OUTPUT->heading($pagetitle);
echo $OUTPUT->box_start();
echo $content;
echo $OUTPUT->box_end();
echo $OUTPUT->footer();
