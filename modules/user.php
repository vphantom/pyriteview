<?php

class User {
    public static function install() {
        global $db;
        echo "    Installing users... ";
        $db->exec("
            CREATE TABLE IF NOT EXISTS 'users' (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                email        VARCHAR(255),
                passwordHash VARCHAR(255)
            )
        ");
        echo "    done!\n";
    }

    public static function login($email, $password) {
        global $db;

        // TODO:
        // - Lookup and authenticate
        // $hash_in_db = password_hash('password', PASSWORD_DEFAULT);
        // password_verify('password', $hash_in_db);
        // Retrieve the user as an associative array
        // return the user array if successful, null otherwise
        return $db->selectSingleArray("SELECT * FROM users WHERE id='1'");
    }
}

on('install', 'User::install');
on('authenticate', 'User::login');

?>
