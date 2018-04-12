<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once('vendor/autoload.php');

use PhpParser\ParserFactory;
use Psalm\Checker\FileChecker;
use Psalm\Checker\ProjectChecker;
use Psalm\Config;
use Psalm\IssueBuffer;

if (isset($_POST['code'])) {
    $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);

    $config = Config::loadFromXML(
            (string)getcwd(),
            '<?xml version="1.0"?>
            <psalm cacheDirectory="cache">
                <projectFiles>
                    <directory name="src" />
                </projectFiles>
            </psalm>'
        );
    $config->collectPredefinedConstants();
    $config->collectPredefinedFunctions();
    $config->cache_directory = null;
    $config->stop_on_first_error = false;
    $config->allow_includes = false;
    $config->totally_typed = true;
    $config->use_property_default_for_type = false;
    $config->strict_binary_operands = true;
    $config->remember_property_assignments_after_call = true;
    $config->setCustomErrorLevel('MixedArrayAccess', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MixedArrayOffset', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MixedAssignment', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MixedArgument', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MixedMethodCall', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MixedOperand', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MissingParamType', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MissingClosureParamType', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MixedTypeCoercion', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MixedPropertyFetch', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MixedPropertyAssignment', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MixedInferredReturnType', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MixedReturnStatement', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MissingPropertyType', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MissingReturnType', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MissingClosureReturnType', Config::REPORT_SUPPRESS);
    $config->setCustomErrorLevel('DeprecatedMethod', Config::REPORT_INFO);
    $config->setCustomErrorLevel('PossiblyUndefinedGlobalVariable', Config::REPORT_INFO);
    $config->setCustomErrorLevel('PossiblyUndefinedVariable', Config::REPORT_INFO);
    $config->setCustomErrorLevel('NonStaticSelfCall', Config::REPORT_INFO);
    $config->setCustomErrorLevel('UnusedParam', Config::REPORT_INFO);
    $config->setCustomErrorLevel('PossiblyUnusedParam', Config::REPORT_INFO);
    $config->setCustomErrorLevel('UnusedVariable', Config::REPORT_INFO);
    $config->setCustomErrorLevel('UnusedClass', Config::REPORT_SUPPRESS);
    $config->setCustomErrorLevel('UnusedMethod', Config::REPORT_INFO);
    $config->setCustomErrorLevel('PossiblyUnusedMethod', Config::REPORT_INFO);
    $config->setCustomErrorLevel('PossiblyUnusedProperty', Config::REPORT_INFO);
    $config->setCustomErrorLevel('UnusedProperty', Config::REPORT_INFO);
    $config->setCustomErrorLevel('MoreSpecificReturnType', Config::REPORT_INFO);
    $config->setCustomErrorLevel('LessSpecificReturnStatement', Config::REPORT_INFO);
    $file_contents = $_POST['code'];

    $file_hash = md5($file_contents);
    if ($file_hash === '0e0eee096e2def5c7ec41e1c4eb0c56d') {
        echo '{"results":[{"severity":"info","line_number":15,"message":"Possibly undefined variable $a, first seen on line 10","file_name":"somefile.php","file_path":"somefile.php","snippet":"echo $a; \/\/ uncomment the line above to fix!","from":229,"to":231},{"severity":"error","line_number":3,"message":"Cannot find referenced variable $on_your","file_name":"somefile.php","file_path":"somefile.php","snippet":"  return $on_your . \"behalf\";","from":66,"to":74},{"severity":"info","line_number":2,"message":"Could not verify return type \'string|null\' for psalmCanCheck","file_name":"somefile.php","file_path":"somefile.php","snippet":"function psalmCanCheck(int $your_code) : ?string {\n  return $on_your . \"behalf\";\n}","from":47,"to":54}]}';
        exit;
    }

    if (strlen($file_contents) > 6000) {
        header(sprintf('HTTP/1.0 %s', 418));
        echo json_encode(['error' => 'Code too long']);
        exit;
    }

    $file_provider = new Psalm\Tests\Provider\FakeFileProvider();
    $project_checker = new ProjectChecker(
    $config,
        $file_provider,
        new Psalm\Tests\Provider\FakeParserCacheProvider(),
    new \Psalm\Provider\NoCache\NoFileStorageCacheProvider(),
        new \Psalm\Provider\NoCache\NoClassLikeStorageCacheProvider(),
        false,
    true,
    ProjectChecker::TYPE_JSON
    );
    $project_checker->codebase->collect_references = true;
    $project_checker->infer_types_from_usage = true;
    $file_path = __DIR__ . '/src/somefile.php';
    $file_provider->registerFile(
        $file_path,
        $file_contents
    );
    $project_checker->codebase->scanner->queueFileForScanning(__DIR__ . '/src/somefile.php');
    $codebase = $project_checker->getCodebase();
    $codebase->addFilesToAnalyze([$file_path => $file_path]);
    $codebase->scanFiles();

    try {
        $file_checker = new FileChecker(
            $project_checker,
            $file_path,
            $config->shortenFileName($file_path)
        );
        $context = new \Psalm\Context();
        $context->collect_references = true;
        $file_checker->analyze($context);
        $project_checker->checkClassReferences();
        $issue_data = IssueBuffer::getIssuesData();

        echo json_encode(['results' => $issue_data]);
        exit;

    } catch (PhpParser\Error $e) {
        $attributes = $e->getAttributes();
        echo json_encode([
            'results' => [[
                'message' => $e->getRawMessage(),
                'line_number' => $e->getStartLine(),
                'from' => $attributes['startFilePos'],
                'to' => $attributes['endFilePos'] + 1,
                'type' => 'error'
            ]]
        ]);
        exit;
    }
}
?>
<html>
<head>
<title>Psalm - a static analysis tool for PHP</title>
<script src="//getpsalm.org/assets/js/fetch.js"></script>
<script src="//getpsalm.org/assets/js/codemirror.js"></script>
<link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/7707372/css/fonts.css" />
<meta property="og:image" content="psalm_preview.png" />
<link rel="stylesheet" href="//getpsalm.org/assets/css/codemirror.css">
<link rel="icon" 
      type="image/png" 
      href="favicon.png">
