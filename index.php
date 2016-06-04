<?php

require_once 'config.php';

ini_set('display_errors', 1); // temporarily
error_reporting(E_ALL); // temporarily

// Have all namespaced classes load automatically.
spl_autoload_extensions(".php");
spl_autoload_register();

// Connect to DB.
try {
    $db = \core\app::load_db($dbconfig);
} catch (Exception $ex) {
    die($ex->getMessage());
}

// Try and load the page.
$app = new \core\app($db);
try {
    $app->run();
} catch (\core\app_exception $ex) {
    // Something went terribly wrong.
    $app->show_fatal_error($ex->getMessage(), $ex->getBacktrace());
}
