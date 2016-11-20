<?php

class Twigger {
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
        self::$_twig = $twig;
        self::$_template = $twig->loadTemplate('layout.html');

        if (self::$_status !== 200) {
            http_response_code(self::$_status);
        };
        echo self::$_template->renderBlock('init', array('http_status' => self::$_status));
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

on('startup', 'Twigger::startup', 99);
on('shutdown', 'Twigger::shutdown', 1);
on('render', 'Twigger::render');
on('title', 'Twigger::title');
on('http_status', 'Twigger::status');

