<?php

namespace PsalmDotOrg;

use League\CommonMark\Inline\Element\HtmlInline;
use League\CommonMark\InlineParserContext;
use League\CommonMark\Util\RegexHelper;
use League\CommonMark\Inline\Parser\InlineParserInterface;

class AltHtmlInlineParser implements InlineParserInterface
{
    /** @var ?string */
    private $title = null;

    /** @var ?string */
    private $author = null;

    /** @var ?string */
    private $author_link = null;

    /** @var ?string */
    private $notice = null;

    /** @var ?string */
    private $date = null;

    /** @var ?string */
    private $canonical = null;

    /**
     * @return string[]
     */
    public function getCharacters(): array
    {
        return ['<'];
    }

    /**
     * @param InlineParserContext $inlineContext
     *
     * @return bool
     */
    public function parse(InlineParserContext $inlineContext): bool
    {
        $cursor = $inlineContext->getCursor();
        if ($m = $cursor->match('/^' . RegexHelper::PARTIAL_HTMLTAG . '/i')) {
            if (strpos($m, '<!--') === 0) {
                $content = trim(substr($m, 4, -3));
                $lines = explode("\n", $content);
                foreach ($lines as $line) {
                    $line_parts = explode(':', trim($line));
                    $key = array_shift($line_parts);
                    $value = trim(implode(':', $line_parts));

                    if (in_array($key, ['title', 'author', 'author_link', 'date', 'canonical', 'notice'])) {
                        $this->$key = $value;
                    }
                }
            } else {
                $inlineContext->getContainer()->appendChild(new HtmlInline($m));
            }

            return true;
        }

        return false;
    }

    public function getTitle() : string
    {
        return (string) $this->title;
    }

    public function getAuthor() : string
    {
        return (string) $this->author;
    }

    public function getNotice() : string
    {
        return (string) $this->notice;
    }

    public function getAuthorLink() : string
    {
        return (string) $this->author_link;
    }

    public function getDate() : string
    {
        return (string) $this->date;
    }

    public function getCanonical() : string
    {
        return (string) $this->canonical;
    }
}