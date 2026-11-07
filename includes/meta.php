<?php
/**
 * Shared <head> tags. Pages may set before including:
 * - $meta_description: text for <meta name="description">
 * - $canonical_url: canonical URL, or false to omit it. When unset, it is https://psalm.dev plus
 *   the request path without query, repeated slashes or a trailing slash (pages with fixed
 *   URLs set it explicitly, since routes are case-insensitive)
 */
if (!isset($canonical_url)) {
    // Not parse_url(): it reads "//plugins" as a host and returns no path
    $canonical_path = rtrim((string) preg_replace('#/+#', '/', strtok($_SERVER['REQUEST_URI'] ?? '/', '?')), '/');
    $canonical_url = 'https://psalm.dev' . ($canonical_path === '' ? '/' : $canonical_path);
}
?>
<?php if (!empty($meta_description)): ?>
<meta name="description" content="<?= htmlspecialchars($meta_description, ENT_QUOTES) ?>" />
<?php endif ?>
<?php if ($canonical_url): ?>
<link rel="canonical" href="<?= htmlspecialchars($canonical_url, ENT_QUOTES) ?>" />
<?php endif ?>
<meta property="og:site_name" content="Psalm" />
<meta property="og:image" content="https://psalm.dev/psalm_preview.png" />
<link rel="stylesheet" href="/assets/css/site.css?29">
<?php /* Fonts used above the fold on every page: request them with the stylesheet instead of after it is parsed */ ?>
<link rel="preload" href="/extra/national-2-web-bold.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/extra/national-2-web-medium.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/extra/TiemposTextWeb-Regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="icon" type="image/png" href="/favicon.png">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php require __DIR__ . '/speculation_rules.php'; ?>
