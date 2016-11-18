<?php

// Load dependencies provided by Composer
require_once __DIR__ . '/vendor/autoload.php';

// Supplements to sphido/event
function grab() {
    return array_pop(call_user_func_array('trigger', func_get_args()));
};
function pass() {
    return array_pop(call_user_func_array('trigger', func_get_args())) !== false;
};

// Load core components before modular components
require_once 'lib/pdb.php';
require_once 'lib/router.php';

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

// Router
Router::run();

// Shut down
trigger('shutdown');

?>
