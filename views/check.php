<?php

use PsalmDotOrg\OnlineChecker;

error_reporting(E_ALL);
ini_set('display_errors', '0');
http_response_code(500);

require_once('../vendor/autoload.php');
// not included in autoload

if (!isset($_POST['code']) || !is_string($_POST['code'])) {
    http_response_code(400);
    exit;
}

if (!isset($_POST['settings']) || !is_string($_POST['settings'])) {
    http_response_code(400);
    exit;
}

$fix_file = filter_var($_POST['fix'] ?? false, FILTER_VALIDATE_BOOLEAN);

$settings = json_decode($_POST['settings'], true);
if (!is_array($settings)) {
    http_response_code(400);
    exit;
}

const PHP_PARSER_VERSION = '4.0.0';

header('Content-Type: application/json');

// Set user-defined error handler function
set_exception_handler([\PsalmDotOrg\ExceptionHandler::class, 'json']);

$file_contents = $_POST['code'];

if (strlen($file_contents) > 100000) {
    http_response_code(418);
    echo json_encode(['error' => ['message' => 'Code too long', 'line_from' => 0]], JSON_HEX_TAG | JSON_HEX_AMP);
    exit;
}

$php_version = $_POST['php'] ?? OnlineChecker::defaultPhpVersion();

if (!is_string($php_version) || !preg_match('/' . OnlineChecker::SUPPORTED_PHP_VERSION_REGEX . '/D', $php_version)) {
    http_response_code(422);
    echo json_encode(['error' => 'PHP version ' . $php_version . ' not supported'], JSON_HEX_TAG | JSON_HEX_AMP);
    exit;
}

$error_level = OnlineChecker::parseErrorLevel($_POST['level'] ?? OnlineChecker::DEFAULT_ERROR_LEVEL);

if ($error_level === null) {
    http_response_code(422);
    echo json_encode(['error' => 'Error level must be 1 to 8'], JSON_HEX_TAG | JSON_HEX_AMP);
    exit;
}

$results = PsalmDotOrg\OnlineChecker::getResults($file_contents, $settings, $fix_file, $php_version, $error_level);

http_response_code(200);
echo json_encode($results, JSON_HEX_TAG | JSON_HEX_AMP);
