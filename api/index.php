<?php

require_once('../vendor/autoload.php');
require_once('functions.php');

// init Slim
$app = new \Slim\Slim();
$app->view(new \JsonApiView());
$app->add(new \JsonApiMiddleware());
$app->config('debug', true);

// init bdd
$bdd = new PDO('mysql:host=localhost;dbname=android-starter', 'root', '');
$bdd->exec('SET CHARACTER SET utf8');

// init logger
$logger = new Katzgrau\KLogger\Logger('C:/wamp/www/android-starter/logs');

// add all
require_once('users.php');

// Run !
$app->run();

?>