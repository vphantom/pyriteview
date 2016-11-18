## PDB Class

 * **Author:** Stéphane Lavergne <lis@imars.com>
 * **Copyright:** 2016 Stéphane Lavergne
 * **License:** https://opensource.org/licenses/MIT  MIT License
 * **Link:** https://github.com/vphantom/php-library


### __construct(*$dsn*, *$user* = null, *$pass* = null)

Constructor

**Parameters:**

* `$dsn` — `string` — Database specification to pass to PDO
* `$user` — `string` — (Optional) Username for database
* `$pass` — `string` — (Optional) Password for database

**Returns:** `object` — PDB instance


### begin()

Begin transaction

**Returns:** `object` — PDB instance (for chaining)


### commit()

Commit pending transaction

**Returns:** `object` — PDB instance (for chaining)


### rollback()

Rollback pending transaction

**Returns:** `object` — PDB instance (for chaining)


### exec(*$q*, *$args* = null)

Execute a result-less statement

Typically `INSERT`, `UPDATE`, `DELETE`.

**Parameters:**

* `$q` — `string` — SQL query with '?' value placeholders
* `$args` — `array` — (Optional) List of values corresponding to placeholders

**Returns:** `int` — Number of affected rows, false on error


### lastInsertId()

Fetch last `INSERT`/`UPDATE` auto_increment ID

This is a shortcut to the API value, to avoid performing a `SELECT LAST_INSERT_ID()` round-trip manually.

**Returns:** `string` — Last ID if supported/available


### selectAtom(*$q*, *$args* = null)

Fetch a single value

The result is the value returned in row 0, column 0. Useful for `COUNT(*)` and such. Extra columns/rows are safely ignored.

**Parameters:**

* `$q` — `string` — SQL query with '?' value placeholders
* `$args` — `array` — (Optional) List of values corresponding to placeholders

**Returns:** `mixed` — Single result cell, null if no results


### selectList(*$q*, *$args* = null)

Fetch a simple list of result values

The result is a list of the values found in the first column of each row.

**Parameters:**

* `$q` — `string` — SQL query with '?' value placeholders
* `$args` — `array` — (Optional) List of values corresponding to placeholders

**Returns:** `array` — 


### selectSingleArray(*$q*, *$args* = null)

Fetch a single row as associative array

Fetches the first row of results, so from the caller's side it's equivalent to `selectArray()[0]` however only the first row is ever fetched from the server.

Note that if you're not selecting by a unique ID, a `LIMIT 1` should still be specified in SQL for optimal performance.

**Parameters:**

* `$q` — `string` — SQL query with '?' value placeholders
* `$args` — `array` — (Optional) List of values corresponding to placeholders

**Returns:** `array` — Single associative row


### selectArray(*$q*, *$args* = null)

Fetch all results in an associative array

**Parameters:**

* `$q` — `string` — SQL query with '?' value placeholders
* `$args` — `array` — (Optional) List of values corresponding to placeholders

**Returns:** `array` — All associative rows


### selectArrayIndexed(*$q*, *$args* = null)

Fetch all results in an associative array, index by first column

Whereas `selectArray()` returns a list of associative rows, this returns an associative array keyed on the first column of each row.

**Parameters:**

* `$q` — `string` — SQL query with '?' value placeholders
* `$args` — `array` — (Optional) List of values corresponding to placeholders

**Returns:** `array` — All associative rows, keyed on first column


### selectArrayPairs(*$q*, *$args* = null)

Fetch 2-column result into associative array

Create one key per row, indexed on the first column, containing the second column. Handy for retreiving key/value pairs.

**Parameters:**

* `$q` — `string` — SQL query with '?' value placeholders
* `$args` — `array` — (Optional) List of values corresponding to placeholders

**Returns:** `array` — Associative pairs
