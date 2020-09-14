<?php

use PsalmDotOrg\PluginRepository;

require_once __DIR__ . '/../vendor/autoload.php';

try {
    PluginRepository::updateList();
} catch (RuntimeException $e) {
    echo $e->getMessage() . PHP_EOL;
    exit($e->getCode());
}

exit(0);
