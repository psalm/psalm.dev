<?php

require_once('../../vendor/autoload.php');

$blog = new Muglug\Blog\MarkdownBlog(dirname(__DIR__, 2) . '/assets/articles/');

$articles = $blog->articles->getAll();

$canonical_url = 'https://psalm.dev/articles';
$meta_description = 'News, release announcements and deep dives into PHP static analysis from the Psalm team.';
$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES);
?>
<html lang="en">
<head>
<title>Blog - Psalm</title>
<?php require('../../includes/meta.php'); ?>
</head>
<body>
<?php require('../../includes/nav.php'); ?>
<div class="post">
<?php foreach ($articles as $i => $article): ?>
<?php if ($i !== 0): ?><hr><?php endif ?>
<h3><a href="/articles/<?= $e($article->slug) ?>"><?= $e($article->title) ?></a></h3>
<p class="meta inline"><?= date('F j, Y', strtotime($article->date)) ?> by <?= $e($article->author) ?></p>
<?php /* Already HTML: the library builds it from strip_tags() output, entities included */ ?>
<p><?= $article->description ?></p>
<?php endforeach; ?>
</div>
<?php require('../../includes/footer.php'); ?>
</body>
</html>
