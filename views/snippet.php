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
/** @var array<string, mixed>|false $result FETCH_ASSOC: a row or false */
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result === false) {
    if (($_GET['format'] ?? null) === 'json') {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'not found']);
        exit;
    }

    $not_found_message = 'This code snippet does not exist.';
    require dirname(__DIR__) . '/includes/not_found.php';
    exit; // not_found.php exits too, but Psalm does not see that through the require
}

$code = (string) $result['code'];
$created_on = (new DateTime())->setTimestamp((int) $result['created_on']);
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

// NULL for snippets saved before the level was stored: they ran at the default level
$stored_error_level = OnlineChecker::parseErrorLevel($result['error_level'] ?? null);

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
                'error_level' => $stored_error_level,
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

        $error_level = OnlineChecker::parseErrorLevel(
            $_GET['level'] ?? $stored_error_level ?? OnlineChecker::DEFAULT_ERROR_LEVEL
        );

        if ($error_level === null) {
            http_response_code(422);
            echo json_encode(['error' => ['message' => 'Error level must be 1 to 8']], JSON_HEX_TAG | JSON_HEX_AMP);
            exit;
        }

        http_response_code(200);
        set_exception_handler([\PsalmDotOrg\ExceptionHandler::class, 'json']);

        echo json_encode(
            PsalmDotOrg\OnlineChecker::getResults($code, $settings, false, $php_version, $error_level),
            JSON_HEX_TAG | JSON_HEX_AMP
        );
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
// Same preview for every snippet: it never shows the submitted code
$og = [
    'title' => 'Psalm snippet',
    'description' => 'PHP code analyzed by Psalm, the static analysis tool for PHP. Open it to see the code and the issues Psalm found.',
    'url' => 'https://psalm.dev/r/' . $hash,
    'image' => '/assets/og/snippet.png',
    'image_alt' => 'Psalm code snippet: a PHP function with a PossiblyNullPropertyFetch issue underlined in red',
];
?>
<html lang="en">
<head>
<title>Psalm snippet <?= $hash ?></title>
<meta name="robots" content="noindex, follow" />
<?php require_once __DIR__ . '/../includes/asset.php'; ?>
<script src="<?= asset_url('/assets/js/codemirror.min.js') ?>"></script>
<?php require('../includes/meta.php'); ?>
<link rel="stylesheet" href="<?= asset_url('/assets/css/playground.css') ?>">
</head>
<body class="front snippet_page">
<a class="pg_skip" href="#playground" onclick="return focusEditor();">Skip to editor</a>
<?php require('../includes/nav.php'); ?>
<?php
require_once '../includes/playground.php';
render_playground(htmlentities($code), 'Saved ' . $created_on->format('M j Y H:i') . ' UTC');
?>
<script>
var settings = {
<?php foreach ($settings_fields as $field) : ?>
    <?php echo $field ?>: <?php echo $settings[$field] ? 'true' : 'false' ?>,
<?php endforeach ?>
};
var snippetPhpVersion = <?= json_encode($stored_php_version, JSON_HEX_TAG) ?>;
var snippetErrorLevel = <?= json_encode($stored_error_level, JSON_HEX_TAG) ?>;
</script>
<?php require('../includes/script.php'); ?>
</body>
</html>
