#!/usr/bin/php
<?php

require __DIR__ . '/../vendor/autoload.php';

$app = new \Pacsar\Console\PacsarApplication('Pacsar', '0.0.1');

$app->setRootDir(__DIR__);

$app->run();
