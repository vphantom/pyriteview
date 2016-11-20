<?php

/**
 * Session
 *
 * PHP version 5
 *
 * @category  Library
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2016 Stéphane Lavergne
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU AGPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */

/**
 * Session class
 *
 * @category  Library
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2016 Stéphane Lavergne
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU AGPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */

class Session
{

    /**
     * Discover and initialize session
     *
     * @return null
     */
    public static function startup()
    {
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
            self::_init();
        };
    }

    /**
     * Clean up and save session
     *
     * @return null
     */
    public static function shutdown()
    {
        session_write_close();
    }

    /**
     * Populate session with fresh starting values
     *
     * @return null
     */
    private static function _init()
    {
        $_SESSION['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['USER_INFO'] = null;
        $_SESSION['USER_OK'] = false;
    }

    /**
     * Wipe out and re-initialize current session
     *
     * @return null
     */
    public static function reset()
    {
        session_unset();
        self::_init();
    }

    /**
     * Attempt to attach a user to current session
     *
     * @param string $email    E-mail address
     * @param string $password Plain text password (supplied via web form)
     *
     * @return bool Whether the operation succeeded
     */
    public static function login($email, $password)
    {
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

