<?php

class Templating {
    private static $twig;
    private static $title = '';
    private static $status = 200;

    public static function startup() {
        $twig = new \Twig_Environment(
            new \Twig_Loader_Filesystem(__DIR__ . '/../templates'),
            array(
        //        'cache' => __DIR__ . '/var/twig_cache',
                'autoescape' => true,
            )
        );
        /*
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
         */
        self::$twig = $twig;

        if (self::$status !== 200) {
            http_response_code(self::$status);
        };
        echo $twig->render('head.html');
        flush();
        ob_start();
    }

    public static function shutdown() {
        $name = 'body.html';
        if (self::$status !== 200) {
            $name = 'status' . self::$status . '.html';
        };
        $body = self::$twig->render($name, array('title' => self::$title, 'body' => filter('body', ob_get_contents())));
        ob_end_clean();
        echo $body;
    }

    public static function status($code) {
        self::$status = $code;
    }

    public static function title($prepend, $sep = ' - ') {
        self::$title = $prepend . (self::$title !== '' ? ($sep . self::$title) : '');
    }

    public static function render($name, $args) {
        echo self::$twig->render($name, $args);
    }
}

on('startup', 'Templating::startup', 99);
on('shutdown', 'Templating::shutdown', 1);
on('render', 'Templating::render');
on('title', 'Templating::title');
on('http_status', 'Templating::status');

?>
