<?php
if (!isset($_POST['code']) ) {
    http_response_code(412);
    echo 'Expecting code';
    exit();
}

$code = trim($_POST['code']);

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

require_once('vendor/autoload.php');

$hash = substr(hash_hmac('sha256', $code, 'not much of a secret'), 0, 10);

/**
 * @var array{dsn:string, user:string, password:string}
 */
$db_config = require_once('dbconfig.php');

try {
    $pdo = new PDO($db_config['dsn'], $db_config['user'], $db_config['password']);
} catch (PDOException $e) {
    die('Connection to database failed');
}

$stmt = $pdo->prepare('select `code`, `created_on` from `codes` where `hash` = :hash');
$stmt->execute([':hash' => $hash]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo $_SERVER['SERVER_NAME'] . '/r/' . $hash;
    exit();
}

$stmt = $pdo->prepare('insert into `codes` (`hash`, `code`, `ip`) values (:hash, :code, :ip)');
$stmt->execute([':hash' => $hash, ':code' => $code, 'ip' => $_SERVER['REMOTE_ADDR']]);

echo $_SERVER['SERVER_NAME'] . '/r/' . $hash;
exit();

