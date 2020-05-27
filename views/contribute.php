<?php

require_once('../vendor/autoload.php');
error_reporting(E_ALL);
ini_set('html_errors', '1');
ini_set('display_errors', '1');

$contribution_markdown = file_get_contents(dirname(__DIR__) . '/assets/pages/contribute.md');

$html = PsalmDotOrg\ArticleRepository::convertMarkdownToHtml($contribution_markdown, null);
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
<div class="contribute_intro">
  <img src="https://psalm.dev/assets/images/beardy_me.jpg" alt="" width="75" height="100" style="align: right; padding: 0 20px 10px 10px; float: left;">
  <p>Hi, I'm Matt, the creator of Psalm. I work at Vimeo, and part of my job there involves maintaining Psalm (I spend a lot of non-work time on Psalm as well).
  </p>
</div>
<?= $html ?>
</div>
<?php require('../includes/footer.php'); ?>
</body>
</html>
