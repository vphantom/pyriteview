<?php

class Session {
    public static function reset() {
        session_unset();
        $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
    }

    public static function login($email, $password) {
        if (User::login($email, $password)) {
            self::reset();
            $_SESSION['email'] = $email;
            $_SESSION['password'] = $password;
            trigger('login');
        } else {
            return false;
        };
    }
}

// Session startup
//
// Start a PHP-handled session and bind it to the current remote IP address as
// a precaution per https://www.owasp.org/index.php/PHP_Security_Cheat_Sheet
on('startup', function () {
    ini_set('session.gc_maxlifetime', 12 * 60 * 60);
    ini_set('session.cookie_lifetime', 12 * 60 * 60);
    ini_set('session.cookie_httponly', true);
    session_start();
    if (isset($_SESSION['REMOTE_ADDR'])) {
        if ($_SESSION['REMOTE_ADDR'] !== $_SERVER['REMOTE_ADDR']) {
            Session::reset();
        };
    } else {
        $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
    };
    if (isset($_SESSION['email']) && isset($_SESSION['password'])) {
        if (!User::login($_SESSION['email'], $_SESSION['password'])) {
            Session::reset();
            trigger('login');
        };
    };
}, 1);

// Session shutdown
//
on('shutdown', function () {
    session_write_close();
}, 99);

?>
