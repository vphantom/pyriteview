<?php

/**
 * Index
 *
 * PHP version 5
 *
 * @category  Application
 * @package   PyritePHP
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2016 Stéphane Lavergne
 * @license   https://opensource.org/licenses/MIT  MIT
 * @link      https://github.com/vphantom/pyrite-php
 */

// Load dependencies provided by Composer
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Pyrite
Pyrite::bootstrap(__DIR__);

// Route request
Pyrite::run();

// Shut down
Pyrite::shutdown();
