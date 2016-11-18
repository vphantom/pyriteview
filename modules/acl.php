<?php

class ACL {
    public static function install() {
        global $db;
        echo "    Installing ACL...";
        $db->exec("
            CREATE TABLE IF NOT EXISTS 'acl_roles' (
                role       VARCHAR(64) NOT NULL DEFAULT '',
                action     VARCHAR(64) DEFAULT NULL,
                objectType VARCHAR(64) DEFAULT NULL,
                objectId   INTEGER     DEFAULT NULL
            )
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS 'acl_users' (
                userId     INTEGER     NOT NULL DEFAULT '0',
                action     VARCHAR(64) DEFAULT NULL,
                objectType VARCHAR(64) DEFAULT NULL,
                objectId   INTEGER     DEFAULT NULL
            )
        ");
        $db->exec("
            CREATE TABLE IF NOT EXISTS 'users_roles' (
                userId     INTEGER     NOT NULL DEFAULT '0',
                role       VARCHAR(64) NOT NULL DEFAULT ''
            )
        ");
        echo "    done!\n";
    }

    private static function _load($flat) {
        if (is_array($flat) && !is_array($_SESSION['ACL_INFO']) && count($flat) > 0) {
            $_SESSION['ACL_INFO'] = array();
        };
        foreach ($flat as $row) {
            if (!array_key_exists($row['action'], $_SESSION['ACL_INFO'])) {
                $_SESSION['ACL_INFO'][$row['action']] = Array();
            }
            if (!array_key_exists($row['objectType'], $_SESSION['ACL_INFO'][$row['action']])) {
                $_SESSION['ACL_INFO'][$row['action']][$row['objectType']] = Array();
            };
            if (!in_array($row['objectId'], $_SESSION['ACL_INFO'][$row['action']][$row['objectType']])) {
                $_SESSION['ACL_INFO'][$row['action']][$row['objectType']][] = $row['objectId'];
            };
        };
    }

    public static function reload() {
        global $db;
        $_SESSION['ACL_INFO'] = null;
        if (!array_key_exists('id', $_SESSION['USER_INFO'])) {
            return;
        };
        $userId = $_SESSION['USER_INFO']['id'];

        $flat = $db->selectArray("
            SELECT action, objectType, objectId FROM acl_users WHERE userId=?
        ",
        array($userId));
        self::_load($flat);

        $flat = $db->selectArray("
            SELECT action, objectType, objectId FROM users_roles INNER JOIN acl_roles ON acl_roles.role=users_roles.role WHERE users_roles.userId=?
        ",
        array($userId));
        self::_load($flat);
    }

    public static function can($action, $object = null, $objectId = null) {
        if (!is_array($_SESSION['ACL_INFO'])) {
            return false;
        };

        $acl = $_SESSION['ACL_INFO'];

        if (array_key_exists(NULL, $acl)) {
            return true;
        };
        if (array_key_exists($action, $acl)) {
            $acl2 = $acl[$action];
            if (array_key_exists(NULL, $acl2)) {
                return true;
            };
            if (array_key_exists($object, $acl2)) {
                $acl3 = $acl2[$object];
                if (in_array(NULL, $acl3) || in_array($objectId, $acl3)) {
                    return true;
                };
            };
        };

        return false;
    }
}

on('install', 'ACL::install');
on('newuser', 'ACL::reload');

?>
