<?php

use PsalmDotOrg\Settings;

error_reporting(E_ALL);
ini_set('html_errors', '1');
ini_set('display_errors', '0');
http_response_code(500);

require_once('../vendor/autoload.php');

$title = 'Psalm - article not found';
$name = $_GET['name'];

$blogconfig = require(dirname(__DIR__) . '/blogconfig.php');

$blog = new Muglug\Blog\MarkdownBlog(
    dirname(__DIR__) . '/assets/articles/',
    new Muglug\Blog\GithubConfig($blogconfig['owner'], $blogconfig['repo'], $blogconfig['github_token'])
);

try {
    $article = $blog->articles->get($name);
    // GitHub errors other than 404 (e.g. 401/403) come back as markdown without front matter,
    // so an article with no title is treated as not found
    if ($article->title === '') {
        throw new \UnexpectedValueException('Article "' . $name . '" has no title');
    }
    http_response_code(200);
} catch (\Exception $e) {
    // Logged so a broken preview token or a bad article is not mistaken for a mistyped URL
    error_log('Article ' . $name . ': ' . $e->getMessage());
    $not_found_message = 'This article does not exist.';
    require dirname(__DIR__) . '/includes/not_found.php';
}

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES);

// Translations are <slug>-<lang>.md next to the English file; add new two-letter codes here
$translations = ['fr', 'it', 'uk'];
$lang = preg_match('/-(' . implode('|', $translations) . ')$/', $name, $m) ? $m[1] : 'en';
$base_name = $lang === 'en' ? $name : substr($name, 0, -3);
$alternates = [];
foreach (array_merge(['en'], $translations) as $language) {
    $slug = $language === 'en' ? $base_name : $base_name . '-' . $language;
    if (is_file(dirname(__DIR__) . '/assets/articles/' . $slug . '.md')) {
        $alternates[$language] = 'https://psalm.dev/articles/' . $slug;
    }
}

$url = 'https://psalm.dev/articles/' . $name;
// Previews are unpublished drafts: keep them out of the index
if ($article->is_preview) {
    header('X-Robots-Tag: noindex');
    $canonical_url = false;
} else {
    $canonical_url = $article->canonical ?: $url;
}
// The library builds the description from rendered HTML, so it holds entities like &amp;
$description = trim(preg_replace('/\s+/u', ' ', html_entity_decode($article->description, ENT_QUOTES | ENT_HTML5, 'UTF-8')));
$meta_description = $description;
$timestamp = strtotime($article->date);
$published = $timestamp === false ? null : date('c', $timestamp);

