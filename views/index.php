<html>
<head>
<title>Psalm - a static analysis tool for PHP</title>
<?php require('../includes/meta.php'); ?>
</head>
<body class="front">
<?php require('../includes/nav.php'); ?>
<div class="container front" id="page_container">
    <div class="intro">
        <h2>Give PHP the <span class="CodeMirror-lint-mark-error">love</span> it&nbsp;deserves</h2>

        <p>It’s easy to make great things in PHP, but bugs can creep in just as easily. Psalm is a free &amp; open-source static analysis tool that helps you identify problems in your code, so you can sleep a little better.</p>

        <p>Psalm helps people maintain a wide variety of codebases – large and small, ancient and modern. On its strictest setting it can help you <a href="articles/php-or-type-safety-pick-any-two">prevent almost all type-related runtime errors</a>, and enables you to take advantage of <a href="articles/immutability-and-beyond">safe coding patterns</a> popular in other languages.</p>

        <p>Psalm <a href="https://psalm.dev/docs/manipulating_code/fixing/">also fixes bugs automatically</a>, allowing you to improve your code without breaking a sweat.</p>
    </div>
    <div class="cm_container">
        <textarea
            name="code"
            id="code"
            rows="20" style="visibility: hidden; font-family: monospace; font-size: 14px; max-width: 900px; min-width: 320px;"
        >&lt;<?='?'?>php

/**
 * @return array<string>
 */
function takesAnInt(int $i) {
    return [$i, "hello"];
}

$data = ["some text", 5];
takesAnInt($data[0]);

$condition = rand(0, 5);
if ($condition) {
} elseif ($condition) {}</textarea>
        <div id="psalm_output"></div>
        <div id="settings_panel" class="hidden"></div>
        <div class="button_bar">
            <button onclick="javascript:expandCode();" id="expander"><svg width="15" height="14" xmlns="http://www.w3.org/2000/svg"><path d="M0 5h1.5v6.4L12.9 0l1.4 1.2L2.8 12.5H9V14H0z" fill-rule="evenodd"/></svg> Expand</button>
            <button onclick="javascript:shrinkCode();" id="shrinker"><svg width="15" height="14" xmlns="http://www.w3.org/2000/svg"><path d="M15 9h-1.5V2.6L2.1 14 .8 12.8 12.2 1.5H6V0h9z" fill-rule="evenodd"/></svg> Shrink</button>

            <button onclick="javascript:toggleSettings();" id="settings"><svg width="18" height="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 18 18"><g fill="none" fill-rule="evenodd"><circle cx="9" cy="9" r="6.4" stroke="#000"/><path fill="#000" fill-rule="nonzero" d="M9 17.5l-1-2 4.1-.6v2l-3.1.6zm6-2.5L13.4 14l1.8-2.9 1.6 1.4L15 15zm2.5-6l-2 .8V9l-.6-2.6 2-.4.6 3zM15 3l-1 2-3-2.1 1.4-1.6L15 3zM9 .6l.8 2H9l-3 .6v-2l3-.6h.1zM3.1 3l2 .6L2.6 7 1.3 5.5l1.8-2.6zM.5 8.8l2.1-.6V9l.6 3H1L.5 9v-.2zm2.3 6l1-1.8L7 15l-1.5 1.7-2.6-1.9z"/><circle cx="9" cy="9" r="3.3" stroke="#000" transform="rotate(18 9 9)"/><path fill="#000" fill-rule="nonzero" d="M7.5 13.6l-.2-1.3 2.4.4-.4 1-1.8-.1zm3.7-.3l-.7-.9 1.5-1.2.6 1-1.4 1zm2.4-2.8h-1.3l.2-.4.1-1.5 1.2.1-.2 1.8zM13.3 7l-.9.8-1.2-1.6 1-.7L13.3 7zm-2.7-2.4v1.2l-.5-.2-1.7-.2.4-1.1 1.7.2zm-3.7.2l1 .7-2 1.4-.4-1 1.4-1.1zM4.5 7.4h1.2l-.2.5-.2 1.7-1.1-.4.2-1.7v-.1zm.2 3.6l.8-.7 1.3 1.6-1 .6L4.6 11z"/></g></svg> Settings</button>
            <button onclick="javascript:getLink();"><svg width="28" height="15" xmlns="http://www.w3.org/2000/svg"><g fill-rule="evenodd"><path d="M17.3 2.5A5 5 0 0 0 13 0H5a5 5 0 0 0-5 5v1a5 5 0 0 0 5 5h8a5 5 0 0 0 4.8-3.5H16a4 4 0 0 1-3.5 2h-7a4 4 0 1 1 0-8h7c1 0 2 .4 2.6 1h2.2z"/><path d="M10.4 12.5a5 5 0 0 0 4.4 2.5h8a5 5 0 0 0 5-5V9a5 5 0 0 0-5-5h-8A5 5 0 0 0 10 7.5h1.8a4 4 0 0 1 3.5-2h7a4 4 0 1 1 0 8h-7c-1 0-2-.4-2.7-1h-2.2z"/></g></svg> Get link</button>
        </div>
    </div>
</div>
<script>
var settings = {
    'unused_variables': true,
    'unused_methods': false,
    'memoize_properties': true,
    'memoize_method_calls': false,
    'check_throws': false,
    'strict_internal_functions': false,
    'allow_phpstorm_generics': false,
};
</script>
<?php require('../includes/footer.php'); ?>
<?php require('../includes/script.php'); ?>
</body>
</html>
