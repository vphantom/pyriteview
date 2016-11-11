<?php

ini_set('session.save_handler',   'memcached');
ini_set('session.gc_maxlifetime', 24 * 60 * 60);

require_once __DIR__ . '/vendor/autoload.php';

$twig = new Twig_Environment(
    new Twig_Loader_Filesystem(__DIR__ . '/templates'),
    array(
        'cache' => __DIR__ . '/var/twig_cache',
        'autoescape' => true,
    )
);

?>
