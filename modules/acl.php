<?php

class ACL {
    public static function reload() {
        $user = User::whoami();
        // TODO: Load context-optimized ACL in session based on authenticated user
    }

    public static function can($verb, $object = null, $objectId = null) {
        // TODO: Return true or false, according to found permission
    }
}

on('login', 'ACL::reload');

?>
