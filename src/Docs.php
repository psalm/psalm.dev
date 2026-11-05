<?php declare(strict_types=1);

namespace PsalmDotOrg;

use DateTimeImmutable;
use DateTimeZone;

use function array_filter;
use function array_pop;
use function array_values;
use function basename;
use function count;
use function dirname;
use function explode;
use function file_get_contents;
use function filemtime;
use function filesize;
use function gmdate;
use function header;
use function http_response_code;
use function implode;
use function is_dir;
use function is_file;
use function is_string;
use function max;
use function md5;
use function preg_match;
use function preg_match_all;
use function preg_replace;
use function preg_replace_callback;
use function realpath;
use function rtrim;
use function scandir;
use function sort;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function strcspn;
use function strlen;
use function strrpos;
use function substr;
use function trim;
use function usort;

/**
 * The Psalm docs as Markdown, read at request time from vendor/vimeo/psalm/docs, the same
 * directory mkdocs builds the HTML docs from (see mkdocs.yml), so both always match.
 */
final class Docs
{
    public const BASE_URL = 'https://psalm.dev/docs/';

    /**
     * Docs-relative path of a Markdown file: path segments of letters, digits, "_" and "-".
     * No dots outside the extension, so no "..", hidden files or encoded characters.
     */
    private const PATH_REGEX = '~^(?:[A-Za-z0-9_-]+/)*[A-Za-z0-9_-]+\.md$~D';

    /** mkdocs serves README.md and index.md as the page of their directory */
    private const INDEX_FILES = ['index.md', 'README.md'];

    private static ?string $root = null;

    public static function root(): string
    {
        if (self::$root === null) {
            $root = realpath(dirname(__DIR__) . '/vendor/vimeo/psalm/docs');
            if ($root === false || !is_dir($root)) {
                throw new \RuntimeException('Psalm docs directory not found');
            }
            self::$root = $root;
        }

        return self::$root;
    }

