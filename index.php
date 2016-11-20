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
 * @license   http://www.gnu.org/licenses/agpl-3.0.txt  GNU AGPL version 3
 * @link      https://github.com/vphantom/pyriteview
 */

// Load dependencies provided by Composer
require_once __DIR__ . '/vendor/autoload.php';

// Supplements to sphido/event

/**
 * Trigger an event and get the last return value
 *
 * Parameters are passed as-is to trigger().
 *
 * @return mixed The last return value of the result stack.
 */
function grab()
{
    return array_pop(call_user_func_array('trigger', func_get_args()));
};

/**
 * Trigger an event and test falsehood of the last return value
 *
 * Parameters are passed as-is to trigger()
 *
 * @return bool Whether the last result wasn't false
 */
function pass()
{
    return array_pop(call_user_func_array('trigger', func_get_args())) !== false;
};

// Load core components before modular components
require_once 'lib/pdb.php';
require_once 'lib/router.php';

// Load modular components
foreach (glob(__DIR__ . '/modules/*.php') as $fname) {
    include_once $fname;
};

// Database
$GLOBALS['db'] = new PDB('sqlite:' . __DIR__ . '/var/main.db');

// From the command line means install mode
if (php_sapi_name() === 'cli') {
    trigger('install');
    return;
};

// Start up
trigger('startup');
trigger('title', 'PyriteView');

// Router
Router::run();

// Shut down
trigger('shutdown');

?>
