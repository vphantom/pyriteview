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
        $config = $PPHP['config']['articles'];
        $article = false;

        if (pass('can', 'view', 'article', $id)) {
            $article = $db->selectSingleArray("SELECT articles.*, issues.number FROM articles, issues WHERE issues.id=articles.issueId AND articles.id=?", array($id));

            if ($article !== false) {
                $article['authors'] = grab('object_users', 'edit', 'article', $id);
                $article['peers'] = grab('object_users', 'review', 'article', $id);

                /*
                 * Try to open config.articles.path '/' article.number '/' article.id as directory
                 */
                $article['files'] = array();
                foreach (glob("{$config['path']}/{$article['number']}/{$article['id']}/*.*") as $fname) {
                    $bytes = filesize($fname);
                    $pi = pathinfo($fname);
                    $article['files'][] = array(
                        'dir' => $pi['dirname'],
                        'name' => $pi['basename'],
                        'bytes' => $bytes
                    );
                };
            };
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
        $q->and(grab('can_sql', 'articles.id', 'view', 'article'));
        if ($keyword !== null) {
            $search = array();
            $search[] = $db->query('articles.title LIKE ?', "%{$keyword}%");
            $search[] = $db->query('articles.keywords LIKE ?', "%{$keyword}%");
            $search[] = $db->query('articles.abstract LIKE ?', "%{$keyword}%");
            $q->and()->implodeClosed('OR', $search);
        };
        $q->order_by('issues.number DESC, articles.id DESC');
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
    function ($path) {
        if (!$_SESSION['identified']) return trigger('http_status', 403);
        $req = grab('request');

        // A binary request is necessarily for a file within an article
        //
        if ($req['binary']) {
            $articleId = array_shift($path);
            if (!pass('can', 'view', 'article', $articleId)) return trigger('http_status', 403);

            $fname = array_shift($path);
            $article = grab('article', $articleId);
            if ($article === false) return trigger('http_status', 404);

            foreach ($article['files'] as $file) {
                if ($file['name'] === $fname) {
                    header('Content-Type: application/octet-stream');
                    header('Content-Disposition: attachment; filename="'.$fname.'"');
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . $file['bytes']);
                    readfile($file['dir'] . '/' . $file['name']);
                    exit;
                };
            };
            return trigger('http_status', 404);
        };

        // Non-binary request can be for general listing or a specific article
        //
        $articleId = array_shift($path);
        if ($articleId !== null) {
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
            if (is_numeric($articleId)) {
                if (!pass('can', 'view', 'article', $articleId)) return trigger('http_status', 403);
                $article = grab('article', $articleId);


                $history = grab(
                    'history',
                    array(
                        'objectType' => 'article',
                        'objectId' => $articleId,
                        'order' => 'DESC'
                    )
                );
                $editors = grab('role_users', 'editor');
                $editors_active = grab('object_users', '*', 'article', $articleId);
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
        } else {
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
        };

    }
);

on(
    'route/articles+edit',
    function () {
        if (!$_SESSION['identified']) return trigger('http_status', 403);
    }
);
