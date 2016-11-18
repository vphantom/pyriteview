<?php

class Router {
    private static $_base = null;
    private static $_PATH = array();

    public static function startup() {
        self::$_PATH = explode('/', $_SERVER['PATH_INFO']);
        if (self::$_PATH[0] === '') {
            array_shift(self::$_PATH);
        };

        if (isset(self::$_PATH[1]) && listeners('route/' . self::$_PATH[0] . '+' . self::$_PATH[1])) {
            self::$_base = array_shift(self::$_PATH) . '+' . array_shift(self::$_PATH);
        } elseif (isset(self::$_PATH[0])) {
            if (listeners('route/' . self::$_PATH[0])) {
                self::$_base = array_shift(self::$_PATH);
            } else {
                trigger('http_status', 404);
            };
        } else {
            self::$_base = 'main';
        };
    }

    public static function run() {
        if (self::$_base !== null  &&  !pass('route/' . self::$_base, self::$_PATH)) {
            trigger('http_status', 500);
        };
    }
}

on('startup', 'Router::startup', 50);

?>
