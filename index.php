<?php

require 'vendor/autoload.php';

$config = require 'config.php';

use JumpTwentyFour\Client as HttpClient;

$client = new HttpClient($config);