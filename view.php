<?php

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
