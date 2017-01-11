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
