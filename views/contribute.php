<?php

require_once('../vendor/autoload.php');
error_reporting(E_ALL);
ini_set('html_errors', '1');
ini_set('display_errors', '1');

$contribution_markdown = file_get_contents(dirname(__DIR__) . '/assets/pages/contribute.md');

$article = PsalmDotOrg\ArticleRepository::convertMarkdownToHtml($contribution_markdown, null);
?>
<html>
<head>
<title>Contribute to Psalm</title>
<script src="/assets/js/codemirror.js"></script>
<link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/7707372/css/fonts.css" />
<link rel="stylesheet" href="/assets/css/site.css?1">
<link rel="icon" type="image/png" href="/favicon.png">
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
</head>
<body>
<?php require('../includes/nav.php'); ?>
<div class="post">
<h1><?= PsalmDotOrg\AltHeadingParser::preventOrphans('Contribute to Psalm!') ?></h1>
<?= $article->html ?>
</div>
<?php require('../includes/footer.php'); ?>
</body>
</html>
