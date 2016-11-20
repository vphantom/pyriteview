<?php

/**
 * FormSafe
 *
 * PHP version 5
 *
 * @category  Library
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2016 Stéphane Lavergne
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU AGPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */

namespace FormSafe;

/**
 * Produce an opaque form name unique to current session
 *
 * @param string $form_name Application-wide unique name for this form
 *
 * @return string
 */
function hashname($form_name)
{
    return 'form' . md5($form_name . session_id());
}

on(
    'form_begin',
    function ($form_name) {
        $name = hashname($form_name);
        $token = md5(mcrypt_create_iv(32));
        $_SESSION[$name] = $token;
        return '<input type="hidden" name="'.$name.'" value="'.$token.'" />';
    }
);

on(
    'form_validate',
    function ($form_name) {
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
    }
);

