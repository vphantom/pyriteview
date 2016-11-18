<?php

class AuditTrail {
    public static function install() {
        global $db;
        echo "    Installing log... ";
        $db->exec("
            CREATE TABLE IF NOT EXISTS 'transactions' (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                timestamp TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                userId INTEGER NOT NULL DEFAULT '0',
                objectType VARCHAR(64) DEFAULT NULL,
                objectId INTEGER DEFAULT NULL,
                action VARCHAR(64) NOT NULL DEFAULT '',
                fieldName VARCHAR(64) DEFAULT NULL,
                oldValue VARCHAR(255) DEFAULT NULL,
                newValue VARCHAR(255) DEFAULT NULL
            )
            ");
        echo "    done!\n";
    }

    public static function add($args) {
        global $db;
        $user = User::whoami();
        $db->exec("
            INSERT INTO transactions
            (userId, objectType, objectId, action, fieldName, oldValue, newValue)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ", array(
            $user['id'],
            (isset($args['objectType']) ? $args['objectType'] : null),
            (isset($args['objectId'])   ? $args['objectId'] : null),
            (isset($args['action'])     ? $args['action'] : null),
            (isset($args['fieldName'])  ? $args['fieldName'] : null),
            (isset($args['oldValue'])   ? $args['oldValue'] : null),
            (isset($args['newValue'])   ? $args['newValue'] : null)
        ));
    }
}

on('install', 'AuditTrail::install');
on('log', 'AuditTrail::add');

?>
