<?php

class FormSafe
{

    private static function _hash($form_name)
    {
        return 'form' . md5($form_name . session_id());
    }

    public static function inject($form_name)
    {
        $name = self::_hash($form_name);
        $token = md5(mcrypt_create_iv(32));
        $_SESSION[$name] = $token;
        return '<input type="hidden" name="'.$name.'" value="'.$token.'" />';
    }

    public static function validate($form_name)
    {
        $name = self::_hash($form_name);
        $sess = (isset($_SESSION[$name]) ? $_SESSION[$name] : false);
        $_SESSION[$name] = ' ';
        unset($_SESSION[$name]);
        if ($sess && isset($_POST[$name]) && $_POST[$name] === $sess) {
            return true;
        } else {
            $_POST = array();
            return false;
        };
    }

}

?>
