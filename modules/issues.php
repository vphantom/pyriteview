<?php

/**
 * Issues
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
 * Issues class
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

class Issues
{
    /**
     * Bootstrap: define event handlers
     *
     * @return null
     */
    public static function bootstrap()
    {
        global $PPHP;

        on('install',    'Issues::install');
        on('issues',     'Issues::getList');
        on('issue',      'Issues::get');
        on('issue_save', 'Issues::save');

        // Work around limitation of PHP's handling of true and false entries
        $PPHP['config']['issues']['allow_issue_zero'] = (bool)$PPHP['config']['issues']['allow_issue_zero'];
    }

    /**
     * Create database tables if necessary
     *
     * @return null
     */
    public static function install()
    {
        global $PPHP;
        $db = $PPHP['db'];
        $config = $PPHP['config']['issues'];

        echo "    Installing issues...";
        $db->begin();


        $customs = '';
        if (isset($config['fields'])) {
            foreach ($config['fields'] as $name => $definition) {
                $customs .= "                {$name} {$definition},\n";
            };
        };
        $db->exec(
            "
            CREATE TABLE IF NOT EXISTS 'issues' (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                publication DATE NOT NULL DEFAULT '1980-01-01',
                volume      VARCHAR(16) NOT NULL DEFAULT '',
                number      VARCHAR(16) NOT NULL DEFAULT '',
                title       VARCHAR(255) NOT NULL DEFAULT '',
                {$customs}
                description TEXT NOT NULL DEFAULT ''
            )
            "
        );
        $db->exec(
            "
            CREATE UNIQUE INDEX IF NOT EXISTS 'idx_issues_publication'
            ON issues (publication)
            "
        );
        $db->commit();
        echo "    done!\n";
    }

    /**
     * Compute issue nickname
     *
     * For compatibility with special non-existent issue zero, returns "0" if
     * anything failed along the way.
     *
     * @param int|array $in Issue ID or instance
     *
     * @return string Issue "issue" nickname
     */
    public static function getIssueName($in)
    {
        global $PPHP;
        $db = $PPHP['db'];
        $out = '0';

        if (!is_array($in)) {
            $in = $db->selectSingleArray(
                "
                SELECT volume, number FROM issues WHERE id=?
                ",
                array($in)
            );
        };
        if (!is_array($in)) {
            return $out;
        };
        if ($in['number']) {
            $out = ($in['volume'] ? $in['volume'] . '.' : '') . $in['number'];
        };
        return $out;
    }

    /**
     * Get an issue
     *
     * Only works if the current user is allowed to view it.
     *
     * Special column "issue" is created, set to one of "{$volume}.{$number}",
     * $number or "0" depending on their values.
     *
     * @param int $id Which issue to load
     *
     * @return array|bool Issue (associative) or false on failure
     */
    public static function get($id)
    {
        global $PPHP;
        $db = $PPHP['db'];
        $issue = false;

        if (pass('can', 'view', 'issue', $id)) {
            $issue = $db->selectSingleArray("SELECT * FROM issues WHERE id=?", array($id));
            if (is_array($issue)) {
                $issue['issue'] = self::getIssueName($issue);
                $issue['editors'] = grab('object_users', '*', 'issue', $id);
                $issue['permalink'] = makePermalink($issue['title']);
            };
        };

        return $issue;
    }

    /**
     * Get issues, most recent first
     *
     * Only issues which the current user is allowed to view are returned.
     * This means unpublished issues and explicitly allowed ones.
     *
     * @param string $keyword (Optional) Search in all columns
     *
     * @return array Issues
     */
    public static function getList($keyword = null)
    {
        global $PPHP;
        $db = $PPHP['db'];

        $allowed = grab('can_sql', 'id', 'view', 'issue');
        $q = $db->query('SELECT * FROM issues')->where();
        $q->implodeClosed(
            'OR',
            array(
                $allowed,
                $db->query("publication > date('now', '-1 day')")
            )
        );
        if ($keyword !== null) {
            $search = array();
            $search[] = $db->query('volume LIKE ?', "%{$keyword}%");
            $search[] = $db->query('number LIKE ?', "%{$keyword}%");
            $search[] = $db->query('title LIKE ?', "%{$keyword}%");
            $search[] = $db->query('description LIKE ?', "%{$keyword}%");
            $q->and()->implodeClosed('OR', $search);
        };
        $q->order_by('publication DESC, volume DESC, number DESC');
        $issues = $db->selectArray($q);
        foreach ($issues as $key => $issue) {
            // Weird bug with PHP using $list => &$issue
            $issues[$key]['permalink'] = makePermalink($issue['title']);
        };
        return $issues;
    }

    /**
     * Save/create an issue
     *
     * Additional key 'id' specifies that we're trying to update an existing
     * issue.
     *
     * @param array $cols Issue information
     *
     * @return int|bool The Id (possibly created) on success, false on error
     */
    public static function save($cols)
    {
        global $PPHP;
        $db = $PPHP['db'];
        $res = false;
        $log = null;

        if (isset($cols['log'])) {
            // Only log comments in first of a series of transactions
            $log = $cols['log'];
            unset($cols['log']);
        };

        $db->begin();
        if (isset($cols['id'])) {
            $id = $cols['id'];
            unset($cols['id']);
            $oldIssue = self::get($id);
            $res = $db->update('issues', $cols, 'WHERE id=?', array($id));
            if ($res !== false) {
                $res = $id;
                // Log each modified interesting column
                foreach (array('volume', 'number', 'publication', 'title', 'description') as $col) {
                    if (isset($cols[$col]) && $oldIssue[$col] !== $cols[$col]) {
                        trigger(
                            'log',
                            array(
                                'action' => 'modified',
                                'objectType' => 'issue',
                                'objectId' => $id,
                                'fieldName' => $col,
                                'oldValue' => ($col === 'description' ? filter('html_to_text', $oldIssue[$col]) : $oldIssue[$col]),
                                'newValue' => ($col === 'description' ? filter('html_to_text', $cols[$col]) : $cols[$col]),
                                'content' => $log
                            )
                        );
                        $log = null;
                    };
                };

            };
        } else {
            $res = $db->insert('issues', $cols);
            if ($res !== false) {
                trigger('http_status', 201);
                trigger(
                    'log',
                    array(
                        'action' => 'created',
                        'objectType' => 'issue',
                        'objectId' => $res
                    )
                );
            };
        };
        if (!isset($cols['editors'])) {
            $cols['editors'] = array();
        };
        if ($res !== false) {
            $oldEditors = grab('object_users', '*', 'issue', $res);
            $deled = array_diff($oldEditors, $cols['editors']);
            $added = array_diff($cols['editors'], $oldEditors);

            foreach ($added as $editor) {
                trigger('grant', $editor, 'editor');
                trigger('grant', $editor, null, '*', 'issue', $res);
                trigger(
                    'log',
                    array(
                        'objectType' => 'issue',
                        'objectId' => $res,
                        'action' => 'invited',
                        'fieldName' => 'editors',
                        'newValue' => $editor,
                        'content' => $log
                    )
                );
                $log = null;
                trigger(
                    'send_invite',
                    'invitation_editor',
                    $editor,
                    array(
                        'issue' => $res
                    )
                );
            };

            foreach ($deled as $editor) {
                trigger('revoke', $editor, null, '*', 'issue', $res);
                trigger(
                    'log',
                    array(
                        'objectType' => 'issue',
                        'objectId' => $res,
                        'action' => 'uninvited',
                        'fieldName' => 'editors',
                        'newValue' => $editor,
                        'content' => $log
                    )
                );
                $log = null;
            };
        };
        $db->commit();

        return $res;
    }
}

