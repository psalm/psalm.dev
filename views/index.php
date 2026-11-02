<!DOCTYPE html>
<html lang="en">
<head>
<title>Psalm - a static analysis tool for PHP</title>
<meta name="description" content="Psalm is a free & open-source static analysis tool that helps you identify problems in your code, so you can sleep a little better" />
<?php

use PsalmDotOrg\Settings;
require_once dirname(__DIR__) . '/src/Settings.php';
$canonical_url = 'https://psalm.dev/';

 require('../includes/meta.php'); ?>
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
<script src="/assets/js/codemirror.min.js?2"></script>
<link rel="stylesheet" href="/assets/css/playground.css?1">
</head>
<body class="front">
<?php require('../includes/nav.php'); ?>
<div class="container front" id="page_container">
    <div class="intro">
        <h1>Give PHP the <span class="CodeMirror-lint-mark-error">love</span> it&nbsp;deserves</h1>

        <p>It’s easy to make great things in PHP, but bugs can creep in just as easily. Psalm is a free &amp; open-source static analysis tool that helps you identify problems in your code, so you can sleep a little better.</p>

        <p>Psalm helps people maintain a wide variety of codebases – large and small, ancient and modern. On its strictest setting it can help you <a href="articles/php-or-type-safety-pick-any-two">prevent almost all type-related runtime errors</a>, and enables you to take advantage of <a href="articles/immutability-and-beyond">safe coding patterns</a> popular in other languages.</p>

        <p>Psalm <a href="https://psalm.dev/docs/manipulating_code/fixing/">also fixes bugs automatically</a>, allowing you to improve your code without breaking a sweat.</p>

        <p>Help support Psalm’s ongoing development costs by requesting a <a href="/support">paid support contract</a> from Psalm’s maintainer.</p>
    </div>
</div>
<?php
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
<script>
var settings = <?php echo json_encode(Settings::defaultValues()) ?>;
</script>
<?php require('../includes/footer.php'); ?>
<?php require('../includes/script.php'); ?>
</body>
</html>
