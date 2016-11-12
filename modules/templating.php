<?php

namespace PyriteView\Templating;

$twig = new \Twig_Environment(
    new \Twig_Loader_Filesystem(__DIR__ . '/../templates'),
    array(
//        'cache' => __DIR__ . '/var/twig_cache',
        'autoescape' => true,
    )
);
$twig->addFunction(new \Twig_SimpleFunction('trigger', function () {
    ob_start();
    call_user_func_array('trigger', func_get_args());
    $result = ob_get_contents();
    ob_end_clean();
    return $result;
}));
$twig->addFunction(new \Twig_SimpleFunction('filter', function () {
    return call_user_func_array('filter', func_get_args());
}));
$twig->addFunction(new \Twig_SimpleFunction('pass', function () {
    return array_pop(call_user_func_array('trigger', func_get_args())) !== false;
}));

on('startup', function () {
    global $twig;
    echo $twig->render('head.html');
    flush();
    ob_start();
}, 99);

// Page title
$title = 'PyriteView';
on('title', function($prepend) {
    global $title;
    $title = $prepend . ' - ' . $title;
});

on('shutdown', function () {
    global $twig, $title;
    $body = $twig->render('body.html', array('title' => $title, 'body' => filter('body', ob_get_contents())));
    ob_end_clean();
    echo $body;
}, 1);

on('render', function ($name, $args) {
    global $twig;
    echo $twig->render($name, $args);
});

?>
