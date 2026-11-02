<?php

use PsalmDotOrg\Settings;
require_once dirname(__DIR__) . '/src/Settings.php';

// views/playground.php sets this: nav plus the playground filling the viewport, nothing else
$playground_page = !empty($playground_page);
$canonical_url = $playground_page ? 'https://psalm.dev/playground' : 'https://psalm.dev/';
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
<script src="/assets/js/codemirror.min.js?2"></script>
<link rel="stylesheet" href="/assets/css/playground.css?3">
</head>
<body class="<?= $playground_page ? 'front playground_page' : 'front pg_expandable pg_compact' ?>">
<a class="pg_skip" href="#playground" onclick="return focusEditor();">Skip to editor</a>
<?php require('../includes/nav.php'); ?>
<div class="container front" id="page_container">
<?php if (!$playground_page) : ?>
    <div class="intro">
        <h1>Give PHP the <span class="CodeMirror-lint-mark-error">love</span> it&nbsp;deserves</h1>

        <p>It’s easy to make great things in PHP, but bugs can creep in just as easily. Psalm is a free &amp; open-source static analysis tool that helps you identify problems in your code, so you can sleep a little better.</p>

        <p>Psalm helps people maintain a wide variety of codebases – large and small, ancient and modern. On its strictest setting it can help you <a href="articles/php-or-type-safety-pick-any-two">prevent almost all type-related runtime errors</a>, and enables you to take advantage of <a href="articles/immutability-and-beyond">safe coding patterns</a> popular in other languages.</p>

        <p>Psalm <a href="https://psalm.dev/docs/manipulating_code/fixing/">also fixes bugs automatically</a>, allowing you to improve your code without breaking a sweat.</p>

        <p>Help support Psalm’s ongoing development costs by requesting a <a href="/support">paid support contract</a> from Psalm’s maintainer.</p>
    </div>
<?php endif ?>
<?php
$playground_expandable = !$playground_page;
$playground_code = '&lt;?php

/**
 * @return array&lt;string&gt;
 */
function takesAnInt(int $i) {
    return [$i, "hello"];
}

$data = ["some text", 5];
takesAnInt($data[0]);

$condition = rand(0, 5);
if ($condition) {
} elseif ($condition) {}';
require('../includes/playground.php');
?>
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
