PRAGMA foreign_keys=OFF;
BEGIN TRANSACTION;
CREATE TABLE test2 ( id INTEGER PRIMARY KEY, bar text not null, baz integer not null );
COMMIT;
