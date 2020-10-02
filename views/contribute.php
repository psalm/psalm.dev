<?php

require_once('../vendor/autoload.php');
error_reporting(E_ALL);
ini_set('html_errors', '1');
ini_set('display_errors', '1');

$contribution_markdown = file_get_contents(dirname(__DIR__) . '/assets/pages/contribute.md');

$html = Muglug\Blog\ArticleRepository::convertMarkdownToHtml($contribution_markdown, null);
?>
<html>
<head>
<title>Contribute to Psalm</title>
<?php require('../includes/meta.php'); ?>
</head>
<body>
<?php require('../includes/nav.php'); ?>
<div class="post">
<h1><?= Muglug\Blog\AltHeadingParser::preventOrphans('Contribute to Psalm!') ?></h1>
<div class="contribute_intro">
  <img src="https://psalm.dev/assets/images/beardy_me.jpg" alt="" width="75" height="100" style="align: right; padding: 0 20px 10px 10px; float: left;">
  <p>Hi, I'm Matt, the creator of Psalm. I work at Vimeo, and part of my job there involves maintaining Psalm (I spend a lot of my spare time on Psalm as well).
  </p>
</div>
<?= $html ?>
</div>
<?php require('../includes/footer.php'); ?>
</body>
</html>
