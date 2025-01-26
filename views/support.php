<?php

error_reporting(E_ALL);
ini_set('html_errors', '1');
ini_set('display_errors', '1');
http_response_code(500);

require_once('../vendor/autoload.php');

$contribution_markdown = file_get_contents(dirname(__DIR__) . '/assets/pages/support.md');

$html = Muglug\Blog\ArticleRepository::convertMarkdownToHtml($contribution_markdown, null);

http_response_code(200);
?>
<html>
<head>
<title>Support Psalm</title>
<?php require('../includes/meta.php'); ?>
</head>
<body>
<?php require('../includes/nav.php'); ?>
<div class="post">
<h1><?= Muglug\Blog\AltHeadingParser::preventOrphans('Support Psalm!') ?></h1>
<?= $html ?>
</div>
<?php require('../includes/footer.php'); ?>
</body>
</html>
