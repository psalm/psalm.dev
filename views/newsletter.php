<?php

require_once('../vendor/autoload.php');

$canonical_url = 'https://psalm.dev/newsletter';
$meta_description = 'Subscribe to the Psalm newsletter to get new articles from the Psalm blog by email.';
?>
<html lang="en">
<head>
<title>Newsletter - Psalm</title>
<?php require('../includes/meta.php'); ?>
</head>
<body>
<?php require('../includes/nav.php'); ?>
<div class="post">
<h1>Newsletter</h1>
<p>Subscribe to get new articles from the <a href="/articles">Psalm blog</a> by email: a few emails a year.</p>
<p>You'll get an email asking you to confirm: newsletters are only sent to confirmed addresses, and every newsletter has a link to unsubscribe.</p>
<?php require_once('../includes/subscribe.php'); ?>
<?= NewsletterForm::render('page') ?>
<p>Past newsletters are in the <a href="https://newsletter.daniil.it/archive">archive</a>. For every release, follow Psalm on <a href="https://t.me/psalmphp">Telegram</a>, <a href="https://discord.gg/RwH8rEpxt9">Discord</a>, <a href="https://phpc.social/@psalm">Mastodon</a> or <a href="https://x.com/psalmphp">X</a>.</p>
</div>
<?php require('../includes/footer.php'); ?>
</body>
</html>
