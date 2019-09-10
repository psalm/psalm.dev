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

	public function __construct(
		string $title,
		string $description,
		string $canonical,
		string $date,
		string $author,
		string $slug,
		string $html
	) {
		$this->title = $title;
		$this->description = $description;
		$this->canonical = $canonical;
		$this->html = $html;
		$this->date = $date;
		$this->author = $author;
		$this->slug = $slug;
	}
}