// Routes

on(
    'route/issues',
    function ($path) {
        $req = grab('request');

        if (!$_SESSION['identified']) return trigger('http_status', 403);

        $issueId = array_shift($path);
        if ($issueId !== null) {
            $issue = null;
            $saved = false;
            $added = false;
            $deleted = false;
            $success = false;
            $history = null;
            $history_id = $issueId;
            $articles = array();
            if (isset($req['post']['title'])) {
                if (!pass('form_validate', 'issues_edit')) return trigger('http_status', 440);
                if (!isset($req['post']['userdata'])) {
                    $req['post']['userdata'] = array();
                };
                $req['post']['editors'] = grab('clean_userids', $req['post']['editors'], $req['post']['userdata']);
                $saved = true;
                $success = grab('issue_save', $req['post']);
                $issue = $success ? grab('issue', $success) : null;
                if ($success !== false) return trigger('http_redirect', $req['base'] . '/issues/' . $success . '/' . $issue['permalink']);
            };
            if (is_numeric($issueId)) {
                if (!pass('can', 'view', 'issue', $issueId)) return trigger('http_status', 403);
                if ($issueId == 0) {
                    // Mock structure for proper display
                    $issue = array(
                        'id' => 0,
                        'number' => '0'
                    );
                } else {
                    $issue = grab('issue', $issueId);
                };

                $articles = grab(
                    'articles',
                    array(
                        'issueId' => $issueId,
                        'byStatus' => true
                    )
                );
                $history = grab(
                    'history',
                    array(
                        'objectType' => 'issue',
                        'objectId' => $issueId,
                        'order' => 'DESC'
                    )
                );
            };
            trigger(
                'render',
                'issues_edit.html',
                array(
                    'saved' => $saved,
                    'added' => $added,
                    'deleted' => $deleted,
                    'success' => $success,
                    'issue' => $issue,
                    'history' => $history,
                    'history_type' => 'issue',
                    'history_id' => $history_id,
                    'articles' => $articles
                )
            );

        } else {
            $keyword = null;
            if (isset($req['post']['keyword'])) {
                if (!pass('form_validate', 'issue_search')) return trigger('http_status', 440);
                $keyword = $req['post']['keyword'];
            };
            trigger(
                'render',
                'issues.html',
                array(
                    'issues' => grab('issues', $keyword)
                )
            );
        };

    }
);
