#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Console\Application;
use WebsiteCloner\Cli\Commands\CloneCommand;

$application = new Application('Website Cloner', '1.0.0');
$application->add(new CloneCommand());
$application->run();