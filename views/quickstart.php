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
<div class="container">
<?php require('../includes/nav.php'); ?>
<div class="documentation">
    <h3>Quickstart Guide</h3>

    <p>Install via <a href="http://getcomposer.org">Composer</a> or download <a href="https://github.com/vimeo/psalm">via GitHub</a>:</p>
    <pre>composer require --dev vimeo/psalm</pre>

    <p>Add a <a href="https://github.com/vimeo/psalm/blob/master/docs/configuration.md">config</a>:</p>

    <pre>./vendor/bin/psalm --init</pre>

    <p>Then run Psalm:</p>
    <pre>./vendor/bin/psalm</pre>
    <p>The config created above will show you all issues in your code, but will emit <code>INFO</code> issues (as opposed to <code>ERROR</code>) for certain common trivial code problems. If you want a more lenient config you can specify the level with</p>

    <pre>./vendor/bin/psalm --init [source_dir] [level]</pre>

    <p>You can also <a href="https://github.com/vimeo/psalm/blob/master/docs/dealing_with_code_issues.md">learn how to suppress certain issues</a>.</p>

    <p>Want to know more? Check out the <a href="https://github.com/vimeo/psalm/blob/master/docs/index.md">docs</a>!</p>
</div>
</div>

<?php require('../includes/footer.php'); ?>

</body>
</html>
