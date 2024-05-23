<?php
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

require_once dirname(__FILE__, 5) . '/wp-load.php';
global $cacheInvalidator;
$cacheInvalidator->processQueue();
