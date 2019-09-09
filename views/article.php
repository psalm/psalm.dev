<?php

require_once('../vendor/autoload.php');

$title = 'Psalm - article not found';
$name = $_GET['name'];
$description = '';
$canonical = '';
$html = PsalmDotOrg\ArticleRepository::getHtml($name, $title, $description, $canonical);

?>
<html>
<head>
<title><?= $title ?></title>
<script src="/assets/js/fetch.js"></script>
<script src="/assets/js/codemirror.js"></script>
<link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/7707372/css/fonts.css" />
<link rel="stylesheet" href="/assets/css/site.css?1">
<link rel="icon" type="image/png" href="favicon.png">
<?php if ($canonical): ?><link rel="canonical" href="<?= $canonical ?>" /><?php endif; ?>
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
<meta name="twitter:card" content="summary" />
<meta name="twitter:site" content="@psalmphp" />
<meta name="twitter:title" content="<?= $title ?>" />
<meta name="twitter:creator" content="@mattbrowndev" />
<meta name="twitter:description" content="<?= $description ?>" />
<meta name="twitter:image" content="https://psalm.dev/article_thumbnail.png" />
<meta name="og:type" content="article" />
</head>
<body>
<?php require('../includes/nav.php'); ?>
<div class="post">
<?= $html ?>
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
    'unused_methods': <?= $name === 'psalm-3-and-a-half' ? 'true' : 'false' ?>,
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
            settings: JSON.stringify(settings),
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
		const parent = code_element.parentNode;
		const container = document.createElement('div');
		const textarea = document.createElement('textarea');
		textarea.value = code_element.innerText;

		const text = code_element.innerText;
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
