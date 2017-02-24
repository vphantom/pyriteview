<?php

/**
 * Reports
 *
 * PHP version 5
 *
 * @category  Application
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2017 Stéphane Lavergne
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU Affero GPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */

/**
 * Reports class
 *
 * PHP version 5
 *
 * @category  Application
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2017 Stéphane Lavergne
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU Affero GPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */

class Reports
{
    /**
     * Bootstrap: define event handlers
     *
     * @return null
     */
    public static function bootstrap()
    {
        // on('install',              'Articles::install');
    }
}

on(
    'route/admin+reports',
    function ($path) {
        global $PPHP;
        $req = grab('request');
        $env = array();

        if (!pass('can', 'edit', 'issue')) return trigger('http_status', 403);

        $next = array_shift($path);
        switch ($next) {
        case 'editing':
            $env['date_now'] = date('Y-m-d');
            $env['date_earlier'] = (new DateTime())->modify('-3 month')->format('Y-m-d');

            if (isset($req['post']['begin']) && isset($req['post']['end'])) {
                if (!pass('form_validate', 'editing_report')) return trigger('http_status', 440);
                $articleIds = grab('in_history', 'article', $req['post']['begin'], $req['post']['end']);
                $env['issues'] = array();
                foreach ($articleIds as $articleId) {
                    $article = grab('article', $articleId);
                    $article['statusChanges'] = grab(
                        'history',
                        array(
                            'objectType' => 'article',
                            'objectId' => $articleId,
                            'action' => 'modified',
                            'fieldName' => 'status'
                        )
                    );
                    foreach ($article['versions'] as $version) {
                        foreach ($version['reviews'] as $review) {
                            $article['hasReviews'] = true;
                            break 2;
                        };
                    };
                    $env['issues'][$article['issueId']][] = $article;
                };
                ksort($env['issues']);
            };

            trigger('render', 'reports_editing.html', $env);
            break;
        }
    }
);
