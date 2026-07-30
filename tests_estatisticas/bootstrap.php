<?php

require_once __DIR__ . '/vendor/autoload.php';

require_once __DIR__ . '/sei/src/sei/web/SEI.php';

if (!defined('DIR_SEI_WEB')) {
    define('DIR_SEI_WEB', __DIR__ . '/sei/src/sei/web/');
}
define('DIR_TEST', __DIR__);
define('DIR_PROJECT', __DIR__ . '/..');

error_reporting(E_ERROR);
restore_error_handler();

require_once __DIR__ . '/tests/CenarioBaseTestCase.php';
