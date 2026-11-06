<?php declare(strict_types=1);

namespace PsalmDotOrg;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use function array_filter;
use function array_values;
use function basename;
use function dirname;
use function explode;
use function file_get_contents;
use function preg_match;
use function preg_replace;
use function realpath;
use function rtrim;
use function sort;
use function str_ends_with;
use function str_starts_with;
use function strlen;
use function substr;
use function trim;

/**
 * The Psalm docs as Markdown, read from vendor/vimeo/psalm/docs, the same directory mkdocs
 * builds the HTML docs from (see mkdocs.yml), so both always match.
 */
final class Docs
{
    public const BASE_URL = 'https://psalm.dev/docs/';

    public static function root(): string
    {
        $root = realpath(dirname(__DIR__) . '/vendor/vimeo/psalm/docs');
        if ($root === false) {
            throw new \RuntimeException('Psalm docs directory not found');
        }

        return $root;
    }

    /**
     * Every Markdown file of the docs, sorted by path. README.md comes first in its
     * directory, since uppercase letters sort before lowercase ones.
     *
     * @return list<string> docs-relative paths
     */
    public static function all(): array
    {
        $root = self::root();
        $paths = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS));
        /** @var \SplFileInfo $file */
        foreach ($files as $file) {
            if ($file->isFile() && str_ends_with($file->getFilename(), '.md')) {
                $paths[] = substr($file->getPathname(), strlen($root) + 1);
            }
        }
        sort($paths);

        return $paths;
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

    public static function contents(string $path): string
    {
        $contents = file_get_contents(self::root() . '/' . $path);
        if ($contents === false) {
            throw new \RuntimeException('Cannot read docs file ' . $path);
        }

        return $contents;
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
}
