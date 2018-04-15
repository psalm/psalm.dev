<?
if (!isset($_GET['r']) ) {
    header('Location: /');
    exit;
}

$hash = trim($_GET['r']);

if (!preg_match('/^[a-z0-9]+$/', $hash)) {
    header('Location: /');
    exit;
}

require_once('../vendor/autoload.php');

/**
 * @var array{dsn:string, user:string, password:string}
 */
$db_config = require_once('../dbconfig.php');

try {
    $pdo = new PDO($db_config['dsn'], $db_config['user'], $db_config['password']);
} catch (PDOException $e) {
    die('Connection to database failed');
}

$stmt = $pdo->prepare('select `code`, UNIX_TIMESTAMP(`created_on`) as `created_on` from `codes` where `hash` = :hash');
$stmt->execute([':hash' => $hash]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    header('Location: /');
    exit;
}

$code = $result['code'];
$created_on = (new DateTime())->setTimestamp($result['created_on']);
$created_on->setTimezone(new DateTimeZone("UTC"));

?>
<html>
<head>
<title>Psalm - a static analysis tool for PHP</title>
<script src="/assets/js/fetch.js"></script>
<script src="/assets/js/codemirror.js"></script>
<link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/7707372/css/fonts.css" />
<link rel="stylesheet" href="/assets/css/site.css">
<link rel="icon" type="image/png" href="favicon.png">
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
</head>
<body class="code_expanded">
<div class="container" id="page_container">
    <? require('../includes/nav.php'); ?>
    <div class="cm_container">
        <textarea
            name="code"
            id="code"
            rows="20" style="visibility: hidden; font-family: monospace; font-size: 14px; max-width: 900px; min-width: 320px;"
        ><?= htmlentities($code) ?></textarea>
        <div class="button_bar">
            <span class="date">Snippet created on <?= $created_on->format('F j Y \a\t H:i') ?> UTC</span>
            
            <button onclick="javascript:getLink();">Get link</button>
        </div>
    </div>
</div>

<? require('../includes/script.php'); ?>
</body>
</html>
