<?php

/**
 * Twigger
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

/**
 * Twigger class
 *
 * @category  Library
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2016 Stéphane Lavergne
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU AGPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */
class Twigger
{
    private static $_twig;
    private static $_title = '';
    private static $_status = 200;
    private static $_template;
    private static $_safeBody = '';

    /**
     * Initialize wrapper around Twig templating and display headers
     *
     * @return null
     */
    public static function startup()
    {
        $twig = new \Twig_Environment(
            new \Twig_Loader_Filesystem(__DIR__ . '/../templates'),
            array(
                // 'cache' => __DIR__ . '/var/twig_cache',
                'autoescape' => true,
            )
        );
        self::$_twig = $twig;
        self::$_template = $twig->loadTemplate('layout.html');

        if (self::$_status !== 200) {
            http_response_code(self::$_status);
        };
        echo self::$_template->renderBlock('init', array('http_status' => self::$_status));
        flush();
        ob_start();
    }

    /**
     * Clean up content capture and display main template
     *
     * @return null
     */
    public static function shutdown()
    {
        $headName = 'head';
        $footName = 'foot';
        if (self::$_status !== 200) {
            $headName .= '_error';
            $footName .= '_error';
        };
        $body = ob_get_contents();
        ob_end_clean();
        echo self::$_template->renderBlock($headName, array('http_status' => self::$_status, 'title' => self::$_title));
        echo self::$_safeBody;
        echo self::$_template->renderBlock($footName, array('http_status' => self::$_status, 'body' => $body));
    }

    /**
     * Set HTTP response status code
     *
     * @param int $code New code (between 100 and 599)
     *
     * @return null
     */
    public static function status($code)
    {
        if ($code >= 100  &&  $code < 600) {
            self::$_status = (int)$code;
        };
    }

    /**
     * Prepend new section to page title
     *
     * @param string $prepend New section of title text
     * @param string $sep     Separator with current title
     *
     * @return null
     */
    public static function title($prepend, $sep = ' - ')
    {
        self::$_title = $prepend . (self::$_title !== '' ? ($sep . self::$_title) : '');
    }

    /**
     * Render a template file
     *
     * @param string $name File name from within templates/
     * @param array  $args Associative array of variables to pass along
     *
     * @return null
     */
    public static function render($name, $args)
    {
        self::$_safeBody .= self::$_twig->render($name, $args);
    }
}

on('startup', 'Twigger::startup', 99);
on('shutdown', 'Twigger::shutdown', 1);
on('render', 'Twigger::render');
on('title', 'Twigger::title');
on('http_status', 'Twigger::status');