    /**
     * Finds the file for a requested docs-relative path. Besides the file path itself
     * ("running_psalm/issues/InvalidReturnType.md") it accepts the page URL with "index.md"
     * appended ("running_psalm/issues/InvalidReturnType/index.md", "index.md").
     *
     * The result matches PATH_REGEX (no "..", slashes at either end or line breaks) and
     * names a file inside the docs directory, so it is safe in file paths, URLs and headers.
     *
     * @return ?string the docs-relative path of an existing file, null if there is none
     * @psalm-taint-escape file
     * @psalm-taint-escape ssrf
     * @psalm-taint-escape header
     */
    public static function locate(string $path): ?string
    {
        if (!preg_match(self::PATH_REGEX, $path)) {
            return null;
        }

        if (self::file($path) !== null) {
            return $path;
        }

        if ($path !== 'index.md' && !str_ends_with($path, '/index.md')) {
            return null;
        }

        $dir = substr($path, 0, -strlen('index.md'));
        $candidates = [$dir . 'README.md'];
        if ($dir !== '') {
            $candidates[] = rtrim($dir, '/') . '.md';
        }

        foreach ($candidates as $candidate) {
            if (self::file($candidate) !== null) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return ?string the absolute path of a Markdown file inside the docs directory,
     *                 null if the path is invalid, missing or resolves outside it
     * @psalm-taint-escape file
     * @psalm-taint-escape ssrf
     * @psalm-taint-escape url_component
     * @psalm-taint-escape url_path
     */
    public static function file(string $path): ?string
    {
        if (!preg_match(self::PATH_REGEX, $path)) {
            return null;
        }

        $root = self::root();
        // realpath resolves symlinks, so a link pointing outside the docs is rejected too
        $file = realpath($root . '/' . $path);

        if ($file === false
            || !str_starts_with($file, $root . '/')
            || !str_ends_with($file, '.md')
            || !is_file($file)
        ) {
            return null;
        }

        return $file;
    }

    public static function contents(string $path): string
    {
        $file = self::file($path);
        $contents = $file === null ? false : file_get_contents($file);
        if ($contents === false) {
            throw new \RuntimeException('Cannot read docs file ' . $path);
        }

        return $contents;
    }

    /**
     * Every Markdown file of the docs, ordered as the docs navigation: the directory index
     * page first, then files, then subdirectories, each alphabetically.
     *
     * @return list<string> docs-relative paths
     */
    public static function all(string $dir = ''): array
    {
        $root = self::root();
        $entries = scandir($root . '/' . $dir);
        if ($entries === false) {
            return [];
        }

        $files = [];
        $dirs = [];
        foreach ($entries as $entry) {
            if ($entry[0] === '.') {
                continue;
            }
            $path = $dir . $entry;
            if (is_dir($root . '/' . $path)) {
                $dirs[] = $path . '/';
            } elseif (self::file($path) !== null) {
                $files[] = $path;
            }
        }

        sort($files);
        usort($files, static fn(string $a, string $b): int => self::isIndex($b) <=> self::isIndex($a));
        sort($dirs);

        $all = $files;
        foreach ($dirs as $subdir) {
            foreach (self::all($subdir) as $path) {
                $all[] = $path;
            }
        }

        return $all;
    }

    /**
     * @param list<string> $paths
     * @return list<string>
     */
    public static function under(array $paths, string $prefix): array
    {
        return array_values(array_filter(
            $paths,
            static fn(string $path): bool => str_starts_with($path, $prefix),
        ));
    }

    public static function url(string $path): string
    {
        return self::BASE_URL . $path;
    }

    /** The URL of the HTML page mkdocs builds from a file */
    public static function pageUrl(string $path): string
    {
        foreach (self::INDEX_FILES as $index) {
            if ($path === $index || str_ends_with($path, '/' . $index)) {
                return self::BASE_URL . substr($path, 0, -strlen($index));
            }
        }

        return self::BASE_URL . substr($path, 0, -strlen('.md')) . '/';
    }

    /** The first "# " heading, or the file name without extension */
    public static function title(string $contents, string $path): string
    {
        if (preg_match('/^#[ \t]+(.+?)[ \t#]*$/m', $contents, $matches)) {
            return $matches[1];
        }

        return basename($path, '.md');
    }

    /**
     * The first sentence of the first plain paragraph after the title, for llms.txt notes
     */
    public static function summary(string $contents): ?string
    {
        $body = preg_replace('/\A.*?^#[ \t].*?$/ms', '', $contents, 1) ?? $contents;

        foreach (explode("\n\n", $body) as $paragraph) {
            $paragraph = trim($paragraph);
            // Skip headings, code, lists, tables, quotes and HTML
            if ($paragraph === '' || preg_match('/^(?:[#`<|>*!-]|\d+\.|\[!|:::)/', $paragraph)) {
                continue;
            }

            // A code block or list can follow the text without a blank line
            $paragraph = preg_replace('/\n(?:```|[-*] |\d+\. ).*/s', '', $paragraph) ?? $paragraph;
            // Keep link text, drop link targets
            $text = preg_replace('/!?\[([^\]]*)\]\([^)]*\)/', '$1', $paragraph) ?? $paragraph;
            $text = preg_replace('/\s+/', ' ', $text) ?? $text;
            if (preg_match('/^(.{20,}?[.!?])(?:\s|$)/', $text, $matches)) {
                $text = $matches[1];
            }
            if (strlen($text) > 200) {
                $text = rtrim(substr($text, 0, 197)) . '...';
            }
            $text = rtrim($text, ' :');

            return $text;
        }

        return null;
    }

    /**
     * Rewrites relative link and image targets to absolute URLs, for Markdown shown away from
     * its own URL (llms-full.txt). Absolute URLs, anchors and code are left as they are.
     *
     * @param string $path docs-relative path the Markdown comes from
     */
    public static function absoluteLinks(string $contents, string $path): string
    {
        // The URL path of the directory holding the file, e.g. /docs/running_psalm/
        $base = '/docs/' . (str_contains($path, '/') ? substr($path, 0, (int) strrpos($path, '/') + 1) : '');

        $lines = explode("\n", $contents);
        $fence = null;
        foreach ($lines as $i => $line) {
            if (preg_match('/^\s*(`{3,}|~{3,})/', $line, $matches)) {
                if ($fence === null) {
                    $fence = $matches[1];
                } elseif ($matches[1][0] === $fence[0] && strlen($matches[1]) >= strlen($fence)) {
                    $fence = null;
                }
                continue;
            }
            if ($fence !== null) {
                continue;
            }

            // Code spans are matched first and returned unchanged
            $lines[$i] = preg_replace_callback(
                '/(`+).+?(?<!`)\1(?!`)|\]\(\s*<?([^\s)>]+)>?((?:\s+(?:"[^"]*"|\'[^\']*\'))?\s*)\)/',
                static function (array $match) use ($base): string {
                    if (($match[2] ?? '') === '' || preg_match('~^(?:[A-Za-z][A-Za-z0-9+.-]*:|#|//)~', $match[2])) {
                        return $match[0];
                    }

                    return '](' . self::resolveUrl($base, $match[2]) . ($match[3] ?? '') . ')';
                },
                $line,
            ) ?? $line;
        }

        return implode("\n", $lines);
    }

    /** Resolves a relative URL against a directory URL path of psalm.dev */
    private static function resolveUrl(string $base, string $target): string
    {
        $suffix = '';
        $cut = strcspn($target, '?#');
        if ($cut < strlen($target)) {
            $suffix = substr($target, $cut);
            $target = substr($target, 0, $cut);
        }

        $path = str_starts_with($target, '/') ? $target : $base . $target;
        $segments = [];
        foreach (explode('/', $path) as $segment) {
            if ($segment === '..') {
                array_pop($segments);
            } elseif ($segment !== '.' && $segment !== '') {
                $segments[] = $segment;
            }
        }

        $trailing = str_ends_with($path, '/') && $segments !== [] ? '/' : '';

        return 'https://psalm.dev/' . implode('/', $segments) . $trailing . $suffix;
    }

    /**
     * Sends cache headers, and a 304 response if the client copy is current.
     *
     * Responses built from several files get no Last-Modified: removing a page that is older
     * than the rest changes the response but not the newest date. Their ETag covers the list.
     *
     * @param list<string> $paths docs-relative paths the response is built from
     * @param list<string> $code  PHP files that build the response
     */
    public static function sendCacheHeaders(array $paths, array $code): void
    {
        $modified = 0;
        $tag = '';
        foreach ($paths as $path) {
            $file = self::root() . '/' . $path;
            $modified = max($modified, (int) filemtime($file));
            $tag .= $path . ':' . (int) filemtime($file) . ':' . (int) filesize($file) . "\n";
        }
        foreach ($code as $file) {
            $modified = max($modified, (int) filemtime($file));
            $tag .= $file . ':' . (int) filemtime($file) . "\n";
        }
        $dated = count($paths) === 1;

        $etag = md5($tag);
        header('Cache-Control: public, max-age=3600');
        header('ETag: "' . $etag . '"');
        if ($dated) {
            header('Last-Modified: ' . gmdate('D, d M Y H:i:s', $modified) . ' GMT');
        }

        $if_none_match = $_SERVER['HTTP_IF_NONE_MATCH'] ?? null;
        $if_modified_since = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;
        // If-None-Match wins when both are sent (RFC 9110, 13.2.2)
        if (is_string($if_none_match)) {
            $current = self::etagMatches($if_none_match, $etag);
        } else {
            $since = $dated && is_string($if_modified_since) ? self::parseHttpDate($if_modified_since) : null;
            $current = $since !== null && $since >= $modified;
        }

        if ($current) {
            http_response_code(304);
            exit;
        }
    }

    /**
     * Weak comparison (RFC 9110, 13.1.2). mod_deflate appends "-gzip" to the ETag of
     * compressed responses, so that suffix matches too.
     */
    private static function etagMatches(string $header, string $etag): bool
    {
        if (trim($header) === '*') {
            return true;
        }

        preg_match_all('/(?:W\/)?"([^"]*)"/', $header, $matches);
        foreach ($matches[1] as $candidate) {
            if ($candidate === $etag || $candidate === $etag . '-gzip') {
                return true;
            }
        }

        return false;
    }

    /**
     * An HTTP date (RFC 9110, 5.6.7): IMF-fixdate, or the obsolete RFC 850 and asctime forms
     */
    private static function parseHttpDate(string $value): ?int
    {
        $value = trim($value);
        $formats = [
            '!D, d M Y H:i:s \G\M\T',
            '!l, d-M-y H:i:s \G\M\T',
            '!D M j H:i:s Y',
        ];
        foreach ($formats as $format) {
            // asctime pads single-digit days with a space
            $input = $format === '!D M j H:i:s Y' ? (preg_replace('/ +/', ' ', $value) ?? $value) : $value;
            $date = DateTimeImmutable::createFromFormat($format, $input, new DateTimeZone('UTC'));
            $errors = DateTimeImmutable::getLastErrors();
            if ($date !== false && ($errors === false || $errors['warning_count'] === 0)) {
                return $date->getTimestamp();
            }
        }

        return null;
    }

    private static function isIndex(string $path): bool
    {
        foreach (self::INDEX_FILES as $index) {
            if ($path === $index || str_ends_with($path, '/' . $index)) {
                return true;
            }
        }

        return false;
    }
}
