<?php

/**
 * Issues
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
 * Issues class
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

class Issues
{
    /**
     * Bootstrap: define event handlers
     *
     * @return null
     */
    public static function bootstrap()
    {
        on('install',    'Issues::install');
        on('issues',     'Issues::getList');
        on('issue',      'Issues::get');
        on('issue_save', 'Issues::save');
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

        echo "    Installing issues...";
        $db->begin();
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
        $db->commit();
        echo "    done!\n";
    }

    /**
     * Get an issue
     *
     * Only works if the current user is allowed to view it.
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
                $issue['editors'] = grab('object_users', '*', 'issue', $id);
            };
        };

        return $issue;
    }

    /**
     * Get issues, most recent first
     *
     * Only issues which the current user is allowed to view are returned.
     *
     * @param string $title (Optional) Keyword to search in titles
     *
     * @return array Issues
     */
    public static function getList($title = null)
    {
        global $PPHP;
        $db = $PPHP['db'];

        $allowed = grab('can_sql', 'id', 'view', 'issue');
        $q = $db->query('SELECT * FROM issues')->where($allowed);
        if ($title !== null) {
            $q->and('title LIKE ?', "%{$title}%");
        };
        $q->order_by('number DESC');
        return $db->selectArray($q);
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

        $db->begin();
        if (isset($cols['id'])) {
            $id = $cols['id'];
            unset($cols['id']);
            $res = $db->update('issues', $cols, 'WHERE id=?', array($id));
            if ($res !== false) {
                $res = $id;
                trigger(
                    'log',
                    array(
                        'action' => 'modified',
                        'objectType' => 'issue',
                        'objectId' => $res
                    )
                );
            };
        } else {
            $res = $db->insert('issues', $cols);
            if ($res !== false) {
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
                trigger('grant', $editor, null, '*', 'issue', $res);
            };

            foreach ($deled as $editor) {
                trigger('revoke', $editor, null, '*', 'issue', $res);
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
        if (!$_SESSION['identified']) return trigger('http_status', 403);

        $issueId = array_shift($path);
        if ($issueId !== null) {
            $issue = null;
            $saved = false;
            $added = false;
            $deleted = false;
            $success = false;
            $history = null;
            $articles = array();
            if (isset($_POST['number'])) {
                if (!pass('form_validate', 'issues_edit')) return trigger('http_status', 440);
                $saved = true;
                $success = pass('issue_save', $_POST);
            };
            if (is_numeric($issueId)) {
                if (!pass('can', 'view', 'issue', $issueId)) return trigger('http_status', 403);
                $issue = grab('issue', $issueId);

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
                    'articles' => $articles
                )
            );

        } else {
            $title = null;
            if (isset($_POST['title'])) {
                $title = $_POST['title'];
            };
            trigger(
                'render',
                'issues.html',
                array(
                    'issues' => grab('issues', $title)
                )
            );
        };

    }
);
