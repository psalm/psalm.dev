<!--
	title: Fixing code that ain’t broken
	canonical: https://medium.com/@muglug/fixing-code-that-aint-broken-a99e05998c24
    date: 2018-03-16
    author: Matt Brown
    author_link: https://twitter.com/mattbrowndev
-->

In June of 2015, the Vimeo Codebase was large, sprawling, and full of magic. It processed many millions of requests every hour. The users were happy, and the company grew.

The Codebase _worked_.

* * *

The Codebase didn’t have many tests or much documentation. The only practical way to figure out whether changing one file was going to break code elsewhere on Vimeo was to deploy it to production, cross your fingers, and be ready to roll back.

Engineers seldom changed existing methods drastically, opting for the safety of a brand new method. The resultant bloated classes weren’t refactored into smaller logical units for fear of breaking existing references.

But as long as developers avoided refactoring existing logic, the Codebase _worked_.

* * *

In June of 2015, I created a pull request that broke apart a 10,000-line file. That pull request was QAed and approved by my peers. I got a nice pat on the back for my bravery. And then, once in production, a bunch of things broke.

The underlying bug — an inaccurate classname — was utterly avoidable, and it was clear that we needed a tool that would prevent these sorts of errors in the future. I’ve spent plenty of time writing C#, and I was keenly aware of how much a good static analysis engine could help. Since there were none available for PHP at the time¹, I wrote one myself.

As that in-house static analysis engine improved, it found more and more potentially fatal issues in Vimeo’s codebase. The realities of software development were such that it tended to find bugs in less-used parts of it — most of the critical paths were well-tested — but by fixing a given type of issue everywhere, it could be prevented from recurring anywhere.

Originally the engine didn’t fix code directly — it only revealed potential issues. To increase the breadth of its coverage I gave it the ability to [add missing return types automatically](https://github.com/vimeo/psalm/blob/master/docs/fixing_code.md#missingreturntype) and [change existing return types](https://github.com/vimeo/psalm/blob/master/docs/fixing_code.md#invalidreturntype) as well. This allowed us to find many multitudes more issues in the codebase.

Eighteen months later we released it publicly as [Psalm](https://github.com/vimeo/psalm) (or, if you prefer, the PHP Static Analysis Linting Machine).

* * *

Today, we use Psalm at Vimeo as a key part of our PHP development process:

*   Passing Psalm’s checks is a requirement for code to get into production.
*   Psalm runs its analysis on every PHP CI build, taking about 15 seconds on average.
*   It catches fatal issues in about 4 percent of our CI builds (and developers also run it locally, where it catches more).
*   A full analysis of our main PHP repository takes about 90 seconds on modern hardware with no caching.
*   Psalm can infer types for 85 percent of Vimeo’s codebase.

Psalm gives us the confidence to make large changes to the codebase without breaking things. There’s another benefit — code reviewers can spend their time looking for things like off-by-one errors and other logical landmines without worrying that a variable might be undefined or a class name misspelled.

Psalm can currently find [160 distinct types of issues](https://psalm.dev/docs/running_psalm/issues/). Some of these, like [UndefinedClass](https://psalm.dev/docs/running_psalm/issues/#undefinedclass) are more serious than others, like [TypeCoercion](https://psalm.dev/docs/running_psalm/issues/#typecoercion), and it would be essentially impossible to fix all the issues in Vimeo’s codebase that Psalm is capable of finding while maintaining any sort of forward momentum — so we compromise. Our `psalm.xml` config (a heavily redacted version is available on [GitHub](https://gist.github.com/muglug/6c6ac543a51d94c62967bd861783ed07)) prevents 28 of the original 160 issue types from ever breaking CI builds, and a bunch more issues are ignored on certain files and folders.

Having per-directory configurations for different issue types also enables us to apply more rigorous standards to new code than to old, which improves the codebase over time.

### Tradeoffs

Getting Psalm to work well across our codebase has meant spending a non-trivial amount of the last two and a half years fixing things that weren’t, from the user’s point of view, broken. We’ve made a number of changes to our internal APIs so that Psalm has a better time analyzing their output, and developers have had to learn to deal with yet another tool standing between their code and our production servers.

But while there have been some growing pains, writing code that passes Psalm’s checks normally means writing better code overall. That’s especially important in an organization where many developers might end up editing the same file in a single day.

## Striving boldly into the future

Psalm 1.0, which we released a few weeks ago, continues our support for PHP 5.6. The main goal of the first version was to be able to support the myriad ways that people expect PHP to work.

There are a bunch of features we’d like to add in the next version that aim to improve the experience of writing PHP:

*   [Support the Language Server Protocol](https://github.com/vimeo/psalm/issues/521) to enable the issues that Psalm catches to show up in the user’s IDEs
*   Include more automatic code modification, such as adding missing [property types](https://github.com/vimeo/psalm/issues/435) and [parameter types](https://github.com/vimeo/psalm/issues/204)
*   Add a [constraint-based analysis](https://github.com/vimeo/psalm/issues/207) mode that finds more bugs in less rigorous code

## Using Psalm to improve your own codebase

Psalm is obviously not just for Vimeo. A number of companies, organizations, and [open source packages](https://packagist.org/packages/vimeo/psalm/dependents) use it to prevent bugs in their software.

Psalm offers a [range of different default configs](https://github.com/vimeo/psalm/tree/master/assets/config_levels) to support codebases with varying levels of quality. At its strictest, it requires the codebase to be totally typed (similar to TypeScript’s `noImplicitAny` option), and at its most lenient it permits undefined method calls.

To initialise Psalm, run

```
composer install vimeo/psalm
vendor/bin/psalm --init [source_dir] [level]
```

where `[source_dir]` is your project’s main folder (it defaults to `src`) and `[level]` dictates Psalm’s level of strictness (it defaults to 3, and ranges from 1 to 6).

[Let us know](https://github.com/vimeo/psalm/issues) if you run into problems or have ideas for feature requests.

## Go forth and type all the things

Psalm allows us to avoid [the fear cycle](http://www.michaelnygard.com/blog/2015/07/the-fear-cycle/), and it can help you too. PHP 7 adds support for a whole bunch of types in method signatures and elsewhere, so there’s never been a better time to improve your code.

* * *

1.  There are now a number of other PHP static analysis engines — [Phan](https://github.com/phan/phan) and [PHPStan](https://github.com/phpstan/phpstan) are two popular ones. [Hack](https://hacklang.org), a mostly-compatible fork of PHP created by Facebook, comes with its own great type-checker, but one that doesn’t work with regular PHP files.

