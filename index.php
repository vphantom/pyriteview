<?php

// Load dependencies provided by Composer
require_once __DIR__ . '/vendor/autoload.php';

// Supplement to sphido/event
function pass() {
    return array_pop(call_user_func_array('trigger', func_get_args())) !== false;
};

// Load modular components
foreach (glob(__DIR__ . '/modules/*.php') as $fname) {
    include_once $fname;
};

// Database
$GLOBALS['db'] = new PDB('sqlite:' . __DIR__ . '/var/main.db');

// From the command line means install mode
if (php_sapi_name() === 'cli') {
    trigger('install');
    return;
};

// Start up
trigger('startup');

// Shut down
trigger('shutdown');

?>
