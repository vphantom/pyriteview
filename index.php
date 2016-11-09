<?php

require_once __DIR__ . '/vendor/autoload.php';

$twig = new Twig_Environment(
    new Twig_Loader_Filesystem(__DIR__ . '/templates'),
    array(
        'cache' => __DIR__ . '/var/twig_cache',
        'autoescape' => true,
    )
);

?>
