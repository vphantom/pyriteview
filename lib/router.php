<?php

class Router {
    private static $bases = array();
    private static $base = null;

    public static function register($newbase) {
        if (!in_array($newbase, self::$bases)) {
            self::$bases[] = $newbase;
        };
    }

    public static function startup() {
        $PATH = explode('/', $_SERVER['PATH_INFO']);
        if ($PATH[0] === '') {
            array_shift($PATH);
        };

        if (isset($PATH[1]) && in_array($PATH[0] . '+' . $PATH[1], self::$bases)) {
            self::$base = array_shift($PATH) . '+' . array_shift($PATH);
        } elseif (isset($PATH[0]) && in_array($PATH[0], self::$bases)) {
            self::$base = array_shift($PATH);
        } else {
            trigger('http_status', 404);
        };
    }

    public static function run() {
        if (self::$base !== null  &&  !pass('route/' . self::$base, $PATH)) {
            trigger('http_status', 500);
        };
    }
}

on('startup', 'Router::startup', 50);

?>
