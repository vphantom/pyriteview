<?php

class Templating {
    private static $_twig;
    private static $_title = '';
    private static $_status = 200;
    private static $_template;
    private static $_safeBody = '';

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
        self::$_twig = $twig;
        self::$_template = $twig->loadTemplate('layout.html');

        if (self::$_status !== 200) {
            http_response_code(self::$_status);
        };
        echo self::$_template->renderBlock('init', array());
        flush();
        ob_start();
    }

    public static function shutdown() {
        $headName = 'head';
        $footName = 'foot';
        if (self::$_status !== 200) {
            $headName .= '_error';
            $footName .= '_error';
        };
        $body = ob_get_contents();
        ob_end_clean();
        echo self::$_template->renderBlock($headName, array('http_status' => self::$_status, 'title' => self::$_title));
        echo self::$_safeBody;
        echo self::$_template->renderBlock($footName, array('http_status' => self::$_status, 'body' => $body));
    }

    public static function status($code) {
        self::$_status = $code;
    }

    public static function title($prepend, $sep = ' - ') {
        self::$_title = $prepend . (self::$_title !== '' ? ($sep . self::$_title) : '');
    }

    public static function render($name, $args) {
        self::$_safeBody .= self::$_twig->render($name, $args);
    }
}

on('startup', 'Templating::startup', 99);
on('shutdown', 'Templating::shutdown', 1);
on('render', 'Templating::render');
on('title', 'Templating::title');
on('http_status', 'Templating::status');

?>