<style><!--
body {
    font-family: "Sentinel SSm A", "Sentinel SSm B";
    line-height: 1.5em;
    margin: 0;
    padding: 0;
}
hgroup {
    margin: 20px 0 30px;
}
h1 {
    width: 300px;
    margin: 10px auto;
    font-size: 56px;
}
h1 img {
    width: 64px;
    height: auto;
    display: inline-block;
    margin-right: 2px;
    vertical-align: text-top;
    margin-left: 5px;
    box-shadow: 0 -1px 0px rgba(0,0,0,0.1);
    border-radius: 8px;
}
h2 {
    font-weight: normal;
    margin: 10px auto;
    text-align: center;
    font-size: 22px;
}
div.cm_container_container {
    background-color: #f3f3f3;
    box-sizing: border-box;
    padding: 30px 15px;
}

div.cm_container {
    max-width: 638px;
    margin: 0 auto;
}
div.CodeMirror,
div.CodeMirror-lint-tooltip,
.intro pre,
code {
    font-family: "Operator Mono SSm A", "Operator Mono SSm B";
    font-style: normal;
    font-weight: 400;
    line-height: 1.5em;
}
div.CodeMirror {
    border: 1px solid #ddd;
    border-radius: 8px;
    z-index: 2;
    overflow: hidden;
    height: 340px;
    font-size: 14px;
    transition: 0.2s linear border-color, 0.2s linear box-shadow;
}
@media all and (min-device-width: 600px) {
    div.CodeMirror.CodeMirror-focused {
        box-shadow: 0 0 0 5px rgba(0, 0, 0, 0.03);
        border-color: #aaa;
    }

    div.CodeMirror:not(.CodeMirror-focused):hover {
        box-shadow: 0 0 0px 10px rgba(255, 255, 255, 0.5);
    }
    div.CodeMirror-lint-tooltip {
        padding: 4px 6px;
    }
}

