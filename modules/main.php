<?php

/**
 * Main
 *
 * Main routes for our application
 *
 * PHP version 5
 *
 * @category  Application
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2016 Stéphane Lavergne
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU Affero GPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */

// Create database tables if necessary
on(
    'install',
    function () {
        global $PPHP;
        $db = $PPHP['db'];

        // Issues
        //
        $db->exec(
            "
            CREATE TABLE IF NOT EXISTS 'issues' (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                publication DATE NOT NULL DEFAULT '1980-01-01',
                number      VARCHAR(16),
                title       VARCHAR(255)
            )
            "
        );
        $db->exec(
            "
            CREATE UNIQUE INDEX IF NOT EXISTS 'idx_issues_publication'
            ON issues (publication)
            "
        );
        $db->exec(
            "
            CREATE UNIQUE INDEX IF NOT EXISTS 'idx_issues_number'
            ON issues (number)
            "
        );

        // Articles
        //
        $db->exec(
            "
            CREATE TABLE IF NOT EXISTS 'articles' (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                issueId     INTEGER NOT NULL DEFAULT '0',
                wordCount   INTEGER NOT NULL DEFAULT '0',
                title       VARCHAR(255),
                keywords    TEXT NOT NULL DEFAULT '',
                abstract    TEXT NOT NULL DEFAULT ''
            )
            "
        );
    }
);

on(
    'route/main',
    function () {
        if (!$_SESSION['identified']) return trigger('http_status', 403);
        // TODO: Your application's authenticated interface starts here.
        echo "<p>Dashboard will go here</p>\n";
    }
);

on(
    'route/admin',
    function () {
        if (!$_SESSION['identified']) return trigger('http_status', 403);

        $recentHistory = grab(
            'history',
            array(
                'action' => array('login', 'created'),
                'order' => 'DESC',
                'max' => 12
            )
        );
        trigger(
            'render',
            'admin.html',
            array(
                'recentHistory' => $recentHistory
            )
        );
    }
);
