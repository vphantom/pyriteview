<?php

ini_set('session.gc_maxlifetime', 12 * 60 * 60);
ini_set('session.cookie_lifetime', 12 * 60 * 60);
ini_set('session.cookie_httponly', true);
session_start();

require_once __DIR__ . '/vendor/autoload.php';

$twig = new Twig_Environment(
    new Twig_Loader_Filesystem(__DIR__ . '/templates'),
    array(
        'cache' => __DIR__ . '/var/twig_cache',
        'autoescape' => true,
    )
);

session_write_close();

?>
