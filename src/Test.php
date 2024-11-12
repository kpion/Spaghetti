<?php 

namespace Kpion\Spaghetti;

require __DIR__ . '/../vendor/autoload.php';

// Create an instance of Spaghetti and load the specified .phd file
$sp = new Spaghetti($argv);
require_once $sp->inputFile(false);

