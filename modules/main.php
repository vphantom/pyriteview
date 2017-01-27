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

// Utility functions

/**
 * Clean up a string to make a permalink
 *
 * @param string $string String to clean up
 *
 * @return string Cleaned up string
 */
function makePermalink($string)
{
    return substr(filter('clean_filename', $string), 0, 64);
}

// Start-up definitions from our support modules
Issues::bootstrap();
Articles::bootstrap();

// Dashboard
on(
    'route/main',
    function () {
        global $PPHP;

        if (!$_SESSION['identified']) return trigger('http_status', 403);

        $historyFilter = array(
            'action' => array('login', 'created', 'activated', 'deactivated'),
            'order' => 'DESC',
            'max' => 12
        );
        if (!pass('can', 'create', 'user')) {
            $historyFilter['userId'] = $_SESSION['user']['id'];
        };
        $states = array();
        if (!pass('has_role', 'author')) {
            $states = $PPHP['config']['articles']['states_wip'];
        };
        trigger(
            'render',
            'dashboard.html',
            array(
                'recentHistory' => grab('history', $historyFilter),
                'articles' => grab(
                    'articles',
                    array(
                        'byStatus' => true,
                        'states' => $states
                    )
                )
            )
        );
    }
);
