<?php

use PsalmDotOrg\OnlineChecker;
use PsalmDotOrg\Settings;
require_once dirname(__DIR__) . '/src/Settings.php';

// errors go to the server log; the response body is shown to the user by "Get link"
ini_set('display_errors', '0');
error_reporting(-1);
http_response_code(500);

if (!isset($_POST['code']) || !isset($_POST['settings']) ) {
    http_response_code(412);
    echo 'Expecting code';
    exit();
}

$code = trim($_POST['code']);

$settings = json_decode($_POST['settings'], true);

if (!$settings) {
    http_response_code(412);
    echo 'Expecting settings';
    exit();
}

if (substr($code, 0, 5) !== '<?php') {
    http_response_code(412);
    echo 'There should be a PHP tag at the beginning of the snippet';
    exit();
}

if ($code === '<?php') {
    http_response_code(412);
    echo 'There is no code';
    exit();
}

if (strlen($code) > 6000) {
    http_response_code(412);
    echo 'There is too much code';
    exit();
}

require_once('../vendor/autoload.php');

$php_version = $_POST['php'] ?? OnlineChecker::defaultPhpVersion();

if (!is_string($php_version) || !preg_match('/' . OnlineChecker::SUPPORTED_PHP_VERSION_REGEX . '/D', $php_version)) {
    http_response_code(412);
    echo 'Unsupported PHP version';
    exit();
}

// the version is part of the hash so the same code saved for different PHP versions gets separate links
$hash = substr(hash_hmac('sha256', $code . json_encode($settings) . $php_version, 'not much of a secret'), 0, 10);

/**
 * @var array{dsn:string, user:string, password:string}
 */
$db_config = require_once('../dbconfig.php');

try {
    $pdo = new PDO($db_config['dsn'], $db_config['user'], $db_config['password']);
} catch (PDOException $e) {
    error_log('add_code: ' . $e->getMessage());
    die(
        'Could not save the snippet: cannot connect to the database (error ' . $e->getCode() . '). '
        . 'Please try again later or report it at https://github.com/psalm/psalm.dev/issues'
    );
}

try {
    $stmt = $pdo->prepare('select `code`, `created_on` from `codes` where `hash` = :hash');
    $stmt->execute([':hash' => $hash]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    $port = (int) $_SERVER['SERVER_PORT'];
    $server = $_SERVER['SERVER_NAME'] . ($port === 80 || $port === 443 ? '' : ':' . $port);

    if ($result) {
        http_response_code(200);
        echo $server . '/r/' . $hash;
        exit();
    }

    $data = ['hash' => $hash, 'code' => $code, 'ip' => $_SERVER['REMOTE_ADDR'], 'php_version' => $php_version];

    $settings_fields = Settings::names();

    foreach ($settings_fields as $field) {
        $data[$field] = ($settings[$field] ?? false) ? '1' : '';
    }
    $insert_sql = 'insert into `codes` (`' . implode('`,`', array_keys($data)) .  '`) values (:' . implode(', :', array_keys($data)) . ')';

    $stmt = $pdo->prepare($insert_sql);
    $stmt->execute($data);
} catch (PDOException $e) {
    error_log('add_code: ' . $e->getMessage());
    // the SQLSTATE code says what kind of failure it was without exposing queries or server paths
    die(
        'Could not save the snippet: database error (SQLSTATE ' . $e->getCode() . '). '
        . 'Please try again later or report it at https://github.com/psalm/psalm.dev/issues'
    );
}

http_response_code(200);
echo $server . '/r/' . $hash;
exit();

