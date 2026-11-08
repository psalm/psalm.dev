<?php

use PsalmDotOrg\Settings;
require_once dirname(__DIR__) . '/src/Settings.php';
require_once dirname(__DIR__) . '/src/FreshArticle.php';
require_once dirname(__DIR__) . '/src/HomepageExample.php';

// /play (?page=play from .htaccess): nav plus the playground filling the viewport, nothing else
$playground_page = ($_GET['page'] ?? null) === 'play';
$canonical_url = $playground_page ? 'https://psalm.dev/play' : 'https://psalm.dev/';
$og = $playground_page ? [
    'title' => 'Psalm Playground: PHP static analysis in your browser',
    'description' => 'Paste PHP, see type errors and bugs as you type, and share a link to the result.',
    'image' => '/assets/og/play.png',
    'image_alt' => 'Psalm Playground: a PHP function with an InvalidArgument error underlined in red',
] : [
    'title' => 'Psalm: give PHP the love it deserves',
    'description' => 'Psalm is a free & open-source static analysis tool that helps you identify problems in your PHP code, so you can sleep a little better.',
];
// homepage news: up to three articles from the last two weeks, listed under the playground widget
$fresh_articles = $playground_page ? [] : PsalmDotOrg\FreshArticle::recent(dirname(__DIR__) . '/assets/articles');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<?php if ($playground_page) : ?>
<title>Playground - Psalm</title>
<meta name="description" content="Try Psalm, a static analysis tool for PHP, in your browser and share the results" />
<?php else : ?>
<title>Psalm - a static analysis tool for PHP</title>
<meta name="description" content="Psalm is a free & open-source static analysis tool that helps you identify problems in your code, so you can sleep a little better" />
<?php endif ?>
<?php require('../includes/meta.php'); ?>
<?php if (!$playground_page) : ?>
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "SoftwareApplication",
    "name": "Psalm",
    "description": "A free and open-source static analysis tool for PHP that finds bugs and fixes them automatically.",
    "applicationCategory": "DeveloperApplication",
    "operatingSystem": "Linux, macOS, Windows",
    "url": "https://psalm.dev/",
    "downloadUrl": "https://packagist.org/packages/vimeo/psalm",
    "license": "https://opensource.org/licenses/MIT",
    "offers": {"@type": "Offer", "price": "0", "priceCurrency": "USD"}
}
</script>
<?php endif ?>
<script src="<?= asset_url('/assets/js/codemirror.min.js') ?>"></script>
<link rel="stylesheet" href="<?= asset_url('/assets/css/playground.css') ?>">
</head>
<body class="<?= $playground_page ? 'front playground_page' : 'front pg_expandable pg_compact' ?>">
<a class="pg_skip" href="#playground" onclick="return focusEditor();">Skip to editor</a>
<?php require('../includes/nav.php'); ?>
<div class="container front" id="page_container">
<?php if (!$playground_page) : ?>
    <div class="intro">
        <h1>Give PHP the <span class="CodeMirror-lint-mark-error">love</span> it&nbsp;deserves</h1>

        <p>It’s easy to make great things in PHP, but bugs can creep in just as easily. Psalm is a free &amp; open-source static analysis tool that helps you identify problems in your code, so you can sleep a little better.</p>

        <p class="intro_cta"><a class="button_primary" href="/docs/running_psalm/installation/">Get started</a></p>

        <p>Psalm helps people maintain a wide variety of codebases – large and small, ancient and modern. On its strictest setting it can help you <a href="articles/php-or-type-safety-pick-any-two">prevent almost all type-related runtime errors</a>, and enables you to take advantage of <a href="articles/immutability-and-beyond">safe coding patterns</a> popular in other languages.</p>

        <p>Psalm <a href="https://psalm.dev/docs/manipulating_code/fixing/">also fixes bugs automatically</a>, allowing you to improve your code without breaking a sweat.</p>

        <p>Help support Psalm’s ongoing development costs by requesting a <a href="/support">paid support contract</a> from Psalm’s maintainer.</p>
    </div>
<?php endif ?>
<?php
require_once '../includes/playground.php';
render_playground(htmlspecialchars(PsalmDotOrg\HomepageExample::CODE), null, !$playground_page);
?>
<?php if ($fresh_articles !== []) : ?>
    <section class="home_news" aria-labelledby="home_news_title">
        <h2 id="home_news_title">From the blog</h2>
        <ul>
<?php foreach ($fresh_articles as $article) : ?>
            <li><a href="/articles/<?= htmlspecialchars($article->slug, ENT_QUOTES) ?>"><?= htmlspecialchars($article->title, ENT_QUOTES) ?></a> <time datetime="<?= $article->date->format('Y-m-d') ?>"><?= $article->date->format('F j, Y') ?></time></li>
<?php endforeach ?>
        </ul>
    </section>
<?php endif ?>
</div>
<script>
var settings = <?php echo json_encode(Settings::defaultValues()) ?>;
</script>
<?php if (!$playground_page) : ?>
<?php require('../includes/footer.php'); ?>
<?php endif ?>
<?php require('../includes/script.php'); ?>
</body>
</html>
