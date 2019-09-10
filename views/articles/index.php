<?php

require_once('../../vendor/autoload.php');

$articles = PsalmDotOrg\ArticleRepository::getAll();

?>
<html>
<head>
<title>Psalm - Articles</title>
<script src="/assets/js/fetch.js"></script>
<script src="/assets/js/codemirror.js"></script>
<link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/7707372/css/fonts.css" />
<link rel="stylesheet" href="/assets/css/site.css?1">
<link rel="icon" type="image/png" href="favicon.png">
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
</head>
<body>
<?php require('../../includes/nav.php'); ?>
<div class="post">
<?php foreach ($articles as $i => $article): ?>
<?php if ($i !== 0): ?><hr><?php endif ?>
<h3><a href="/articles/<?= $article->slug ?>"><?= $article->title ?></a></h3>
<p class="meta inline"><?= date('F j, Y', strtotime($article->date)) ?> by <?= $article->author ?></p>
<p><?= $article->description ?></p>
<?php endforeach; ?>
</div>
<?php require('../../includes/footer.php'); ?>
</body>
</html>
