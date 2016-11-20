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

