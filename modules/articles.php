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
            CREATE TABLE IF NOT EXISTS 'articleStatus' (
                name VARCHAR(64) NOT NULL DEFAULT '' PRIMARY KEY
            )
            "
        );
        $db->exec(
            "
            REPLACE INTO articleStatus (name) VALUES
            ('created'),
            ('dispatched_review'),
            ('accepted_with_condition'),
            ('accepted'),
            ('printing'),
            ('published'),
            ('rejected'),
            ('deleted')
            "
        );
        $db->exec(
            "
            CREATE TABLE IF NOT EXISTS 'articles' (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                issueId     INTEGER NOT NULL DEFAULT '0',
                status      VARCHAR(64) NOT NULL DEFAULT 'created',
                wordCount   INTEGER NOT NULL DEFAULT '0',
                title       VARCHAR(255),
                keywords    TEXT NOT NULL DEFAULT '',
                abstract    TEXT NOT NULL DEFAULT '',
                FOREIGN KEY(status) REFERENCES articleStatus(name)
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

        $article = $db->selectSingleArray(
            "
            SELECT articles.*, issues.number FROM articles
            LEFT JOIN issues ON issues.id=articles.issueId
            WHERE articles.id=?
            ",
            array($id)
        );
        if (pass('can', 'view', 'article', $id) || pass('can', 'view', 'issue', $article['issueId'])) {

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
     * The following keys may be defined in $args:
     *
     * keyword: Search in titles, keywords, abstracts
     * issueId: Restrict to a specific issue
     * states: Restrict to specific states (string for one, array for many)
     * byStatus: Set true to group results by status code
     *
     * @param array $args (Optional) Arguments described above
     *
     * @return array Articles or arrays keyed by status
     */
    public static function getList($args = array())
    {
        global $PPHP;
        $db = $PPHP['db'];
        $keyword = null;
        $issueId = null;
        $byStatus = false;
        $states = array();

        foreach ($args as $key => $val) {
            switch ($key) {
            case 'keyword':
                $keyword = $val;
                break;
            case 'issueId':
                $issueId = $val;
                break;
            case 'states':
                if (is_array($val)) {
                    $states = $val;
                } else {
                    $states[] = $val;
                };
                break;
            case 'byStatus':
                $byStatus = $val;
                break;
            };
        };

        $q = $db->query('SELECT articles.*, issues.number FROM articles');
        $q->left_join('issues ON issues.id=articles.issueId');
        $q->where();
        $sources = array();
        $sources[] = grab('can_sql', 'issues.id', 'view', 'issue');
        $sources[] = grab('can_sql', 'articles.id', 'view', 'article');
        if (pass('has_role', 'author')) {
            $sources[] = grab('can_sql', 'articles.id', 'edit', 'article');
        };
        $q->implodeClosed('OR', $sources);
        if (pass('has_role', 'reader')) {
            $q->and("articles.status='published'");
        };
        if ($issueId !== null) {
            $q->and('articles.issueId=?', $issueId);
        };
        if (count($states) > 0) {
            $q->append('AND articles.status IN')->varsClosed($states);
        };
        if ($keyword !== null) {
            $search = array();
            $search[] = $db->query('articles.title LIKE ?', "%{$keyword}%");
            $search[] = $db->query('articles.keywords LIKE ?', "%{$keyword}%");
            $search[] = $db->query('articles.abstract LIKE ?', "%{$keyword}%");
            $q->and()->implodeClosed('OR', $search);
        };
        $q->order_by('issues.number DESC, articles.id DESC');
        print_r($q);
        $list = $db->selectArray($q);
        if ($byStatus) {
            $sorted = array();
            foreach ($list as $article) {
                $sorted[$article['status']][] = $article;
            };
            return $sorted;
        } else {
            return $list;
        };
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

        $db->begin();
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
                if (pass('has_role', 'author')) {
                    // Authors need explicit rights to their creations
                    trigger('grant', $_SESSION['user']['id'], null, 'view', 'article', $res);
                    trigger('grant', $_SESSION['user']['id'], null, 'edit', 'article', $res);
                };
            };
        };
        $db->commit();

        return $res;
    }
}

// Routes

on(
    'route/articles',
    function ($path) {
        if (!$_SESSION['identified']) return trigger('http_status', 403);
        $req = grab('request');
        $articleId = array_shift($path);
        $article = grab('article', $articleId);

        // A binary request is necessarily for a file within an article
        //
        if ($req['binary']) {
            if ($article === false) return trigger('http_status', 404);
            if (!(pass('can', 'view', 'article', $articleId) || pass('can', 'edit', 'article', $articleId) || pass('can', 'view', 'issue', $article['issueId']))) return trigger('http_status', 403);

            $fname = array_shift($path);

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
        if ($articleId !== null) {
            $saved = false;
            $added = false;
            $deleted = false;
            $success = false;
            $history = null;
            $editors = array();
            $editors_active = array();
            if (isset($_POST['wordCount'])) {
                if (!pass('form_validate', 'articles_edit')) return trigger('http_status', 440);
                if (is_numeric($articleId)) {
                    if (!(pass('can', 'edit', 'article', $articleId) || pass('can', 'edit', 'issue', $article['issueId']))) return trigger('http_status', 403);
                } else {
                    if (!pass('can', 'create', 'article')) return trigger('http_status', 403);
                };
                $saved = true;
                $success = pass('article_save', $_POST);
                $article = grab('article', $articleId);
            };
            if (is_numeric($articleId)) {
                if (!(pass('can', 'view', 'article', $articleId) || pass('can', 'edit', 'article', $articleId) || pass('can', 'view', 'issue', $article['issueId']))) return trigger('http_status', 403);

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
                    'articles' => grab(
                        'articles',
                        array(
                            'keyword' => $keyword,
                            'byStatus' => true
                        )
                    )
                )
            );
        };

    }
);
