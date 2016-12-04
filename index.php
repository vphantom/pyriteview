<?php

/**
 * Index
 *
 * PHP version 5
 *
 * @category  Application
 * @package   PyriteView
 * @author    Stéphane Lavergne <lis@imars.com>
 * @copyright 2016 Stéphane Lavergne
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt  GNU Affero GPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */

// Load dependencies provided by Composer
require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Pyrite
Pyrite::bootstrap(__DIR__);

// Route request
Pyrite::run();

// Shut down
Pyrite::shutdown();
