<?php
/**
 * Shared <head> tags. Pages may set before including:
 * - $meta_description: text for <meta name="description">
 * - $canonical_url: canonical URL, or false to omit it. When unset, it is https://psalm.dev plus
 *   the request path without query, repeated slashes or a trailing slash (pages with fixed
 *   URLs set it explicitly, since routes are case-insensitive)
 * - $og: link preview (Open Graph and Twitter card) values, merged over the defaults below: type, title,
 *   description, url, image (site path of a PNG), image_width, image_height, image_alt, twitter_card.
 *   Without a title or description, previews fall back to <title> and $meta_description.
 */
require_once __DIR__ . '/asset.php';

if (!isset($canonical_url)) {
    // Not parse_url(): it reads "//plugins" as a host and returns no path
    $canonical_path = rtrim((string) preg_replace('#/+#', '/', strtok($_SERVER['REQUEST_URI'] ?? '/', '?')), '/');
    $canonical_url = 'https://psalm.dev' . ($canonical_path === '' ? '/' : $canonical_path);
}

$og = ($og ?? []) + [
    'type' => 'website',
    'title' => null,
    'description' => $meta_description ?? null,
    'url' => $canonical_url ?: null,
    'image' => '/assets/og/home.png',
    'image_width' => 1200,
    'image_height' => 630,
    'image_alt' => 'Psalm: Give PHP the love it deserves',
    'twitter_card' => 'summary_large_image',
];
$og_attr = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE);
?>
<?php if (!empty($meta_description)): ?>
<meta name="description" content="<?= htmlspecialchars($meta_description, ENT_QUOTES) ?>" />
<?php endif ?>
<?php if ($canonical_url): ?>
<link rel="canonical" href="<?= htmlspecialchars($canonical_url, ENT_QUOTES) ?>" />
<?php endif ?>
<meta property="og:site_name" content="Psalm" />
<meta property="og:type" content="<?= $og_attr($og['type']) ?>" />
<?php if ($og['title']): ?>
<meta property="og:title" content="<?= $og_attr($og['title']) ?>" />
<?php endif ?>
<?php if ($og['description']): ?>
<meta property="og:description" content="<?= $og_attr($og['description']) ?>" />
<?php endif ?>
<?php if ($og['url']): ?>
<meta property="og:url" content="<?= $og_attr($og['url']) ?>" />
<?php endif ?>
<meta property="og:image" content="<?= $og_attr('https://psalm.dev' . asset_url($og['image'])) ?>" />
<meta property="og:image:type" content="image/png" />
<meta property="og:image:width" content="<?= (int) $og['image_width'] ?>" />
<meta property="og:image:height" content="<?= (int) $og['image_height'] ?>" />
<meta property="og:image:alt" content="<?= $og_attr($og['image_alt']) ?>" />
<meta name="twitter:card" content="<?= $og_attr($og['twitter_card']) ?>" />
<meta name="twitter:site" content="@psalmphp" />
<meta name="theme-color" content="#0057b7" />
<link rel="stylesheet" href="<?= asset_url('/assets/css/site.css') ?>">
<?php /* Fonts used above the fold on every page: request them with the stylesheet instead of after it is parsed */ ?>
<link rel="preload" href="/extra/national-2-web-bold.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/extra/national-2-web-medium.woff2" as="font" type="font/woff2" crossorigin>
<link rel="preload" href="/extra/TiemposTextWeb-Regular.woff2" as="font" type="font/woff2" crossorigin>
<link rel="icon" type="image/png" href="/favicon.png">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php require __DIR__ . '/speculation_rules.php'; ?>
