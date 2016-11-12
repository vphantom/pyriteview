<?php

namespace FormSafe;

function hashname($form_name)
{
    return 'form' . md5($form_name . session_id());
}

on('form_begin', function ($form_name) {
    $name = hashname($form_name);
    $token = md5(mcrypt_create_iv(32));
    $_SESSION[$name] = $token;
    return '<input type="hidden" name="'.$name.'" value="'.$token.'" />';
});

on('form_validate', function ($form_name)
{
    $name = hashname($form_name);
    $sess = (isset($_SESSION[$name]) ? $_SESSION[$name] : false);
    $_SESSION[$name] = ' ';
    unset($_SESSION[$name]);
    if ($sess && isset($_POST[$name]) && $_POST[$name] === $sess) {
        return true;
    } else {
        $_POST = array();
        return false;
    };
});

?>
