<?php

namespace PyriteView\User;

on('install', function () {
    global $db;
    echo "    Installing " . __NAMESPACE__ . "... ";
    $db->exec("
        CREATE TABLE IF NOT EXISTS 'users' (
            id int,
            email varchar(255)
        )
        ");
    echo "    done!\n";
});

on('startup', function () {
    // TODO: Authenticate based on session
    // TODO: Trigger 'login' if non-anonymous
}, 2);

?>
