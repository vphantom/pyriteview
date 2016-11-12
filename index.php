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

// Start up
trigger('startup');

// Shut down
trigger('shutdown');

?>
