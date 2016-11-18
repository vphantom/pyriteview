<?php

class User {
    private static $_user;

    public static function install() {
        global $db;
        echo "    Installing users... ";
        $db->exec("
            CREATE TABLE IF NOT EXISTS 'users' (
                id int,
                email varchar(255)
            )
            ");
        echo "    done!\n";
    }

    public static function whoami() {
        return self::$_user;
    }

    public static function login($email, $password) {
        // TODO:
        // - Lookup and authenticate
        // $hash = password_hash('password', PASSWORD_DEFAULT);
        // password_verify('password', $hash);
        // - Populate self::$_user
        // return true if successful
    }
}

on('install', 'User::install');
on('login', 'User::login');
on('whoami', 'User::whoami');

?>
