<?php

$settings->add(new admin_setting_heading('headerconfig', get_string('headerconfig', 'block_assignments'), get_string('descconfig', 'block_assignments')));

$settings->add(new admin_setting_configselect(
    'block_assignments/current_months',
    get_string('labelcurrentmonths', 'block_assignments'),
    get_string('desccurrentmonths', 'block_assignments'),
    3,
    array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12)
));