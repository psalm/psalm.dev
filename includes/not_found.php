<?php
/**
 * Renders a noindex 404 page and exits. Include it before any output, or the status and
 * headers are lost. Optional: $not_found_message (defaults to a generic message).
 */
http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
header('X-Robots-Tag: noindex');
$canonical_url = false;
?>
<html>
<head>
<title>Page not found - Psalm</title>
<?php require __DIR__ . '/meta.php'; ?>
</head>
<body>
<?php require __DIR__ . '/nav.php'; ?>
<div class="post">
<h1>Page not found</h1>
<p><?= htmlspecialchars($not_found_message ?? 'This page does not exist.', ENT_QUOTES) ?></p>
<p><a href="/">Go to the Psalm homepage</a> or <a href="/docs/">read the documentation</a>.</p>
</div>
<?php require __DIR__ . '/footer.php'; ?>
</body>
</html>
<?php exit; ?>
