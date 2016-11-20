<?php

/**
 * AuditTrail
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
 * AuditTrail class
 *
 * @category  Library
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2016 Stéphane Lavergne
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU AGPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */
class AuditTrail
{

    /**
     * Create database tables if necessary
     *
     * @return null
     */
    public static function install()
    {
        global $db;
        echo "    Installing log... ";
        $db->exec(
            "
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
            "
        );
        echo "    done!\n";
    }

    /**
     * Add a new transaction to the audit trail
     *
     * The following keys must be defined in $args:
     *
     * action     - Type of action performed
     *
     * The following keys may be defined in $args:
     *
     * objectType - Class of object this applies to
     * objectId   - Specific instance acted upon
     * fieldName  - Name of specific field affected
     * oldValue   - Previous value for affected field
     * newValue   - New value for affected field
     *
     * Note that if fieldName is specified, at least newValue should be
     * defined as well.
     *
     * @param array $args Details of the transaction
     *
     * @return null
     */
    public static function add($args)
    {
        global $db;
        $user = User::whoami();
        $db->exec(
            "
            INSERT INTO transactions
            (userId, objectType, objectId, action, fieldName, oldValue, newValue)
            VALUES (?, ?, ?, ?, ?, ?, ?)
            ",
            array(
                $user['id'],
                (isset($args['objectType']) ? $args['objectType'] : null),
                (isset($args['objectId'])   ? $args['objectId'] : null),
                (isset($args['action'])     ? $args['action'] : null),
                (isset($args['fieldName'])  ? $args['fieldName'] : null),
                (isset($args['oldValue'])   ? $args['oldValue'] : null),
                (isset($args['newValue'])   ? $args['newValue'] : null)
            )
        );
    }
}

on('install', 'AuditTrail::install');
on('log', 'AuditTrail::add');

