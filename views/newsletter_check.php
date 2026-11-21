<?php

require_once('../vendor/autoload.php');

$canonical_url = false;
?>
<html lang="en">
<head>
<title>Check your email - Psalm</title>
<meta name="robots" content="noindex">
<?php require_once __DIR__ . '/../includes/meta.php'; page_meta(null, $canonical_url); ?>
</head>
<body>
<?php require('../includes/nav.php'); ?>
<div class="post">
<h1>Check your email</h1>
<p>Thanks for subscribing! We've sent you an email: open the link in it to confirm your subscription.</p>
<p>If it doesn't arrive within a few minutes, check your spam folder, or <a href="/newsletter">try again</a>.</p>
</div>
<?php require('../includes/footer.php'); ?>
</body>
</html>
