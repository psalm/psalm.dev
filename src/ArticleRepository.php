<?php

namespace PsalmDotOrg;

use League\CommonMark\CommonMarkConverter;

class ArticleRepository
{
	public static function getHtml(string $name, string &$title, string &$description) : string
	{
		if (!preg_match('/^[a-z0-9\-]+$/', $name)) {
			return '';
		}

		$is_preview = false;

		$markdown = self::getMarkdown($name, $is_preview);

		$alt_heading_parser = new AltHeadingParser();
		$alt_html_inline_parser = new AltHtmlInlineParser();

		$environment = \League\CommonMark\Environment::createCommonMarkEnvironment();
		$environment->addBlockParser($alt_heading_parser, 100);
		$environment->addInlineParser($alt_html_inline_parser, 100);

		$converter = new CommonMarkConverter([], $environment);

		$html = $converter->convertToHtml($markdown);

		$description = substr(strip_tags($html), 0, 50) . '&hellip;';

		$title = (string) $alt_html_inline_parser->getTitle();
		$attribution = $alt_html_inline_parser->getDate() . ' by ' . $alt_html_inline_parser->getAuthor();

		return '<h1>' . $title . '</h1>' . PHP_EOL
			. '<p class="meta">' . $attribution . '</p>' . PHP_EOL
			. ($is_preview
				? '<p class="preview_warning">Article preview - contents subject to change</p>' . PHP_EOL 
				: '')
			. $html;
	}

	private static function getMarkdown(string $name, bool &$is_preview) : string
	{
		$static_file_name = __DIR__ . '/../assets/articles/' . $name . '.md';

		if (file_exists($static_file_name)) {
			return file_get_contents($static_file_name);
		}

		$blogconfig = require(__DIR__ . '/../blogconfig.php');

		try {
			$markdown = self::getMarkdownFromGithub(
				$name,
				$blogconfig['owner'],
				$blogconfig['repo'],
				$blogconfig['github_token']
			);
			$is_preview = true;
			return $markdown;
		} catch (\Exception $e) {
			header("HTTP/1.0 404 Not Found");

			return $e->getMessage();
		}
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

		// Close cURL session handle
		curl_close($ch);

		if (!$response) {
		    throw new \UnexpectedValueException('Response should exist');
		}

		return $response;
	}
}