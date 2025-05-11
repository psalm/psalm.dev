<?php

require_once('../../vendor/autoload.php');

$blog = new Muglug\Blog\MarkdownBlog(dirname(__DIR__, 2) . '/assets/articles/');

$articles = $blog->articles->getAll();

?>
<html>
<head>
<title>Psalm - Blog</title>
<?php require('../../includes/meta.php'); ?>
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
