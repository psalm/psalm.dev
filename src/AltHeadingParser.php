<?php

namespace PsalmDotOrg;

class AltHeadingParser implements \League\CommonMark\Block\Parser\BlockParserInterface
{
	public function parse(\League\CommonMark\ContextInterface $context, \League\CommonMark\Cursor $cursor): bool
    {
        if ($cursor->isIndented()) {
            return false;
        }

        $match = \League\CommonMark\Util\RegexHelper::matchAll('/^#{1,6}(?:[ \t]+|$)/', $cursor->getLine(), $cursor->getNextNonSpacePosition());
        if (!$match) {
            return false;
        }

        $cursor->advanceToNextNonSpaceOrTab();

        $cursor->advanceBy(\strlen($match[0]));

        $level = \strlen(\trim($match[0]));
        $str = $cursor->getRemainder();
        $str = \preg_replace('/^[ \t]*#+[ \t]*$/', '', $str);
        $str = \preg_replace('/[ \t]+#+[ \t]*$/', '', $str);

        $heading = new \League\CommonMark\Block\Element\Heading($level, self::preventOrphans($str));

        $id = preg_replace('/[^a-z\-]+/', '', strtolower(str_replace(' ', '-', $str)));

        $heading->data['attributes'] = ['id' => $id];

    	$context->addBlock($heading);

        $context->setBlocksParsed(true);

        return true;
    }

    public static function preventOrphans(string $text): string
    {
        $article_title_parts = explode(' ', $text);

        if (count($article_title_parts) > 1) {
            $last_word = array_pop($article_title_parts);
            return implode(' ', $article_title_parts) . '&nbsp;' . $last_word;
        }

        return $text;
    }
}