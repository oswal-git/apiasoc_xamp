<?php
// require_once ("../classes/globals.php");
declare (strict_types = 1);
define('URL_SEPARATOR', '/');

/**
 *  Type declarations: *
 *                     - class/ interfaces names
 *                     - self
 *                     - array
 *                     - callable
 *                     - bool
 *                     - float
 *                     - int
 *                     - string
 *                     - iterable
 *                     - object
 *
 * */

require_once realpath("../vendor/autoload.php");

use Apiasoc\Classes\Globals;
use Apiasoc\Classes\Helper;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(dirname(__FILE__, 2));
$dotenv->load();

$globals = new Globals();

$helper = new Helper();
$helper->httpHeaderDev($_SERVER['REQUEST_METHOD']);