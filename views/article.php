<?php

require_once('../vendor/autoload.php');
error_reporting(E_ALL);
ini_set('html_errors', '1');
ini_set('display_errors', '1');

$title = 'Psalm - article not found';
$name = $_GET['name'];

$article = PsalmDotOrg\ArticleRepository::get($name);

if (!$article) {
    exit;
}

$word_count = str_word_count(strip_tags(preg_replace('/<pre>(.*?)<\\/pre>/', '', $article->html)));

$word_count += 2 * substr_count($article->html, '<p>');

$word_count += substr_count($article->html, '<h');

$word_count += substr_count($article->html, '<code');

$word_count += substr_count($article->html, '<a href=');

$minutes_taken = round(0.25 + ($word_count / 265));
?>
<html>
<head>
<title><?= $article->title ?></title>
<script src="/assets/js/fetch.js"></script>
<script src="/assets/js/codemirror.js"></script>
<link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/7707372/css/fonts.css" />
<link rel="stylesheet" href="/assets/css/site.css?6">
<link rel="icon" type="image/png" href="favicon.png">
<?php if ($article->canonical): ?><link rel="canonical" href="<?= $article->canonical ?>" /><?php endif; ?>
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@psalmphp" />
<meta name="twitter:title" content="<?= $article->title ?>" />
<meta name="twitter:creator" content="@mattbrowndev" />
<meta name="twitter:description" content="<?= $article->description ?>" />
<meta name="twitter:image" content="https://psalm.dev/article_thumbnail.png" />
<meta name="og:type" content="article" />
</head>
<body>
<?php require('../includes/nav.php'); ?>
<div class="post">
<?php if ($article->is_preview) : ?>
    <p class="preview_warning">Article preview - contents subject to change</p>
<?php endif ?>
<h1><?= PsalmDotOrg\AltHeadingParser::preventOrphans($article->title) ?></h1>
<p class="meta">
    <?= date('F j, Y', strtotime($article->date)) ?> by <?= $article->author ?> - 
    <?php if ($article->canonical): ?>
        <a href="<?= $article->canonical ?>">original article</a>
    <?php else: ?>
        <?= $minutes_taken ?>&nbsp;minute&nbsp;read
    <?php endif; ?>
</p>
<?php if ($article->notice) : ?>
    <div class="notice"><?= $article->notice ?></div>
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

const settings = {
    'unused_variables': true,
    'unused_methods': false,
    'memoize_properties': true,
    'memoize_method_calls': false,
    'check_throws': false,
    'strict_internal_functions': false,
    'allow_phpstorm_generics': false,
};

var fetchAnnotations = function (code, callback, options, cm) {
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
            if (response.results.length === 0) {
                callback([]);
            }
            else {
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
                    )
                );
            }  
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
