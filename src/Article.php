<?php

namespace PsalmDotOrg;

/** @psalm-immutable */
class Article
{
	public $title;
	public $description;
	public $canonical;
	public $html;
	public $date;
	public $author;
	public $slug;
	public $notice;
	public $is_preview;

	public function __construct(
		string $title,
		string $description,
		string $canonical,
		string $date,
		string $author,
		string $slug,
		string $html,
		string $notice,
		bool $is_preview
	) {
		$this->title = $title;
		$this->description = $description;
		$this->canonical = $canonical;
		$this->html = $html;
		$this->date = $date;
		$this->author = $author;
		$this->slug = $slug;
		$this->is_preview = $is_preview;
		$this->notice = $notice;
	}
}