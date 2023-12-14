<?php

use DiDom\Exceptions\InvalidSelectorException;
use Src\Parser;

require_once 'vendor/autoload.php';

$parser = new Parser();

try {
    $parser->run();
} catch (InvalidSelectorException $e) {
}
