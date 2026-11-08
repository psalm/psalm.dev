<?php declare(strict_types=1);

namespace PsalmDotOrg;

use DateTimeImmutable;
use DateTimeZone;
use Exception;

use function array_slice;
use function basename;
use function explode;
use function file_get_contents;
use function glob;
use function preg_match;
use function trim;
use function usort;

/**
 * Blog articles published within the last few days, for the homepage news list.
 *
 * Reads only each article's frontmatter: the blog library renders every article's Markdown,
 * which is too much work for each homepage view.
 */
final class FreshArticle
{
    /** Article dates are New York time, as in Muglug\Blog\ArticleRepository */
    private const TIMEZONE = 'America/New_York';

    private function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly DateTimeImmutable $date,
    ) {
    }

    /**
     * Articles published within the last $max_age_days days, newest first, at most $limit.
     *
     * @return list<self>
     */
    public static function recent(string $article_dir, int $max_age_days = 14, int $limit = 3): array
    {
        $timezone = new DateTimeZone(self::TIMEZONE);
        $now = new DateTimeImmutable('now', $timezone);
        $oldest = $now->modify('today')->modify('-' . $max_age_days . ' days');
        $recent = [];

        $files = glob($article_dir . '/*.md');

        foreach ($files === false ? [] : $files as $file) {
            $slug = basename($file, '.md');

            if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
                continue;
            }

            $meta = self::frontmatter($file);

            if (!isset($meta['title'], $meta['date']) || $meta['title'] === '') {
                continue;
            }

            try {
                $date = new DateTimeImmutable($meta['date'], $timezone);
            } catch (Exception) {
                continue;
            }

            // scheduled articles stay hidden until their date, as on the blog
            if ($date < $oldest || $date > $now) {
                continue;
            }

            $recent[] = new self($slug, $meta['title'], $date);
        }

        usort($recent, static fn (self $a, self $b): int => $b->date <=> $a->date);

        return array_slice($recent, 0, $limit);
    }

    /**
     * The <!-- key: value --> header, parsed as Muglug\Blog\ArticleRepository::extractFrontmatter() does
     * (private there, and the repository's get() renders the whole article).
     *
     * @return array<string, string>
     */
    private static function frontmatter(string $file): array
    {
        $markdown = file_get_contents($file);

        if ($markdown === false || !preg_match('/^<!--\s*(.*?)\s*-->/s', $markdown, $block)) {
            return [];
        }

        $meta = [];

        foreach (explode("\n", $block[1]) as $line) {
            [$key, $value] = explode(':', $line, 2) + ['', null];

            if ($value !== null && trim($key) !== '') {
                $meta[trim($key)] = trim($value);
            }
        }

        return $meta;
    }
}
