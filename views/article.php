<?php

require_once('../vendor/autoload.php');
error_reporting(E_ALL);
ini_set('html_errors', '1');
ini_set('display_errors', '1');

$title = 'Psalm - article not found';
$name = $_GET['name'];

$blogconfig = require(dirname(__DIR__) . '/blogconfig.php');

$blog = new Muglug\Blog\MarkdownBlog(
    dirname(__DIR__) . '/assets/articles/',
    new Muglug\Blog\GithubConfig($blogconfig['owner'], $blogconfig['repo'], $blogconfig['github_token'])
);

try {
    $article = $blog->articles->get($name);
} catch (\Exception $e) {
    header("HTTP/1.0 404 Not Found");
    echo $e->getMessage();
    exit;
}

?>
<html>
<head>
<title><?= $article->title ?></title>
<?php require('../includes/meta.php'); ?>
<?php if ($article->canonical): ?><link rel="canonical" href="<?= $article->canonical ?>" /><?php endif; ?>
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
<h1><?= Muglug\Blog\AltHeadingParser::preventOrphans($article->title) ?></h1>
<p class="meta">
    <?= date('F j, Y', strtotime($article->date)) ?> by <?= $article->author ?> - 
    <?php if ($article->canonical): ?>
        <a href="<?= $article->canonical ?>">original article</a>
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

            if ('fixable_errors' in response && response.fixable_errors > 0) {
                document.getElementById('psalm_output').innerHTML = 'Psalm detected ' + response.fixable_errors + ' fixable errors<br>&nbsp;';

                const textarea = cm.getTextArea()
                const container = textarea.parentNode;

                let fix_button = document.createElement('button');
                fix_button.innerText = 'Fix code';
                container.appendChild(fix_button);

                fix_button.addEventListener(
                    'click',
                    function() {
                        fetchFixedContents(cm.getValue(), cm);
                    }
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
	}
);
</script>
</body>
</html>
