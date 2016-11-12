<?php

namespace PyriteView\Session;

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
            session_unset();
            $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        };
    } else {
        $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
    };
}, 1);

// Session shutdown
//
on('shutdown', function () {
    session_write_close();
}, 99);

?>
