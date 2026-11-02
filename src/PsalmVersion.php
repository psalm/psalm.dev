<?php

namespace PsalmDotOrg;

/**
 * Resolves the installed vimeo/psalm commit to the nearest tag it is built on and the number of
 * commits on top of it, using the GitHub REST API, and caches the result in psalm_version.json.
 *
 * The cache is refreshed lazily because Psalm on psalm.dev gets updated without composer scripts running.
 */
final class PsalmVersion
{
    public const CACHE_FILE = __DIR__ . '/../psalm_version.json';
    private const REPO_URL = 'https://github.com/vimeo/psalm';
    private const API_URL = 'https://api.github.com/repos/vimeo/psalm';
    private const MAX_COMPARES = 5;
    private const RETRY_FAILED_AFTER = 3600;
    // the tag ends up in the label and in URLs, so anything unusual is ignored
    private const TAG_PATTERN = '/^v?\d+\.\d+\.\d+(?:-[0-9A-Za-z.]+)?$/D';
    private const COMMIT_PATTERN = '/^[0-9a-f]{40}$/D';

    /**
     * @return array{
     *     label: string,
     *     label_url: string,
     *     tag: ?string,
     *     tag_url: ?string,
     *     commits_ahead: ?int,
     *     compare_url: ?string,
     *     commit: string,
     *     short_commit: string,
     *     commit_url: string
     * }
     */
    public static function describe(string $commit, float $time_budget = 3.0): array
    {
        $resolved = self::cached($commit, $time_budget);
        $short_commit = substr($commit, 0, 7);
        $commit_url = self::REPO_URL . '/commit/' . $commit;
        $result = [
            'label' => $short_commit,
            'label_url' => $commit_url,
            'tag' => null,
            'tag_url' => null,
            'commits_ahead' => null,
            'compare_url' => null,
            'commit' => $commit,
            'short_commit' => $short_commit,
            'commit_url' => $commit_url,
        ];

        if ($resolved === null) {
            return $result;
        }

        [$tag, $commits_ahead] = $resolved;
        $result['tag'] = $tag;
        $result['tag_url'] = self::REPO_URL . '/releases/tag/' . rawurlencode($tag);
        $result['commits_ahead'] = $commits_ahead;

        if ($commits_ahead === 0) {
            $result['label'] = $tag;
            $result['label_url'] = $result['tag_url'];
        } else {
            $result['compare_url'] = self::REPO_URL . '/compare/' . rawurlencode($tag) . '...' . $commit;
            $result['label'] = $tag . '+' . $commits_ahead;
            $result['label_url'] = $result['compare_url'];
        }

        return $result;
    }

    /**
     * @return array{0: string, 1: int}|null
     */
    private static function cached(string $commit, float $time_budget): ?array
    {
        if (preg_match(self::COMMIT_PATTERN, $commit) !== 1) {
            return null;
        }

        $data = @file_get_contents(self::CACHE_FILE);
        $data = $data !== false ? json_decode($data, true) : null;

        if (is_array($data) && ($data['commit'] ?? null) === $commit) {
            $tag = $data['tag'] ?? null;
            $commits_ahead = $data['commits_ahead'] ?? null;

            if (self::isValidTag($tag) && is_int($commits_ahead) && $commits_ahead >= 0) {
                return [$tag, $commits_ahead];
            }

            if (is_int($data['failed_at'] ?? null) && time() - $data['failed_at'] < self::RETRY_FAILED_AFTER) {
                return null;
            }
        }

        // without a writable cache every request would call GitHub (and soon hit the 60/h anonymous limit)
        if (!is_writable(dirname(self::CACHE_FILE))) {
            return null;
        }

        // one resolver at a time; concurrent requests show the bare commit instead of piling onto GitHub
        $lock = @fopen(self::CACHE_FILE . '.lock', 'c');

        if ($lock === false) {
            return null;
        }

        if (!@flock($lock, LOCK_EX | LOCK_NB)) {
            fclose($lock);
            return null;
        }

        try {
            $resolved = self::resolve($commit, $time_budget);
            self::store(
                $resolved !== null
                    ? ['commit' => $commit, 'tag' => $resolved[0], 'commits_ahead' => $resolved[1]]
                    : ['commit' => $commit, 'failed_at' => time()]
            );

            return $resolved;
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    /**
     * Mirrors `git describe --tags`: the most recent tag the commit contains, plus commits since it.
     *
     * @return array{0: string, 1: int}|null
     */
    public static function resolve(string $commit, float $time_budget = 3.0): ?array
    {
        if (preg_match(self::COMMIT_PATTERN, $commit) !== 1) {
            return null;
        }

        $deadline = microtime(true) + $time_budget;
        $tags = self::get('/tags?per_page=100', $deadline);

        if (!is_array($tags)) {
            return null;
        }

        $names = [];

        foreach ($tags as $tag) {
            if (!self::isValidTag($tag['name'] ?? null)) {
                continue;
            }

            if (($tag['commit']['sha'] ?? null) === $commit) {
                return [$tag['name'], 0];
            }

            $names[] = $tag['name'];
        }

        // GitHub lists the legacy "v4.x" tags first, so order by version instead
        usort($names, static fn(string $a, string $b): int => version_compare(ltrim($b, 'v'), ltrim($a, 'v')));

        foreach (array_slice($names, 0, self::MAX_COMPARES) as $name) {
            $comparison = self::get('/compare/' . rawurlencode($name) . '...' . $commit, $deadline);

            if (!is_array($comparison)) {
                return null;
            }

            if (($comparison['behind_by'] ?? null) === 0
                && is_int($comparison['ahead_by'] ?? null)
                && $comparison['ahead_by'] >= 0
            ) {
                return [$name, $comparison['ahead_by']];
            }
        }

        return null;
    }

    private static function get(string $path, float $deadline): ?array
    {
        $timeout = $deadline - microtime(true);

        if ($timeout <= 0) {
            return null;
        }

        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "User-Agent: psalm.dev\r\nAccept: application/vnd.github+json\r\n",
                'timeout' => $timeout,
                'ignore_errors' => true,
            ],
        ]);
        $stream = @fopen(self::API_URL . $path, 'r', false, $context);

        if ($stream === false) {
            return null;
        }

        // wrapper_data instead of $http_response_header, which is deprecated as of PHP 8.5
        $status = stream_get_meta_data($stream)['wrapper_data'][0] ?? '';
        $body = @stream_get_contents($stream);
        fclose($stream);

        if ($body === false || !is_string($status) || !preg_match('/^HTTP\/\S+ 200\b/', $status)) {
            return null;
        }

        $data = json_decode($body, true);

        return is_array($data) ? $data : null;
    }

    private static function store(array $data): void
    {
        $tmp = self::CACHE_FILE . '.' . bin2hex(random_bytes(4)) . '.tmp';

        if (@file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT) . PHP_EOL) !== false
            && !@rename($tmp, self::CACHE_FILE)
        ) {
            @unlink($tmp);
        }
    }

    /**
     * @param mixed $tag
     * @psalm-assert-if-true string $tag
     */
    private static function isValidTag($tag): bool
    {
        return is_string($tag) && preg_match(self::TAG_PATTERN, $tag) === 1;
    }
}
