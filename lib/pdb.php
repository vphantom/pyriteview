<?php

/**
 * PDB
 *
 * Thin wrapper around PDO with convenience methods
 *
 * PHP Version 5
 *
 * @category  Library
 * @package   PDB
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2016 Stéphane Lavergne
 * @license   https://opensource.org/licenses/MIT  MIT License
 * @link      https://github.com/vphantom/php-library
 */

/**
 * PDB class
 *
 * @category  Library
 * @package   PDB
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2016 Stéphane Lavergne
 * @license   https://opensource.org/licenses/MIT  MIT License
 * @link      https://github.com/vphantom/php-library
 */

class PDB
{
    private $_dbh;
    private $_sth;
    private $_err;

    /**
     * Constructor
     *
     * @param string $dsn  Database specification to pass to PDO
     * @param string $user (Optional) Username for database
     * @param string $pass (Optional) Password for database
     *
     * @return object PDB instance
     */
    public function __construct($dsn, $user = null, $pass = null)
    {
        $this->_err = null;
        $this->_sth = null;
        $this->_dbh = new PDO($dsn, $user, $pass);  // Letting PDOException bubble up
    }

    /**
     * Begin transaction
     *
     * @return object PDB instance (for chaining)
     */
    public function begin()
    {
        $this->_dbh->beginTransaction();
        return $this;
    }

    /**
     * Commit pending transaction
     *
     * @return object PDB instance (for chaining)
     */
    public function commit()
    {
        $this->_dbh->commit();
        return $this;
    }

    /**
     * Rollback pending transaction
     *
     * @return object PDB instance (for chaining)
     */
    public function rollback()
    {
        $this->_dbh->rollBack();
        return $this;
    }

    /**
     * Prepare a statement
     *
     * @param string $q SQL query with '?' value placeholders
     *
     * @return object PDB instance (for chaining)
     */
    private function _prepare($q)
    {
        if ($this->_err) {
            return $this;
        };
        if (!$this->_sth = $this->_dbh->prepare($q)) {
            $this->_err = implode(' ', $this->_dbh->errorInfo());
        };
        return $this;
    }

    /**
     * Execute stored prepared statement
     *
     * @param array $args (Optional) List of values corresponding to placeholders
     *
     * @return object PDB instance (for chaining)
     */
    private function _execute($args = array())
    {
        if ($this->_err) {
            return $this;
        };
        if ($this->_sth) {
            if (!$this->_sth->execute($args)) {
                $this->_err = implode(' ', $this->_sth->errorInfo());
            };
        } else {
            $this->_err = "No statement to execute.";
        };
        return $this;
    }

    /**
     * Execute a result-less statement
     *
     * Typically INSERT, UPDATE, DELETE.
     *
     * @param string $q    SQL query with '?' value placeholders
     * @param array  $args (Optional) List of values corresponding to placeholders
     *
     * @return int Number of affected rows, false on error
     */
    public function exec($q, $args = array())
    {
        $this->_prepare($q)->_execute($args);
        return $this->_sth ? $this->_sth->rowCount() : false;
    }

    /**
     * Fetch last INSERT/UPDATE auto_increment ID
     *
     * This is a shortcut to the API value, to avoid performing a SELECT
     * LAST_INSERT_ID() round-trip manually.
     *
     * @return string Last ID if supported/available
     */
    public function lastInsertId()
    {
        return $this->dbh->lastInsertId();
    }

    /**
     * Fetch a single value
     *
     * The result is the value returned in row 0, column 0.  Useful for
     * COUNT(*) and such.  Extra columns/rows are safely ignored.
     *
     * @param string $q    SQL query with '?' value placeholders
     * @param array  $args (Optional) List of values corresponding to placeholders
     *
     * @return mixed Single result cell, null if no results
     */
    public function selectAtom($q, $args = array())
    {
        $this->exec($q, $args);
        // FIXME: Test if it is indeed NULL
        return $this->_sth ? $this->_sth->fetchColumn() : false;
    }

    /**
     * Fetch a simple list of result values
     *
     * The result is a list of the values found in the first column of each
     * row.
     *
     * @param string $q    SQL query with '?' value placeholders
     * @param array  $args (Optional) List of values corresponding to placeholders
     *
     * @return array
     */
    public function selectList($q, $args = array())
    {
        $this->exec($q, $args);
        return $this->_sth ? $this->_sth->fetchAll(PDO::FETCH_COLUMN, 0) : false;
    }

    /**
     * Fetch a single row as associative array
     *
     * Fetches the first row of results, so from the caller's side it's
     * equivalent to selectArray()[0] however only the first row is ever
     * fetched from the server.
     *
     * Note that if you're not selecting by a unique ID, a LIMIT of 1 should
     * still be specified in SQL for optimal performance.
     *
     * @param string $q    SQL query with '?' value placeholders
     * @param array  $args (Optional) List of values corresponding to placeholders
     *
     * @return array Single associative row
     */
    public function selectSingleArray($q, $args = array())
    {
        $this->exec($q, $args);
        return $this->_sth ? $this->_sth->fetch(PDO::FETCH_ASSOC) : false;
    }

    /**
     * Fetch all results in an associative array
     *
     * @param string $q    SQL query with '?' value placeholders
     * @param array  $args (Optional) List of values corresponding to placeholders
     *
     * @return array All associative rows
     */
    public function selectArray($q, $args = array())
    {
        $this->exec($q, $args);
        return $this->_sth ? $this->_sth->fetchAll(PDO::FETCH_ASSOC) : false;
    }

    /**
     * Fetch all results in an associative array, index by first column
     *
     * Whereas selectArray() returns a list of associative rows, this returns
     * an associative array keyed on the first column of each row.
     *
     * @param string $q    SQL query with '?' value placeholders
     * @param array  $args (Optional) List of values corresponding to placeholders
     *
     * @return array All associative rows, keyed on first column
     */
    public function selectArrayIndexed($q, $args = array())
    {
        $this->exec($q, $args);
        if ($this->_sth) {
            $result = array();
            while ($row = $this->_sth->fetch(PDO::FETCH_ASSOC)) {
                $result[$row[key($row)]] = $row;
            };
            return $result;
        } else {
            return false;
        };
    }

    /**
     * Fetch 2-column result into associative array
     *
     * Create one key per row, indexed on the first column, containing the
     * second column.  Handy for retreiving key/value pairs.
     *
     * @param string $q    SQL query with '?' value placeholders
     * @param array  $args (Optional) List of values corresponding to placeholders
     *
     * @return array Associative pairs
     */
    public function selectArrayPairs($q, $args = array())
    {
        $this->exec($q, $args);
        return $this->_sth ? $this->_sth->fetchAll(PDO::FETCH_COLUMN | PDO::FETCH_GROUP) : false;
    }

}

?>
