<?php

$blogconfig = require(__DIR__ . '/../../blogconfig.php');

$params = [
	'client_id' => $blogconfig['client_id'],
	'allow_signup' => false,
	'scope' => 'repo',
	'state' => hash_hmac('sha256', $_SERVER['REMOTE_ADDR'], $blogconfig['client_secret'])
];

$github_url = 'https://github.com';

header('Location: ' . $github_url . '/login/oauth/authorize?' . http_build_query($params));