<?php

// Load dependencies provided by Composer
require_once __DIR__ . '/vendor/autoload.php';

// Load local library of classes, event handlers, etc.
$plugdir = __DIR__ . '/lib/';
if ($dir = opendir($plugdir)) {
    while (($fname = readdir($dir)) !== false) {
        if (is_file($plugdir . $fname)) {
            include_once "{$plugdir}{$fname}";
        };
    };
    closedir($dir);
};

// Start up
trigger('startup');

// Shut down
trigger('shutdown');

?>
