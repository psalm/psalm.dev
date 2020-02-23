<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('../vendor/autoload.php');
// not included in autoload

if (!isset($_POST['code'])) {
    exit;
}

if (!isset($_POST['settings'])) {
    exit;
}

$fix_file = $_POST['fix'] ?? false;

$settings = json_decode($_POST['settings'], true);
if (!is_array($settings)) {
    exit;
}

const PHP_PARSER_VERSION = '4.0.0';

// Set user-defined error handler function
set_exception_handler([\PsalmDotOrg\ExceptionHandler::class, 'json']);

$file_contents = $_POST['code'];

$file_hash = md5($file_contents);

$cached_results = [
    
];

if (!$fix_file) {
    foreach ($cached_results as $cached_result) {
        $decoded = json_decode($cached_result, true);

        if ($decoded['hash'] === $file_hash) {
            echo $cached_result;
            exit;
        }
    }
}

if (strlen($file_contents) > 10000) {
    header(sprintf('HTTP/1.0 %s', 418));
    echo json_encode(['error' => 'Code too long']);
    exit;
}

$php_version = $_POST['php'] ?? '7.4';
		
if (!preg_match('/^[57]\.\d$/', $php_version)) {
    echo json_encode(['error' => 'PHP version ' . $php_version . ' not supported']);
    exit;
}

echo json_encode(PsalmDotOrg\OnlineChecker::getResults($file_contents, $settings, $fix_file, $php_version));
