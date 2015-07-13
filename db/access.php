<?php

$capabilities = array(

    'block/assignments:myaddinstance' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array( 'user' => CAP_ALLOW ),
        'clonepermissionsfrom' => 'moodle/my:manageblocks'
    ),

    'block/assignments:addinstance' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => array(
            'user' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/site:manageblocks'
    ),

);