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
        on('install',              'Articles::install');
        on('articles',             'Articles::getList');
        on('article',              'Articles::get');
        on('article_save',         'Articles::save');
        on('article_version_save', 'Articles::saveVersion');
        on('peer_reviews',         'Articles::getPeerReviews');
        on('review_save',          'Articles::saveReview');
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
        $config = $PPHP['config']['articles'];

        echo "    Installing articles...";
        $db->begin();
        $db->exec(
            "
            CREATE TABLE IF NOT EXISTS 'articleStatus' (
                name VARCHAR(64) NOT NULL DEFAULT '' PRIMARY KEY
            )
            "
        );
        foreach ($config['states'] as $status) {
            $db->exec("REPLACE INTO articleStatus (name) VALUES (?)", array($status));
        };
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
        $db->exec(
            "
            CREATE TABLE IF NOT EXISTS 'articleVersions' (
                id          INTEGER PRIMARY KEY AUTOINCREMENT,
                articleId   INTEGER NOT NULL DEFAULT '0',
                created     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                files       BLOB NOT NULL DEFAULT '',
                FOREIGN KEY (articleId) REFERENCES articles(id)
            )
            "
        );
        $db->exec(
            "
            CREATE TABLE 'reviews' (
                id        INTEGER PRIMARY KEY AUTOINCREMENT,
                versionId INTEGER NOT NULL DEFAULT '0',
                peerId    INTEGER NOT NULL DEFAULT '0',
                created   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                deadline  date NOT NULL DEFAULT '2000-01-01',
                status    VARCHAR(32) NOT NULL DEFAULT 'created',
                files     BLOB NOT NULL DEFAULT '',
                FOREIGN KEY(versionId) REFERENCES articleVersions(id),
                FOREIGN KEY(peerId)    REFERENCES users(id),
                FOREIGN KEY(status)    REFERENCES articleStatus(name)
            )

            "
        );
        $db->exec(
            "
            CREATE UNIQUE INDEX idx_reviews_versions_peers ON reviews (versionId, peerId)
            "
        );
        $db->commit();
        echo "    done!\n";
    }

    /**
     * Compute issue nickname
     *
     * For compatibility with special non-existent issue "0", this returns "0"
     * if anything failed along the way.
     *
     * @param int|array $in Article ID or instance
     *
     * @return string Issue "issue" nickname
     */
    private static function _getIssueName($in)
    {
        global $PPHP;
        $db = $PPHP['db'];

        if (!is_array($in)) {
            $in = $db->selectSingleArray(
                "
                SELECT articles.id, issues.volume, issues.number FROM articles
                LEFT JOIN issues ON issues.id=articles.issueId
                WHERE articles.id=?
                ",
                array($in)
            );
        };
        if (!is_array($in)) {
            return '0';
        };
        return Issues::getIssueName($in);
    }

    /**
     * Get an article
     *
     * Only works if the current user is allowed to view it.
     *
     * Special columns volume, number and issue are added to describe the
     * issue in which the article currently belongs.  The issue is either
     * "{$volume}.{$number}", $number or "0" depending on their values.
     *
     * Special column versions contains version sub-information.  For each,
     * special column 'localcreated' contains the local conversion of the UTC
     * 'created' and 'isPeer' is set if one review is for the current user.
     *
     * As a convenience, if THE LAST VERSION has 'isPeer', it is also set for
     * the entire article.
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
            SELECT articles.*, issues.volume, issues.number FROM articles
            LEFT JOIN issues ON issues.id=articles.issueId
            WHERE articles.id=?
            ",
            array($id)
        );

        // Process up to the point where we can determine if the user is
        // allowed access to this article.  This means getting reviews.
        $article['versions'] = $db->selectArray(
            "
            SELECT articleVersions.*, datetime(articleVersions.created, 'localtime') AS localcreated, reviews.id AS isPeer
            FROM articleVersions
            LEFT JOIN reviews ON reviews.versionId=articleVersions.id AND reviews.peerId=?
            WHERE articleVersions.articleId=?
            ORDER BY id ASC
            ",
            array($_SESSION['user']['id'], $article['id'])
        );
        if (!is_array($article['versions'])) {
            $article['versions'] = array();
        };
        // The following is quite ugly with $vkey/$rkey but it's to edit in place.
        foreach ($article['versions'] as $vkey => $version) {
            $article['versions'][$vkey]['files'] = json_decode($version['files'], true);
            if (!is_array($article['versions'][$vkey]['files'])) {
                $article['versions'][$vkey]['files'] = array();
            };
            $article['versions'][$vkey]['reviews'] = $db->selectArray(
                "
                SELECT *, CAST(round(julianday(deadline) - julianday('now')) AS INTEGER) AS daysLeft
                FROM reviews
                WHERE versionId=?
                ORDER BY deadline ASC
                ",
                array($version['id'])
            );
            foreach ($article['versions'][$vkey]['reviews'] as $rkey => $review) {
                $article['versions'][$vkey]['reviews'][$rkey]['files'] = json_decode($review['files'], true);
                if (!is_array($article['versions'][$vkey]['reviews'][$rkey]['files'])) {
                    $article['versions'][$vkey]['reviews'][$rkey]['files'] = array();
                };
            };
        };
        $article['isPeer'] = count($article['versions']) > 0
            ? $article['versions'][count($article['versions'])-1]['isPeer']
            : false
        ;
        if (pass('can', 'view', 'article', $id)
            || pass('can', 'view', 'issue', $article['issueId'])
            || pass('can', 'edit', 'article', $id)
            || pass('can', 'edit', 'issue', $article['issueId'])
            || $article['isPeer']
        ) {

            if ($article !== false) {
                $article['keywords'] = dejoin(';', $article['keywords']);
                $article['permalink'] = makePermalink($article['title']);
                $article['authors'] = grab('object_users', 'edit', 'article', $id);
                $article['editors'] = grab('object_users', '*', 'issue', $article['issueId']);
                if (count($article['editors']) < 1) {
                    $article['editors'] = grab('role_users', 'editor-in-chief');
                };
                $article['issue'] = self::_getIssueName($article);
                $article['files_dir'] = "{$config['path']}/{$article['issue']}/{$article['id']}";
            };
        } else {
            return array();
        };

        return $article;
    }

    /**
     * Get articles, most recent first
     *
     * Only articles which the current user is allowed to view are returned.
     *
     * Convenience virtual columns 'volume', 'number' and 'issue' are fetched
     * from the issues table as well in addition to 'issueId'.
     *
     * The following keys may be defined in $args:
     *
     * keyword: Search in titles, keywords, abstracts
     * issueId: Restrict to a specific issue
     * states: Restrict to specific states (string for one, array for many)
     * byStatus: Set true to group results by status code
     * current: Status in states_wip[] OR status in states_final[] in recent/future issues
     * noReviews: None of the article's versions have any reviews
     * miaPeers: Some of the article's reviews aren't accepted beyond time limit
     * lateReviews: Some of the article's reviews aren't done by deadline
     *
     * @param array $args (Optional) Arguments described above
     *
     * @return array Articles or arrays keyed by status
     */
    public static function getList($args = array())
    {
        global $PPHP;
        $db = $PPHP['db'];
        $config = $PPHP['config'];
        $res = array();
        $keyword = null;
        $issueId = null;
        $current = false;
        $byStatus = false;
        $noReviews = false;
        $miaPeers = false;
        $lateReviews = false;
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
            case 'noReviews':
                $noReviews = true;
                break;
            case 'miaPeers':
                $miaPeers = true;
                break;
            case 'lateReviews':
                $lateReviews = true;
                break;
            case 'current':
                $current = true;
                break;
            case 'byStatus':
                $byStatus = $val;
                break;
            };
        };

        $q = $db->query('SELECT articles.*, issues.volume, issues.number FROM articles');
        $q->left_join('issues ON issues.id=articles.issueId');
        if ($noReviews || $miaPeers || $lateReviews) {
            // Get last version for each article
            $q->left_join(
                '
                articleVersions ON articleVersions.id=(
                    SELECT MAX(id) FROM articleVersions WHERE articleId=articles.id
                )
                '
            );
            // Get reviews for chosen version
            $q->left_join('reviews ON reviews.versionId=articleVersions.id');
        };
        $q->where();
        $sources = array();
        $sources[] = grab('can_sql', 'issues.id', 'view', 'issue');
        $sources[] = grab('can_sql', 'articles.id', 'view', 'article');
        if (pass('has_role', 'author')) {
            $sources[] = grab('can_sql', 'articles.id', 'edit', 'article');
        };
        if (pass('has_role', 'peer')) {
            $sources[] = $db->query('reviews.peerId=?', $_SESSION['user']['id']);
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
        if ($current) {
            $search = array();

            $search[] = $db->query('articles.status IN')
                ->varsClosed($config['articles']['states_wip']);

            $search[] = $db->query('(articles.status IN')
                ->varsClosed($config['articles']['states_final'])
                ->and("issues.publication > date('now', '-1 month') )");

            $q->and()->implodeClosed('OR', $search);
        };
        if ($keyword !== null) {
            $search = array();
            $search[] = $db->query('articles.title LIKE ?', "%{$keyword}%");
            $search[] = $db->query('articles.keywords LIKE ?', "%{$keyword}%");
            $search[] = $db->query('articles.abstract LIKE ?', "%{$keyword}%");
            $q->and()->implodeClosed('OR', $search);
        };
        if ($noReviews) {
            $q->and('reviews.id IS NULL');
            $q->group_by('articles.id');  // Allowed with ORDER BY in SQLite
        };
        if ($miaPeers) {
            $q->and('reviews.id IS NOT NULL');
            $q->and("reviews.status = 'created'");
            $q->and("reviews.created < date('now', '-{$config['reviews']['accept_days']} days')");
        };
        if ($lateReviews) {
            $q->and('reviews.id IS NOT NULL');
            $q->and("reviews.status = 'reviewing'");
            $q->and("reviews.deadline < date('now')");
        };
        $q->order_by('issues.volume DESC, issues.number DESC, articles.id DESC');

        if ($config['global']['debug']) {
            print_r($q);
        };
        $list = $db->selectArray($q);
        if ($list === false) {
            return array();
        };

        foreach ($list as $key => $article) {
            // Weird bug with PHP using $list => &$article
            $list[$key]['keywords'] = dejoin(';', $article['keywords']);
            $list[$key]['permalink'] = makePermalink($article['title']);
            $list[$key]['issue'] = self::_getIssueName($article);
        };

        if ($byStatus === true) {
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
     * Attach a new file to an article
     *
     * @param int   $articleId Article ID
     * @param array $file      File upload, usually from grab('request')['files'][...]
     *
     * @return array|bool Associative with: name, bytes, type OR false on failure
     */
    private static function _attach($articleId, $file)
    {
        global $PPHP;
        $config = $PPHP['config']['articles'];

        if (in_array($file['type'], $config['file_types'])
            && in_array($file['extension'], $config['file_extensions'])
        ) {
            $issue = self::_getIssueName($articleId);
            $bad_format = false;
            $ext = filter('clean_filename', $file['extension']);
            $base = "{$config['path']}/{$issue}/{$articleId}/" . filter('clean_filename', $file['filename']);

            if (!file_exists("{$config['path']}/{$issue}")) {
                mkdir("{$config['path']}/{$issue}", 06770);
            };
            if (!file_exists("{$config['path']}/{$issue}/{$articleId}")) {
                mkdir("{$config['path']}/{$issue}/{$articleId}", 06770);
            };

            // Attempt to save the file, avoiding name collisions
            $i = 2;
            $try = "{$base}.{$ext}";
            while (file_exists($try) && $i < 100) {
                $try = "{$base}_{$i}.{$ext}";
                $i++;
            };
            if (move_uploaded_file($file['tmp_name'], $try)) {
                $pi = pathinfo($try);
                return array(
                    'name'  => $pi['basename'],
                    'bytes' => $file['size'],
                    'type'  => $file['type']
                );
            };
        };
        return false;
    }

    /**
     * Save/create an article
     *
     * Additional key 'id' specifies that we're trying to update an existing
     * article.
     *
     * @param array $cols  Article information
     * @param array $files (Optional.) Files, usually from grab('request')['files']
     *
     * @return int|bool The Id (possibly created) on success, false on error
     */
    public static function save($cols, $files = array())
    {
        global $PPHP;
        $db = $PPHP['db'];
        $config = $PPHP['config'];
        $res = false;
        $oldArticle = false;

        $db->begin();
        if (isset($cols['keywords']) && is_array($cols['keywords'])) {
            $cols['keywords'] = implode(';', $cols['keywords']);
        };
        if (isset($cols['id'])) {
            $id = $cols['id'];
            unset($cols['id']);
            $oldArticle = self::get($id);
            $oldArticle['keywords'] = implode(';', $oldArticle['keywords']);  // This is for comparison only
            $res = $db->update('articles', $cols, 'WHERE id=?', array($id));
            if ($res !== false) {
                $res = $id;

                if ($oldArticle['issueId'] !== $cols['issueId'] && count($oldArticle['versions']) > 0) {
                    // Move files directory if issue was reassigned
                    $issue = grab('issue', $cols['issueId']);
                    $issuePath = $config['articles']['path'] . '/' . $issue['issue'];
                    if (!file_exists($issuePath)) {
                        mkdir($issuePath, 06770);
                    };
                    rename(
                        "{$config['articles']['path']}/{$oldArticle['issue']}/{$id}",
                        "{$config['articles']['path']}/{$issue['issue']}/{$id}"
                    );
                };

                // Log each modified interesting column
                foreach (array('issueId', 'status', 'wordCount', 'title', 'keywords', 'abstract') as $col) {
                    if (isset($cols[$col]) && $oldArticle[$col] !== $cols[$col]) {
                        trigger(
                            'log',
                            array(
                                'action' => 'modified',
                                'objectType' => 'article',
                                'objectId' => $id,
                                'fieldName' => $col,
                                'oldValue' => $oldArticle[$col],
                                'newValue' => $cols[$col],
                                'content' => $cols['log']
                            )
                        );
                        $cols['log'] = null;
                    };
                };

            };
        } else {
            $res = $db->insert('articles', $cols);
            if ($res !== false) {
                trigger('http_status', 201);
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
        if ($res !== false) {
            if (isset($cols['authors'])) {
                $oldAuthors = grab('object_users', 'edit', 'article', $res);
                $deled = array_diff($oldAuthors, $cols['authors']);
                $added = array_diff($cols['authors'], $oldAuthors);
                foreach ($added as $author) {
                    trigger('grant', $author, 'author');
                    trigger('grant', $author, null, 'edit', 'article', $res);
                };
                foreach ($deled as $author) {
                    trigger('revoke', $author, null, 'edit', 'article', $res);
                };
            };

            // Handle file uploads
            $newFiles = array();
            foreach ($files as $file) {
                $newFile = self::_attach($res, $file);
                if ($newFile !== false) {
                    $newFiles[] = $newFile;
                    trigger(
                        'log',
                        array(
                            'action' => 'attached',
                            'objectType' => 'article',
                            'objectId' => $res,
                            'fieldName' => 'files',
                            'newValue' => $newFile['name'],
                            'content' => $cols['log']
                        )
                    );
                    $cols['log'] = null;
                };
            };
            if (count($newFiles) > 0) {
                $newVersion = self::saveVersion($res, $newFiles);
                if ($oldArticle && $newVersion) {
                    // Update work-in-progress reviews to latest versionId
                    $versionIds = array();
                    foreach ($oldArticle['versions'] as $version) {
                        $versionIds[] = $version['id'];
                    };
                    $q = $db->query('UPDATE reviews SET versionId=?', $newVersion);
                    $q->where('versionId IN')->varsClosed($versionIds);
                    $q->and('status IN')->varsClosed($config['reviews']['states_wip']);
                    // Not saving result as this is a "best effort" maintenance attempt.
                    print_r($q);
                    $db->exec($q);
                };
            };

        };
        $db->commit();

        return $res;
    }

    /**
     * Save a version for an article
     *
     * If $ver is supplied, that specific version is updated and $articleId is
     * ignored.
     *
     * @param int      $articleId The article
     * @param array    $files     List of [name,bytes,type] arrays
     * @param int|null $ver       (Optional) Version ID
     *
     * @return int|bool New ID on success, false on failure
     */
    public static function saveVersion($articleId, $files, $ver = null)
    {
        global $PPHP;
        $db = $PPHP['db'];
        if ($ver !== null) {
            return $db->update(
                'articleVersions',
                array(
                    'files' => json_encode($files)
                ),
                'WHERE id=?',
                array($ver)
            );
        } else {
            return $db->insert(
                'articleVersions',
                array(
                    'articleId' => $articleId,
                    'files'     => json_encode($files)
                )
            );
        };
    }

    /**
     * Get work-in-progress reviews for current user
     *
     * The resulting array is keyed by status (thus 'created' and 'reviewing'
     * keys per config.ini's reviews.states_wip[]) then sorted by due date,
     * chronologically.
     *
     * @return array WIP reviews
     */
    public static function getPeerReviews()
    {
        global $PPHP;
        $db = $PPHP['db'];
        $config = $PPHP['config']['reviews'];
        $res = array();

        $q = $db->query('SELECT reviews.*, articleVersions.articleId FROM reviews');
        $q->left_join('articleVersions')->on('articleVersions.id = reviews.versionId');
        $q->where('peerId = ?', $_SESSION['user']['id']);
        $q->and('status IN')->varsClosed($config['states_wip']);
        $q->order_by('deadline ASC');
        $reviews = $db->selectArray($q);

        if ($reviews !== false) {
            foreach ($reviews as $review) {
                $res[$review['status']][] = $review;
            };
        };

        return $res;
    }

    /**
     * Save/create review(s)
     *
     * @param array      $cols  Columns to set
     * @param array|null $files (Optional) List of [name,bytes,type] arrays
     *
     * @return bool Whether it succeeded
     */
    public static function saveReview($cols, $files = array())
    {
        global $PPHP;
        $db = $PPHP['db'];
        $config = $PPHP['config']['reviews'];

        if (isset($cols['peers']) && isset($cols['versionId'])) {
            // We're creating new reviews, one per peer
            // Return false if any of the inserts fails
            $success = true;
            $db->begin();
            foreach ($cols['peers'] as $peer) {
                $cols['peerId'] = $peer;
                if ($db->insert('reviews', $cols) === false) {
                    $success = false;
                    break;
                };
                // Log, which will be part of the rolled back transaction if we fail.
                trigger(
                    'log',
                    array(
                        'objectType' => 'article',
                        'objectId' => $cols['articleId'],
                        'action' => 'invited',
                        'newValue' => $peer
                    )
                );
            };
            if ($success) {
                $db->commit();
            } else {
                $db->rollback();
            };
            return $success;
        };

        if (isset($cols['id'])) {
            // We're updating a review
            $id = $cols['id'];
            unset($cols['id']);

            $db->begin();

            // Handle file uploads
            $newFiles = array();
            foreach ($files as $file) {
                $newFile = self::_attach($cols['articleId'], $file);
                if ($newFile !== false) {
                    $newFiles[] = $newFile;
                };
            };
            if (count($newFiles) > 0) {
                $oldFiles = $db->selectAtom('SELECT files FROM reviews WHERE id=?', array($id));
                if ($oldFiles) {
                    $oldFiles = json_decode($oldFiles);
                };
                if (!is_array($oldFiles)) {
                    $oldFiles = array();
                };
                array_merge_indexed($oldFiles, $newFiles);
                $cols['files'] = json_encode($oldFiles);
            };

            $res = $db->update('reviews', $cols, 'WHERE id=?', array($id)) !== false;
            if ($res && isset($cols['status'])) {
                trigger(
                    'log',
                    array(
                        'objectType' => 'article',
                        'objectId' => $cols['articleId'],
                        'action' => 'reviewed',
                        'newValue' => $cols['status'],
                        'content' => (isset($cols['log']) && $cols['log'] !== '' ? $cols['log'] : null)
                    )
                );
            };

            $db->commit();
            return $res !== false;
        };
    }
}

// Routes

on(
    'route/articles',
    function ($path) {
        global $PPHP;

        if (!$_SESSION['identified']) return trigger('http_status', 403);
        $req = grab('request');
        $articleId = array_shift($path);
        $article = (is_numeric($articleId) ? grab('article', $articleId) : false);

        // A binary request is necessarily for a file within an article
        //
        if ($req['binary']) {
            if ($article === false) return trigger('http_status', 404);
            if (!(pass('can', 'view', 'article', $articleId)
                || pass('can', 'edit', 'article', $articleId)
                || pass('can', 'view', 'issue', $article['issueId'])
                || $article['isPeer'])
            ) return trigger('http_status', 403);

            $fname = array_shift($path);

            // Look for it explicitly to avoid exploit surprises
            $files = array();
            foreach ($article['versions'] as $version) {
                foreach ($version['files'] as $file) {
                    array_push($files, $file);
                };
                foreach ($version['reviews'] as $review) {
                    foreach ($review['files'] as $file) {
                        array_push($files, $file);
                    };
                };
            };
            foreach ($files as $file) {
                if ($file['name'] === $fname
                    && file_exists($article['files_dir'] . '/' . $file['name'])
                ) {
                    header('Content-Type: ' . $file['type']);
                    header('Expires: 0');
                    header('Cache-Control: must-revalidate');
                    header('Pragma: public');
                    header('Content-Length: ' . $file['bytes']);
                    readfile($article['files_dir'] . '/' . $file['name']);
                    exit;
                };
            };
            // The http_status event is actually too late in binary mode.
            header('Status: 404 Not Found');
            return trigger('http_status', 404);
        };

        // Non-binary request can be for general listing or a specific article
        //
        if ($articleId !== null) {
            $created = false;
            $bad_format = true;
            $saved = false;
            $success = false;
            $history = null;
            $history_id = $articleId;
            if (isset($req['post']['wordCount'])) {
                if (!pass('form_validate', 'articles_edit')) return trigger('http_status', 440);
                if (is_numeric($articleId)) {
                    if (!(pass('can', 'edit', 'article', $articleId) || pass('can', 'edit', 'issue', $article['issueId']))) return trigger('http_status', 403);
                } else {
                    if (!pass('can', 'create', 'article')) return trigger('http_status', 403);
                };
                if (!isset($req['post']['userdata'])) {
                    $req['post']['userdata'] = array();
                };
                $req['post']['authors'] = grab('clean_userids', $req['post']['authors'], $req['post']['userdata']);
                $saved = true;
                $success = grab('article_save', $req['post'], $req['files']);

                if ($success !== false && !is_numeric($articleId)) {
                    $articleId = $success;
                    $created = true;
                }
                // Refresh to discover changes/creation before file handling
                $article = grab('article', $articleId);

                // Only send if updated, not created, and we're an author
                if ($success
                    && is_numeric($articleId)
                    && in_array($_SESSION['user']['id'], $article['authors'])
                ) {
                    trigger(
                        'sendmail',
                        $article['authors'],
                        $article['editors'],
                        null,
                        'editarticle',
                        array(
                            'article' => $article,
                            'log' => $req['post']['log']
                        ),
                        true  // Author-editors could see Bcc
                    );
                };

                if ($created) return trigger('http_redirect', $req['base'] . '/articles/' . $articleId . '/' . $article['permalink']);
            };
            if (isset($req['get']['unlink'])) {
                if (!pass('can', 'delete', 'article', $articleId)) return trigger('http_status', 403);
                $saved = true;
                $fname = array_shift($path);
                foreach ($article['versions'] as $version) {
                    foreach ($version['files'] as $key => $file) {
                        if ($file['name'] === $fname
                            && file_exists($article['files_dir'] . '/' . $file['name'])
                        ) {
                            $success = unlink($article['files_dir'] . '/' . $file['name']);
                            if ($success) {
                                unset($version['files'][$key]);
                                trigger('article_version_save', null, $version['files'], $version['id']);
                            };
                            break;
                        };
                    };
                };

                if ($success) return trigger('http_redirect', $req['base'] . '/articles/' . $articleId . '/' . $article['permalink']);

                // Reload to be aware of changes
                $article = grab('article', $articleId);
            };
            if (is_numeric($articleId)) {

                if (isset($req['post']['review'])) {
                    if (!pass('form_validate', 'review')) return trigger('http_status', 440);
                    $saved = true;
                    if (!(pass('can', 'delete', 'article', $articleId)
                        || pass('can', 'edit', 'issue', $article['issueId'])
                        || $article['isPeer'])
                    ) return trigger('http_status', 403);
                    if (!isset($req['post']['userdata'])) {
                        $req['post']['userdata'] = array();
                    };
                    if (isset($req['post']['peers'])) {
                        $req['post']['peers'] = grab('clean_userids', $req['post']['peers'], $req['post']['userdata']);
                    };
                    if (count($article['versions']) > 0) {
                        $req['post']['versionId'] = $article['versions'][count($article['versions'])-1]['id'];
                    };
                    $req['post']['articleId'] = $articleId;
                    // This handles create and update
                    $success = grab('review_save', $req['post'], $req['files']);
                    if ($success) {
                        $article = grab('article', $articleId);
                    };
                };

                // View only from this point
                if (!(pass('can', 'view', 'article', $articleId)
                    || pass('can', 'edit', 'article', $articleId)
                    || pass('can', 'view', 'issue', $article['issueId'])
                    || $article['isPeer'])
                ) return trigger('http_status', 403);

                $history = grab(
                    'history',
                    array(
                        'objectType' => 'article',
                        'objectId' => $articleId,
                        'order' => 'DESC'
                    )
                );
            } else {
                // New article editor
                $article['authors'] = array($_SESSION['user']['id']);
            };
            $deadline = (new DateTime())->modify($PPHP['config']['reviews']['deadline_modifier'])->format('Y-m-d');
            trigger(
                'render',
                'articles_edit.html',
                array(
                    'saved' => $saved,
                    'success' => $success,
                    'bad_format' => $bad_format,
                    'article' => $article,
                    'issues' => grab('issues'),
                    'history' => $history,
                    'history_type' => 'article',
                    'history_id' => $history_id,
                    'deadline' => $deadline
                )
            );
        } else {
            $search = array('byStatus' => true);
            if (isset($req['post']['keyword'])) {
                if (!pass('form_validate', 'article_search')) return trigger('http_status', 440);
                $search['keyword'] = $req['post']['keyword'];
            } elseif (isset($req['get']['filter'])) {
                switch ($req['get']['filter']) {
                case 'noreviews':
                    $search['current'] = true;
                    $search['noReviews'] = true;
                    break;
                case 'miapeers':
                    $search['current'] = true;
                    $search['miaPeers'] = true;
                    break;
                case 'latereviews':
                    $search['current'] = true;
                    $search['lateReviews'] = true;
                    break;
                };
            } else {
                $search['current'] = true;
            };
            trigger(
                'render',
                'articles.html',
                array(
                    'articles' => grab('articles', $search)
                )
            );
        };

    }
);
