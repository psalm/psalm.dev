<html>
<head>
<title>Psalm - a static analysis tool for PHP</title>
<script src="/assets/js/fetch.js"></script>
<script src="/assets/js/codemirror.js"></script>
<link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/7707372/css/fonts.css" />
<meta property="og:image" content="psalm_preview.png" />
<link rel="stylesheet" href="/assets/css/site.css">
<link rel="icon" type="image/png" href="favicon.png">
<meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
</head>
<body>
<div class="container" id="page_container">
    <?php require('../includes/nav.php'); ?>
    <div class="cm_container">
        <textarea
            name="code"
            id="code"
            rows="20" style="visibility: hidden; font-family: monospace; font-size: 14px; max-width: 900px; min-width: 320px;"
        >&lt;<?='?'?>php
  
function foo(string $s) : void {
    return "bar";
}

$a = ["hello", 5];
foo($a[1]);
foo();

if (rand(0, 1)) $b = 5;
echo $b;

$c = rand(0, 5);
if ($c) {} elseif ($c) {}
</textarea>
        <div id="psalm_output"></div>
        <div class="button_bar">
            <button onclick="javascript:expandCode();" id="expander"><svg width="15" height="15" xmlns="http://www.w3.org/2000/svg"><path d="M0 6h2v5.8L13 .7 14.2 2 3.3 13H9v2H0z" fill="#000" fill-rule="evenodd"/></svg> Expand</button>
            <button onclick="javascript:shrinkCode();" id="shrinker"><svg width="15" height="15" xmlns="http://www.w3.org/2000/svg"><path d="M15 9h-2V3.2L2 14.3.8 13 11.7 2H6V0h9z" fill="#000" fill-rule="evenodd"/></svg> Shrink</button>
            <button onclick="javascript:getLink();"><svg width="27" height="16" xmlns="http://www.w3.org/2000/svg"><g fill-rule="evenodd"><path d="M9.67 13.5A5 5 0 0 0 14 16h8a5 5 0 0 0 5-5V9a5 5 0 0 0-5-5h-8a5 5 0 0 0-4.98 4.5h1.92A3 3 0 0 1 13.89 6h8.22a3 3 0 0 1 3 3v2a3 3 0 0 1-3 3h-8.22c-.6 0-1.18-.18-1.65-.5H9.67z"/><path d="M17.33 2.5A5 5 0 0 0 13 0H5a5 5 0 0 0-5 5v2a5 5 0 0 0 5 5h8a5 5 0 0 0 4.98-4.5h-1.92a3 3 0 0 1-2.95 2.5H4.89a3 3 0 0 1-3-3V5a3 3 0 0 1 3-3h8.22c.6 0 1.18.18 1.65.5h2.57z"/></g></svg> Get link</button>
        </div>
    </div>
    

    <div class="intro">
        <p>Life is complicated. PHP can be, too.</p>

        <p>Psalm is designed to understand that complexity, allowing it to quickly find common programmer errors like null references and misspelled variable names.</p>

        <p>You should use Psalm if you run PHP 5.6+ or PHP 7, and you want&nbsp;to</p>

        <ul>
            <li>prevent errors in a big refactor</li>
            <li>maintain a consistent level of quality across a large team</li>
            <li>guarantee that there won’t be any type-related runtime errors</li>
        </ul>

        <p>Psalm has a number of other features that help you improve your codebase, including a fixer called Psalter that updates your code directly by leveraging Psalm’s analysis engine.</p>

        <p>Interested in how Psalm came to be? Read <a href="https://medium.com/vimeo-engineering-blog/fixing-code-that-aint-broken-a99e05998c24">this explainer</a>.</p>
    </div>
</div>

<?php require('../includes/footer.php'); ?>
<?php require('../includes/script.php'); ?>
</body>
</html>
