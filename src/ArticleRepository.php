<?php

namespace PsalmDotOrg;

use League\CommonMark\CommonMarkConverter;

class ArticleRepository
{
	public static function getHtml(string $name, string &$title) : string
	{
		if (!preg_match('/^[a-z0-9\-]+$/', $name)) {
			return '';
		}

		$markdown = self::getMarkdown($name);

		$alt_heading_parser = new AltHeadingParser();
		$alt_html_inline_parser = new AltHtmlInlineParser();

		$environment = \League\CommonMark\Environment::createCommonMarkEnvironment();
		$environment->addBlockParser($alt_heading_parser, 100);
		$environment->addInlineParser($alt_html_inline_parser, 100);

		$converter = new CommonMarkConverter([], $environment);

		$html = $converter->convertToHtml($markdown);

		$title = (string) $alt_html_inline_parser->getTitle();
		$attribution = $alt_html_inline_parser->getDate() . ' by ' . $alt_html_inline_parser->getAuthor();

		return '<h1>' . $title . '</h1>'
			. '<p class="meta">' . $attribution . '</p>'
			. $html;
	}

	private static function getMarkdown(string $name) : string
	{
		$static_file_name = __DIR__ . '/../assets/articles/' . $name . '.md';

		if (file_exists($static_file_name)) {
			return file_get_contents($static_file_name);
		}

		$blogconfig = require(__DIR__ . '/../blogconfig.php');

		try {
			return self::getMarkdownFromGithub(
				$name,
				$blogconfig['owner'],
				$blogconfig['repo'],
				$blogconfig['github_token']
			);
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