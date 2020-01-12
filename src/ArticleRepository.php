<?php

namespace PsalmDotOrg;

use League\CommonMark\CommonMarkConverter;
use League\CommonMark\Ext\Table\TableExtension;

class ArticleRepository
{
    /** @return Article[] */
    public static function getAll() : array
    {
        $article_dir = __DIR__ . '/../assets/articles/';

        $articles = [];

        foreach (scandir($article_dir) as $file) {
            if (strpos($file, '.md') === (strlen($file) - 3)) {
                $article = self::get(substr($file, 0, -3));

                if ($article) {
                    $date = new \DateTime($article->date, new \DateTimeZone('America/New_York'));
                    
                    if ($date->format('U') < mktime()) {
                        $articles[] = $article;
                    }
                }
            }
        }

        usort(
            $articles,
            function (Article $a, Article $b) : int {
                return (int) ($a->date < $b->date);
            }
        );

        return $articles;
    }
    
    public static function get(
        string $name
    ) : ?Article {
        if (!preg_match('/^[a-z0-9\-]+$/', $name)) {
            return null;
        }

        $is_preview = false;

        try {
            $markdown = self::getMarkdown($name, $is_preview);
        } catch (\Exception $e) {
            header("HTTP/1.0 404 Not Found");
            return null;
        }

        $alt_heading_parser = new AltHeadingParser();
        $alt_html_inline_parser = new AltHtmlInlineParser();

        $environment = \League\CommonMark\Environment::createCommonMarkEnvironment();

        // Add this extension
        $environment->addExtension(new TableExtension());
        $environment->addBlockParser($alt_heading_parser, 100);
        $environment->addInlineParser($alt_html_inline_parser, 100);

        $converter = new CommonMarkConverter([], $environment);

        $html = $converter->convertToHtml($markdown);

        $snippet = mb_substr(trim(strip_tags($html)), 0, 150);

        $description = substr($snippet, 0, strrpos($snippet, ' ')) . '…';

        $date = $alt_html_inline_parser->getDate();

        $title = $alt_html_inline_parser->getTitle();
        $canonical = $alt_html_inline_parser->getCanonical();
        $author = $alt_html_inline_parser->getAuthor();
        $notice = $converter->convertToHtml($alt_html_inline_parser->getNotice());

        return new Article(
            $title,
            $description,
            $canonical,
            $date,
            $author,
            $name,
            $html,
            $notice,
            $is_preview
        );
    }

    private static function getMarkdown(string $name, bool &$is_preview) : string
    {
        $static_file_name = __DIR__ . '/../assets/articles/' . $name . '.md';

        if (file_exists($static_file_name)) {
            return file_get_contents($static_file_name);
        }

        $blogconfig = require(__DIR__ . '/../blogconfig.php');

        $markdown = self::getMarkdownFromGithub(
            $name,
            $blogconfig['owner'],
            $blogconfig['repo'],
            $blogconfig['github_token']
        );
        $is_preview = true;
        return $markdown;
    }

    private static function getMarkdownFromGithub(
        string $name,
        string $owner,
        string $repo,
        string $github_token
    ) : string {
        $github_api_url = 'https://api.github.com';

        // Prepare new cURL resource
        $ch = curl_init($github_api_url . '/repos/' . $owner . '/' . $repo . '/contents/' . $name . '.md');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

        // Set HTTP Header for POST request
        curl_setopt(
            $ch,
            CURLOPT_HTTPHEADER,
            [
                'Accept: application/vnd.github.v3.raw',
                'Authorization: token ' . $github_token,
                'User-Agent: Psalm Blog crawler',
            ]
        );

        // Submit the POST request
        $response = (string) curl_exec($ch);

        $status = curl_getinfo($ch, \CURLINFO_HTTP_CODE);

        // Close cURL session handle
        curl_close($ch);

        if (!$response || $status === 404) {
            throw new \UnexpectedValueException('Response should exist');
        }

        return $response;
    }
}