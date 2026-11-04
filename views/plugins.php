<?php

use PsalmDotOrg\Plugin;
use PsalmDotOrg\PluginRepository;

require_once __DIR__ . "/../vendor/autoload.php";

$catalog = PluginRepository::getCatalog();
$psalm_major = $catalog->psalm_major;
$filter_majors = $psalm_major ? range($psalm_major, max(1, $psalm_major - 2), -1) : [];

$e = static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES | ENT_HTML5);

$release_label = static function (?string $time): string {
    if ($time === null) {
        return 'No stable release yet';
    }
    $released = new DateTimeImmutable($time);
    $days = (int) $released->diff(new DateTimeImmutable())->days;
    if ($days === 0) {
        return 'Released today';
    }
    if ($days < 31) {
        return 'Released ' . $days . ($days === 1 ? ' day' : ' days') . ' ago';
    }
    if ($days > 730) {
        return 'No release since ' . $released->format('M Y');
    }
    return 'Released ' . $released->format('M Y');
};

$library_list = static function (array $libraries) use ($e): string {
    $items = array_map(static fn (string $library): string => '<code>' . $e($library) . '</code>', $libraries);
    return count($items) > 1
        ? implode(', ', array_slice($items, 0, -1)) . ' or ' . end($items)
        : implode('', $items);
};

$works_classes = static function (Plugin $plugin): string {
    if ($plugin->psalm_majors === null) {
        return 'works-unknown';
    }
    return implode(' ', array_map(static fn (int $major): string => 'works-' . $major, $plugin->psalm_majors));
};

$meta_description = 'Psalm plugins for Laravel, Symfony, PHPUnit, Doctrine and other frameworks and libraries, ranked by monthly downloads.';
?>
<html lang="en">

<head>
    <title>Psalm plugins</title>
    <?php require __DIR__ . "/../includes/meta.php" ?>
    <?php if ($filter_majors) : ?>
    <style>
        <?php foreach ($filter_majors as $major) : ?>
        .plugin_list:has(#works-with-<?= $major ?>:checked) .plugin:not(.works-<?= $major ?>, .works-unknown) { display: none; }
        <?php endforeach; ?>
    </style>
    <?php endif; ?>
</head>

<body>
    <?php require __DIR__ . "/../includes/nav.php" ?>
    <div class="plugin_list">
        <h1>Psalm plugins</h1>
        <p>Plugins teach Psalm about the frameworks and libraries you use, or add checks Psalm doesn’t make on its own.</p>
        <p>Listed by monthly downloads on Packagist. See <a href="https://psalm.dev/docs/running_psalm/plugins/using_plugins/">Using plugins</a> for how to install and configure them.</p>

        <?php if ($catalog->plugins) : ?>
            <fieldset class="works_with">
                <legend>Works with</legend>
                <?php foreach ($filter_majors as $major) : ?>
                <label><input type="radio" name="works_with" id="works-with-<?= $major ?>"<?= $major === $psalm_major ? ' checked' : '' ?>> Psalm <?= $major ?></label>
                <?php endforeach; ?>
                <label><input type="radio" name="works_with" id="works-with-any"> Any Psalm</label>
                <p class="hint">Plugins that don’t declare a Psalm version stay in the list.</p>
            </fieldset>

            <?php foreach ($catalog->plugins as $plugin) : ?>
                <?php $max_major = $plugin->maxPsalmMajor(); ?>
                <section class="plugin <?= $works_classes($plugin) ?>" id="<?= $e($plugin->name) ?>">
                    <h2><a href="https://packagist.org/packages/<?= $e($plugin->name) ?>"><?= $e($plugin->name) ?></a></h2>
                    <p><?= $e($plugin->description) ?></p>
                    <p class="meta">
                        <span title="Monthly downloads" class="downloads"><?= number_format($plugin->monthly_downloads) ?></span>
                        <span title="Stars on GitHub" class="stars"><?= number_format($plugin->stars) ?></span>
                        <span title="Latest stable release" class="released"><?= $e($release_label($plugin->last_release)) ?></span>
                        <?php if ($max_major !== null && $max_major < $psalm_major) : ?>
                        <span class="outdated">Up to Psalm <?= $max_major ?></span>
                        <?php endif; ?>
                    </p>
                    <?php if ($plugin->bundled) : ?>
                    <p class="included">
                        <strong>Included with Psalm <?= $psalm_major ?>.</strong>
                        <?php if ($plugin->libraries) : ?>
                        On automatically when <code>composer.json</code> requires <?= $library_list($plugin->libraries) ?>.
                        <?php else : ?>
                        On automatically when your project uses the library it covers.
                        <?php endif; ?>
                    </p>
                    <?php else : ?>
                    <details class="install">
                        <summary>Install</summary>
                        <div class="commands">
                            <pre><code>composer require --dev <?= $e($plugin->name) ?>

vendor/bin/psalm-plugin enable <?= $e($plugin->name) ?></code></pre>
                            <button type="button" class="copy" hidden>Copy</button>
                        </div>
                    </details>
                    <?php endif; ?>
                </section>
            <?php endforeach; ?>

            <?php
            $hidden = array_filter([
                $catalog->abandoned_count ? $catalog->abandoned_count . ' marked abandoned on Packagist' : null,
                $catalog->unreleased_count ? $catalog->unreleased_count . ' with no tagged release' : null,
            ]);
            ?>
            <?php if ($hidden) : ?>
            <p class="footnote">Not listed: <?= implode(' and ', $hidden) ?>.</p>
            <?php endif; ?>
        <?php else : ?>
            <p>The plugin list is unavailable right now. Search <a href="https://packagist.org/?type=psalm-plugin">Packagist</a> instead.</p>
        <?php endif; ?>
    </div>
    <script>
        document.querySelectorAll('.plugin_list .install .copy').forEach(function (button) {
            button.hidden = !navigator.clipboard;
            button.addEventListener('click', function () {
                var code = button.parentNode.querySelector('code').textContent.replace(/\n+/g, '\n');
                navigator.clipboard.writeText(code).then(function () {
                    button.textContent = 'Copied';
                    setTimeout(function () { button.textContent = 'Copy'; }, 1500);
                });
            });
        });
    </script>
    <?php require __DIR__ . "/../includes/footer.php" ?>
</body>

</html>
