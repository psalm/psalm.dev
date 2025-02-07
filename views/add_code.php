<?php

use PsalmDotOrg\Settings;
require_once dirname(__DIR__) . '/src/Settings.php';

ini_set("display_errors", 1);
ini_set("display_startup_errors", 1);
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

$hash = substr(hash_hmac('sha256', $code . json_encode($settings), 'not much of a secret'), 0, 10);

/**
 * @var array{dsn:string, user:string, password:string}
 */
$db_config = require_once('../dbconfig.php');

try {
    $pdo = new PDO($db_config['dsn'], $db_config['user'], $db_config['password']);
} catch (PDOException $e) {
    die('Connection to database failed');
}

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

$data = ['hash' => $hash, 'code' => $code, 'ip' => $_SERVER['REMOTE_ADDR']];

$settings_fields = Settings::names();

foreach ($settings_fields as $field) {
    $data[$field] = ($settings[$field] ?? false) ? '1' : '';
}
$insert_sql = 'insert into `codes` (`' . implode('`,`', array_keys($data)) .  '`) values (:' . implode(', :', array_keys($data)) . ')';

$pdo->query("ALTER TABLE `codes` ADD `disable_var_parsing` bit(1) NOT NULL DEFAULT b'0'");

$stmt = $pdo->prepare($insert_sql);
$stmt->execute($data);

http_response_code(200);
echo $server . '/r/' . $hash;
exit();

