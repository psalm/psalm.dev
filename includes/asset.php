<?php
/**
 * Versioned URL of a stylesheet or script: "/assets/css/site.css?v=<mtime>", so a changed file gets a new
 * URL and browsers can cache each version for good (see the Cache-Control rules in .htaccess).
 */
function asset_url(string $path): string
{
    $mtime = @filemtime(dirname(__DIR__) . $path);

    return $mtime === false ? $path : $path . '?v=' . $mtime;
}
