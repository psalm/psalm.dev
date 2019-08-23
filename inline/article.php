<?php

require_once('../vendor/autoload.php');

use League\CommonMark\CommonMarkConverter;

class AltHeadingParser implements \League\CommonMark\Block\Parser\BlockParserInterface
{
    public function parse(\League\CommonMark\ContextInterface $context, \League\CommonMark\Cursor $cursor): bool
    {
        if ($cursor->isIndented()) {
            return false;
        }

        $match = \League\CommonMark\Util\RegexHelper::matchAll('/^#{1,6}(?:[ \t]+|$)/', $cursor->getLine(), $cursor->getNextNonSpacePosition());
        if (!$match) {
            return false;
        }

        $cursor->advanceToNextNonSpaceOrTab();

        $cursor->advanceBy(\strlen($match[0]));

        $level = \strlen(\trim($match[0]));
        $str = $cursor->getRemainder();
        $str = \preg_replace('/^[ \t]*#+[ \t]*$/', '', $str);
        $str = \preg_replace('/[ \t]+#+[ \t]*$/', '', $str);

        $heading = new \League\CommonMark\Block\Element\Heading($level, $str);

        $id = preg_replace('/[^a-z\-]+/', '', strtolower(str_replace(' ', '-', $str)));

        $heading->data['attributes'] = ['id' => $id];

        $context->addBlock($heading);
        $context->setBlocksParsed(true);

        return true;
    }
}

$environment = League\CommonMark\Environment::createCommonMarkEnvironment();
$environment->addBlockParser(new AltHeadingParser(), 100);

$converter = new CommonMarkConverter([], $environment);

$html = $converter->convertToHtml(file_get_contents('psalm-3-and-a-half.md'));

?>
<html>
<head>
<title>Psalm 3-and-a-half</title>
<script src="/assets/js/fetch.js"></script>
<script src="/assets/js/codemirror.js"></script>
<link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/7707372/css/fonts.css" />
<link rel="stylesheet" href="/assets/css/site.css?1">
<link rel="icon" type="image/png" href="favicon.png">
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
</head>
<body>
<?php require('../includes/nav.php'); ?>
<div class="post">
<h1>Psalm 3-and-a-half</h1>

<p class="meta">
	<span class="date">August 23 2019</span> by <a href="https://twitter.com/mattbrowndev" rel="author">Matt Brown</a>
</p>

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
    'unused_methods': true,
    'memoize_properties': true,
    'memoize_method_calls': false,
    'check_throws': false,
    'strict_internal_functions': false,
    'allow_phpstorm_generics': false,
};

var fetchAnnotations = function (code, callback, options, cm) {
    latestFetch++;
    fetchKey = latestFetch;
    fetch('/check.php', {
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
    fetch('/check.php', {
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
