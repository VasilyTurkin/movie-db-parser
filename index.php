<?php

use DiDom\Exceptions\InvalidSelectorException;
use Dotenv\Dotenv;
use Src\Parser;
use Src\Db;

require_once 'vendor/autoload.php';

$dotenv = Dotenv::createUnsafeImmutable(__DIR__);
$dotenv->load();

$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$host = getenv('DB_HOST');
$dbName = getenv('DB_NAME');

$dbDriver = new PDO("mysql:host=$host;dbname=$dbName", $user, $pass);
$db = new Db($dbDriver);
$parser = new Parser($db);

try {
    $parser->run();
} catch (InvalidSelectorException $e) {
}
