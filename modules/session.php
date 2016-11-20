<?php

class Session {
    public static function startup() {
        // Start a PHP-handled session and bind it to the current remote IP address as
        // a precaution per https://www.owasp.org/index.php/PHP_Security_Cheat_Sheet
        ini_set('session.gc_maxlifetime', 12 * 60 * 60);
        ini_set('session.cookie_lifetime', 12 * 60 * 60);
        ini_set('session.cookie_httponly', true);
        session_start();
        if (isset($_SESSION['REMOTE_ADDR'])) {
            if ($_SESSION['REMOTE_ADDR'] !== $_SERVER['REMOTE_ADDR']) {
                self::reset();
            };
        } else {
            self::init();
        };
    }

    public static function shutdown() {
        session_write_close();
    }

    private static function init() {
        $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['USER_INFO'] = null;
        $_SESSION['USER_OK'] = false;
    }

    public static function reset() {
        session_unset();
        self::init();
    }

    public static function login($email, $password) {
        if (is_array($user = grab('authenticate', $email, $password))) {
            self::reset();
            $_SESSION['USER_INFO'] = $user;
            $_SESSION['USER_OK'] = true;
            trigger('newuser');
            return true;
        } else {
            return false;
        };
    }
}

on('startup', 'Session::startup', 1);
on('shutdown', 'Session::shutdown', 99);
on('login', 'Session::login', 1);

