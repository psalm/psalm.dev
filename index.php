<html>
<head>
<title>Psalm - a static analysis tool for PHP</title>
<script src="//getpsalm.org/assets/js/fetch.js"></script>
<script src="//getpsalm.org/assets/js/codemirror.js"></script>
<link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/7707372/css/fonts.css" />
<meta property="og:image" content="psalm_preview.png" />
<link rel="stylesheet" href="//getpsalm.org/assets/css/site.css">
<link rel="icon" type="image/png" href="favicon.png">
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
</head>
<body>
    <? require('nav.php'); ?>
    <div class="intro">
        <p>Life is complicated. PHP can be, too.</p>

        <p>Psalm is designed to under stand that complexity, allowing it to quickly find common programmer errors like null references and misspelled variable names.</p>

        <p>You should use Psalm if you run PHP 5.6+ or PHP 7, and you want&nbsp;to</p>

        <ul>
            <li>prevent errors in a big refactor</li>
            <li>maintain a consistent level of quality across a large team</li>
            <li>guarantee that there won’t be any type-related runtime errors</li>
        </ul>

        <p>Psalm has a number of other features that help you improve your codebase, including a fixer called Psalter that updates your code directly by leveraging Psalm’s analysis engine.</p>

        <h3>Quickstart Guide</h3>

        <p>Install via <a href="http://getcomposer.org">Composer</a> or download <a href="https://github.com/vimeo/psalm">via GitHub</a>:</p>
        <pre>
composer require --dev vimeo/psalm
        </pre>

        <p>Add a <a href="https://github.com/vimeo/psalm/blob/master/docs/configuration.md">config</a>:</p>

        <pre>
./vendor/bin/psalm --init
        </pre>

        <p>Then run Psalm:</p>
        <pre>
./vendor/bin/psalm
        </pre>
        <p>The config created above will show you all issues in your code, but will emit <code>INFO</code> issues (as opposed to <code>ERROR</code>) for certain common trivial code problems. If you want a more lenient config you can specify the level with</p>

        <pre>
./vendor/bin/psalm --init [source_dir] [level]
        </pre>

        <p>You can also <a href="https://github.com/vimeo/psalm/blob/master/docs/dealing_with_code_issues.md">learn how to suppress certain issues</a>.</p>

        <p>Want to know more? Check out the <a href="https://github.com/vimeo/psalm/blob/master/docs/index.md">docs</a>!</p>
    </div>

    <div class="cm_container_container">
        <div class="cm_container">
            <textarea
                name="code"
                id="code"
                rows="20" style="visibility: hidden; font-family: monospace; font-size: 14px; max-width: 900px; min-width: 320px;"
            >
<<?='?'?>php
function psalmCanCheck(int $your_code) : ?string {
  return $on_your . "behalf";
}

// it requires PHP 5.6+ or PHP 7.*
const AND_IT_IS = WRITTEN_IN_PLAIN_PHP;

if (rand(0, 100) > 10) {
  $a = 5;
} else {
  //$a = 2;
}

echo $a; // uncomment the line above to fix!
            </textarea>
        </div>
    </div>

    <? require('footer.php'); ?>

<script>
var serializeJSON = function(data) {
    return Object.keys(data).map(function (keyName) {
        return encodeURIComponent(keyName) + '=' + encodeURIComponent(data[keyName])
    }).join('&');
}

var latestFetch = 0;

var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
    lineNumbers: true,
    matchBrackets: true,
    mode: "text/x-php",
    indentUnit: 2,
    gutters: ["CodeMirror-lint-markers"],
    theme: 'elegant',
    lint: {
        getAnnotations: function (code, callback, options, cm) {
            latestFetch++;
            fetchKey = latestFetch;
            fetch('//getpsalm.org/check.php', {
                method: 'POST',
                headers: {
                    'Accept': 'application/json, application/xml, text/plain, text/html, *.*',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=utf-8'
                },
                body: serializeJSON({code: code})
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
                        callback(response.results.map(function (issue) {
                            return {
                                severity: issue.severity === 'error' ? 'error' : 'warning',
                                message: issue.message,
                                from: cm.posFromIndex(issue.from),
                                to: cm.posFromIndex(issue.to)
                            };
                        }));
                    }  
                }
                else {
                    callback({
                       message: response.error.message,
                       severity: 'error',
                       from: CodeMirror.Pos(error.line - 1, start),
                       to: CodeMirror.Pos(error.line - 1, end)
                    });
                }
            })
            .catch (function (error) {
                console.log('Request failed', error);
            });
        },
        async: true,
    }
});

editor.focus();
editor.setCursor(editor.lineCount(), 0);

</script>
</body>
</html>
