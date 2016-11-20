<?php

/**
 * User
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
 * User class
 *
 * @category  Library
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2016 Stéphane Lavergne
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU AGPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */
class User
{

    /**
     * Create database tables if necessary
     *
     * @return null
     */
    public static function install()
    {
        global $db;
        echo "    Installing users... ";
        $db->exec(
            "
            CREATE TABLE IF NOT EXISTS 'users' (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                email        VARCHAR(255),
                passwordHash VARCHAR(255)
            )
            "
        );
        echo "    done!\n";
    }

    /**
     * Load and authenticate a user
     *
     * @param string $email    E-mail address
     * @param string $password Plain text password (supplied via web form)
     *
     * @return array|bool Associative array for the user or false if not authorized
     */
    public static function login($email, $password)
    {
        global $db;

        if ($user = $db->selectSingleArray("SELECT * FROM users WHERE email=?", array($email))) {
            if (password_verify($password, $user['passwordHash'])) {
                return $user;
            };
        };

        return false;
    }
}

on('install', 'User::install');
on('authenticate', 'User::login');

