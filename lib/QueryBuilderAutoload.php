<?php
require_once __DIR__.'/vendor/SplClassLoader.php';

$loader = new SplClassLoader('SQL', __DIR__);
$loader->register();