.cm-s-elegant span.cm-number, .cm-s-elegant span.cm-string, .cm-s-elegant span.cm-atom { color: #998200; }
.cm-s-elegant span.cm-comment { color: #518e3a; line-height: 1em; }
.cm-s-elegant span.cm-meta { color: #555; font-style: italic; line-height: 1em; }
.cm-s-elegant span.cm-variable { color: #7d4646; }
.cm-s-elegant span.cm-variable-2 { color: #2082af; }
.cm-s-elegant span.cm-qualifier { color: #555; }
.cm-s-elegant span.cm-keyword { color: #6b2eab; }
.cm-s-elegant span.cm-builtin { color: #30a; }
.cm-s-elegant span.cm-link { color: #762; }
.cm-s-elegant span.cm-error { background-color: #fdd; }

.cm-s-elegant .CodeMirror-activeline-background { background: #e8f2ff; }
.cm-s-elegant .CodeMirror-matchingbracket { outline:1px solid rgba(0,0,0,0.15); color:black !important; }

.CodeMirror-lint-mark-error {
    background-image: none;
    box-shadow: 0 -1px rgba(255,0,0,0.3) inset, 0 3px rgba(255,0,0,0.2);
}
.CodeMirror-lint-mark-warning {
    background-image: none;
    box-shadow: 0 -1px rgba(255,200,0,0.6) inset, 0 3px rgba(255,200,0,0.3);
}
.CodeMirror pre.CodeMirror-line {
    padding: 0 8px;
}
.CodeMirror-linenumber {
    padding-left: 0;
}
.CodeMirror-lint-marker-error,
.CodeMirror-lint-message-error {
    background-size: 16px 16px;
    background-image: url(data:image/svg+xml,%3Csvg%20width%3D%2224%22%20height%3D%2225%22%20viewBox%3D%22342%20152%2024%2025%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Ccircle%20fill%3D%22%23D51717%22%20fill-rule%3D%22evenodd%22%20cx%3D%22354%22%20cy%3D%22164.9775%22%20r%3D%2212%22/%3E%3Cg%20fill%3D%22none%22%20fill-rule%3D%22evenodd%22%20stroke-linecap%3D%22square%22%20stroke%3D%22%23FFF%22%20stroke-width%3D%223%22%3E%3Cpath%20d%3D%22M349.0328%20169.9503L359.2832%20159.7M359.5187%20169.9503L349.2684%20159.7%22/%3E%3C/g%3E%3C/svg%3E);
}
.CodeMirror-lint-marker-warning,
.CodeMirror-lint-message-warning {
    background-size: 16px 16px;
    background-image: url(data:image/svg+xml,%3Csvg%20width%3D%2228%22%20height%3D%2223%22%20viewBox%3D%22375%20153%2028%2023%22%20xmlns%3D%22http%3A//www.w3.org/2000/svg%22%3E%3Cpath%20d%3D%22M389%20153.34c2.22%200%203.1%201.68%203.1%201.68l9.04%2015.37s1.55%202.54.5%203.94c-1.06%201.4-2.68%201.66-2.68%201.66h-19.53s-2.35-.32-3.2-1.66c-.85-1.35.8-3.95.8-3.95l8.9-15.38s.85-1.68%203.07-1.68z%22%20fill%3D%22%23F3CF00%22%20fill-rule%3D%22evenodd%22/%3E%3Cpath%20fill%3D%22%23544800%22%20fill-rule%3D%22evenodd%22%20d%3D%22M387%20159l.78%209%202.57.03.65-9.03%22/%3E%3Ccircle%20fill%3D%22%23544800%22%20fill-rule%3D%22evenodd%22%20cx%3D%22389%22%20cy%3D%22171.5%22%20r%3D%222%22/%3E%3C/svg%3E);
}
.CodeMirror-lint-marker-error,
.CodeMirror-lint-marker-warning {
    left: 4px;
    top: 2px;
}
.CodeMirror-lint-message-error,
.CodeMirror-lint-message-warning {
    background-position-y: 2px;
    padding-left: 20px;
}

.CodeMirror-lint-markers {
    width: 24px;
}
.CodeMirror-linenumber {
    color: #ccc;
}
.CodeMirror-gutters {
    background: #fff;
}
.hanger {
    background: #f3f3f3;
    margin: 0 auto 40px;
    width: 230px;
    border-bottom-left-radius: 10px;
    border-bottom-right-radius: 10px;
    padding-bottom: 25px;
}

.intro {
    max-width: 540px;
    padding: 0 15px;
    margin: 0 auto;
}

.intro p,
.intro ul {
    hyphens: auto;
    margin: 1em 0;
}

.intro ul {
    margin-left: 0;
    -webkit-padding-start: 30px;
    padding-inline-start: 30px;
}

.intro h3 {
    font-weight: normal;
    font-size: 24px;
    margin: 1.5em 0 0.8em;
}

.intro code {
    background: #eee;
    border-radius: 3px;
    display: inline-block;
    padding: 0 4px;
    font-size: 0.9em;
    box-shadow: 0 0 0.5px 0 rgba(0, 0, 0, 0.5) inset;
    margin: 0 0.2em 0;
}

.intro pre {
    background: #555;
    padding: 20px 30px;
    font-size: 14px;
    color: #eee;
    border-radius: 6px;
    margin-bottom: 2em;
    max-width: 100%;
    box-sizing: border-box;
    overflow: auto;
}

@media all and (max-device-width: 600px) {
    .intro pre {
        border-radius: 0;
    }
}

.github_button {
    width: 150px;
    display: block;
    text-decoration: none;
    color: #777;
    padding: 14px 10px;
    text-align: center;
    margin: 0 auto;
    border-radius: 6px;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Helvetica, Arial, sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol";
    background-color: #fcfcfc;
    border: 1px solid #d5d5d5;
    box-shadow: 0;
    transition: 0.2s box-shadow ease-out;
}
.github_button:hover {
    box-shadow: 0 0 0px 8px rgba(255, 255, 255, 0.5);
}
.github_button:focus {
    box-shadow: 0 0 0 5px rgba(0, 0, 0, 0.03);
    border-color: #aaa;
}
.github_button strong {
    font-weight: normal;
    color: #000;
}

footer {
    box-sizing: border-box;
    padding: 20px 30px;
    margin-top: 40px;
    text-align: center;
    background: #f3f3f3;
}

.vimeo_logo {
    display: inline-block;
    vertical-align: middle;
    margin: 0 3px 5px;
}

footer a:hover svg path {
    fill: #4bf;
}
--></style>
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
</head>
<body>
<hgroup>
<h1><img src="assets/images/logo.svg" alt=""> Psalm</h1>
<h2>A static analysis tool for PHP</h2>
</hgroup>
<div class="cm_container_container">
<div class="cm_container">
<textarea
    name="code"
    id="code"
    rows="20" style="font-family: monospace; font-size: 14px; max-width: 900px; min-width: 320px;"
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
<div class="hanger">
<a href="https://github.com/vimeo/psalm" class="github_button">View on <strong>GitHub</strong></a>
</div>
<div class="intro">

<p>Life is complicated. PHP can be, too. Psalm is designed to under&shy;stand that complexity, so it can quickly pinpoint type errors in your&nbsp;codebase.</p>

<p>You should use Psalm if you run PHP 5.6+ or PHP 7.0+, and:</p>

<ul>
    <li>you're worried about introducing errors in a big refactor, or</li>
    <li>you have a large team working on the same codebase, or</li>
    <li>you want to add your own application-specific type checks, or</li>
    <li>you just want the assurance that your codebase is type-safe.</li>
</ul>

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

<footer>
<p>Psalm is a
<a href="https://github.com/vimeo"><svg alt="Vimeo" class="vimeo_logo" width="100" height="40"><path d="M22.448 14.575c-.104 2.17-1.618 5.146-4.544 8.912-3.03 3.942-5.59 5.912-7.686 5.912-1.297 0-2.397-1.204-3.3-3.6-.6-2.2-1.202-4.398-1.794-6.598-.664-2.396-1.38-3.6-2.147-3.6-.166 0-.747.354-1.753 1.05l-1.048-1.35c1.1-.965 2.19-1.93 3.257-2.905 1.463-1.265 2.573-1.94 3.3-2.002 1.732-.166 2.8 1.017 3.205 3.558.435 2.74.736 4.44.902 5.115.498 2.27 1.048 3.402 1.65 3.402.466 0 1.172-.737 2.105-2.21.934-1.473 1.432-2.593 1.504-3.37.133-1.277-.365-1.91-1.506-1.91-.53 0-1.08.125-1.65.364 1.1-3.59 3.186-5.333 6.277-5.23 2.273.073 3.35 1.556 3.227 4.46m13.755 7.034c-.933 1.764-2.22 3.37-3.86 4.803-2.24 1.93-4.47 2.905-6.7 2.905-1.037.002-1.826-.33-2.376-.994-.55-.663-.81-1.535-.777-2.603.03-1.1.372-2.8 1.025-5.104.654-2.303.976-3.537.976-3.703 0-.86-.3-1.297-.902-1.297-.198 0-.77.353-1.702 1.048l-1.152-1.35c1.07-.962 2.137-1.927 3.206-2.902 1.43-1.266 2.5-1.94 3.205-2.002 1.1-.103 1.91.23 2.428.976.518.747.705 1.722.58 2.915-.435 2.034-.902 4.607-1.4 7.73-.03 1.43.488 2.146 1.556 2.146.467 0 1.297-.498 2.5-1.483.996-.82 1.815-1.598 2.45-2.324l.942 1.244m-4.357-17.8c-.03.83-.446 1.628-1.255 2.395-.9.86-1.97 1.296-3.204 1.296-1.9 0-2.822-.83-2.75-2.49.032-.86.54-1.69 1.526-2.49.985-.797 2.074-1.19 3.278-1.19.705 0 1.286.27 1.753.82.467.54.684 1.1.653 1.66m35.612 17.8c-.933 1.763-2.22 3.37-3.86 4.802-2.24 1.93-4.47 2.904-6.7 2.904-2.168.002-3.216-1.202-3.153-3.598.03-1.07.238-2.355.622-3.85.384-1.503.59-2.665.623-3.505.03-1.265-.353-1.898-1.152-1.898-.87 0-1.91 1.036-3.112 3.1-1.276 2.168-1.96 4.274-2.064 6.308-.073 1.43.072 2.54.425 3.298-2.324.064-3.963-.32-4.886-1.15-.83-.737-1.212-1.95-1.15-3.652.03-1.068.198-2.137.488-3.205.29-1.07.457-2.023.488-2.853.062-1.234-.384-1.857-1.35-1.857-.84 0-1.74.955-2.706 2.853-.966 1.9-1.505 3.89-1.61 5.956-.06 1.867.053 3.174.364 3.9-2.293.062-3.92-.415-4.876-1.452-.798-.86-1.16-2.18-1.1-3.942.032-.86.188-2.075.457-3.62.28-1.546.425-2.75.457-3.62.062-.603-.083-.903-.446-.903-.197 0-.768.34-1.702 1.015l-1.203-1.348c.168-.135 1.216-1.1 3.156-2.905 1.4-1.297 2.354-1.97 2.852-2.002.872-.073 1.567.29 2.106 1.08.53.787.8 1.69.8 2.727 0 .332-.032.654-.105.954.497-.766 1.08-1.43 1.752-2 1.537-1.34 3.26-2.086 5.157-2.252 1.64-.135 2.8.25 3.505 1.152.57.736.83 1.784.8 3.153.237-.198.486-.416.746-.654.767-.903 1.514-1.62 2.25-2.148 1.235-.902 2.52-1.4 3.86-1.504 1.597-.135 2.75.25 3.454 1.152.602.726.87 1.774.8 3.143-.032.933-.26 2.28-.675 4.065-.416 1.773-.624 2.8-.624 3.07-.03.695.03 1.182.197 1.442.166.27.57.394 1.203.394.467 0 1.297-.497 2.5-1.482.996-.82 1.816-1.598 2.45-2.324l.963 1.254m18.765-.052c-.965 1.598-2.874 3.195-5.706 4.793-3.538 2.032-7.127 3.05-10.758 3.05-2.706 0-4.636-.904-5.808-2.7-.83-1.233-1.234-2.696-1.203-4.407.03-2.698 1.234-5.27 3.6-7.71 2.603-2.665 5.674-4.003 9.21-4.003 3.27 0 5 1.328 5.208 3.994.135 1.702-.798 3.445-2.8 5.24-2.137 1.96-4.824 3.215-8.06 3.744.6.83 1.504 1.245 2.707 1.245 2.396 0 5.02-.613 7.863-1.837 2.033-.86 3.63-1.753 4.803-2.676l.945 1.265m-11.36-5.228c.032-.892-.33-1.35-1.1-1.35-.994 0-2.01.686-3.048 2.066-1.038 1.38-1.567 2.697-1.598 3.962-.02 0-.02.218 0 .643 1.63-.6 3.05-1.514 4.242-2.738.966-1.058 1.463-1.92 1.505-2.583m24.946 1.868c-.135 3.072-1.265 5.717-3.402 7.947-2.135 2.23-4.79 3.35-7.955 3.35-2.635 0-4.637-.85-6.006-2.55-.997-1.267-1.557-2.854-1.65-4.752-.166-2.863.86-5.498 3.1-7.894 2.408-2.665 5.427-3.993 9.057-3.993 2.334 0 4.098.788 5.3 2.344 1.132 1.443 1.65 3.29 1.557 5.55m-5.664-.185c.03-.903-.094-1.733-.374-2.48-.28-.747-.695-1.13-1.224-1.13-1.702 0-3.102.923-4.202 2.76-.933 1.503-1.43 3.11-1.504 4.812-.03.84.114 1.577.446 2.21.364.736.882 1.1 1.556 1.1 1.505 0 2.79-.883 3.86-2.656.892-1.472 1.38-3.008 1.44-4.615h.002z" fill="#112233"></path></svg></a>
open source project</p>
</footer>

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
            fetch('//getpsalm.org/', {
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