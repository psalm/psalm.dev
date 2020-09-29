<?php

$blogconfig = require(__DIR__ . '/../../blogconfig.php');

$client_id = $blogconfig['client_id'];
$client_secret = $blogconfig['client_secret'];
$state = (string) ($_GET['state'] ?? '');
$code = (string) ($_GET['code'] ?? '');

echo \Muglug\Blog\GithubAuth::getToken($state, $code, $client_id, $secret);

