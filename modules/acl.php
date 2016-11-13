<?php

namespace PyriteView\ACL;

on('login', function () {
    // TODO: Load context-optimized ACL based on authenticated user
});

on('can_user', function($verb, $object = null, $objectId = null) {
    // TODO: Return true or false, according to the permission
});

?>
