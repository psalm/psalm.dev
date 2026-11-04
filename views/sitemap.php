<?php

// Site pages outside /docs; the docs have their own sitemap from mkdocs
require_once dirname(__DIR__) . '/vendor/autoload.php';

$blog = new Muglug\Blog\MarkdownBlog(dirname(__DIR__) . '/assets/articles/');

$urls = [
    ['loc' => 'https://psalm.dev/', 'lastmod' => null],
    ['loc' => 'https://psalm.dev/plugins', 'lastmod' => null],
    ['loc' => 'https://psalm.dev/articles', 'lastmod' => null],
    ['loc' => 'https://psalm.dev/support', 'lastmod' => null],
    ['loc' => 'https://psalm.dev/contribute', 'lastmod' => null],
];

foreach ($blog->articles->getAll() as $article) {
    if ($article->canonical) {
        continue; // the original lives elsewhere
    }
    $urls[] = ['loc' => 'https://psalm.dev/articles/' . $article->slug, 'lastmod' => date('Y-m-d', strtotime($article->date))];
}

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>', "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">', "\n";
foreach ($urls as $url) {
    echo '  <url><loc>', htmlspecialchars($url['loc'], ENT_XML1), '</loc>';
    if ($url['lastmod']) {
        echo '<lastmod>', $url['lastmod'], '</lastmod>';
    }
    echo '</url>', "\n";
}
echo '</urlset>', "\n";
