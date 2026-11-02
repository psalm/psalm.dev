<?php

use PsalmDotOrg\OnlineChecker;
use PsalmDotOrg\Settings;

http_response_code(500);

if (!isset($_GET['r']) ) {
    http_response_code(303);
    header('Location: /');
    exit;
}

$hash = trim($_GET['r']);

if (!preg_match('/^[a-z0-9]+$/', $hash)) {
    if (($_GET['format'] ?? null) === 'json') {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'not found']);
        exit;
    }

    $not_found_message = 'This code snippet does not exist.';
    require dirname(__DIR__) . '/includes/not_found.php';
}

require_once('../vendor/autoload.php');

/**
 * @var array{dsn:string, user:string, password:string}
 */
$db_config = require_once('../dbconfig.php');

try {
    $pdo = new PDO($db_config['dsn'], $db_config['user'], $db_config['password']);
} catch (PDOException $e) {
    error_log('Snippet database connection failed: ' . $e->getMessage());
    die('Connection to database failed');
}

$stmt = $pdo->prepare('select *, UNIX_TIMESTAMP(`created_on`) as `created_on` from `codes` where `hash` = :hash');
$stmt->execute([':hash' => $hash]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    if (($_GET['format'] ?? null) === 'json') {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'not found']);
        exit;
    }

    $not_found_message = 'This code snippet does not exist.';
    require dirname(__DIR__) . '/includes/not_found.php';
}

$code = $result['code'];
$created_on = (new DateTime())->setTimestamp($result['created_on']);
$created_on->setTimezone(new DateTimeZone("UTC"));

$settings_fields = Settings::names();
$settings = Settings::fromRow($result);

// D: without it $ also matches before a trailing newline, so "8.1\n" would pass
$php_version_regex = '/' . OnlineChecker::SUPPORTED_PHP_VERSION_REGEX . '/D';
// NULL for snippets saved before the version was stored
$stored_php_version = $result['php_version'] ?? null;

if (!is_string($stored_php_version) || !preg_match($php_version_regex, $stored_php_version)) {
    $stored_php_version = null;
}

const PHP_PARSER_VERSION = '4.0.0';

if (isset($_GET['format'])) {
    if ($_GET['format'] === 'raw') {
        http_response_code(200);
        header('Content-Type: text/plain');
        echo $code;
        exit;
    }

    if ($_GET['format'] === 'json') {
        header('Content-Type: application/json');
        set_exception_handler([\PsalmDotOrg\ExceptionHandler::class, 'json']);

        $body = json_encode(
            [
                'hash' => $result['hash'],
                'code' => $code,
                'settings' => $settings,
                'php_version' => $stored_php_version,
            ],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        );

        http_response_code(200);
        echo $body;
        exit;
    }

    if ($_GET['format'] === 'results') {
        $php_version = $_GET['php'] ?? $stored_php_version ?? OnlineChecker::defaultPhpVersion();

        header('Content-Type: application/json');

        if (!is_string($php_version) || !preg_match($php_version_regex, $php_version)) {
            http_response_code(422);
            echo json_encode(['error' => ['message' => 'PHP version ' . $php_version . ' not supported']], JSON_HEX_TAG | JSON_HEX_AMP);
            exit;
        }

        http_response_code(200);
        set_exception_handler([\PsalmDotOrg\ExceptionHandler::class, 'json']);

        echo json_encode(PsalmDotOrg\OnlineChecker::getResults($code, $settings, false, $php_version), JSON_HEX_TAG | JSON_HEX_AMP);
        exit;
    }

    http_response_code(400);
    header('Content-Type: text/plain');
    echo 'Unrecognized format';
    exit;
}

http_response_code(200);
// Snippets are user-submitted code: crawlable for their links, but kept out of the index
header('X-Robots-Tag: noindex, follow');
$canonical_url = false;
?>
<html lang="en">
<head>
<title>Psalm snippet <?= $hash ?></title>
<meta name="robots" content="noindex, follow" />
<script src="/assets/js/codemirror.min.js?2"></script>
<?php require('../includes/meta.php'); ?>
<link rel="stylesheet" href="/assets/css/playground.css?7">
</head>
<body class="front snippet_page">
<a class="pg_skip" href="#playground" onclick="return focusEditor();">Skip to editor</a>
<?php require('../includes/nav.php'); ?>
<?php
$playground_code = htmlentities($code);
$playground_saved_on = 'Saved ' . $created_on->format('M j Y H:i') . ' UTC';
require('../includes/playground.php');
?>
<script>
var settings = {
<?php foreach ($settings_fields as $field) : ?>
    <?php echo $field ?>: <?php echo $settings[$field] ? 'true' : 'false' ?>,
<?php endforeach ?>
};
var snippetPhpVersion = <?= json_encode($stored_php_version, JSON_HEX_TAG) ?>;
</script>
<?php require('../includes/script.php'); ?>
</body>
</html>