?>
<html lang="<?= $lang ?>">
<head>
<title><?= $e($article->title) ?></title>
<?php require('../includes/meta.php'); ?>
<script src="/assets/js/codemirror.min.js?2"></script>
<?php if (count($alternates) > 1): ?>
<?php foreach ($alternates as $language => $alternate_url): ?>
<link rel="alternate" hreflang="<?= $language ?>" href="<?= $e($alternate_url) ?>" />
<?php endforeach ?>
<link rel="alternate" hreflang="x-default" href="<?= $e($alternates['en'] ?? $url) ?>" />
<?php endif ?>
<meta property="og:type" content="article" />
<meta property="og:title" content="<?= $e($article->title) ?>" />
<meta property="og:description" content="<?= $e($description) ?>" />
<meta property="og:url" content="<?= $e($canonical_url ?: $url) ?>" />
<?php if ($published): ?>
<meta property="article:published_time" content="<?= $published ?>" />
<?php endif ?>
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@psalmphp" />
<meta name="twitter:title" content="<?= $e($article->title) ?>" />
<meta name="twitter:description" content="<?= $e($description) ?>" />
<meta name="twitter:image" content="https://psalm.dev/article_thumbnail.png" />
<script type="application/ld+json">
<?= json_encode(array_filter([
    '@context' => 'https://schema.org',
    '@type' => 'BlogPosting',
    'headline' => $article->title,
    'description' => $description,
    'datePublished' => $published,
    'author' => ['@type' => 'Person', 'name' => $article->author],
    'publisher' => ['@type' => 'Organization', 'name' => 'Psalm', 'url' => 'https://psalm.dev/'],
    'image' => 'https://psalm.dev/article_thumbnail.png',
    'inLanguage' => $lang,
    'mainEntityOfPage' => $canonical_url ?: $url,
]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_INVALID_UTF8_SUBSTITUTE | JSON_PRETTY_PRINT) ?>

</script>
<?php if ($lang === 'uk'): ?>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Lora:ital,wght@0,400;0,600;1,400&display=swap" rel="stylesheet">
<?php endif ?>
</head>
<body>
<?php require('../includes/nav.php'); ?>
<div class="post" lang="<?= $lang ?>">
<?php if ($article->is_preview) : ?>
    <p class="preview_warning">Article preview - contents subject to change</p>
<?php endif ?>
<h1><?= Muglug\Blog\AltHeadingParser::preventOrphans($article->title) ?></h1>
<p class="meta">
    <?= date('F j, Y', strtotime($article->date)) ?> by <?= $e($article->author) ?> -
    <?php if ($article->canonical): ?>
        <a href="<?= $e($article->canonical) ?>">original article</a>
    <?php else: ?>
        <?= $article->getReadingMinutes() ?>&nbsp;minute&nbsp;read
    <?php endif; ?>
</p>
<?php if ($article->notice) : ?>
    <div class="notice"><?= $article->notice ?></div>
    <hr />
<?php endif ?>
<?= $article->html ?>
</div>
<?php require('../includes/footer.php'); ?>
<script>
const serializeJSON = function(data) {
    return Object.keys(data).map(function (keyName) {
        return encodeURIComponent(keyName) + '=' + encodeURIComponent(data[keyName])
    }).join('&');
}
let latestFetch = 0;
let fetchKey = null;

const settings = <?php echo json_encode(Settings::defaultValues()) ?>;

var fetchAnnotations = function (code, callback, options, cm) {
    // A prerendered page must not run Psalm until the user actually opens it
    if (document.prerendering) {
        document.addEventListener('prerenderingchange', function () {
            fetchAnnotations(code, callback, options, cm);
        }, {once: true});
        return;
    }

    latestFetch++;
    fetchKey = latestFetch;
    fetch('/check', {
        method: 'POST',
        headers: {
            'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        },
        body: serializeJSON({
            code: code,
            settings: JSON.stringify(settings),
        })
    })
    .then(function (response) {
        return response.json();
    })
    .then(function (response) {
        if (latestFetch != fetchKey) {
            return;
        }

        if ('results' in response) {
            callback(
                response.results.map(
                    function (issue) {
                        return {
                            severity: issue.severity === 'error' ? 'error' : 'warning',
                            message: issue.message,
                            from: cm.posFromIndex(issue.from),
                            to: cm.posFromIndex(issue.to)
                        };
                    }
                ).concat(
                    response.type_map.map(
                        function (type_data) {
                            return {
                                severity: 'type',
                                message: type_data.type,
                                from: cm.posFromIndex(type_data.from),
                                to: cm.posFromIndex(type_data.to)
                            };
                        }
                    )
                )
            );
        }
        else if ('error' in response) {
            callback({
               message: response.error.message,
               severity: 'error',
               from: cm.posFromIndex(response.error.from),
               to: cm.posFromIndex(response.error.to),
            });
        }
    })
    .catch (function (error) {
        console.log('Request failed', error);
    });
};

var fetchFixedContents = function (code, cm) {
    latestFetch++;
    fetchKey = latestFetch;
    fetch('/check', {
        method: 'POST',
        headers: {
            'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
            'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
        },
        body: serializeJSON({
            code: code,
            settings: JSON.stringify({...settings, ...{unused_methods: true}}),
            fix: true,
        })
    })
    .then(function (response) {
        return response.json();
    })
    .then(function (response) {
        if (latestFetch != fetchKey) {
            return;
        }

        if ('fixed_contents' in response && response.fixed_contents) {
            cm.setValue(response.fixed_contents);
        }
        else if ('error' in response) {
            callback({
               message: response.error.message,
               severity: 'error',
               from: cm.posFromIndex(response.error.from),
               to: cm.posFromIndex(response.error.to),
            });
        }
    })
    .catch (function (error) {
        console.log('Request failed', error);
    });
};

[...document.querySelectorAll('pre code.language-php')].forEach(
	function (code_element) {
		code_element = code_element.parentNode;
		const text = code_element.innerText;
		if (text.indexOf('<?= '<?' ?>php') !== 0) {
			return;
		}
		const parent = code_element.parentNode;
		const container = document.createElement('div');
		const textarea = document.createElement('textarea');
		textarea.value = code_element.innerText;

		container.appendChild(textarea);
		container.className = 'cm_inline_container';

		let fix_button = null;

		let reset_button = null;

		if (textarea.value.indexOf('<?= '<?php' ?> // fix') === 0) {
			fix_button = document.createElement('button');
			fix_button.innerText = 'Fix code';
			container.appendChild(fix_button);

			reset_button = document.createElement('button');
			reset_button.innerText = 'Reset';
			reset_button.className = 'reset';
			container.appendChild(reset_button);
		}

		parent.replaceChild(container, code_element);
		const cm = CodeMirror.fromTextArea(textarea, {
		    lineNumbers: false,
		    matchBrackets: true,
		    mode: "text/x-php",
		    indentUnit: 2,
		    theme: 'elegant',
		    viewportMargin: Infinity,
		    lint: lint = {
		        getAnnotations: fetchAnnotations,
		        async: true,
		    }
		});

		if (fix_button) {
			fix_button.addEventListener(
				'click',
				function() {
					fetchFixedContents(cm.getValue(), cm);
				}
			);
		}

		if (reset_button) {
			reset_button.addEventListener(
				'click',
				function() {
					cm.setValue(text);
				}
			);
		}
	}
);
</script>
</body>
</html>
