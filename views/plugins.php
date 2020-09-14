<?php

use PsalmDotOrg\PluginRepository;

require_once __DIR__ . "/../vendor/autoload.php";

$plugins = PluginRepository::getAll();
?>
<html>

<head>
    <title>Psalm plugins</title>
    <link rel="stylesheet" type="text/css" href="https://cloud.typography.com/751592/7707372/css/fonts.css" />
    <link rel="stylesheet" href="/assets/css/site.css?1">
    <link rel="icon" type="image/png" href="/favicon.png">
    <meta name="viewport" content="initial-scale=1.0,maximum-scale=1.0,user-scalable=no">
</head>

<body>
    <?php require __DIR__ . "/../includes/nav.php" ?>
    <div class="plugin_list">
        <h1>Psalm plugins</h1>
        <?php if ($plugins) : ?>
            <?php foreach ($plugins as $plugin) : ?>
                <section id="<?= $plugin->name ?>">
                    <h2><a href="https://packagist.org/packages/<?= $plugin->name ?>"><?= $plugin->name ?></a></h2>
                    <p><?= $plugin->description ?></p>
                </section>
            <?php endforeach; ?>
        <?php else : ?>
        <?php endif; ?>
    </div>
    <?php require __DIR__ . "/../includes/footer.php" ?>
</body>

</html>
