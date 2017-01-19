<?php

/**
 * Articles
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
 * Articles class
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

class Articles
{
    /**
     * Bootstrap: define event handlers
     *
     * @return null
     */
    public static function bootstrap()
    {
        on('install',      'Articles::install');
        on('articles',     'Articles::getList');
        on('article',      'Articles::get');
        on('article_save', 'Articles::save');
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

        echo "    Installing articles...";
        $db->begin();
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
        $db->commit();
        echo "    done!\n";
    }

    /**
     * Get an article
     *
     * Only works if the current user is allowed to view it.
     *
     * @param int $id Which article to load
     *
     * @return array|bool Article (associative) or false on failure
     */
    public static function get($id)
    {
        global $PPHP;
        $db = $PPHP['db'];
        $article = false;

        if (pass('can', 'view', 'article', $id)) {
            $article = $db->selectSingleArray("SELECT * FROM articles WHERE id=?", array($id));
        };

        return $article;
    }

    /**
     * Get articles, most recent first
     *
     * Only articles which the current user is allowed to view are returned.
     *
     * A convenience virtual column 'number' is fetched from the issues table
     * as well in addition to 'issueId'.
     *
     * @param string $keyword (Optional) Search in titles, keywords, abstracts
     * @param int    $issueId (Optional) Restrict to a specific issue
     *
     * @return array Articles
     */
    public static function getList($keyword = null, $issueId = null)
    {
        global $PPHP;
        $db = $PPHP['db'];

        $q = $db->query('SELECT articles.*, issues.number FROM articles, issues');
        $q->where('issues.id=articles.issueId');
        if ($issueId !== null) {
            $q->and('articles.issueId=?', $issueId);
        };
        $q->and(grab('can_sql', 'issues.id', 'view', 'issue'));
        $q->and(grab('can_sql', 'id', 'view', 'article'));
        if ($keyword !== null) {
            $search = array();
            $search[] = $db->query('title LIKE ?', "%{$keyword}%");
            $search[] = $db->query('keywords LIKE ?', "%{$keyword}%");
            $search[] = $db->query('abstract LIKE ?', "%{$keyword}%");
            $q->and()->implodeClosed('OR', $search);
        };
        $q->order_by('number DESC');
        return $db->selectArray($q);
    }

    /**
     * Save/create an article
     *
     * Additional key 'id' specifies that we're trying to update an existing
     * article.
     *
     * @param array $cols Article information
     *
     * @return int|bool The Id (possibly created) on success, false on error
     */
    public static function save($cols)
    {
        global $PPHP;
        $db = $PPHP['db'];
        $res = false;

        if (isset($cols['id'])) {
            $id = $cols['id'];
            unset($cols['id']);
            $res = $db->update('articles', $cols, 'WHERE id=?', array($id));
            if ($res !== false) {
                $res = $id;
                trigger(
                    'log',
                    array(
                        'action' => 'modified',
                        'objectType' => 'article',
                        'objectId' => $res
                    )
                );
            };
        } else {
            $res = $db->insert('articles', $cols);
            if ($res !== false) {
                trigger(
                    'log',
                    array(
                        'action' => 'created',
                        'objectType' => 'article',
                        'objectId' => $res
                    )
                );
            };
        };

        return $res;
    }
}

// Routes

on(
    'route/articles',
    function () {
        if (!$_SESSION['identified']) return trigger('http_status', 403);
        $keyword = null;
        if (isset($_POST['keyword'])) {
            $keyword = $_POST['keyword'];
        };
        trigger(
            'render',
            'articles.html',
            array(
                'articles' => grab('articles', $keyword)
            )
        );
    }
);

on(
    'route/articles+edit',
    function () {
        if (!$_SESSION['identified']) return trigger('http_status', 403);
        $article = null;
        $saved = false;
        $added = false;
        $deleted = false;
        $success = false;
        $history = null;
        $editors = array();
        $editors_active = array();
        if (isset($_POST['wordCount'])) {
            if (!pass('form_validate', 'articles_edit')) return trigger('http_status', 440);
            $saved = true;
            $success = pass('article_save', $_POST);
        };
        if (isset($_GET['id'])) {
            if (!pass('can', 'view', 'article', $_GET['id'])) return trigger('http_status', 403);
            $article = grab('article', $_GET['id']);

            if (isset($_POST['addeditor'])) {
                if (!pass('can', 'edit', 'article', $_GET['id'])) return trigger('http_status', 403);
                $added = true;
                $success = pass('grant', $_POST['addeditor'], null, '*', 'article', $_GET['id']);
            };
            if (isset($_POST['deleditor'])) {
                if (!pass('can', 'edit', 'article', $_GET['id'])) return trigger('http_status', 403);
                $deleted = true;
                $success = pass('revoke', $_POST['deleditor'], null, '*', 'article', $_GET['id']);
            };

            $history = grab(
                'history',
                array(
                    'objectType' => 'article',
                    'objectId' => $_GET['id'],
                    'order' => 'DESC'
                )
            );
            $editors = grab('role_users', 'editor');
            $editors_active = grab('object_users', '*', 'article', $_GET['id']);
        };
        trigger(
            'render',
            'articles_edit.html',
            array(
                'saved' => $saved,
                'added' => $added,
                'deleted' => $deleted,
                'success' => $success,
                'article' => $article,
                'editors' => $editors,
                'editors_active' => $editors_active,
                'issues' => grab('issues'),
                'history' => $history
            )
        );
    }
);
