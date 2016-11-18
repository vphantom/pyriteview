<?php

class User {
    public static function install() {
        global $db;
        echo "    Installing users... ";
        $db->exec("
            CREATE TABLE IF NOT EXISTS 'users' (
                id int,
                email varchar(255),
                password_hash varchar(255)
            )
            ");
        echo "    done!\n";
    }

    public static function login($email, $password) {
        // TODO:
        // - Lookup and authenticate
        // $hash_in_db = password_hash('password', PASSWORD_DEFAULT);
        // password_verify('password', $hash_in_db);
        // Retrieve the user as an associative array
        // return the user array if successful, null otherwise
    }
}

on('install', 'User::install');
on('login', 'User::login');

?>
