<?php
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

$stmt = $pdo->prepare('select *, UNIX_TIMESTAMP(`created_on`) as `created_on` from `codes` where `hash` = :hash');
$stmt->execute([':hash' => $hash]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$result) {
    header('Location: /');
    exit;
}

$code = $result['code'];
$created_on = (new DateTime())->setTimestamp($result['created_on']);
$created_on->setTimezone(new DateTimeZone("UTC"));

$settings_fields = [
    'unused_variables',
    'unused_methods',
    'memoize_properties',
    'memoize_method_calls',
    'check_throws',
    'strict_internal_functions',
    'allow_phpstorm_generics'
];

const PHP_PARSER_VERSION = '4.0.0';

if (isset($_GET['format'])) {
    if ($_GET['format'] === 'raw') {
        header('Content-Type: text/plain');
        echo $code;
        exit;
    }

    if ($_GET['format'] === 'results') {
        $settings = array_intersect_key(
            array_map(function (string $val): bool {
                return (bool) intval($val);
            }, $result),
            array_flip($settings_fields)
        );

        $php_version = $_GET['php'] ?? '8.0';

        if (!preg_match('/^[578]\.\d$/', $php_version)) {
            echo json_encode(['error' => ['message' => 'PHP version ' . $php_version . ' not supported']]);
            exit;
        }

        header('Content-Type: application/json');
        set_exception_handler([\PsalmDotOrg\ExceptionHandler::class, 'json']);

        echo json_encode(PsalmDotOrg\OnlineChecker::getResults($code, $settings, false, $php_version));
        exit;
    }

    header('HTTP/1.1 400 Bad Request');
    header('Content-Type: text/plain');
    echo 'Unrecognized format';
    exit;
}


?>
<html>
<head>
<title>Psalm - a static analysis tool for PHP</title>
<script src="/assets/js/codemirror.js"></script>
<link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/7707372/css/fonts.css" />
<link rel="stylesheet" href="/assets/css/site.css?13">
<link rel="icon" type="image/png" href="/favicon.png">
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
</head>
<body class="front code_expanded">
<?php require('../includes/nav.php'); ?>
<div class="container snippet" id="page_container">
    <div class="cm_container">
        <textarea
            name="code"
            id="code"
            rows="20" style="visibility: hidden; font-family: monospace; font-size: 14px; max-width: 900px; min-width: 320px;"
        ><?= htmlentities($code) ?></textarea>
        <div id="psalm_output"></div>
        <div id="settings_panel" class="hidden"></div>
        <div class="button_bar">
            <span class="date">Snippet created on <?= $created_on->format('F j Y \a\t H:i') ?> UTC</span>

            <button onclick="javascript:toggleSettings();" id="settings"><svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><g fill="none" fill-rule="evenodd"><circle cx="9" cy="9" r="6.4" stroke="#000"/><path fill="#000" fill-rule="nonzero" d="M9 17.5l-1-2 4.1-.6v2l-3.1.6zm6-2.5L13.4 14l1.8-2.9 1.6 1.4L15 15zm2.5-6l-2 .8V9l-.6-2.6 2-.4.6 3zM15 3l-1 2-3-2.1 1.4-1.6L15 3zM9 .6l.8 2H9l-3 .6v-2l3-.6h.1zM3.1 3l2 .6L2.6 7 1.3 5.5l1.8-2.6zM.5 8.8l2.1-.6V9l.6 3H1L.5 9v-.2zm2.3 6l1-1.8L7 15l-1.5 1.7-2.6-1.9z"/><circle cx="9" cy="9" r="3.3" stroke="#000" transform="rotate(18 9 9)"/><path fill="#000" fill-rule="nonzero" d="M7.5 13.6l-.2-1.3 2.4.4-.4 1-1.8-.1zm3.7-.3l-.7-.9 1.5-1.2.6 1-1.4 1zm2.4-2.8h-1.3l.2-.4.1-1.5 1.2.1-.2 1.8zM13.3 7l-.9.8-1.2-1.6 1-.7L13.3 7zm-2.7-2.4v1.2l-.5-.2-1.7-.2.4-1.1 1.7.2zm-3.7.2l1 .7-2 1.4-.4-1 1.4-1.1zM4.5 7.4h1.2l-.2.5-.2 1.7-1.1-.4.2-1.7v-.1zm.2 3.6l.8-.7 1.3 1.6-1 .6L4.6 11z"/></g></svg> Settings</button>
            <button onclick="javascript:getLink();" id="getlink"><svg width="28" height="15" xmlns="http://www.w3.org/2000/svg"><g fill-rule="evenodd"><path d="M17.3 2.5A5 5 0 0 0 13 0H5a5 5 0 0 0-5 5v1a5 5 0 0 0 5 5h8a5 5 0 0 0 4.8-3.5H16a4 4 0 0 1-3.5 2h-7a4 4 0 1 1 0-8h7c1 0 2 .4 2.6 1h2.2z"/><path d="M10.4 12.5a5 5 0 0 0 4.4 2.5h8a5 5 0 0 0 5-5V9a5 5 0 0 0-5-5h-8A5 5 0 0 0 10 7.5h1.8a4 4 0 0 1 3.5-2h7a4 4 0 1 1 0 8h-7c-1 0-2-.4-2.7-1h-2.2z"/></g></svg> Get link</button>
        </div>
    </div>
</div>
<script>
var settings = {
<?php foreach ($settings_fields as $field) : ?>
    <?php echo $field ?>: <?php echo $result[$field] ? 'true' : 'false' ?>,
<?php endforeach ?>
};
</script>
<?php require('../includes/script.php'); ?>
</body>
</html>
