<?php

/**
 * Reports
 *
 * PHP version 5
 *
 * @category  Application
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2017-2018 Stéphane Lavergne
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
 * @copyright 2017-2018 Stéphane Lavergne
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
        $db = $PPHP['db'];
        $states = $PPHP['config']['articles']['states'];
        $req = grab('request');
        $env = array();

        if (!(pass('can', 'edit', 'issue') || pass('has_role', 'editor'))) return trigger('http_status', 403);

        $next = array_shift($path);
        switch ($next) {

        case 'editing':
            $env['date_now'] = date('Y-m-d');
            $env['date_earlier'] = (new DateTime())->modify('-3 month')->format('Y-m-d');
            $env['all_issues'] = grab('issues');
            array_unshift($env['all_issues'], array('id' => 0));  // Dummy for passing articles along

            if (isset($req['post']['begin']) && isset($req['post']['end'])) {
                if (!pass('form_validate', 'editing_report')) return trigger('http_status', 440);
                $articleIds = grab('in_history', 'article', $req['post']['begin'], $req['post']['end']);
                $issueArticles = array();
                foreach ($articleIds as $articleId) {
                    $article = grab('article', $articleId);
                    if ($article === false) {
                        continue;
                    };
                    if (isset($req['post']['issueId'])
                        && $req['post']['issueId'] !== '*'
                        && $req['post']['issueId'] != $article['issueId']
                    ) {
                        continue;
                    };
                    $article['statusChanges'] = grab(
                        'history',
                        array(
                            'objectType' => 'article',
                            'objectId' => $articleId,
                            'action' => 'modified',
                            'fieldName' => 'status'
                        )
                    );
                    $article['hasReviews'] = false;
                    $article['hasAcceptedReviews'] = false;
                    foreach ($article['versions'] as $version) {
                        foreach ($version['reviews'] as $review) {
                            $article['hasReviews'] = true;
                            if ($review['agreed']) {
                                $article['hasAcceptedReviews'] = true;
                                break 2;
                            };
                        };
                    };
                    $issueArticles[$article['issueId']][] = $article;
                };
                foreach ($env['all_issues'] as $i => $issue) {
                    if (isset($issueArticles[$issue['id']])) {
                        usort($issueArticles[$issue['id']], 'Articles::compareStatus');
                        $env['all_issues'][$i]['articles'] = $issueArticles[$issue['id']];
                    };
                };
            };

            trigger('render', 'reports_editing.html', $env);
            break;

        case 'activity':
            $env['date_now'] = date('Y-m-d');
            $env['date_earlier'] = (new DateTime())->modify('-3 month')->format('Y-m-d');

            // Brute force some introspection
            $env['objectTypes'] = $db->selectList("SELECT DISTINCT(objectType) FROM transactions");
            if (isset($req['post']['objectType'])) { $env['objectType_set'] = true; };
            $env['actions']     = $db->selectList("SELECT DISTINCT(action)     FROM transactions");

            if (isset($req['post']['begin']) && isset($req['post']['end'])) {
                $args = Array(
                    'begin' => $req['post']['begin'],
                    'end'   => $req['post']['end'],
                );
                if (isset($req['post']['objectType']) && $req['post']['objectType'] != '*') {
                    $args['objectType'] = $req['post']['objectType'];
                };
                if (isset($req['post']['action']) && $req['post']['action'] != '*') {
                    $args['action'] = $req['post']['action'];
                };
                $env['history'] = grab('history', $args);
            };
            trigger('render', 'reports_activity.html', $env);
            break;

        }

    }
);
