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

// Start-up definitions from our support modules
Issues::bootstrap();
Articles::bootstrap();

// Dashboard
on(
    'route/main',
    function () {
        if (!$_SESSION['identified']) return trigger('http_status', 403);

        $historyFilter = array(
            'action' => array('login', 'created'),
            'order' => 'DESC',
            'max' => 12
        );
        if (!pass('can', 'create', 'user')) {
            $historyFilter['userId'] = $_SESSION['user']['id'];
        };
        trigger(
            'render',
            'dashboard.html',
            array(
                'recentHistory' => grab('history', $historyFilter)
            )
        );
    }
);
